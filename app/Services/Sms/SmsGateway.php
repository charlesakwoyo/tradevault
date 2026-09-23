<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * Send a plain-text SMS. Implementations must throw on delivery failure.
     */
    public function send(string $to, string $message): void;
}
