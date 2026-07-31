<?php

return [
    'login_enabled' => filter_var(env('ADMIN_LOGIN_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'panel_allowed_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAIL', ''))
    ))),

    'development_user' => [
        'name' => env('ADMIN_NAME', 'Development Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@example.test'),
        'password' => env('ADMIN_PASSWORD', 'change-this-password'),
    ],
];
