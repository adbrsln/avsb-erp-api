<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ChartOfAccount extends Model
{
    use Auditable;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code', 'name', 'type', 'category', 'is_active', 'is_system', 'description',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_system' => 'bool',
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
