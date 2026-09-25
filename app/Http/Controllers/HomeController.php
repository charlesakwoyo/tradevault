<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Public landing page with the live price board.
     */
    public function __invoke(): View
    {
        $markets = Market::query()
            ->active()
            ->with('latestPrice')
            ->orderBy('id')
            ->get();

        return view('welcome', [
            'markets' => $markets,
            'lastUpdated' => $markets->pluck('latestPrice.recorded_at')->filter()->max(),
        ]);
    }
}
