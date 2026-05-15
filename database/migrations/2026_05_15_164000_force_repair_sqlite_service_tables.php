<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        if (Schema::hasTable('service_categories')) {
            $this->addColumnIfMissing('service_categories', 'government_office_id', 'INTEGER');
            $this->addColumnIfMissing('service_categories', 'name', 'VARCHAR');
            $this->addColumnIfMissing('service_categories', 'description', 'TEXT');
        }

        if (Schema::hasTable('services')) {
            $this->addColumnIfMissing('services', 'government_office_id', 'INTEGER');
            $this->addColumnIfMissing('services', 'service_category_id', 'INTEGER');
            $this->addColumnIfMissing('services', 'name', 'VARCHAR');
            $this->addColumnIfMissing('services', 'description', 'TEXT');
            $this->addColumnIfMissing('services', 'price', 'NUMERIC DEFAULT 0');
            $this->addColumnIfMissing('services', 'duration_days', 'INTEGER DEFAULT 1');
            $this->addColumnIfMissing('services', 'required_documents', 'TEXT');
            $this->addColumnIfMissing('services', 'is_active', 'INTEGER DEFAULT 1');
        }
    }

    public function down(): void
    {
        // SQLite does not support dropping columns safely without rebuilding tables.
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (!Schema::hasColumn($table, $column)) {
            DB::statement(sprintf('ALTER TABLE "%s" ADD COLUMN "%s" %s', $table, $column, $definition));
        }
    }
};
