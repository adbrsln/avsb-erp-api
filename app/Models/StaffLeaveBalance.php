<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class StaffLeaveBalance extends Model
{
    use Auditable;

    protected $table = 'staff_leave_balances';

    protected $fillable = [
        'staff_id', 'type', 'year', 'entitled', 'used', 'adjusted', 'balance',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }
}
