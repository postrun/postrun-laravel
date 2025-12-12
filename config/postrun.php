<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PostRun API Key
    |--------------------------------------------------------------------------
    |
    | Your PostRun domain API key. This key is specific to a domain and
    | authenticates your application to send emails through that domain.
    |
    */
    'api_key' => env('POSTRUN_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | PostRun API Endpoint
    |--------------------------------------------------------------------------
    |
    | The base URL of your PostRun instance. For self-hosted installations,
    | set this to your PostRun server URL.
    |
    */
    'endpoint' => env('POSTRUN_ENDPOINT', 'https://postrun.io'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling for receiving email event notifications
    | from PostRun. The secret should match the one configured in your
    | PostRun webhook endpoint settings.
    |
    */
    'webhook' => [
        // The webhook signing secret from your PostRun webhook endpoint
        'secret' => env('POSTRUN_WEBHOOK_SECRET'),

        // The path where the webhook controller will be registered
        'path' => '/postrun/webhooks',

        // Middleware to apply to the webhook route
        'middleware' => ['api'],

        // Signature timestamp tolerance in seconds (prevents replay attacks)
        'tolerance' => 300,
    ],
];
