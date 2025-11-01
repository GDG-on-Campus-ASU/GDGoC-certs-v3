<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;

class AdminLoginLogController extends Controller
{
    /**
     * Display a listing of login logs.
     */
    public function index()
    {
        $logs = LoginLog::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.logs.index', compact('logs'));
    }

    /**
     * Generate RSS feed of login logs.
     */
    public function feed()
    {
        $logs = LoginLog::orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->view('admin.logs.feed', compact('logs'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
