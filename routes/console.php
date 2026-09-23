<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ledger:verify')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
Schedule::command('queue:prune-failed --hours=168')->daily();
