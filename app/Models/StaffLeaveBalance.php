<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLeaveBalance extends Model
{
    use Auditable, HasFactory;

    protected $table = 'staff_leave_balances';

    protected $fillable = [
        'staff_id', 'type', 'year', 'entitled', 'used', 'adjusted', 'balance',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }
}
