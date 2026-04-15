<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

// Webhook — outside auth group, CSRF exempt
Route::post(
    config('aws-ses-observer.route_prefix', 'ses-observer').'/webhooks/{sourceToken}',
    [WebhookController::class, 'store'],
)
    ->name('ses-observer.webhook')
    ->middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class]);
