<?php

return [

    /*
     | Force back-office users to enable 2FA before accessing /admin.
     | Defaults to on in production.
     */
    'require_staff_2fa' => env('SECURITY_REQUIRE_STAFF_2FA', env('APP_ENV') === 'production'),

];
