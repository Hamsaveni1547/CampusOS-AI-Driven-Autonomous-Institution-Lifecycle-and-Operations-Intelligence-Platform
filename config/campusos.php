<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-factor authentication
    |--------------------------------------------------------------------------
    */
    'mfa' => [
        'enabled' => env('CAMPUSOS_MFA_ENABLED', true),

        // Roles that must always use MFA (values of App\Enums\RoleName).
        'required_roles' => [
            'institutional_admin',
            'finance_officer',
            'examiner',
        ],

        'otp' => [
            'length' => 6,
            'ttl_minutes' => 10,
            'max_attempts' => 5,
            'resend_cooldown_seconds' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    | Only these roles may be obtained through public self-registration.
    */
    'registration' => [
        'self_register_roles' => ['applicant'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password policy
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Initial administrator (used only by AdminSeeder)
    |--------------------------------------------------------------------------
    | Set CAMPUSOS_ADMIN_PASSWORD in .env. If empty, a random password is
    | generated and printed once in the console.
    */
    // 'admin' => [
    //     'name' => env('CAMPUSOS_ADMIN_NAME', 'Institution Admin'),
    //     'email' => env('CAMPUSOS_ADMIN_EMAIL', 'admin@campusos.test'),
    //     'username' => env('CAMPUSOS_ADMIN_USERNAME', 'admin'),
    //     'password' => env('CAMPUSOS_ADMIN_PASSWORD'),
    // ],

];
