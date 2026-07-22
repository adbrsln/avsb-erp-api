<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class JournalEntry extends Model
{
    use Auditable;

    protected $table = 'journal_entries';

    protected $fillable = [
        'entry_number', 'entry_date', 'description',
        'reference_type', 'reference_id', 'status',
        'created_by', 'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
