<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;

it('creates a source with auto-generated token', function () {
    $source = Source::create([
        'name' => 'Production',
        'color' => 'blue',
    ]);

    expect($source->token)->not->toBeEmpty()
        ->and($source->name)->toBe('Production')
        ->and($source->color->value)->toBe('blue');
});

it('creates a source with retention days', function () {
    $source = Source::create([
        'name' => 'Staging',
        'color' => 'green',
        'retention_days' => 30,
    ]);

    expect($source->retention_days)->toBe(30);
});

it('lists sources alphabetically', function () {
    Source::factory()->create(['name' => 'Zebra']);
    Source::factory()->create(['name' => 'Alpha']);
    Source::factory()->create(['name' => 'Middle']);

    $sources = Source::alphabetically()->pluck('name')->toArray();

    expect($sources)->toBe(['Alpha', 'Middle', 'Zebra']);
});

it('deletes a source and cascades to messages', function () {
    $source = Source::factory()->create();
    $message = Message::factory()->create([
        'source_id' => $source->id,
    ]);
    $event = Event::factory()->create([
        'message_id' => $message->id,
        'ses_message_id' => $message->ses_message_id,
    ]);

    $source->delete();

    expect(Message::count())->toBe(0)
        ->and(Event::count())->toBe(0);
});

it('generates unique tokens for each source', function () {
    $source1 = Source::factory()->create();
    $source2 = Source::factory()->create();

    expect($source1->token)->not->toBe($source2->token);
});
