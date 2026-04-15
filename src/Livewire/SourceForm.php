<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Livewire;

use codearachnid\AwsSesObserver\Enums\SourceColor;
use codearachnid\AwsSesObserver\Models\Source;
use Livewire\Component;

class SourceForm extends Component
{
    public ?Source $source = null;

    public string $name = '';

    public string $color = 'blue';

    public ?int $retentionDays = null;

    public bool $isEditing = false;

    public function mount(?Source $source = null): void
    {
        if ($source && $source->exists) {
            $this->source = $source;
            $this->name = $source->name;
            $this->color = $source->color->value;
            $this->retentionDays = $source->retention_days;
            $this->isEditing = true;
        } else {
            $this->color = SourceColor::nextAvailable()->value;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|in:'.implode(',', array_column(SourceColor::cases(), 'value')),
            'retentionDays' => 'nullable|integer|min:1',
        ]);

        if ($this->isEditing) {
            $this->source->update([
                'name' => $validated['name'],
                'color' => $validated['color'],
                'retention_days' => $validated['retentionDays'],
            ]);
        } else {
            $this->source = Source::create([
                'name' => $validated['name'],
                'color' => $validated['color'],
                'retention_days' => $validated['retentionDays'],
            ]);
        }

        $this->redirect(route('ses-observer.sources.show', $this->source), navigate: true);
    }

    public function delete(): void
    {
        if ($this->isEditing && $this->source) {
            $this->source->delete();
            $this->redirect(route('ses-observer.sources.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('aws-ses-observer::livewire.source-form')
            ->layout('aws-ses-observer::components.layouts.app', [
                'title' => ($this->isEditing ? 'Edit' : 'Create').' Source — SES Observer',
            ]);
    }
}
