<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;
use Livewire\Component;

class MessageDetail extends Component
{
    public Source $source;

    public Message $message;

    public bool $showMetadata = false;

    public function mount(Source $source, Message $message): void
    {
        $this->source = $source;
        $this->message = $message->load(['events' => fn ($q) => $q->orderBy('event_at')]);
    }

    public function toggleMetadata(): void
    {
        $this->showMetadata = ! $this->showMetadata;
    }

    public function render()
    {
        return view('aws-ses-observer::livewire.message-detail')
            ->layout('aws-ses-observer::components.layouts.app', [
                'title' => ($this->message->subject ?? 'Message').' — SES Observer',
            ]);
    }
}
