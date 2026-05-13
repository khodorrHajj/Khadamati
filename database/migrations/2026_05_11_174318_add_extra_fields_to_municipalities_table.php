<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('street')->nullable()->after('city');
            $table->string('building')->nullable()->after('street');
            $table->text('google_maps_url')->nullable()->after('building');
            $table->string('status')->default('active')->after('google_maps_url');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropColumn([
                'city', 'street', 'building',
                'google_maps_url', 'status', 'notes'
            ]);
        });
    }
};