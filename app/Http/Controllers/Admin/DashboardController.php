<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminDashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'stats' => $dashboard->stats(),
            'charts' => $dashboard->charts(),
            'recentActivity' => $request->user()->can('audit-logs.view')
                ? AuditLog::query()->with('user')->latest('created_at')->limit(8)->get()
                : collect(),
        ]);
    }
}
