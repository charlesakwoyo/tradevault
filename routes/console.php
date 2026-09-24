<?php

use App\Models\MarketPrice;
use Illuminate\Support\Facades\Schedule;

Schedule::command('markets:refresh-prices')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('model:prune', ['--model' => [MarketPrice::class]])->daily();
Schedule::command('ledger:verify')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
Schedule::command('queue:prune-failed --hours=168')->daily();
