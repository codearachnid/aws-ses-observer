<div>
    <x-aws-ses-observer::source-layout :source="$source" activeTab="activity">

        {{-- Toolbar: search + date presets + export --}}
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="Search by email or subject..."
                />
            </div>
            <div class="flex items-center gap-2">
                @foreach(['24h' => '24h', '7d' => '7d', '30d' => '30d', 'all' => 'All'] as $value => $label)
                    <flux:button
                        wire:click="setDateRange('{{ $value }}')"
                        size="sm"
                        :variant="($from === '' && $value === 'all') || ($from !== '' && $value !== 'all') ? 'ghost' : 'ghost'"
                    >
                        {{ $label }}
                    </flux:button>
                @endforeach
            </div>
            <flux:button wire:click="exportCsv" size="sm" variant="ghost" icon="arrow-down-tray">
                CSV
            </flux:button>
        </div>

        {{-- Event type filters --}}
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach($allEventTypes as $eventType)
                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-medium cursor-pointer transition
                    {{ in_array($eventType->value, $eventTypes) ? $eventType->filterChipClasses() : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400' }}">
                    <input
                        type="checkbox"
                        value="{{ $eventType->value }}"
                        wire:model.live="eventTypes"
                        class="sr-only"
                    />
                    {{ $eventType->label() }}
                    @if(isset($this->filterCounts[$eventType->value]))
                        <span class="opacity-60">{{ $this->filterCounts[$eventType->value] }}</span>
                    @endif
                </label>
            @endforeach

            @if(!empty($eventTypes) || !empty($bounceTypes) || $search || $from)
                <button wire:click="clearFilters" class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 underline ml-2">
                    Clear all
                </button>
            @endif
        </div>

        {{-- Bounce subtype filters (shown when Bounce is selected) --}}
        @if(in_array(\codearachnid\AwsSesObserver\Enums\EventType::Bounce->value, $eventTypes))
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach($allBounceTypes as $bounceType)
                    <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-medium cursor-pointer transition
                        {{ in_array($bounceType->value, $bounceTypes) ? 'border-red-300 bg-red-50 text-red-700' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400' }}">
                        <input
                            type="checkbox"
                            value="{{ $bounceType->value }}"
                            wire:model.live="bounceTypes"
                            class="sr-only"
                        />
                        {{ $bounceType->label() }}
                    </label>
                @endforeach
            </div>
        @endif

        {{-- Events table --}}
        @if($events->isEmpty())
            <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                <p>No events found.</p>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Recipient</flux:table.column>
                    <flux:table.column>Subject</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($events as $event)
                        <flux:table.row>
                            <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $event->event_at->format('M j, g:ia') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-aws-ses-observer::event-badge :type="$event->event_type" />
                                @if($event->bounce_type)
                                    <span class="text-xs text-zinc-400 ml-1">
                                        {{ \codearachnid\AwsSesObserver\Enums\BounceType::tryFrom($event->bounce_type)?->label() }}
                                    </span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm">
                                {{ $event->recipient_email }}
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400 max-w-xs truncate">
                                {{ $event->message?->subject ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button
                                    href="{{ route('ses-observer.sources.messages.show', [$source, $event->message]) }}"
                                    wire:navigate
                                    variant="ghost"
                                    size="sm"
                                >
                                    Details
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-6">
                {{ $events->links() }}
            </div>
        @endif
    </x-aws-ses-observer::source-layout>
</div>
