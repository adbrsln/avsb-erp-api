<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class UserRole extends Model
{
    use Auditable;
    protected $table = 'user_roles';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
