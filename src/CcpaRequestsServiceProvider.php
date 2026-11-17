<?php

namespace JeffersonGoncalves\CcpaRequests;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CcpaRequestsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ccpa-requests')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
