<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Mercado Pago (Checkout Pro). Las credenciales se leen sólo del archivo
    | .env y nunca se envían al navegador.
    */
    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        // URL pública (https) de la app. Si está vacía no se envían back_urls ni notification_url.
        'url_publica' => rtrim((string) env('MERCADOPAGO_URL_PUBLICA', ''), '/'),
        // auto | true | false: qué URL de checkout usar (sandbox_init_point o init_point).
        'sandbox' => env('MERCADOPAGO_SANDBOX', 'auto'),
        'reserva_minutos' => (int) env('RESERVA_MINUTOS', 15),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
