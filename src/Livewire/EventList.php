<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Enums\BounceType;
use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Support\Carbon;
use League\Csv\Writer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Lazy]
class EventList extends Component
{
    use WithPagination;

    public Source $source;

    #[Url]
    public string $search = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public array $eventTypes = [];

    #[Url]
    public array $bounceTypes = [];

    public function mount(Source $source): void
    {
        $this->source = $source;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEventTypes(): void
    {
        $this->resetPage();
    }

    public function updatedBounceTypes(): void
    {
        $this->resetPage();
    }

    public function setDateRange(string $preset): void
    {
        $this->from = match ($preset) {
            '24h' => Carbon::now()->subDay()->toDateString(),
            '7d' => Carbon::now()->subDays(7)->toDateString(),
            '30d' => Carbon::now()->subDays(30)->toDateString(),
            'all' => '',
            default => $this->from,
        };
        $this->to = $preset === 'all' ? '' : Carbon::now()->toDateString();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'from', 'to', 'eventTypes', 'bounceTypes']);
        $this->resetPage();
    }

    #[Computed]
    public function filterCounts(): array
    {
        return Event::query()
            ->whereRelation('message', 'source_id', $this->source->id)
            ->filterByParams($this->filterParams())
            ->selectRaw('event_type, count(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    public function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $csv = Writer::createFromString();
            $csv->insertOne(['Date', 'Event Type', 'Recipient', 'Subject', 'Bounce Type', 'SES Message ID']);

            Event::query()
                ->whereRelation('message', 'source_id', $this->source->id)
                ->filterByParams($this->filterParams())
                ->reverseChronologically()
                ->with('message')
                ->chunkById(500, function ($events) use ($csv) {
                    foreach ($events as $event) {
                        $csv->insertOne([
                            $event->event_at->toDateTimeString(),
                            $event->event_type->value,
                            $event->recipient_email,
                            $event->message?->subject ?? '',
                            $event->bounce_type ?? '',
                            $event->ses_message_id,
                        ]);
                    }
                });

            echo $csv->toString();
        }, 'ses-events-'.$this->source->name.'-'.now()->format('Y-m-d').'.csv');
    }

    public function render()
    {
        $events = Event::query()
            ->whereRelation('message', 'source_id', $this->source->id)
            ->filterByParams($this->filterParams())
            ->reverseChronologically()
            ->with('message')
            ->paginate(50);

        return view('aws-ses-observer::livewire.event-list', [
            'events' => $events,
            'allEventTypes' => EventType::cases(),
            'allBounceTypes' => BounceType::cases(),
        ])->layout('aws-ses-observer::components.layouts.app', [
            'title' => 'Activity — '.$this->source->name.' — SES Observer',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterParams(): array
    {
        return array_filter([
            'search' => $this->search,
            'from' => $this->from,
            'to' => $this->to,
            'event_types' => $this->eventTypes,
            'bounce_types' => $this->bounceTypes,
        ]);
    }
}
