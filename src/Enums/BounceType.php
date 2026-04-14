<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Enums;

enum BounceType: string
{
    case Permanent = 'Permanent';
    case Transient = 'Transient';
    case Undetermined = 'Undetermined';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Hard Bounce',
            self::Transient => 'Soft Bounce',
            self::Undetermined => 'Undetermined',
        };
    }
}
