<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Models;

use codearachnid\AwsSesObserver\DataTransferObjects\EventPayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $table = 'ses_observer_messages';

    protected $fillable = [
        'source_id',
        'ses_message_id',
        'subject',
        'source_email',
        'sent_at',
        'mail_metadata',
        'events_count',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'mail_metadata' => 'array',
            'events_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ses_message_id';
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return array<int, string>
     */
    public function destinationEmails(): array
    {
        $metadata = $this->mail_metadata ?? [];
        $destination = $metadata['destination'] ?? [];

        return array_values(array_unique($destination));
    }

    /**
     * @return array<int, string>
     */
    public function tags(bool $includeSes = false): array
    {
        $metadata = $this->mail_metadata ?? [];
        $tags = $metadata['tags'] ?? [];

        if (! $includeSes) {
            $tags = array_filter(
                $tags,
                fn (array $tag) => ! str_starts_with($tag['name'] ?? '', 'ses:'),
            );
        }

        return array_values(array_map(
            fn (array $tag) => ($tag['name'] ?? '').':'.($tag['value'] ?? ''),
            $tags,
        ));
    }

    public static function findOrCreateFromPayload(EventPayload $payload, Source $source): self
    {
        return self::firstOrCreate(
            ['ses_message_id' => $payload->messageId()],
            [
                'source_id' => $source->id,
                'subject' => $payload->subject(),
                'source_email' => $payload->sourceEmail(),
                'sent_at' => $payload->sentAt(),
                'mail_metadata' => $payload->mailData(),
            ],
        );
    }
}
