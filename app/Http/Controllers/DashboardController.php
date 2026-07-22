<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $activeProjects = Project::where('status', 'active')->count();
        $totalProjects = Project::count();
        $completedProjects = Project::where('status', 'completed')->count();
        $completionRate = $totalProjects > 0
            ? round(($completedProjects / $totalProjects) * 100).'%'
            : '0%';

        $tasksDue = Task::whereNotIn('status', ['completed', 'cancelled'])->count();
        $teamMembers = StaffProfile::where('is_active', true)->count();

        $taskStatus = [
            'todo' => Task::where('status', 'todo')->count(),
            'inProgress' => Task::whereIn('status', ['running', 'paused'])->count(),
            'revisions' => 0,
            'completed' => Task::where('status', 'completed')->count(),
        ];

        $totalInvoiced = Invoice::where('status', 'paid')->sum('total');
        $outstanding = Invoice::where('status', 'unpaid')->sum('total');
        $staffOnLeaveToday = LeaveApplication::where('status', 'approved')
            ->where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'))
            ->count();

        $pendingLeaves = LeaveApplication::where('status', 'pending')->count();
        $pendingClaims = ExpenseClaim::where('status', 'pending')->count();

        $allProjects = Project::select(['id', 'status', 'end_date'])->get();
        $healthCounts = ['success' => 0, 'warning' => 0, 'error' => 0];
        foreach ($allProjects as $p) {
            $health = 'success';
            if ($p->status === 'paused' || $p->status === 'cancelled') {
                $health = 'error';
            } elseif ($p->status === 'draft' || ($p->end_date && Carbon::now()->gt($p->end_date) && $p->status !== 'completed')) {
                $health = 'warning';
            }
            $healthCounts[$health]++;
        }

        $today = date('Y-m-d');
        $todayRecords = Attendance::whereDate('date', $today)->get();
        $presentToday = $todayRecords->whereNotNull('clock_out')->count();
        $activeNow = $todayRecords->whereNull('clock_out')->count();
        $staffWithRecord = $todayRecords->pluck('staff_id')->toArray();
        $absentToday = StaffProfile::where('is_active', true)
            ->whereNotIn('id', $staffWithRecord)
            ->count();
        $availableToday = $teamMembers - $absentToday - $staffOnLeaveToday;

        $recentProjects = Project::with('projectTypes')
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'project_code' => $p->project_code,
                'updated_at' => $p->updated_at,
                'project_types' => $p->projectTypes->map(fn ($t) => ['name' => $t->name, 'color' => $t->color])->values(),
            ]);

        return response()->json(compact(
            'activeProjects', 'completionRate', 'tasksDue',
            'teamMembers', 'taskStatus',
            'totalInvoiced', 'outstanding', 'pendingLeaves', 'pendingClaims', 'staffOnLeaveToday',
            'healthCounts', 'presentToday', 'activeNow', 'absentToday', 'availableToday',
            'recentProjects',
        ));
    }
}
