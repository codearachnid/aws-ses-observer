<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Jobs;

use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteExpiredDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Source::withRetentionPolicy()
            ->chunkById(50, function ($sources) {
                foreach ($sources as $source) {
                    $count = $source->deleteExpiredData();

                    if ($count > 0) {
                        Log::info("SES Observer: Deleted {$count} expired messages for source '{$source->name}'.");
                    }
                }
            });
    }
}
