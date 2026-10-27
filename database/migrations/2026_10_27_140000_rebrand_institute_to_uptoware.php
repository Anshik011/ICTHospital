<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('institute')) {
            $count = DB::table('institute')->count();
            if ($count > 0) {
                DB::table('institute')
                    ->whereIn('name', ['ICTHospital', 'IctVission', 'ictvission', 'Laravel'])
                    ->orWhereNull('name')
                    ->orWhere('name', '')
                    ->update([
                        'name' => 'Uptoware Clinic',
                        'web' => 'https://uptoware.com',
                        'email' => 'contact@uptoware.com',
                    ]);
            } else {
                DB::table('institute')->insert([
                    'name' => 'Uptoware Clinic',
                    'establish' => date('Y'),
                    'email' => 'contact@uptoware.com',
                    'web' => 'https://uptoware.com',
                    'phoneNo' => '',
                    'address' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
