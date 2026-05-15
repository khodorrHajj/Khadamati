<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')->nullable()->after('admin_internal_note')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->after('assigned_to_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by_user_id');
            $table->string('workflow_state', 50)->default('awaiting_municipality')->after('assigned_at');
            $table->timestamp('escalated_to_admin_at')->nullable()->after('workflow_state');
            $table->text('escalation_reason')->nullable()->after('escalated_to_admin_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropConstrainedForeignId('assigned_by_user_id');
            $table->dropColumn([
                'assigned_at',
                'workflow_state',
                'escalated_to_admin_at',
                'escalation_reason',
            ]);
        });
    }
};
