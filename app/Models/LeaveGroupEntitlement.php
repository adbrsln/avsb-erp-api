<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class LeaveGroupEntitlement extends Model
{
    use Auditable;

    protected $table = 'leave_group_entitlements';

    protected $fillable = [
        'leave_group_id', 'type', 'label', 'days_entitled', 'sort_order',
    ];

    public function group()
    {
        return $this->belongsTo(LeaveGroup::class, 'leave_group_id');
    }
}
