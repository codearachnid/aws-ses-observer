<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class SourceShow extends Component
{
    public Source $source;

    public function mount(Source $source): void
    {
        $this->source = $source;
    }

    #[Computed]
    public function stats(): array
    {
        $since = Carbon::now()->subDays(30);

        $counts = $this->source->events()
            ->where('event_at', '>=', $since)
            ->selectRaw('event_type, count(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'sent' => $counts[EventType::Send->value] ?? 0,
            'delivered' => $counts[EventType::Delivery->value] ?? 0,
            'bounced' => $counts[EventType::Bounce->value] ?? 0,
            'complaints' => $counts[EventType::Complaint->value] ?? 0,
            'opens' => $counts[EventType::Open->value] ?? 0,
            'clicks' => $counts[EventType::Click->value] ?? 0,
        ];
    }

    #[Computed]
    public function bounceBreakdown(): array
    {
        $since = Carbon::now()->subDays(30);

        return $this->source->events()
            ->where('event_type', EventType::Bounce->value)
            ->where('event_at', '>=', $since)
            ->selectRaw('bounce_type, count(*) as count')
            ->groupBy('bounce_type')
            ->pluck('count', 'bounce_type')
            ->toArray();
    }

    #[Computed]
    public function chartData(): array
    {
        $since = Carbon::now()->subDays(30);

        $rows = $this->source->events()
            ->where('event_at', '>=', $since)
            ->whereIn('event_type', [EventType::Send->value, EventType::Delivery->value, EventType::Bounce->value])
            ->select(
                DB::raw('DATE(event_at) as date'),
                'event_type',
                DB::raw('count(*) as count'),
            )
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get();

        $labels = [];
        $datasets = [
            EventType::Send->value => [],
            EventType::Delivery->value => [],
            EventType::Bounce->value => [],
        ];

        $period = Carbon::now()->subDays(29)->toPeriod(Carbon::now());

        foreach ($period as $day) {
            $dateStr = $day->format('Y-m-d');
            $labels[] = $day->format('M j');

            foreach ($datasets as $type => &$data) {
                $match = $rows->first(fn ($r) => $r->date === $dateStr && $r->event_type === $type);
                $data[] = $match ? $match->count : 0;
            }
        }

        return [
            'labels' => $labels,
            'sent' => array_values($datasets[EventType::Send->value]),
            'delivered' => array_values($datasets[EventType::Delivery->value]),
            'bounced' => array_values($datasets[EventType::Bounce->value]),
        ];
    }

    public function render()
    {
        return view('aws-ses-observer::livewire.source-show')
            ->layout('aws-ses-observer::components.layouts.app', ['title' => $this->source->name.' — SES Observer']);
    }
}
