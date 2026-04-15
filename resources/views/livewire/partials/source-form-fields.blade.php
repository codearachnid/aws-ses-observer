<form wire:submit="save" class="max-w-lg space-y-6">
    <div>
        <flux:input
            wire:model="name"
            label="Name"
            placeholder="e.g., Production, Marketing"
            required
        />
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Color</label>
        <x-aws-ses-observer::color-picker :selected="$color" />
        @error('color')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <flux:input
            wire:model="retentionDays"
            label="Retention (days)"
            type="number"
            placeholder="Leave blank for unlimited"
            min="1"
        />
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Automatically delete messages older than this many days. Leave blank to keep all data.
        </p>
        @error('retentionDays')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-2">
        <flux:button type="submit" variant="primary">
            {{ $isEditing ? 'Update Source' : 'Create Source' }}
        </flux:button>

        <flux:button href="{{ $isEditing ? route('ses-observer.sources.show', $source) : route('ses-observer.sources.index') }}" wire:navigate variant="ghost">
            Cancel
        </flux:button>
    </div>
</form>

@if($isEditing)
    <div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-700 max-w-lg">
        <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-2">Danger Zone</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            Deleting this source will permanently remove all associated messages and events.
        </p>
        <flux:modal.trigger name="confirm-delete">
            <flux:button variant="danger">Delete Source</flux:button>
        </flux:modal.trigger>

        <flux:modal name="confirm-delete" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete "{{ $source->name }}"?</flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    This will permanently delete the source and all its messages and events. This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <flux:button wire:click="delete" variant="danger">
                        Yes, Delete
                    </flux:button>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </div>
@endif
