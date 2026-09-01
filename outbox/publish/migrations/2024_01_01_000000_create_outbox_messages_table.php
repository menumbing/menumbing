<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stream', 100);
            $table->string('type', 100);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Index for worker polling: WHERE status IN (...) ORDER BY created_at.
            $table->index(['status', 'created_at']);
            // Index for querying by stream and event type (monitoring, filtering).
            $table->index(['stream', 'type']);
            // Index for pruning: DELETE WHERE status = 'sent' AND sent_at < ?.
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
