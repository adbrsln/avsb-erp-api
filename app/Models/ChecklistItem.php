<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ChecklistItem extends Model
{
    use Auditable;
    protected $table = 'checklist_items';

    protected $fillable = [
        'phase_id', 'name', 'description', 'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}
