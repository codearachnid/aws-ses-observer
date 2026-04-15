<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Enums\BounceType;
use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;

beforeEach(function () {
    $this->source = Source::factory()->create();
    $this->message = Message::factory()->create(['source_id' => $this->source->id]);
});

it('filters by event type', function () {
    Event::factory()->create([
        'message_id' => $this->message->id,
        'event_type' => EventType::Delivery,
        'ses_message_id' => $this->message->ses_message_id,
        'event_at' => now(),
    ]);
    Event::factory()->create([
        'message_id' => $this->message->id,
        'event_type' => EventType::Bounce,
        'ses_message_id' => $this->message->ses_message_id.'-bounce',
        'event_at' => now()->subSecond(),
    ]);

    $results = Event::withEventTypes([EventType::Delivery])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->event_type)->toBe(EventType::Delivery);
});

it('filters by multiple event types (OR logic)', function () {
    Event::factory()->ofType(EventType::Send)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id,
        'event_at' => now(),
    ]);
    Event::factory()->ofType(EventType::Delivery)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id.'-d',
        'event_at' => now()->subSecond(),
    ]);
    Event::factory()->ofType(EventType::Bounce)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id.'-b',
        'event_at' => now()->subSeconds(2),
    ]);

    $results = Event::withEventTypes([EventType::Send, EventType::Delivery])->get();

    expect($results)->toHaveCount(2);
});

it('filters by bounce type', function () {
    Event::factory()->bounce(BounceType::Permanent)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id,
        'event_at' => now(),
    ]);
    Event::factory()->bounce(BounceType::Transient)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id.'-t',
        'event_at' => now()->subSecond(),
    ]);

    $results = Event::withBounceTypes([BounceType::Permanent])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->bounce_type)->toBe('Permanent');
});

it('filters by date range', function () {
    Event::factory()->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subDays(2),
        'ses_message_id' => $this->message->ses_message_id,
    ]);
    Event::factory()->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subDays(10),
        'ses_message_id' => $this->message->ses_message_id.'-old',
    ]);

    $results = Event::betweenDates(now()->subDays(5), now())->get();

    expect($results)->toHaveCount(1);
});

it('filters by combined params', function () {
    Event::factory()->ofType(EventType::Delivery)->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subDay(),
        'ses_message_id' => $this->message->ses_message_id,
        'recipient_email' => 'target@example.com',
    ]);
    Event::factory()->ofType(EventType::Bounce)->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subDay(),
        'ses_message_id' => $this->message->ses_message_id.'-b',
        'recipient_email' => 'other@example.com',
    ]);

    $results = Event::filterByParams([
        'event_types' => [EventType::Delivery],
        'search' => 'target',
    ])->get();

    expect($results)->toHaveCount(1);
});

it('orders reverse chronologically', function () {
    $older = Event::factory()->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subDays(2),
        'ses_message_id' => $this->message->ses_message_id,
    ]);
    $newer = Event::factory()->create([
        'message_id' => $this->message->id,
        'event_at' => now(),
        'ses_message_id' => $this->message->ses_message_id.'-new',
    ]);

    $results = Event::reverseChronologically()->get();

    expect($results->first()->id)->toBe($newer->id)
        ->and($results->last()->id)->toBe($older->id);
});

it('computes filter counts grouped by event type', function () {
    Event::factory()->ofType(EventType::Send)->create([
        'message_id' => $this->message->id,
        'ses_message_id' => $this->message->ses_message_id,
        'event_at' => now(),
    ]);
    Event::factory()->ofType(EventType::Delivery)->count(2)->create([
        'message_id' => $this->message->id,
        'event_at' => now(),
    ]);

    $counts = Event::filterCounts([]);

    expect($counts['Send'] ?? 0)->toBe(1)
        ->and($counts['Delivery'] ?? 0)->toBe(2);
});
