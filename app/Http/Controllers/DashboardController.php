<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\UserDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, UserDashboardService $dashboard): View
    {
        return view('dashboard', [
            'summary' => $dashboard->summary($request->user()),
            'charts' => $dashboard->charts($request->user()),
        ]);
    }
}
