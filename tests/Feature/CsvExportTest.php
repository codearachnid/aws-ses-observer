<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;
use League\Csv\Reader;
use League\Csv\Writer;

beforeEach(function () {
    $this->source = Source::factory()->create();
    $this->message = Message::factory()->create([
        'source_id' => $this->source->id,
        'subject' => 'Test CSV Export',
    ]);
});

it('exports CSV with correct headers', function () {
    Event::factory()->create([
        'message_id' => $this->message->id,
        'event_type' => EventType::Delivery,
        'recipient_email' => 'test@example.com',
        'event_at' => now(),
        'ses_message_id' => $this->message->ses_message_id,
    ]);

    $events = Event::query()
        ->whereRelation('message', 'source_id', $this->source->id)
        ->reverseChronologically()
        ->with('message')
        ->get();

    $csv = Writer::createFromString();
    $csv->insertOne(['Date', 'Event Type', 'Recipient', 'Subject', 'Bounce Type', 'SES Message ID']);

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

    $output = $csv->toString();
    $reader = Reader::createFromString($output);
    $reader->setHeaderOffset(0);

    $headers = $reader->getHeader();
    expect($headers)->toBe(['Date', 'Event Type', 'Recipient', 'Subject', 'Bounce Type', 'SES Message ID']);

    $records = iterator_to_array($reader->getRecords());
    expect($records)->toHaveCount(1);

    $row = array_values($records)[0];
    expect($row['Event Type'])->toBe('Delivery')
        ->and($row['Recipient'])->toBe('test@example.com')
        ->and($row['Subject'])->toBe('Test CSV Export');
});

it('includes bounce type in CSV for bounce events', function () {
    Event::factory()->bounce()->create([
        'message_id' => $this->message->id,
        'event_at' => now(),
        'ses_message_id' => $this->message->ses_message_id,
    ]);

    $event = Event::with('message')->first();

    $csv = Writer::createFromString();
    $csv->insertOne(['Date', 'Event Type', 'Recipient', 'Subject', 'Bounce Type', 'SES Message ID']);
    $csv->insertOne([
        $event->event_at->toDateTimeString(),
        $event->event_type->value,
        $event->recipient_email,
        $event->message?->subject ?? '',
        $event->bounce_type ?? '',
        $event->ses_message_id,
    ]);

    $reader = Reader::createFromString($csv->toString());
    $reader->setHeaderOffset(0);
    $row = array_values(iterator_to_array($reader->getRecords()))[0];

    expect($row['Bounce Type'])->toBe('Permanent')
        ->and($row['Event Type'])->toBe('Bounce');
});

it('exports multiple rows in chronological order', function () {
    Event::factory()->ofType(EventType::Send)->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subMinutes(2),
        'ses_message_id' => $this->message->ses_message_id,
    ]);
    Event::factory()->ofType(EventType::Delivery)->create([
        'message_id' => $this->message->id,
        'event_at' => now()->subMinute(),
        'ses_message_id' => $this->message->ses_message_id.'-d',
    ]);

    $events = Event::reverseChronologically()->with('message')->get();

    $csv = Writer::createFromString();
    $csv->insertOne(['Date', 'Event Type', 'Recipient', 'Subject', 'Bounce Type', 'SES Message ID']);
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

    $reader = Reader::createFromString($csv->toString());
    $reader->setHeaderOffset(0);
    $records = array_values(iterator_to_array($reader->getRecords()));

    expect($records)->toHaveCount(2)
        ->and($records[0]['Event Type'])->toBe('Delivery')
        ->and($records[1]['Event Type'])->toBe('Send');
});
