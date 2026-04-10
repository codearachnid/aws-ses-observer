<?php

namespace codearachnid\AwsSesObserver;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use codearachnid\AwsSesObserver\Commands\AwsSesObserverCommand;

class AwsSesObserverServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('aws-ses-observer')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_aws_ses_observer_table')
            ->hasCommand(AwsSesObserverCommand::class);
    }
}
