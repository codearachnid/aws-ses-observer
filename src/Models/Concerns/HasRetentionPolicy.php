<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait HasRetentionPolicy
{
    public function scopeWithRetentionPolicy(Builder $query): Builder
    {
        return $query->whereNotNull('retention_days');
    }

    public function deleteExpiredData(): int
    {
        $cutoff = Carbon::now()->subDays($this->retention_days);

        $count = 0;

        $this->messages()
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($messages) use (&$count) {
                foreach ($messages as $message) {
                    $message->delete();
                    $count++;
                }
            });

        return $count;
    }
}
