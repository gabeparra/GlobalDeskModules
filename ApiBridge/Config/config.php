<?php

return [
    'name' => 'ApiBridge',
    'api_key_salt' => env('APIBRIDGE_API_KEY_SALT', ''),
    'cors_hosts' => env('APIBRIDGE_CORS_HOSTS', ''),
    'webhook_timeout' => env('APIBRIDGE_WEBHOOK_TIMEOUT', 30),
    'webhook_queue' => env('APIBRIDGE_WEBHOOK_QUEUE', 'default'),
];



