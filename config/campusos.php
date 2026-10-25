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
        // Seconds a read is reused for lookups and Livewire round trips (0 = off). See CampusApi::get().
        'read_cache_seconds' => (int) env('CAMPUSOS_API_READ_CACHE_SECONDS', 20),
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

    /*
    | Folder with the Lucide SVG icons (the lucide-static npm package, all ~2,100 icons).
    | Use any name from https://lucide.dev/icons. Deploys must run `npm ci` so it exists.
    */
    'icons_path' => env('CAMPUSOS_ICONS_PATH', base_path('node_modules/lucide-static/icons')),

    'support_email' => env('CAMPUSOS_SUPPORT_EMAIL'),

];
