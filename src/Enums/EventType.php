<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Enums;

enum EventType: string
{
    case Send = 'Send';
    case Delivery = 'Delivery';
    case Bounce = 'Bounce';
    case Complaint = 'Complaint';
    case Reject = 'Reject';
    case DeliveryDelay = 'DeliveryDelay';
    case RenderingFailure = 'RenderingFailure';
    case Subscription = 'Subscription';
    case Open = 'Open';
    case Click = 'Click';

    public function label(): string
    {
        return match ($this) {
            self::Send => 'Sent',
            self::Delivery => 'Delivered',
            self::Bounce => 'Bounced',
            self::Complaint => 'Complaint',
            self::Reject => 'Rejected',
            self::DeliveryDelay => 'Delayed',
            self::RenderingFailure => 'Rendering Failure',
            self::Subscription => 'Subscription',
            self::Open => 'Opened',
            self::Click => 'Clicked',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Send => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::Delivery => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::Bounce => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::Complaint => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            self::Reject => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::DeliveryDelay => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::RenderingFailure => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::Subscription => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            self::Open => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::Click => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
        };
    }

    public function filterChipClasses(): string
    {
        return match ($this) {
            self::Send => 'border-gray-300 bg-gray-50 text-gray-700',
            self::Delivery => 'border-green-300 bg-green-50 text-green-700',
            self::Bounce => 'border-red-300 bg-red-50 text-red-700',
            self::Complaint => 'border-orange-300 bg-orange-50 text-orange-700',
            self::Reject => 'border-red-300 bg-red-50 text-red-700',
            self::DeliveryDelay => 'border-yellow-300 bg-yellow-50 text-yellow-700',
            self::RenderingFailure => 'border-red-300 bg-red-50 text-red-700',
            self::Subscription => 'border-purple-300 bg-purple-50 text-purple-700',
            self::Open => 'border-blue-300 bg-blue-50 text-blue-700',
            self::Click => 'border-cyan-300 bg-cyan-50 text-cyan-700',
        };
    }
}
