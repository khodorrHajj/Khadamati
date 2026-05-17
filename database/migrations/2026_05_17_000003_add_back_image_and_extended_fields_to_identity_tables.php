<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('id_image_back_path')->nullable()->after('id_image_path');
            $table->string('extracted_father_name')->nullable()->after('extracted_family_name');
            $table->string('extracted_mother_name')->nullable()->after('extracted_father_name');
            $table->string('extracted_mother_family_name')->nullable()->after('extracted_mother_name');
            $table->string('extracted_place_of_birth')->nullable()->after('extracted_full_name');
            $table->string('extracted_date_of_birth_text')->nullable()->after('extracted_place_of_birth');
            $table->string('extracted_gender')->nullable()->after('extracted_date_of_birth');
            $table->string('extracted_marital_status')->nullable()->after('extracted_gender');
            $table->string('extracted_record_number')->nullable()->after('extracted_marital_status');
            $table->string('extracted_locality')->nullable()->after('extracted_record_number');
            $table->string('extracted_governorate')->nullable()->after('extracted_locality');
            $table->string('extracted_district')->nullable()->after('extracted_governorate');
            $table->string('extracted_blood_type')->nullable()->after('extracted_district');
        });

        Schema::table('national_ids', function (Blueprint $table) {
            $table->string('mother_family_name_ar')->nullable()->after('mother_name_ar');
            $table->string('gender_ar')->nullable()->after('date_of_birth_text');
            $table->string('marital_status_ar')->nullable()->after('gender_ar');
            $table->string('record_number')->nullable()->after('marital_status_ar');
            $table->string('locality_ar')->nullable()->after('record_number');
            $table->string('governorate_ar')->nullable()->after('locality_ar');
            $table->string('district_ar')->nullable()->after('governorate_ar');
            $table->string('blood_type')->nullable()->after('district_ar');
            $table->string('id_image_back_path')->nullable()->after('id_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'id_image_back_path',
                'extracted_father_name',
                'extracted_mother_name',
                'extracted_mother_family_name',
                'extracted_place_of_birth',
                'extracted_date_of_birth_text',
                'extracted_gender',
                'extracted_marital_status',
                'extracted_record_number',
                'extracted_locality',
                'extracted_governorate',
                'extracted_district',
                'extracted_blood_type',
            ]);
        });

        Schema::table('national_ids', function (Blueprint $table) {
            $table->dropColumn([
                'mother_family_name_ar',
                'gender_ar',
                'marital_status_ar',
                'record_number',
                'locality_ar',
                'governorate_ar',
                'district_ar',
                'blood_type',
                'id_image_back_path',
            ]);
        });
    }
};
