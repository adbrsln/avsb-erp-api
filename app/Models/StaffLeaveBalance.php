<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

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
