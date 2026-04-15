<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;

beforeEach(function () {
    $this->source = Source::factory()->create();

    $this->message1 = Message::factory()->create([
        'source_id' => $this->source->id,
        'subject' => 'Welcome to our platform',
    ]);
    $this->event1 = Event::factory()->create([
        'message_id' => $this->message1->id,
        'recipient_email' => 'alice@example.com',
        'ses_message_id' => $this->message1->ses_message_id,
    ]);

    $this->message2 = Message::factory()->create([
        'source_id' => $this->source->id,
        'subject' => 'Password reset request',
    ]);
    $this->event2 = Event::factory()->create([
        'message_id' => $this->message2->id,
        'recipient_email' => 'bob@testing.org',
        'ses_message_id' => $this->message2->ses_message_id,
    ]);
});

it('searches by recipient email', function () {
    $results = Event::search('alice')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->event1->id);
});

it('searches by message subject', function () {
    $results = Event::search('Welcome')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->event1->id);
});

it('searches with partial match', function () {
    $results = Event::search('example.com')->get();

    expect($results)->toHaveCount(1);
});

it('search is case insensitive', function () {
    $results = Event::search('ALICE')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->event1->id);
});

it('returns empty results for non-matching search', function () {
    $results = Event::search('nonexistent')->get();

    expect($results)->toBeEmpty();
});

it('searches across both email and subject', function (string $term, int $expectedCount) {
    $results = Event::search($term)->get();

    expect($results)->toHaveCount($expectedCount);
})->with([
    'email match' => ['alice', 1],
    'subject match' => ['password', 1],
    'partial domain' => ['testing.org', 1],
    'no match' => ['xyz123', 0],
]);
