<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('models extend Eloquent Model')
    ->expect('codearachnid\AwsSesObserver\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('enums are string-backed')
    ->expect('codearachnid\AwsSesObserver\Enums')
    ->toBeStringBackedEnums();
