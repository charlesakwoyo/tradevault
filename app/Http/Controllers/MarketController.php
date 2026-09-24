<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function index(): View
    {
        $markets = Market::query()
            ->active()
            ->with('latestPrice')
            ->orderBy('id')
            ->get();

        return view('markets.index', ['markets' => $markets]);
    }
}
