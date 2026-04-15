@props(['type'])

@php
    $eventType = $type instanceof \codearachnid\AwsSesObserver\Enums\EventType
        ? $type
        : \codearachnid\AwsSesObserver\Enums\EventType::tryFrom($type);
@endphp

@if($eventType)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $eventType->badgeClasses()]) }}>
        {{ $eventType->label() }}
    </span>
@endif
