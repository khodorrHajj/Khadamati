<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'duration_days_max')) {
                $table->integer('duration_days_max')->nullable()->after('duration_days');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'missing_document_items')) {
                $table->json('missing_document_items')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'duration_days_max')) {
                $table->dropColumn('duration_days_max');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'missing_document_items')) {
                $table->dropColumn('missing_document_items');
            }
        });
    }
};
