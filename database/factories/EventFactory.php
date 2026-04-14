<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Database\Factories;

use codearachnid\AwsSesObserver\Enums\BounceType;
use codearachnid\AwsSesObserver\Enums\EventType;
use codearachnid\AwsSesObserver\Models\Event;
use codearachnid\AwsSesObserver\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'webhook_id' => null,
            'event_type' => EventType::Delivery,
            'recipient_email' => fake()->safeEmail(),
            'event_at' => now(),
            'ses_message_id' => (string) Str::uuid(),
            'event_data' => null,
            'raw_payload' => null,
            'bounce_type' => null,
        ];
    }

    public function ofType(EventType $type): static
    {
        return $this->state(['event_type' => $type]);
    }

    public function bounce(BounceType $bounceType = BounceType::Permanent): static
    {
        return $this->state([
            'event_type' => EventType::Bounce,
            'bounce_type' => $bounceType->value,
        ]);
    }
}
