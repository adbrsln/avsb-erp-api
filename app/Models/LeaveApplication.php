<?php

namespace App\Models;

use App\Services\HolidayService;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'leave_applications';

    protected $fillable = [
        'leave_ref', 'staff_id', 'type', 'start_date', 'end_date',
        'is_half_day', 'reason', 'rejection_reason', 'status',
        'approver_id', 'approved_at', 'mc_document_path',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_half_day' => 'boolean',
    ];

    protected $appends = ['days'];

    private const HALF_DAY_ALLOWED_TYPES = ['annual', 'medical', 'unpaid'];

    public static function halfDayAllowed(string $type): bool
    {
        return in_array($type, self::HALF_DAY_ALLOWED_TYPES);
    }

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function approver()
    {
        return $this->belongsTo(StaffProfile::class, 'approver_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(StaffProfile::class, 'cancelled_by');
    }

    public function getDaysAttribute(): float
    {
        if ($this->is_half_day) {
            return 0.5;
        }

        return self::workingDaysCount($this->start_date, $this->end_date);
    }

    public static function workingDaysCount(Carbon $start, Carbon $end, bool $isHalfDay = false): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $holidays = HolidayService::holidaysBetween($start, $end);

        $count = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if (! $current->isWeekend() && ! isset($holidays[$current->format('Y-m-d')])) {
                $count++;
            }
            $current->addDay();
        }

        return max(1, $count);
    }

    public function scopeOverlapping($query, int $staffId, string $startDate, string $endDate, ?int $excludeId = null): void
    {
        $query->where('staff_id', $staffId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
    }
}
