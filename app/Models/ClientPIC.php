<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ClientPIC extends Model
{
    use Auditable;

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
