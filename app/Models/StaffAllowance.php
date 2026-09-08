<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAllowance extends Model
{
    use Auditable, HasFactory;

    protected $table = 'staff_allowances';

    protected $fillable = [
        'staff_id', 'name', 'amount', 'statutory_type',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'amount' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }
}
