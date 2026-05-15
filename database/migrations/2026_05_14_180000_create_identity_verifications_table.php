<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('id_image_path')->nullable();
            $table->string('status')->default('pending_upload');
            $table->string('extracted_full_name')->nullable();
            $table->string('extracted_id_number')->nullable();
            $table->date('extracted_date_of_birth')->nullable();
            $table->decimal('ocr_confidence', 5, 4)->nullable();
            $table->json('ocr_raw_json')->nullable();
            $table->json('quality_result_json')->nullable();
            $table->json('exif_result_json')->nullable();
            $table->json('validation_result_json')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
