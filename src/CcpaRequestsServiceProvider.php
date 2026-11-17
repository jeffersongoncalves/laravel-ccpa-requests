<?php

namespace JeffersonGoncalves\CcpaRequests;

use JeffersonGoncalves\CcpaRequests\Commands\OverdueCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CcpaRequestsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ccpa-requests')
            ->hasConfigFile()
            ->hasMigrations(['create_ccpa_consumer_requests_table'])
            ->hasCommand(OverdueCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RequestManager::class);
    }
}
