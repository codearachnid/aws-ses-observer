<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Database\Factories;

use codearachnid\AwsSesObserver\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sns_message_id' => (string) Str::uuid(),
            'sns_type' => 'Notification',
            'sns_timestamp' => now(),
            'raw_payload' => ['Message' => '{}'],
            'processed_at' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(['processed_at' => now()]);
    }
}
