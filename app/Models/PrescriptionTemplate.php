<?php
/**
 * ICTHospital - a reusable prescription template.
 *
 * Copyright (c) ICT Innovations <https://www.ictinnovations.com>
 * Part of ICTHospital <https://www.icthospital.com>
 * Licensed under the GNU General Public License v3.0.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionTemplate extends Model
{
    use HasFactory;

    protected $table = 'prescription_templates';

    protected $fillable = [
        'name',
        'doctor_id',
        'symptom',
        'advice',
        'note',
        'validity',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
