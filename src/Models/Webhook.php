<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Webhook extends Model
{
    use HasFactory;

    protected $table = 'ses_observer_webhooks';

    protected $fillable = [
        'sns_message_id',
        'sns_type',
        'sns_timestamp',
        'raw_payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'sns_timestamp' => 'datetime',
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => Carbon::now()]);
    }
}
