<?php

namespace codearachnid\AwsSesObserver\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \codearachnid\AwsSesObserver\AwsSesObserver
 */
class AwsSesObserver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \codearachnid\AwsSesObserver\AwsSesObserver::class;
    }
}
