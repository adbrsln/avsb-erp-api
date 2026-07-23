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

    'storage' => [
        'driver' => env('STORAGE_DRIVER', 'local'),
        'bucket' => env('R2_BUCKET', 'avsb-uploads'),
        'r2_account_id' => env('R2_ACCOUNT_ID', ''),
        'r2_endpoint' => env('R2_ENDPOINT', ''),
        'r2_region' => env('R2_REGION', 'us-east-1'),
        'r2_access_key_id' => env('R2_ACCESS_KEY_ID', ''),
        'r2_secret_access_key' => env('R2_SECRET_ACCESS_KEY', ''),
        'r2_use_path_style' => env('R2_USE_PATH_STYLE', 'true'),
    ],

];
