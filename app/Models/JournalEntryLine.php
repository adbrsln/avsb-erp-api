<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    use Auditable;

    protected $table = 'journal_entry_lines';

    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit', 'description',
    ];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
