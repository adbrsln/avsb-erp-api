<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPIC extends Model
{
    use Auditable, HasFactory;
    use SoftDeletes;

    protected $table = 'client_pics';

    protected $fillable = [
        'client_id', 'name', 'phone', 'email',
        'job_title', 'department', 'notes', 'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
