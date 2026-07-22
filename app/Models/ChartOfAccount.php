<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

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
