<?php

return [

    /*
    | Backend API connection (campusos-backend). URL and timeout also live in
    | config/services.php under "campusos_api" for backwards compatibility.
    */
    'api' => [
        'url' => rtrim((string) env('CAMPUSOS_API_URL', 'http://localhost:8000/api/v1'), '/'),
        'timeout' => (int) env('CAMPUSOS_API_TIMEOUT', 15),
        'connect_timeout' => (int) env('CAMPUSOS_API_CONNECT_TIMEOUT', 5),
    ],

    /*
    | How often the signed-in user's profile/permissions (/me) are refreshed.
    */
    'me_refresh_seconds' => 300,

    /*
    | Long-lived cookie that remembers a verified device (skips two-step verification).
    */
    'trusted_device_cookie' => 'campus_trusted_device',
    'trusted_device_days' => 30,

    /*
    | The local component style guide at /_ui. Never enabled in production.
    */
    'style_guide' => (bool) env('CAMPUSOS_STYLE_GUIDE', env('APP_ENV') === 'local'),

    'support_email' => env('CAMPUSOS_SUPPORT_EMAIL'),

];
