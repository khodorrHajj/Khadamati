<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('government_office_id')
                ->constrained('government_offices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('time_slot_id')
                ->nullable()
                ->constrained('time_slots')
                ->nullOnDelete();
            $table->string('status')->default('Requested');
            $table->text('notes')->nullable();
            $table->text('municipality_notes')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reminder_scheduled_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('email_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
