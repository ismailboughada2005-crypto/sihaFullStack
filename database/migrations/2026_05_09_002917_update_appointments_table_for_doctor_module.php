<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('patientId', 'patient_id');
            $table->renameColumn('doctorId', 'doctor_id');
            $table->renameColumn('date', 'appointment_date');
            $table->renameColumn('time', 'appointment_time');
            $table->text('notes')->nullable()->after('appointment_time');
            $table->text('cancellation_reason')->nullable()->after('notes');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->string('status', 20)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('patient_id', 'patientId');
            $table->renameColumn('doctor_id', 'doctorId');
            $table->renameColumn('appointment_date', 'date');
            $table->renameColumn('appointment_time', 'time');
            $table->dropColumn(['notes', 'cancellation_reason', 'confirmed_at', 'completed_at']);
            $table->string('status', 20)->default('Scheduled')->change();
        });
    }
};
