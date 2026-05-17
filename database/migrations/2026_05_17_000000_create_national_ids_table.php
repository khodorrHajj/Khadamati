<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('national_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('identity_verification_id')->nullable()->constrained('identity_verifications')->cascadeOnUpdate()->nullOnDelete();
            $table->string('national_id_number')->nullable()->unique();
            $table->string('national_id_number_normalized')->nullable()->unique();
            $table->string('first_name_ar')->nullable();
            $table->string('family_name_ar')->nullable();
            $table->string('father_name_ar')->nullable();
            $table->string('mother_name_ar')->nullable();
            $table->string('place_of_birth_ar')->nullable();
            $table->string('date_of_birth_text')->nullable();
            $table->longText('raw_ocr_text')->nullable();
            $table->string('id_image_path')->nullable();
            $table->decimal('ocr_confidence', 5, 4)->nullable();
            $table->string('status')->default('pending_review');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('national_ids');
    }
};
