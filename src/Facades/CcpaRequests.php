<?php

namespace JeffersonGoncalves\CcpaRequests\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\CcpaRequests\CcpaRequests
 */
class CcpaRequests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-ccpa-requests';
    }
}
