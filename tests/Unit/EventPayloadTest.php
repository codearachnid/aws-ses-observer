<?php

declare(strict_types=1);

use codearachnid\AwsSesObserver\DataTransferObjects\EventPayload;

function loadFixture(string $file): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../fixtures/'.$file),
        true,
    );
}

it('extracts event type', function (string $fixture, string $expected) {
    $payload = new EventPayload(loadFixture($fixture));

    expect($payload->eventType())->toBe($expected);
})->with([
    'send' => ['ses_send.json', 'Send'],
    'delivery' => ['ses_delivery.json', 'Delivery'],
    'bounce permanent' => ['ses_bounce_permanent.json', 'Bounce'],
    'bounce transient' => ['ses_bounce_transient.json', 'Bounce'],
    'complaint' => ['ses_complaint.json', 'Complaint'],
    'open' => ['ses_open.json', 'Open'],
    'click' => ['ses_click.json', 'Click'],
]);

it('extracts message ID from mail data', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->messageId())->toBe('EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000');
});

it('extracts source email', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->sourceEmail())->toBe('sender@example.com');
});

it('extracts subject from common headers', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->subject())->toBe('Test Email Subject');
});

it('extracts sent at timestamp', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->sentAt())->not->toBeNull()
        ->and($payload->sentAt()->toDateString())->toBe('2024-01-15');
});

it('extracts event-specific timestamp', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->timestamp())->not->toBeNull()
        ->and($payload->timestamp()->toIso8601String())->toContain('2024-01-15');
});

it('extracts delivery recipients', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->recipients())->toBe(['recipient@example.com']);
});

it('extracts bounced recipients', function () {
    $payload = new EventPayload(loadFixture('ses_bounce_permanent.json'));

    expect($payload->recipients())->toBe(['bounce@example.com']);
});

it('extracts complained recipients', function () {
    $payload = new EventPayload(loadFixture('ses_complaint.json'));

    expect($payload->recipients())->toBe(['complaint@example.com']);
});

it('extracts open recipients from mail destination', function () {
    $payload = new EventPayload(loadFixture('ses_open.json'));

    expect($payload->recipients())->toBe(['reader@example.com']);
});

it('extracts click recipients from mail destination', function () {
    $payload = new EventPayload(loadFixture('ses_click.json'));

    expect($payload->recipients())->toBe(['clicker@example.com']);
});

it('extracts bounce type for bounce events', function () {
    $permanent = new EventPayload(loadFixture('ses_bounce_permanent.json'));
    $transient = new EventPayload(loadFixture('ses_bounce_transient.json'));

    expect($permanent->bounceType())->toBe('Permanent')
        ->and($transient->bounceType())->toBe('Transient');
});

it('returns null bounce type for non-bounce events', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    expect($payload->bounceType())->toBeNull();
});

it('extracts event data block', function () {
    $payload = new EventPayload(loadFixture('ses_click.json'));

    $eventData = $payload->eventData();
    expect($eventData)->toHaveKey('link')
        ->and($eventData['link'])->toBe('https://example.com/welcome');
});

it('returns mail data array', function () {
    $payload = new EventPayload(loadFixture('ses_delivery.json'));

    $mailData = $payload->mailData();
    expect($mailData)->toHaveKey('messageId')
        ->and($mailData)->toHaveKey('source')
        ->and($mailData)->toHaveKey('destination');
});
