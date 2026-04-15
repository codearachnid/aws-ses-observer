<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Database\Factories;

use codearachnid\AwsSesObserver\Models\Message;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'ses_message_id' => (string) Str::uuid(),
            'subject' => fake()->sentence(),
            'source_email' => fake()->safeEmail(),
            'sent_at' => now(),
            'mail_metadata' => [
                'destination' => [fake()->safeEmail()],
                'source' => fake()->safeEmail(),
                'messageId' => (string) Str::uuid(),
            ],
            'events_count' => 0,
        ];
    }
}
