<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcontractor extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'subcontractors';

    protected $fillable = [
        'subcontractor_code', 'company_name', 'registration_no', 'tax_id', 'sst_reg_no',
        'phone', 'email', 'address',
        'status', 'notes',
        'cidb_reg_no', 'cidb_grade', 'cidb_expiry',
        'licenses', 'insurances',
    ];

    protected $hidden = [
        'tax_id',
        'registration_no',
        'sst_reg_no',
    ];

    protected $casts = [
        'cidb_expiry' => 'date',
        'licenses' => 'array',
        'insurances' => 'array',
    ];

    public function projectAssignments()
    {
        return $this->hasMany(ProjectSubcontractor::class);
    }

    public function claims()
    {
        return $this->hasManyThrough(SubcontractorClaim::class, ProjectSubcontractor::class);
    }

    public function pics()
    {
        return $this->hasMany(SubcontractorPIC::class, 'subcontractor_id');
    }

    public function primaryPic()
    {
        return $this->hasOne(SubcontractorPIC::class, 'subcontractor_id')->where('is_primary', true);
    }
}
