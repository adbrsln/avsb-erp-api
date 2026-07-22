<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use Auditable, HasFactory, SoftDeletes;

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
