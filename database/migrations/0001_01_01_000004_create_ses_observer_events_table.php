<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ses_observer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('ses_observer_messages')->cascadeOnDelete();
            $table->foreignId('webhook_id')->nullable()->constrained('ses_observer_webhooks')->nullOnDelete();
            $table->string('event_type');
            $table->string('recipient_email')->index();
            $table->timestamp('event_at')->index();
            $table->string('ses_message_id')->index();
            $table->json('event_data')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('bounce_type')->nullable();
            $table->timestamps();

            $table->unique(
                ['ses_message_id', 'event_type', 'recipient_email', 'event_at'],
                'ses_observer_events_dedup_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ses_observer_events');
    }
};
