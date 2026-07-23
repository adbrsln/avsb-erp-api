<?php

return [

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
    ],

    'cloudflare' => [
        'proxy_secret' => env('PROXY_SECRET', ''),
    ],

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@azamventures.com'),
        'public_key' => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

];
