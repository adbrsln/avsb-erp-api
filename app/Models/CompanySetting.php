<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use Auditable;

    protected $table = 'company_settings';

    protected $fillable = [
        'company_name', 'address', 'reg_no',
        'epf_no', 'socso_no', 'eis_no',
        'sst_registration_no', 'tax_id_number', 'msic_code',
        'msic_description', 'business_phone', 'business_email',
        'socso_24h_phase', 'logo_path',
    ];
}
