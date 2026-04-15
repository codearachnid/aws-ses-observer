<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Http\Controllers\WebhookController;
use codearachnid\AwsSesObserver\Livewire\EventList;
use codearachnid\AwsSesObserver\Livewire\MessageDetail;
use codearachnid\AwsSesObserver\Livewire\SetupGuide;
use codearachnid\AwsSesObserver\Livewire\SourceForm;
use codearachnid\AwsSesObserver\Livewire\SourceIndex;
use codearachnid\AwsSesObserver\Livewire\SourceShow;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

// Dashboard routes — protected by configurable middleware
Route::prefix(config('aws-ses-observer.route_prefix', 'ses-observer'))
    ->middleware(config('aws-ses-observer.middleware', ['web', 'ses-observer.auth']))
    ->group(function () {
        Route::get('/', SourceIndex::class)->name('ses-observer.sources.index');
        Route::get('/sources/create', SourceForm::class)->name('ses-observer.sources.create');
        Route::get('/sources/{source}', SourceShow::class)->name('ses-observer.sources.show');
        Route::get('/sources/{source}/edit', SourceForm::class)->name('ses-observer.sources.edit');
        Route::get('/sources/{source}/events', EventList::class)->name('ses-observer.sources.events');
        Route::get('/sources/{source}/messages/{message}', MessageDetail::class)->name('ses-observer.sources.messages.show');
        Route::get('/sources/{source}/setup', SetupGuide::class)->name('ses-observer.sources.setup');
    });

// Webhook — outside auth group, CSRF exempt
Route::post(
    config('aws-ses-observer.route_prefix', 'ses-observer').'/webhooks/{sourceToken}',
    [WebhookController::class, 'store'],
)
    ->name('ses-observer.webhook')
    ->middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class]);
