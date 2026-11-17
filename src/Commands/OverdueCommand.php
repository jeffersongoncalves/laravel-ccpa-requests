<?php

namespace JeffersonGoncalves\CcpaRequests\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestOverdue;
use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;

class OverdueCommand extends Command
{
    protected $signature = 'ccpa:requests-overdue';

    protected $description = 'Dispatch ConsumerRequestOverdue for every open request past its legal deadline';

    public function handle(): int
    {
        $count = 0;

        ConsumerRequest::query()->overdue()->lazyById()->each(function (ConsumerRequest $request) use (&$count): void {
            event(new ConsumerRequestOverdue($request));
            $count++;
        });

        $unacknowledged = ConsumerRequest::query()->scopes('acknowledgementOverdue')->count();

        if ($unacknowledged > 0) {
            $this->warn("{$unacknowledged} request(s) not acknowledged within 10 business days.");
        }

        $count === 0
            ? $this->info('No overdue consumer requests.')
            : $this->warn("{$count} overdue consumer request(s).");

        return self::SUCCESS;
    }
}
