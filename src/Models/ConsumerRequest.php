<?php

namespace JeffersonGoncalves\CcpaRequests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Ccpa\Attributes\PersonalData;
use JeffersonGoncalves\Ccpa\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Ccpa\Contracts\HasPersonalData;
use JeffersonGoncalves\Ccpa\Enums\AnonymizeStrategy;
use JeffersonGoncalves\Ccpa\Enums\PiCategory;
use JeffersonGoncalves\CcpaRequests\Enums\RequestStatus;
use JeffersonGoncalves\CcpaRequests\Enums\RequestType;

/**
 * @property RequestType $type
 * @property RequestStatus $status
 * @property string|null $export_path
 * @property Carbon $due_at
 * @property Carbon|null $acknowledge_due_at
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $extended_at
 * @property string|null $extension_reason
 * @property Carbon|null $completed_at
 * @property Model|null $subject
 */
#[PersonalData('requester_name', purpose: 'Answer consumer requests (records kept 24 months, Regulations §7101)', piCategory: PiCategory::Identifiers, anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('requester_email', purpose: 'Answer consumer requests (records kept 24 months, Regulations §7101)', piCategory: PiCategory::Identifiers, anonymize: AnonymizeStrategy::Hash)]
class ConsumerRequest extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('ccpa-requests.table', 'ccpa_consumer_requests');
    }

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'due_at' => 'datetime',
            'acknowledge_due_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'verified_at' => 'datetime',
            'extended_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open())->where('due_at', '<', now());
    }

    /**
     * Open requests whose receipt was not confirmed within 10 business days (Regulations §7021).
     *
     * @param  Builder<self>  $query
     */
    public function scopeAcknowledgementOverdue(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open())->whereNull('acknowledged_at')->where('acknowledge_due_at', '<', now());
    }

    public function isOpen(): bool
    {
        return in_array($this->status, RequestStatus::open(), true);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
