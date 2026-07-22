<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

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
