<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('government_office_id');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('job_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('users', 'phone') ? 'phone' : null,
                Schema::hasColumn('users', 'job_title') ? 'job_title' : null,
                Schema::hasColumn('users', 'status') ? 'status' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
