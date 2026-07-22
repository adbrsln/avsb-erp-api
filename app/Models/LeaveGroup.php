<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveGroup extends Model
{
    use Auditable, HasFactory;

    protected $table = 'leave_groups';

    protected $fillable = [
        'name', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function entitlements()
    {
        return $this->hasMany(LeaveGroupEntitlement::class, 'leave_group_id');
    }
}
