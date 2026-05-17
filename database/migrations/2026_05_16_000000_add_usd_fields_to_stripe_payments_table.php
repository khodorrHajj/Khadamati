<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_payments', function (Blueprint $table) {
            $table->decimal('price_amount_usd', 10, 2)->nullable()->after('price_amount');
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('price_amount_usd');
        });
    }

    public function down(): void
    {
        Schema::table('stripe_payments', function (Blueprint $table) {
            $table->dropColumn(['price_amount_usd', 'exchange_rate']);
        });
    }
};
