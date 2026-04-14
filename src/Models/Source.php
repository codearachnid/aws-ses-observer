<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Models;

use codearachnid\AwsSesObserver\Enums\SourceColor;
use codearachnid\AwsSesObserver\Models\Concerns\HasRetentionPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Source extends Model
{
    use HasFactory;
    use HasRetentionPolicy;

    protected $table = 'ses_observer_sources';

    protected $fillable = [
        'name',
        'token',
        'color',
        'retention_days',
    ];

    protected function casts(): array
    {
        return [
            'color' => SourceColor::class,
            'retention_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Source $source) {
            if (empty($source->token)) {
                $source->token = (string) Str::uuid();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function events(): HasManyThrough
    {
        return $this->hasManyThrough(Event::class, Message::class);
    }

    public function scopeAlphabetically(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
