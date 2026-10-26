<?php
/**
 * ICTHospital - reusable prescription templates for doctors.
 *
 * Doctors write recurring sets of medicines for common clinical presentations
 * (e.g. fever/viral illness, acute bronchitis, post-op analgesia, hypertension).
 * Storing these as templates allows 1-click loading on the prescription form
 * without re-typing each drug line from scratch.
 *
 * Copyright (c) ICT Innovations <https://www.ictinnovations.com>
 * Part of ICTHospital <https://www.icthospital.com>
 * Licensed under the GNU General Public License v3.0.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prescription_templates')) {
            Schema::create('prescription_templates', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120)->index();
                $table->unsignedInteger('doctor_id')->nullable()->index();
                $table->string('symptom', 150)->nullable();
                $table->text('advice')->nullable();
                $table->text('note')->nullable();
                $table->string('validity', 100)->nullable();
                $table->json('items')->nullable();
                $table->timestamps();
            });
        }

        // Seed initial common hospital prescription templates if empty
        if (DB::table('prescription_templates')->count() === 0) {
            DB::table('prescription_templates')->insert([
                [
                    'name' => 'Fever & Pain Relief (Adult)',
                    'doctor_id' => null,
                    'symptom' => 'Fever, generalized body aches, headache',
                    'advice' => 'Drink plenty of water and oral fluids. Adequate rest. Return if fever persists beyond 3 days or exceeds 39°C.',
                    'note' => 'Take medication after food.',
                    'validity' => '7 days',
                    'items' => json_encode([
                        ['name' => 'Paracetamol 500mg', 'dosage' => '1+1+1', 'duration' => '5 days', 'instructions' => 'After meals'],
                        ['name' => 'Ibuprofen 400mg', 'dosage' => '1+0+1', 'duration' => '3 days', 'instructions' => 'As needed for severe pain after food'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Upper Respiratory Infection (URTI)',
                    'doctor_id' => null,
                    'symptom' => 'Cough, sore throat, nasal congestion',
                    'advice' => 'Warm saline gargles twice daily. Steam inhalation. Avoid cold beverages.',
                    'note' => 'Review in clinic if shortness of breath or persistent chest pain develops.',
                    'validity' => '10 days',
                    'items' => json_encode([
                        ['name' => 'Amoxil (Amoxicillin 500mg)', 'dosage' => '1+0+1', 'duration' => '5 days', 'instructions' => 'Complete the full course'],
                        ['name' => 'Paracetamol 500mg', 'dosage' => '1+1+1', 'duration' => '5 days', 'instructions' => 'For fever or body ache'],
                        ['name' => 'Antihistamine / Cough Syrup', 'dosage' => '0+0+1', 'duration' => '5 days', 'instructions' => 'At bedtime'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Acute Gastroenteritis (Adult)',
                    'doctor_id' => null,
                    'symptom' => 'Watery diarrhoea, mild abdominal cramps, nausea',
                    'advice' => 'ORS (oral rehydration salts) after every loose bowel movement. Light diet (bananas, rice, soup). Avoid dairy and fatty meals.',
                    'note' => 'Immediate emergency review if signs of severe dehydration, blood in stool, or inability to retain fluids.',
                    'validity' => '5 days',
                    'items' => json_encode([
                        ['name' => 'ORS Oral Rehydration Salts', 'dosage' => '1 sachet', 'duration' => '3 days', 'instructions' => 'Dissolve in 1 litre boiled/cooled water; sip frequently'],
                        ['name' => 'Zinc Sulfate 20mg', 'dosage' => '1+0+0', 'duration' => '10 days', 'instructions' => 'Once daily after breakfast'],
                        ['name' => 'Probiotic Capsule', 'dosage' => '1+0+1', 'duration' => '5 days', 'instructions' => 'Before meals'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_templates');
    }
};
