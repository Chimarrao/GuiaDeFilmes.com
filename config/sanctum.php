<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains that access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:8000,localhost:3000,127.0.0.1,127.0.0.1:8000,127.0.0.1:3000',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will
    | be considered expired. If this value is null, personal access tokens
    | do not expire. This won't affect the lifetime of first-party sessions.
    |
    */

    'expiration' => env('SANCTUM_EXPIRATION'),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may customize this middleware below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'add_cookies_to_response' => Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        'create_fresh_api_token' => Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ],

];