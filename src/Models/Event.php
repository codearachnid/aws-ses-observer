<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Models;

use codearachnid\AwsSesObserver\Enums\BounceType;
use codearachnid\AwsSesObserver\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $table = 'ses_observer_events';

    protected $fillable = [
        'message_id',
        'webhook_id',
        'event_type',
        'recipient_email',
        'event_at',
        'ses_message_id',
        'event_data',
        'raw_payload',
        'bounce_type',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'event_at' => 'datetime',
            'event_data' => 'array',
            'raw_payload' => 'array',
        ];
    }

    protected function recipientEmail(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower(trim($value)),
        );
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function isPermanentBounce(): bool
    {
        return $this->event_type === EventType::Bounce
            && $this->bounce_type === BounceType::Permanent->value;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('recipient_email', 'like', $like)
                ->orWhereHas('message', fn (Builder $q) => $q->where('subject', 'like', $like));
        });
    }

    /**
     * @param  array<int, EventType|string>  $types
     */
    public function scopeWithEventTypes(Builder $query, array $types): Builder
    {
        $values = array_map(
            fn ($type) => $type instanceof EventType ? $type->value : $type,
            $types,
        );

        return $query->whereIn('event_type', $values);
    }

    /**
     * @param  array<int, BounceType|string>  $types
     */
    public function scopeWithBounceTypes(Builder $query, array $types): Builder
    {
        $values = array_map(
            fn ($type) => $type instanceof BounceType ? $type->value : $type,
            $types,
        );

        return $query->whereIn('bounce_type', $values);
    }

    public function scopeBetweenDates(Builder $query, Carbon|string|null $from, Carbon|string|null $to): Builder
    {
        if ($from) {
            $query->where('event_at', '>=', $from);
        }

        if ($to) {
            $query->where('event_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function scopeFilterByParams(Builder $query, array $params): Builder
    {
        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['event_types'])) {
            $query->withEventTypes($params['event_types']);
        }

        if (! empty($params['bounce_types'])) {
            $query->withBounceTypes($params['bounce_types']);
        }

        if (! empty($params['from']) || ! empty($params['to'])) {
            $query->betweenDates($params['from'] ?? null, $params['to'] ?? null);
        }

        return $query;
    }

    public function scopeReverseChronologically(Builder $query): Builder
    {
        return $query->orderByDesc('event_at');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, int>
     */
    public static function filterCounts(array $params): array
    {
        $baseQuery = self::query()->filterByParams($params);

        $eventCounts = (clone $baseQuery)
            ->selectRaw('event_type, count(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        $bounceCounts = (clone $baseQuery)
            ->where('event_type', EventType::Bounce->value)
            ->selectRaw('bounce_type, count(*) as count')
            ->groupBy('bounce_type')
            ->pluck('count', 'bounce_type')
            ->toArray();

        return array_merge($eventCounts, $bounceCounts);
    }
}
