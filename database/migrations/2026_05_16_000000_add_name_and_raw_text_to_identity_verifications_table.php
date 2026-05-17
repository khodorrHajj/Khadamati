<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('extracted_first_name')->nullable()->after('status');
            $table->string('extracted_family_name')->nullable()->after('extracted_first_name');
            $table->text('ocr_raw_text')->nullable()->after('ocr_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'extracted_first_name',
                'extracted_family_name',
                'ocr_raw_text',
            ]);
        });
    }
};
