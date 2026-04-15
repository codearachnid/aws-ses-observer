<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver;

use Livewire\Livewire;
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
        if (class_exists(Livewire::class) && $this->app->bound('livewire')) {
            Livewire::component('ses-observer.source-index', Livewire\SourceIndex::class);
            Livewire::component('ses-observer.source-show', Livewire\SourceShow::class);
            Livewire::component('ses-observer.source-form', Livewire\SourceForm::class);
            Livewire::component('ses-observer.event-list', Livewire\EventList::class);
            Livewire::component('ses-observer.message-detail', Livewire\MessageDetail::class);
            Livewire::component('ses-observer.setup-guide', Livewire\SetupGuide::class);
        }

        $this->app['router']->aliasMiddleware(
            'ses-observer.auth',
            Http\Middleware\OptionalBasicAuth::class,
        );
    }
}
