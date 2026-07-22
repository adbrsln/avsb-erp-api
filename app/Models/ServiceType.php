<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use Auditable, HasFactory;

    protected $table = 'service_types';

    protected $fillable = [
        'name', 'description', 'default_phase_templates', 'unit_rates',
    ];

    protected $casts = [
        'default_phase_templates' => 'array',
        'unit_rates' => 'array',
    ];
}
