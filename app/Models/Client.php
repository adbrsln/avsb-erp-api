<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'client_code', 'company_name', 'registration_no', 'phone', 'email',
        'address', 'billing_address', 'tax_id', 'notes',
        'sst_reg_no', 'buyer_type', 'contact_phone',
    ];

    public function pics()
    {
        return $this->hasMany(ClientPIC::class, 'client_id');
    }

    public function primaryPic()
    {
        return $this->hasOne(ClientPIC::class, 'client_id')->where('is_primary', true);
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
