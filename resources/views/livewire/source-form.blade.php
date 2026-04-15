<div>
    @if($isEditing)
        <x-aws-ses-observer::source-layout :source="$source" activeTab="settings">
            @include('aws-ses-observer::livewire.partials.source-form-fields')
        </x-aws-ses-observer::source-layout>
    @else
        <div>
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('ses-observer.sources.index') }}" wire:navigate>
                    Sources
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>
                    New Source
                </flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8">Create Source</h1>

            @include('aws-ses-observer::livewire.partials.source-form-fields')
        </div>
    @endif
</div>
