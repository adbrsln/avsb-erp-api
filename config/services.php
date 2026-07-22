<?php

return [

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
    ],

    'cloudflare' => [
        'proxy_secret' => env('PROXY_SECRET', ''),
    ],

];
