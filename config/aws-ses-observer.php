<?php

declare(strict_types=1);

return [
    'route_prefix' => env('SES_OBSERVER_ROUTE_PREFIX', 'ses-observer'),
    'middleware' => ['web', 'ses-observer.auth'],
    'table_prefix' => 'ses_observer_',
    'http_auth_username' => env('SES_OBSERVER_AUTH_USERNAME'),
    'http_auth_password' => env('SES_OBSERVER_AUTH_PASSWORD'),
    'disable_auth_warning' => env('SES_OBSERVER_DISABLE_AUTH_WARNING', false),
    'sns_signature_verification' => env('SES_OBSERVER_VERIFY_SNS', true),
];
