<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add high-performance B-Tree composite and single-column indexes on core hospital tables.
     * 
     * Optimizes:
     * 1. Patient search, MRN lookups, and concurrent registration locking.
     * 2. Appointment conflict checks (assertNoClash), slot schedules, and day views.
     * 3. Bed occupancy scans (whereNull('d_time')) and admission history.
     * 4. Patient chart loading across appointments, prescriptions, labs, admissions, and payments.
     */
    public function up(): void
    {
        if (Schema::hasTable('patient')) {
            Schema::table('patient', function (Blueprint $table) {
                if (Schema::hasColumn('patient', 'patient_id')) {
                    $table->index('patient_id', 'idx_patient_mrn');
                }
                if (Schema::hasColumn('patient', 'phone')) {
                    $table->index('phone', 'idx_patient_phone');
                }
                if (Schema::hasColumn('patient', 'cnic')) {
                    $table->index('cnic', 'idx_patient_cnic');
                }
                if (Schema::hasColumn('patient', 'name')) {
                    $table->index('name', 'idx_patient_name');
                }
                if (Schema::hasColumn('patient', 'patient_type_id')) {
                    $table->index('patient_type_id', 'idx_patient_type_id');
                }
            });
        }

        if (Schema::hasTable('appointment')) {
            Schema::table('appointment', function (Blueprint $table) {
                if (Schema::hasColumns('appointment', ['doctor', 'date', 's_time'])) {
                    $table->index(['doctor', 'date', 's_time'], 'idx_appointment_doc_date_stime');
                }
                if (Schema::hasColumns('appointment', ['patient', 'date'])) {
                    $table->index(['patient', 'date'], 'idx_appointment_patient_date');
                }
                if (Schema::hasColumns('appointment', ['date', 'status'])) {
                    $table->index(['date', 'status'], 'idx_appointment_date_status');
                }
            });
        }

        if (Schema::hasTable('alloted_bed')) {
            Schema::table('alloted_bed', function (Blueprint $table) {
                if (Schema::hasColumns('alloted_bed', ['bed_id', 'd_time'])) {
                    $table->index(['bed_id', 'd_time'], 'idx_alloted_bed_bed_dtime');
                }
                if (Schema::hasColumns('alloted_bed', ['patient', 'a_time'])) {
                    $table->index(['patient', 'a_time'], 'idx_alloted_bed_patient_atime');
                }
            });
        }

        if (Schema::hasTable('payment')) {
            Schema::table('payment', function (Blueprint $table) {
                if (Schema::hasColumns('payment', ['patient', 'date'])) {
                    $table->index(['patient', 'date'], 'idx_payment_patient_date');
                }
                if (Schema::hasColumns('payment', ['date', 'status'])) {
                    $table->index(['date', 'status'], 'idx_payment_date_status');
                }
                if (Schema::hasColumn('payment', 'doctor')) {
                    $table->index('doctor', 'idx_payment_doctor');
                }
            });
        }

        if (Schema::hasTable('prescription')) {
            Schema::table('prescription', function (Blueprint $table) {
                if (Schema::hasColumns('prescription', ['patient', 'date'])) {
                    $table->index(['patient', 'date'], 'idx_prescription_patient_date');
                }
                if (Schema::hasColumns('prescription', ['doctor', 'date'])) {
                    $table->index(['doctor', 'date'], 'idx_prescription_doctor_date');
                }
            });
        }

        if (Schema::hasTable('lab')) {
            Schema::table('lab', function (Blueprint $table) {
                if (Schema::hasColumns('lab', ['patient', 'date'])) {
                    $table->index(['patient', 'date'], 'idx_lab_patient_date');
                }
                if (Schema::hasColumns('lab', ['doctor', 'date'])) {
                    $table->index(['doctor', 'date'], 'idx_lab_doctor_date');
                }
                if (Schema::hasColumn('lab', 'invoice_id')) {
                    $table->index('invoice_id', 'idx_lab_invoice_id');
                }
            });
        }

        if (Schema::hasTable('medical_history')) {
            Schema::table('medical_history', function (Blueprint $table) {
                if (Schema::hasColumns('medical_history', ['patient_id', 'date'])) {
                    $table->index(['patient_id', 'date'], 'idx_medical_history_patient_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patient')) {
            Schema::table('patient', function (Blueprint $table) {
                $table->dropIndex('idx_patient_mrn');
                $table->dropIndex('idx_patient_phone');
                $table->dropIndex('idx_patient_cnic');
                $table->dropIndex('idx_patient_name');
                $table->dropIndex('idx_patient_type_id');
            });
        }

        if (Schema::hasTable('appointment')) {
            Schema::table('appointment', function (Blueprint $table) {
                $table->dropIndex('idx_appointment_doc_date_stime');
                $table->dropIndex('idx_appointment_patient_date');
                $table->dropIndex('idx_appointment_date_status');
            });
        }

        if (Schema::hasTable('alloted_bed')) {
            Schema::table('alloted_bed', function (Blueprint $table) {
                $table->dropIndex('idx_alloted_bed_bed_dtime');
                $table->dropIndex('idx_alloted_bed_patient_atime');
            });
        }

        if (Schema::hasTable('payment')) {
            Schema::table('payment', function (Blueprint $table) {
                $table->dropIndex('idx_payment_patient_date');
                $table->dropIndex('idx_payment_date_status');
                $table->dropIndex('idx_payment_doctor');
            });
        }

        if (Schema::hasTable('prescription')) {
            Schema::table('prescription', function (Blueprint $table) {
                $table->dropIndex('idx_prescription_patient_date');
                $table->dropIndex('idx_prescription_doctor_date');
            });
        }

        if (Schema::hasTable('lab')) {
            Schema::table('lab', function (Blueprint $table) {
                $table->dropIndex('idx_lab_patient_date');
                $table->dropIndex('idx_lab_doctor_date');
                $table->dropIndex('idx_lab_invoice_id');
            });
        }

        if (Schema::hasTable('medical_history')) {
            Schema::table('medical_history', function (Blueprint $table) {
                $table->dropIndex('idx_medical_history_patient_date');
            });
        }
    }
};
