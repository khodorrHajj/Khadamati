<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_offices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained('municipalities')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('google_maps_location')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('contact_info')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_offices');
    }
};