<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalPeriod extends Model
{
    use Auditable, HasFactory;

    protected $table = 'fiscal_periods';

    protected $fillable = [
        'name', 'start_date', 'end_date', 'type', 'status',
        'closed_at', 'closed_by', 'opening_balance_entry_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function openingBalanceEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'opening_balance_entry_id');
    }
}
