<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('tracking_code')->nullable()->after('service_id');
            $table->unique('tracking_code');
        });

        DB::table('service_requests')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function ($request) {
                DB::table('service_requests')
                    ->where('id', $request->id)
                    ->update([
                        'tracking_code' => $this->generateTrackingCode(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropColumn('tracking_code');
        });
    }

    private function generateTrackingCode(): string
    {
        do {
            $code = 'REQ-' . Str::upper(Str::random(10));
        } while (DB::table('service_requests')->where('tracking_code', $code)->exists());

        return $code;
    }
};
