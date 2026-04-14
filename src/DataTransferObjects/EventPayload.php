<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class EventPayload
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private array $data,
    ) {}

    public function eventType(): string
    {
        return $this->data['eventType'] ?? '';
    }

    public function messageId(): string
    {
        return $this->mailData()['messageId'] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function mailData(): array
    {
        return $this->data['mail'] ?? [];
    }

    public function sourceEmail(): ?string
    {
        return $this->mailData()['source'] ?? null;
    }

    public function subject(): ?string
    {
        $headers = $this->mailData()['commonHeaders'] ?? [];

        return $headers['subject'] ?? null;
    }

    public function sentAt(): ?Carbon
    {
        $timestamp = $this->mailData()['timestamp'] ?? null;

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    public function timestamp(): ?Carbon
    {
        $eventKey = lcfirst($this->eventType());
        $block = $this->data[$eventKey] ?? [];
        $timestamp = $block['timestamp'] ?? null;

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        $type = $this->eventType();
        $eventKey = lcfirst($type);
        $block = $this->data[$eventKey] ?? [];

        return match ($type) {
            'Bounce' => array_map(
                fn (array $r) => $r['emailAddress'] ?? '',
                $block['bouncedRecipients'] ?? [],
            ),
            'Complaint' => array_map(
                fn (array $r) => $r['emailAddress'] ?? '',
                $block['complainedRecipients'] ?? [],
            ),
            'Delivery' => $block['recipients'] ?? [],
            'DeliveryDelay' => array_map(
                fn (array $r) => $r['emailAddress'] ?? '',
                $block['delayedRecipients'] ?? [],
            ),
            'Send', 'Reject', 'RenderingFailure', 'Subscription' => $this->mailData()['destination'] ?? [],
            'Open', 'Click' => $this->mailData()['destination'] ?? [],
            default => $this->mailData()['destination'] ?? [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function eventData(): array
    {
        $eventKey = lcfirst($this->eventType());

        return $this->data[$eventKey] ?? [];
    }

    public function bounceType(): ?string
    {
        if ($this->eventType() !== 'Bounce') {
            return null;
        }

        return $this->eventData()['bounceType'] ?? null;
    }
}
