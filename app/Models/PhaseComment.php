<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PhaseComment extends Model
{
    use Auditable;

    protected $table = 'phase_comments';

    protected $fillable = [
        'phase_id', 'staff_id', 'body',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }
}
