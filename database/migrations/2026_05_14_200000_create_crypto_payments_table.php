<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->unsignedBigInteger('service_request_id')->nullable();
            $table->string('nowpayments_payment_id')->nullable()->unique();
            $table->string('nowpayments_invoice_id')->nullable();
            $table->decimal('price_amount', 10, 2);
            $table->enum('status', [
                'waiting', 'confirming', 'confirmed', 'sending',
                'partially_paid', 'finished', 'failed', 'refunded', 'expired',
            ])->default('waiting');
            $table->text('payment_url')->nullable();
            $table->string('payin_address')->nullable();
            $table->decimal('actually_paid', 20, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_payments');
    }
};
