<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Jobs\DeleteExpiredDataJob;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;

beforeEach(function () {
    $this->source = Source::factory()->withRetention(30)->create();
});

it('scopes sources with retention policies', function () {
    $noRetention = Source::factory()->create(['retention_days' => null]);

    $results = Source::withRetentionPolicy()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->source->id);
});

it('deletes messages older than retention days', function () {
    // Old message — should be deleted
    $oldMessage = Message::factory()->create([
        'source_id' => $this->source->id,
        'created_at' => now()->subDays(31),
    ]);
    Event::factory()->create([
        'message_id' => $oldMessage->id,
        'ses_message_id' => $oldMessage->ses_message_id,
    ]);

    // Recent message — should be kept
    $recentMessage = Message::factory()->create([
        'source_id' => $this->source->id,
        'created_at' => now()->subDays(5),
    ]);

    $deleted = $this->source->deleteExpiredData();

    expect($deleted)->toBe(1)
        ->and(Message::find($oldMessage->id))->toBeNull()
        ->and(Message::find($recentMessage->id))->not->toBeNull();
});

it('cascades event deletion when message is deleted', function () {
    $oldMessage = Message::factory()->create([
        'source_id' => $this->source->id,
        'created_at' => now()->subDays(31),
    ]);
    $event = Event::factory()->create([
        'message_id' => $oldMessage->id,
        'ses_message_id' => $oldMessage->ses_message_id,
    ]);

    $this->source->deleteExpiredData();

    expect(Event::find($event->id))->toBeNull();
});

it('does nothing when no messages are expired', function () {
    Message::factory()->create([
        'source_id' => $this->source->id,
        'created_at' => now()->subDays(5),
    ]);

    $deleted = $this->source->deleteExpiredData();

    expect($deleted)->toBe(0)
        ->and(Message::count())->toBe(1);
});

it('runs the DeleteExpiredDataJob', function () {
    $oldMessage = Message::factory()->create([
        'source_id' => $this->source->id,
        'created_at' => now()->subDays(31),
    ]);

    (new DeleteExpiredDataJob)->handle();

    expect(Message::find($oldMessage->id))->toBeNull();
});

it('skips sources without retention policies in the job', function () {
    $noRetentionSource = Source::factory()->create(['retention_days' => null]);
    $oldMessage = Message::factory()->create([
        'source_id' => $noRetentionSource->id,
        'created_at' => now()->subDays(365),
    ]);

    (new DeleteExpiredDataJob)->handle();

    expect(Message::find($oldMessage->id))->not->toBeNull();
});
