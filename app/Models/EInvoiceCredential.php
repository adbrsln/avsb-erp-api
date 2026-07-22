<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EInvoiceCredential extends Model
{
    use Auditable;

    protected $table = 'einvoice_credentials';

    protected $fillable = [
        'label', 'client_id', 'client_secret', 'environment',
        'is_active',
        'access_token', 'token_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = ['client_secret', 'access_token'];
}
