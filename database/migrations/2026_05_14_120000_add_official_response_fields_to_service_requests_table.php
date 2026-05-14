<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('official_response_path')->nullable()->after('message');
            $table->string('official_response_original_name')->nullable()->after('official_response_path');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'official_response_path',
                'official_response_original_name',
            ]);
        });
    }
};
