<?php

namespace JeffersonGoncalves\CcpaRequests;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\Ccpa\Ccpa;
use JeffersonGoncalves\Ccpa\Contracts\HasPersonalData;
use JeffersonGoncalves\CcpaRequests\Contracts\ResolvesConsumer;
use JeffersonGoncalves\CcpaRequests\Enums\RequestStatus;
use JeffersonGoncalves\CcpaRequests\Enums\RequestType;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestClosed;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestOpened;
use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;
use LogicException;

class RequestManager
{
    public function __construct(protected Ccpa $ccpa) {}

    /**
     * @param  array{name?: string|null, email?: string|null, details?: string|null}  $requester
     */
    public function open(RequestType $type, ?Model $subject = null, array $requester = []): ConsumerRequest
    {
        $request = ConsumerRequest::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'requester_name' => $requester['name'] ?? null,
            'requester_email' => $requester['email'] ?? null,
            'details' => $requester['details'] ?? null,
            'type' => $type,
            'status' => RequestStatus::Pending,
            'due_at' => now()->add(CarbonInterval::make((string) config('ccpa-requests.deadline', '45 days'))),
            'acknowledge_due_at' => now()->addWeekdays((int) config('ccpa-requests.acknowledge_business_days', 10))->endOfDay(),
        ]);

        event(new ConsumerRequestOpened($request));

        return $request;
    }

    /**
     * Record that you confirmed receipt and explained how you will verify and answer (Regulations §7021).
     */
    public function acknowledge(ConsumerRequest $request): ConsumerRequest
    {
        $request->forceFill(['acknowledged_at' => now()])->save();

        return $request;
    }

    /**
     * Mark the requester's identity as checked. Required before fulfil(): never export or erase on an unverified request.
     */
    public function verify(ConsumerRequest $request, ?Model $subject = null): ConsumerRequest
    {
        $request->forceFill(array_filter([
            'verified_at' => now(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
        ]))->save();

        return $request;
    }

    /**
     * Run the automatic types (know, delete) and complete the request.
     */
    public function fulfil(ConsumerRequest $request, ?string $response = null): ConsumerRequest
    {
        if (! $request->isOpen()) {
            throw new LogicException("Request [{$request->getKey()}] is already closed.");
        }

        if (! $request->isVerified()) {
            throw new LogicException("Verify the requester's identity before fulfilling request [{$request->getKey()}].");
        }

        if (! $request->type->isAutomatic()) {
            throw new LogicException("Requests of type [{$request->type->value}] are answered by hand: call complete() when done.");
        }

        $subject = $request->subject;

        if (! $subject instanceof Model) {
            throw new LogicException("Request [{$request->getKey()}] has no consumer: pass one to verify().");
        }

        match ($request->type) {
            RequestType::Know => $request->export_path = $this->exportToFile($subject, "request-{$request->getKey()}.json"),
            default => $this->erase($subject),
        };

        return $this->complete($request, $response);
    }

    /**
     * Extend the deadline once by 45 more days when reasonably necessary (Cal. Civ. Code §1798.130(a)(2)(A)).
     * Tell the consumer, with the reasons, within the first 45 days.
     */
    public function extend(ConsumerRequest $request, string $reason): ConsumerRequest
    {
        if (! $request->isOpen() || $request->extended_at !== null) {
            throw new LogicException("Request [{$request->getKey()}] is closed or was already extended.");
        }

        if ($request->due_at->isPast()) {
            throw new LogicException("Request [{$request->getKey()}] is past its deadline: extend it within the first 45 days.");
        }

        $request->forceFill([
            'due_at' => $request->due_at->copy()->add(CarbonInterval::make((string) config('ccpa-requests.extension', '45 days'))),
            'extended_at' => now(),
            'extension_reason' => $reason,
        ])->save();

        return $request;
    }

    public function complete(ConsumerRequest $request, ?string $response = null): ConsumerRequest
    {
        return $this->close($request, RequestStatus::Completed, $response);
    }

    public function reject(ConsumerRequest $request, string $reason): ConsumerRequest
    {
        return $this->close($request, RequestStatus::Rejected, $reason);
    }

    /**
     * Every declared personal data of the subject, keyed by table (right to know, §1798.110).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function export(Model $subject): array
    {
        $data = [];

        foreach ($this->records($subject) as $record) {
            $data[$record->getTable()][] = $record->exportPersonalData();
        }

        return $data;
    }

    /**
     * @return string the path on the configured disk
     */
    public function exportToFile(Model $subject, string $filename): string
    {
        $path = trim((string) config('ccpa-requests.directory', 'ccpa/requests'), '/').'/'.$filename;

        Storage::disk(config('ccpa-requests.disk'))->put(
            $path,
            (string) json_encode($this->export($subject), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        return $path;
    }

    /**
     * Anonymize every record of the subject with each column's strategy (Keep columns stay: §1798.105(d) exceptions).
     *
     * @return int records anonymized
     */
    public function erase(Model $subject): int
    {
        return DB::transaction(function () use ($subject): int {
            $count = 0;

            foreach ($this->records($subject) as $record) {
                $record->anonymizePersonalData();
                $count++;
            }

            return $count;
        });
    }

    /**
     * Records of ccpa.models that belong to the subject: the subject itself, plus ResolvesConsumer models.
     *
     * @return iterable<int, Model&HasPersonalData>
     */
    public function records(Model $subject): iterable
    {
        foreach ($this->ccpa->models() as $model) {
            if ($subject instanceof $model) {
                yield $subject;

                continue;
            }

            if (! is_subclass_of($model, ResolvesConsumer::class)) {
                continue;
            }

            /** @var Builder<Model&HasPersonalData> $query */
            $query = $model::consumerQuery($subject);

            foreach ($query->lazyById() as $record) {
                yield $record;
            }
        }
    }

    protected function close(ConsumerRequest $request, RequestStatus $status, ?string $response): ConsumerRequest
    {
        $request->forceFill([
            'status' => $status,
            'response' => $response,
            'completed_at' => now(),
        ])->save();

        event(new ConsumerRequestClosed($request));

        return $request;
    }
}
