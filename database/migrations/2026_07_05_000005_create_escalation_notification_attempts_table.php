<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('escalation_notification_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('escalation_notification_id');
            $table->unsignedTinyInteger('attempt_number');
            $table->string('status', 30);
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();

            $table->foreign('escalation_notification_id', 'esc_notif_attempts_fk')
                ->references('id')
                ->on('escalation_notifications')
                ->cascadeOnDelete();

            $table->unique(['escalation_notification_id', 'attempt_number'], 'esc_notif_attempts_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalation_notification_attempts');
    }
};
