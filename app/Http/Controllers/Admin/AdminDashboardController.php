<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Optimize user statistics by aggregating in a single query
        // This reduces 4 separate queries to 1
        $userStats = User::where('role', 'leader')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'active' then 1 end) as active_count")
            ->selectRaw("count(case when status = 'suspended' then 1 end) as suspended_count")
            ->selectRaw("count(case when status = 'terminated' then 1 end) as terminated_count")
            ->first();

        // Optimize login log statistics by aggregating in a single query
        // This reduces 2 separate queries to 1
        // Note: aliases must not match model attributes (e.g. 'success') to avoid casting
        $logStats = LoginLog::selectRaw('count(case when success = true then 1 end) as success_count')
            ->selectRaw('count(case when success = false then 1 end) as failed_count')
            ->first();

        $stats = [
            'total_users' => $userStats->total ?? 0,
            'active_users' => $userStats->active_count ?? 0,
            'suspended_users' => $userStats->suspended_count ?? 0,
            'terminated_users' => $userStats->terminated_count ?? 0,
            'recent_logins' => $logStats->success_count ?? 0,
            'failed_logins' => $logStats->failed_count ?? 0,
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
