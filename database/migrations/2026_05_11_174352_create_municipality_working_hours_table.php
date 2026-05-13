<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')
                  ->constrained('municipalities')
                  ->onDelete('cascade');
            $table->string('day_of_week');
            $table->boolean('is_open')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_working_hours');
    }
};