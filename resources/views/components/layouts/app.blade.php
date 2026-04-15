<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark:bg-zinc-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SES Observer' }}</title>

    {{-- Inter font --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- FluxUI styles --}}
    @fluxAppearance

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11'; }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 antialiased">

    {{-- Auth warning banner --}}
    @if(app()->environment('production')
        && empty(config('aws-ses-observer.http_auth_username'))
        && empty(config('aws-ses-observer.http_auth_password'))
        && !config('aws-ses-observer.disable_auth_warning'))
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-800 px-4 py-2 text-center text-sm text-yellow-800 dark:text-yellow-200">
            <strong>Warning:</strong> No authentication is configured for this dashboard.
            Set <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">SES_OBSERVER_AUTH_USERNAME</code> and
            <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">SES_OBSERVER_AUTH_PASSWORD</code> in your environment.
        </div>
    @endif

    {{-- Header --}}
    <header class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="{{ route('ses-observer.sources.index') }}" class="flex items-center gap-2 text-zinc-900 dark:text-white hover:text-zinc-600 dark:hover:text-zinc-300 transition" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="font-semibold text-lg">SES Observer</span>
            </a>
        </div>
    </header>

    {{-- Main content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    {{-- FluxUI scripts --}}
    @fluxScripts
</body>
</html>
