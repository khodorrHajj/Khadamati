<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('government_offices', function (Blueprint $table) {
            $table->string('service_type')->nullable()->after('name');
            $table->string('phone')->nullable()->after('service_type');
            $table->string('email')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('street')->nullable()->after('city');
            $table->string('building')->nullable()->after('street');
            $table->text('google_maps_url')->nullable()->after('google_maps_location');
            $table->string('status')->default('active')->after('google_maps_url');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('government_offices', function (Blueprint $table) {
            $table->dropColumn([
                'service_type',
                'phone',
                'email',
                'city',
                'street',
                'building',
                'google_maps_url',
                'status',
                'notes',
            ]);
        });
    }
};
