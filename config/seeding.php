<?php

declare(strict_types=1);

/*
 | Values the seeders need.
 |
 | Read through config rather than env() directly: a production deploy caches the config,
 | and env() returns null from then on. A seeder that reads env() after `config:cache` would
 | quietly create the first administrator with a random password nobody has — which is
 | exactly the kind of failure that is discovered at the worst moment.
 */

return [
    'demo' => filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOL),

    'admin' => [
        'name' => env('ADMIN_NAME', 'Moshiur Rahman'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
