<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Models\Source;
use Livewire\Component;

class SetupGuide extends Component
{
    public Source $source;

    public function mount(Source $source): void
    {
        $this->source = $source;
    }

    public function getWebhookUrlProperty(): string
    {
        return route('ses-observer.webhook', ['sourceToken' => $this->source->token]);
    }

    public function render()
    {
        return view('aws-ses-observer::livewire.setup-guide')
            ->layout('aws-ses-observer::components.layouts.app', [
                'title' => 'Setup — '.$this->source->name.' — SES Observer',
            ]);
    }
}
