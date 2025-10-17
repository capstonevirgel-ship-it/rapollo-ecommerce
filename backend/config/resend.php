<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resend API Key
    |--------------------------------------------------------------------------
    |
    | This value is your Resend API key. You can find this in your Resend
    | dashboard under API Keys.
    |
    */

    'api_key' => env('RESEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default From Address
    |--------------------------------------------------------------------------
    |
    | This value is the default email address that will be used when sending
    | emails from your application. This should be a verified domain in your
    | Resend account.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'onboarding@resend.dev'),
        'name' => env('MAIL_FROM_NAME', 'Rapollo E-commerce'),
    ],
];
