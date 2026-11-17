<?php

namespace JeffersonGoncalves\CcpaRequests\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\CcpaRequests\RequestManager;

/**
 * @method static \JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest open(\JeffersonGoncalves\CcpaRequests\Enums\RequestType $type, ?\Illuminate\Database\Eloquent\Model $subject = null, array $requester = [])
 * @method static \JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest verify(\JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest $request, ?\Illuminate\Database\Eloquent\Model $subject = null)
 * @method static \JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest fulfil(\JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest complete(\JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest reject(\JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest $request, string $reason)
 * @method static array<string, list<array<string, mixed>>> export(\Illuminate\Database\Eloquent\Model $subject)
 * @method static string exportToFile(\Illuminate\Database\Eloquent\Model $subject, string $filename)
 * @method static int erase(\Illuminate\Database\Eloquent\Model $subject)
 *
 * @see RequestManager
 */
class CcpaRequests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RequestManager::class;
    }
}
