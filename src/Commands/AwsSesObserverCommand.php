<?php

namespace codearachnid\AwsSesObserver\Commands;

use Illuminate\Console\Command;

class AwsSesObserverCommand extends Command
{
    public $signature = 'aws-ses-observer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
