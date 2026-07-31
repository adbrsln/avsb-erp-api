<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use Auditable, HasFactory;

    protected $table = 'public_holidays';

    protected $fillable = [
        'name', 'date', 'year', 'is_recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Whether this holiday falls on the given date.
     * Recurring holidays match by month + day (any year).
     */
    public function isOn(Carbon $date): bool
    {
        if ($this->is_recurring) {
            return $this->date->format('m-d') === $date->format('m-d');
        }

        return $this->date->format('Y-m-d') === $date->format('Y-m-d');
    }
}
