<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Vendor extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'vendors';

    protected $fillable = [
        'vendor_code', 'company_name', 'registration_no', 'tax_id',
        'phone', 'email', 'address', 'payment_terms', 'contact_person',
        'status', 'notes',
    ];

    protected $hidden = [
        'tax_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
