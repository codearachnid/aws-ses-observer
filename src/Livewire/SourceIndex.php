<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class SourceIndex extends Component
{
    #[Computed]
    public function sources()
    {
        $since = Carbon::now()->subDays(30);

        return Source::alphabetically()
            ->withCount([
                'events as sent_count' => fn ($q) => $q->where('event_type', EventType::Send->value)->where('event_at', '>=', $since),
                'events as bounce_count' => fn ($q) => $q->where('event_type', EventType::Bounce->value)->where('event_at', '>=', $since),
                'events as total_events_count' => fn ($q) => $q->where('event_at', '>=', $since),
            ])
            ->with(['events' => fn ($q) => $q->latest('event_at')->limit(1)])
            ->get();
    }

    public function render()
    {
        return view('aws-ses-observer::livewire.source-index')
            ->layout('aws-ses-observer::components.layouts.app', ['title' => 'Sources — SES Observer']);
    }
}
