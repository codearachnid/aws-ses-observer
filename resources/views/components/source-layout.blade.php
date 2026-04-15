@props(['source', 'activeTab' => 'overview'])

<div>
    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('ses-observer.sources.index') }}" wire:navigate>
            Sources
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            {{ $source->name }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Source header with color indicator --}}
    <div class="flex items-center gap-3 mb-6">
        <span class="inline-block w-3 h-3 rounded-full {{ $source->color->cssClass() }}"></span>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $source->name }}</h1>
    </div>

    {{-- Tab navigation --}}
    <flux:tabs class="mb-8">
        <flux:tab
            href="{{ route('ses-observer.sources.show', $source) }}"
            :current="$activeTab === 'overview'"
            wire:navigate
        >
            Overview
        </flux:tab>
        <flux:tab
            href="{{ route('ses-observer.sources.events', $source) }}"
            :current="$activeTab === 'activity'"
            wire:navigate
        >
            Activity
        </flux:tab>
        <flux:tab
            href="{{ route('ses-observer.sources.setup', $source) }}"
            :current="$activeTab === 'setup'"
            wire:navigate
        >
            Setup
        </flux:tab>
        <flux:tab
            href="{{ route('ses-observer.sources.edit', $source) }}"
            :current="$activeTab === 'settings'"
            wire:navigate
        >
            Settings
        </flux:tab>
    </flux:tabs>

    {{-- Tab content --}}
    {{ $slot }}
</div>
