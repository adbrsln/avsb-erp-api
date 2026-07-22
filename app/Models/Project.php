<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'name', 'project_code', 'po_number', 'client', 'location', 'status', 'contract_id',
        'client_id', 'client_pic_id',
        'budget_amount', 'project_manager_id',
        'start_date', 'end_date', 'service_type_id', 'description',
        'latitude', 'longitude',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_amount' => 'float',
    ];

    public function phases()
    {
        return $this->hasMany(Phase::class);
    }

    public function projectManager()
    {
        return $this->belongsTo(StaffProfile::class, 'project_manager_id');
    }

    public function clientRelation()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function clientPic()
    {
        return $this->belongsTo(ClientPIC::class, 'client_pic_id');
    }

    public function staffPics()
    {
        return $this->belongsToMany(StaffProfile::class, 'project_staff_pics', 'project_id', 'staff_id');
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class, 'project_id');
    }

    public function claims()
    {
        return $this->hasMany(ProjectClaim::class, 'project_id');
    }

    public function projectTypes()
    {
        return $this->belongsToMany(ProjectType::class, 'project_project_type');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'project_id');
    }

    public function groups()
    {
        return $this->belongsToMany(ProjectGroup::class, 'project_project_group');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'project_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'project_id');
    }
}
