<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_office_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('government_office_id')
                ->constrained('government_offices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('day_of_week');
            $table->boolean('is_open')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_office_working_hours');
    }
};
