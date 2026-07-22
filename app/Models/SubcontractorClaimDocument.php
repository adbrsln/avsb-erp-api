<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SubcontractorClaimDocument extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'subcontractor_claim_documents';

    protected $fillable = [
        'subcontractor_claim_id', 'uploaded_by',
        'original_filename', 'stored_filename', 'file_path',
        'mime_type', 'file_size', 'notes',
    ];

    protected $hidden = ['file_path'];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function claim()
    {
        return $this->belongsTo(SubcontractorClaim::class, 'subcontractor_claim_id');
    }

    public function uploader()
    {
        return $this->belongsTo(StaffProfile::class, 'uploaded_by');
    }
}
