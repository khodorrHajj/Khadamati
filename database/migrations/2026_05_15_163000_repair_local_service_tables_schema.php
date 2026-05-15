<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_categories')) {
            Schema::table('service_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('service_categories', 'government_office_id')) {
                    $table->foreignId('government_office_id')->nullable()->after('id');
                }

                if (!Schema::hasColumn('service_categories', 'name')) {
                    $table->string('name')->nullable()->after('government_office_id');
                }

                if (!Schema::hasColumn('service_categories', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (!Schema::hasColumn('services', 'government_office_id')) {
                    $table->foreignId('government_office_id')->nullable()->after('id');
                }

                if (!Schema::hasColumn('services', 'service_category_id')) {
                    $table->foreignId('service_category_id')->nullable()->after('government_office_id');
                }

                if (!Schema::hasColumn('services', 'name')) {
                    $table->string('name')->nullable()->after('service_category_id');
                }

                if (!Schema::hasColumn('services', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }

                if (!Schema::hasColumn('services', 'price')) {
                    $table->decimal('price', 10, 2)->default(0)->after('description');
                }

                if (!Schema::hasColumn('services', 'duration_days')) {
                    $table->integer('duration_days')->default(1)->after('price');
                }

                if (!Schema::hasColumn('services', 'required_documents')) {
                    $table->text('required_documents')->nullable()->after('duration_days');
                }

                if (!Schema::hasColumn('services', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('required_documents');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_categories')) {
            Schema::table('service_categories', function (Blueprint $table) {
                if (Schema::hasColumn('service_categories', 'description')) {
                    $table->dropColumn('description');
                }

                if (Schema::hasColumn('service_categories', 'name')) {
                    $table->dropColumn('name');
                }

                if (Schema::hasColumn('service_categories', 'government_office_id')) {
                    $table->dropColumn('government_office_id');
                }
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $columns = [
                    'is_active',
                    'required_documents',
                    'duration_days',
                    'price',
                    'description',
                    'name',
                    'service_category_id',
                    'government_office_id',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('services', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
