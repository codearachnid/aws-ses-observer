<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;
use codearachnid\AwsSesObserver\Models\Webhook;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->source = Source::factory()->create();
});

function snsNotification(string $fixtureFile, ?string $messageId = null): array
{
    $sesPayload = json_decode(
        file_get_contents(__DIR__.'/../fixtures/'.$fixtureFile),
        true,
    );

    return [
        'Type' => 'Notification',
        'MessageId' => $messageId ?? fake()->uuid(),
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789012:ses-observer-events',
        'Message' => json_encode($sesPayload),
        'Timestamp' => '2024-01-15T12:00:00.000Z',
        'SignatureVersion' => '1',
        'Signature' => 'test-signature',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-test.pem',
    ];
}

it('returns 404 for unknown source token', function () {
    $this->postJson(route('ses-observer.webhook', ['sourceToken' => 'nonexistent']))
        ->assertNotFound();
})->group('webhook');

it('handles subscription confirmation', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $payload = json_decode(
        file_get_contents(__DIR__.'/../fixtures/sns_subscription_confirmation.json'),
        true,
    );

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'SubscriptionConfirmation'],
    )->assertOk();

    Http::assertSentCount(1);
})->group('webhook');

it('handles unsubscribe confirmation', function () {
    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        ['Type' => 'UnsubscribeConfirmation', 'MessageId' => fake()->uuid(), 'Timestamp' => now()->toIso8601String()],
        ['x-amz-sns-message-type' => 'UnsubscribeConfirmation'],
    )->assertOk();
})->group('webhook');

it('returns 400 for unknown SNS message type', function () {
    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        ['Type' => 'SomethingWeird'],
        ['x-amz-sns-message-type' => 'SomethingWeird'],
    )->assertBadRequest();
})->group('webhook');

it('ingests a delivery notification', function () {
    $payload = snsNotification('ses_delivery.json');

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    expect(Webhook::count())->toBe(1)
        ->and(Message::count())->toBe(1)
        ->and(Event::count())->toBe(1);

    $event = Event::first();
    expect($event->event_type->value)->toBe('Delivery')
        ->and($event->recipient_email)->toBe('recipient@example.com');

    $message = Message::first();
    expect($message->source_id)->toBe($this->source->id)
        ->and($message->subject)->toBe('Test Email Subject')
        ->and($message->events_count)->toBe(1);
})->group('webhook', 'ingestion');

it('ingests a permanent bounce notification', function () {
    $payload = snsNotification('ses_bounce_permanent.json');

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    $event = Event::first();
    expect($event->event_type->value)->toBe('Bounce')
        ->and($event->bounce_type)->toBe('Permanent')
        ->and($event->isPermanentBounce())->toBeTrue();
})->group('webhook', 'ingestion');

it('ingests a transient bounce notification', function () {
    $payload = snsNotification('ses_bounce_transient.json');

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    $event = Event::first();
    expect($event->event_type->value)->toBe('Bounce')
        ->and($event->bounce_type)->toBe('Transient')
        ->and($event->isPermanentBounce())->toBeFalse();
})->group('webhook', 'ingestion');

it('is idempotent — duplicate notifications do not create duplicate events', function () {
    $snsMessageId = fake()->uuid();
    $payload = snsNotification('ses_delivery.json', $snsMessageId);

    // First request
    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    // Second request with same SNS message ID
    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    expect(Webhook::count())->toBe(1)
        ->and(Event::count())->toBe(1);
})->group('webhook', 'ingestion');

it('ingests all event types', function (string $fixture, string $expectedType) {
    $payload = snsNotification($fixture);

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    expect(Event::first()->event_type->value)->toBe($expectedType);
})->with([
    'send' => ['ses_send.json', 'Send'],
    'delivery' => ['ses_delivery.json', 'Delivery'],
    'bounce permanent' => ['ses_bounce_permanent.json', 'Bounce'],
    'bounce transient' => ['ses_bounce_transient.json', 'Bounce'],
    'complaint' => ['ses_complaint.json', 'Complaint'],
    'open' => ['ses_open.json', 'Open'],
    'click' => ['ses_click.json', 'Click'],
])->group('webhook', 'ingestion');

it('handles notification with empty message body gracefully', function () {
    $payload = [
        'Type' => 'Notification',
        'MessageId' => fake()->uuid(),
        'Message' => '{}',
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'test',
        'SigningCertURL' => 'https://example.com/cert.pem',
    ];

    $this->postJson(
        route('ses-observer.webhook', ['sourceToken' => $this->source->token]),
        $payload,
        ['x-amz-sns-message-type' => 'Notification'],
    )->assertOk();

    expect(Webhook::first()->processed_at)->not->toBeNull()
        ->and(Event::count())->toBe(0);
})->group('webhook');
