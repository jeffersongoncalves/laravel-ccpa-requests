<?php

namespace JeffersonGoncalves\CcpaRequests\Tests;

use JeffersonGoncalves\CcpaRequests\CcpaRequestsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CcpaRequestsServiceProvider::class,
        ];
    }
}
