<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Database\Factories;

use codearachnid\AwsSesObserver\Enums\SourceColor;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'token' => (string) Str::uuid(),
            'color' => fake()->randomElement(SourceColor::cases()),
            'retention_days' => null,
        ];
    }

    public function withRetention(int $days = 30): static
    {
        return $this->state(['retention_days' => $days]);
    }
}
