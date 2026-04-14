<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Enums;

use codearachnid\AwsSesObserver\Models\Source;

enum SourceColor: string
{
    case Purple = 'purple';
    case Blue = 'blue';
    case Cyan = 'cyan';
    case Green = 'green';
    case Red = 'red';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Gray = 'gray';

    public function cssClass(): string
    {
        return match ($this) {
            self::Purple => 'bg-purple-500',
            self::Blue => 'bg-blue-500',
            self::Cyan => 'bg-cyan-500',
            self::Green => 'bg-green-500',
            self::Red => 'bg-red-500',
            self::Orange => 'bg-orange-500',
            self::Yellow => 'bg-yellow-500',
            self::Gray => 'bg-gray-500',
        };
    }

    public static function nextAvailable(): self
    {
        $counts = Source::query()
            ->selectRaw('color, count(*) as count')
            ->groupBy('color')
            ->pluck('count', 'color');

        $min = PHP_INT_MAX;
        $pick = self::Blue;

        foreach (self::cases() as $color) {
            $count = (int) ($counts[$color->value] ?? 0);
            if ($count < $min) {
                $min = $count;
                $pick = $color;
            }
        }

        return $pick;
    }
}
