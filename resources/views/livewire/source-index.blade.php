<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Sources</h1>
        <flux:button href="{{ route('ses-observer.sources.create') }}" wire:navigate variant="primary">
            New Source
        </flux:button>
    </div>

    @if($this->sources->isEmpty())
        <div class="text-center py-16 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-zinc-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-1">No sources yet</h3>
            <p class="text-zinc-500 dark:text-zinc-400 mb-4">Create a source to start tracking your SES email events.</p>
            <flux:button href="{{ route('ses-observer.sources.create') }}" wire:navigate variant="primary">
                Create Your First Source
            </flux:button>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Source</flux:table.column>
                <flux:table.column>Sent (30d)</flux:table.column>
                <flux:table.column>Bounce Rate</flux:table.column>
                <flux:table.column>Last Activity</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->sources as $source)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $source->color->cssClass() }}"></span>
                                <a href="{{ route('ses-observer.sources.show', $source) }}" class="font-medium text-zinc-900 dark:text-white hover:text-zinc-600 dark:hover:text-zinc-300" wire:navigate>
                                    {{ $source->name }}
                                </a>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($source->sent_count) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($source->sent_count > 0)
                                {{ number_format(($source->bounce_count / $source->sent_count) * 100, 1) }}%
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500 dark:text-zinc-400 text-sm">
                            @if($source->events->first())
                                {{ $source->events->first()->event_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button href="{{ route('ses-observer.sources.show', $source) }}" wire:navigate variant="ghost" size="sm">
                                View
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
