<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ServiceType extends Model
{
    use Auditable;

    protected $table = 'service_types';

    protected $fillable = [
        'name', 'description', 'default_phase_templates', 'unit_rates',
    ];

    protected $casts = [
        'default_phase_templates' => 'array',
        'unit_rates' => 'array',
    ];
}
