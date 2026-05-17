<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('official_response_uploaded_by')
                ->nullable()
                ->after('official_response_original_name')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('official_response_document_type')
                ->nullable()
                ->after('official_response_uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('official_response_uploaded_by');
            $table->dropColumn('official_response_document_type');
        });
    }
};
