<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver;

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

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware(
            'ses-observer.auth',
            Http\Middleware\OptionalBasicAuth::class,
        );
    }
}
