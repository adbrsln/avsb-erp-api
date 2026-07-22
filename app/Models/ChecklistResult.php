<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistResult extends Model
{
    use Auditable, HasFactory;

    protected $table = 'checklist_results';

    protected $fillable = [
        'phase_id', 'checklist_item_id', 'passed',
        'remarks', 'checked_by', 'checked_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'checked_at' => 'datetime',
    ];
}
