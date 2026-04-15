<div>
    <x-aws-ses-observer::source-layout :source="$source" activeTab="activity">

        {{-- Back link --}}
        <div class="mb-6">
            <flux:button href="{{ route('ses-observer.sources.events', $source) }}" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                Back to Activity
            </flux:button>
        </div>

        {{-- Message header --}}
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ $message->subject ?? 'No Subject' }}
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">From</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $message->source_email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">To</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ implode(', ', $message->destinationEmails()) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">Sent At</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $message->sent_at?->format('M j, Y g:ia T') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">SES Message ID</dt>
                    <dd class="text-zinc-900 dark:text-white font-mono text-xs break-all">{{ $message->ses_message_id }}</dd>
                </div>
            </dl>

            {{-- Tags --}}
            @php $tags = $message->tags(); @endphp
            @if(!empty($tags))
                <div class="mt-4">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Tags</dt>
                    <div class="flex flex-wrap gap-1">
                        @foreach($tags as $tag)
                            <span class="inline-flex px-2 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 text-xs text-zinc-700 dark:text-zinc-300">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Event timeline --}}
        <div class="mb-6">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4">Event Timeline</h3>

            <div class="space-y-3">
                @foreach($message->events as $event)
                    <div class="flex items-start gap-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                        <x-aws-ses-observer::event-badge :type="$event->event_type" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-900 dark:text-white">
                                {{ $event->recipient_email }}
                                @if($event->bounce_type)
                                    <span class="text-zinc-400 ml-1">({{ \codearachnid\AwsSesObserver\Enums\BounceType::tryFrom($event->bounce_type)?->label() }})</span>
                                @endif
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ $event->event_at->format('M j, Y g:i:sa T') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Collapsible raw metadata --}}
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg">
            <button
                wire:click="toggleMetadata"
                class="w-full flex items-center justify-between p-4 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition"
            >
                <span>Raw Mail Metadata</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition {{ $showMetadata ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            @if($showMetadata)
                <div class="border-t border-zinc-200 dark:border-zinc-700 p-4">
                    <pre class="text-xs text-zinc-600 dark:text-zinc-400 overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($message->mail_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    </x-aws-ses-observer::source-layout>
</div>
