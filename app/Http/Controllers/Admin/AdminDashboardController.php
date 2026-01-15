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
        // Optimization: Aggregate user stats in a single query to reduce database round-trips
        $userStats = User::where('role', 'leader')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'active' then 1 end) as active")
            ->selectRaw("count(case when status = 'suspended' then 1 end) as suspended")
            ->selectRaw("count(case when status = 'terminated' then 1 end) as terminated")
            ->first();

        // Optimization: Aggregate login stats in a single query
        $loginStats = LoginLog::selectRaw('count(case when success = true then 1 end) as successful')
            ->selectRaw('count(case when success = false then 1 end) as failed')
            ->first();

        $stats = [
            'total_users' => $userStats->total ?? 0,
            'active_users' => $userStats->active ?? 0,
            'suspended_users' => $userStats->suspended ?? 0,
            'terminated_users' => $userStats->terminated ?? 0,
            'recent_logins' => $loginStats->successful ?? 0,
            'failed_logins' => $loginStats->failed ?? 0,
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
