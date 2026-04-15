<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('models extend Eloquent Model')
    ->expect([
        'codearachnid\AwsSesObserver\Models\Source',
        'codearachnid\AwsSesObserver\Models\Webhook',
        'codearachnid\AwsSesObserver\Models\Message',
        'codearachnid\AwsSesObserver\Models\Event',
    ])
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('enums are string-backed')
    ->expect('codearachnid\AwsSesObserver\Enums')
    ->toBeStringBackedEnums();

arch('actions have execute method')
    ->expect('codearachnid\AwsSesObserver\Actions')
    ->toHaveMethod('execute');

arch('controllers have suffix')
    ->expect('codearachnid\AwsSesObserver\Http\Controllers')
    ->toHaveSuffix('Controller');
