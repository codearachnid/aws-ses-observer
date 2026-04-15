<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ses_observer_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('sns_message_id')->unique();
            $table->string('sns_type');
            $table->timestamp('sns_timestamp');
            $table->json('raw_payload');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ses_observer_webhooks');
    }
};
