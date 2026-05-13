<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('government_offices', function (Blueprint $table) {
            if (!Schema::hasColumn('government_offices', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_url');
            }

            if (!Schema::hasColumn('government_offices', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (!Schema::hasColumn('government_offices', 'place_id')) {
                $table->string('place_id')->nullable()->after('longitude');
            }

            if (!Schema::hasColumn('government_offices', 'formatted_address')) {
                $table->text('formatted_address')->nullable()->after('place_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('government_offices', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('government_offices', 'latitude') ? 'latitude' : null,
                Schema::hasColumn('government_offices', 'longitude') ? 'longitude' : null,
                Schema::hasColumn('government_offices', 'place_id') ? 'place_id' : null,
                Schema::hasColumn('government_offices', 'formatted_address') ? 'formatted_address' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
