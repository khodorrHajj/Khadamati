<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('extracted_issue_date_text')->nullable()->after('extracted_blood_type');
        });

        Schema::table('national_ids', function (Blueprint $table) {
            $table->string('issue_date_text')->nullable()->after('blood_type');
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn('extracted_issue_date_text');
        });

        Schema::table('national_ids', function (Blueprint $table) {
            $table->dropColumn('issue_date_text');
        });
    }
};
