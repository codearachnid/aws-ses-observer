<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Actions;

use codearachnid\AwsSesObserver\DataTransferObjects\EventPayload;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;
use codearachnid\AwsSesObserver\Models\Webhook;

class IngestEvent
{
    public function execute(EventPayload $payload, Source $source, ?Webhook $webhook = null): void
    {
        $message = Message::findOrCreateFromPayload($payload, $source);

        $recipients = $payload->recipients();

        if (empty($recipients)) {
            $recipients = $message->destinationEmails();
        }

        foreach ($recipients as $recipient) {
            $event = Event::firstOrCreate(
                [
                    'ses_message_id' => $payload->messageId(),
                    'event_type' => $payload->eventType(),
                    'recipient_email' => strtolower(trim($recipient)),
                    'event_at' => $payload->timestamp() ?? $payload->sentAt() ?? now(),
                ],
                [
                    'message_id' => $message->id,
                    'webhook_id' => $webhook?->id,
                    'event_data' => $payload->eventData(),
                    'bounce_type' => $payload->bounceType(),
                ],
            );

            if ($event->wasRecentlyCreated) {
                $message->increment('events_count');
            }
        }
    }
}
