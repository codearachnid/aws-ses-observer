<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ses_observer_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('ses_observer_sources')->cascadeOnDelete();
            $table->string('ses_message_id')->unique();
            $table->string('subject')->nullable();
            $table->string('source_email')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->json('mail_metadata')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ses_observer_messages');
    }
};
