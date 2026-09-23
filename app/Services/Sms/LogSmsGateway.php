<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Development driver: writes messages to the log instead of sending them.
 * Refuses to run in production so one-time codes are never logged there.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The log SMS driver cannot be used in production. Configure SMS_DRIVER.');
        }

        Log::channel(config('services.sms.log_channel', 'stack'))->info('[SMS] to '.$to.': '.$message);
    }
}
