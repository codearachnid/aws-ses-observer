<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Actions;

use codearachnid\AwsSesObserver\DataTransferObjects\EventPayload;
use codearachnid\AwsSesObserver\Models\Source;
use codearachnid\AwsSesObserver\Models\Webhook;

class ProcessWebhook
{
    public function __construct(
        private readonly IngestEvent $ingestEvent,
    ) {}

    /**
     * @param  array<string, mixed>  $snsPayload
     */
    public function execute(array $snsPayload, Source $source): void
    {
        $snsMessageId = $snsPayload['MessageId'] ?? '';
        $snsTimestamp = $snsPayload['Timestamp'] ?? now()->toIso8601String();

        $webhook = Webhook::firstOrCreate(
            ['sns_message_id' => $snsMessageId],
            [
                'sns_type' => $snsPayload['Type'] ?? 'Notification',
                'sns_timestamp' => $snsTimestamp,
                'raw_payload' => $snsPayload,
            ],
        );

        if ($webhook->processed_at !== null) {
            return;
        }

        $messageBody = json_decode($snsPayload['Message'] ?? '{}', true);

        if (! is_array($messageBody) || empty($messageBody)) {
            $webhook->markAsProcessed();

            return;
        }

        $payload = new EventPayload($messageBody);

        if ($payload->eventType() === '' || $payload->messageId() === '') {
            $webhook->markAsProcessed();

            return;
        }

        $this->ingestEvent->execute($payload, $source, $webhook);

        $webhook->markAsProcessed();
    }
}
