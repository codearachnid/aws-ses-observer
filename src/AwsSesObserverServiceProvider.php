<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver;

use codearachnid\AwsSesObserver\Commands\AwsSesObserverCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AwsSesObserverServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aws-ses-observer')
            ->hasConfigFile()
            ->discoversMigrations()
            ->hasViews()
            ->hasAssets()
            ->hasRoute('web');
    }
}
