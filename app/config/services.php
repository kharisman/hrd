<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    // API Akademik PalComTech (eUniversity) untuk login pegawai/dosen/staf
    'pct' => [
        'url' => env('PCT_API_URL', 'https://beta1.palcomtech.org/api/v1'),
        'key' => env('PCT_API_KEY'),
        // role API -> peran HRD (urutan = prioritas)
        'peran' => [
            'admin' => ['superadmin', 'admin'],
            'dosen' => ['kaprodi', 'dosen'],
            'staff' => ['staff', 'baak'],
        ],
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],


    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_drive' => [
        'client_id'     => env('GDRIVE_CLIENT_ID', ''),
        'client_secret' => env('GDRIVE_CLIENT_SECRET', ''),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URL', 'http://localhost'),
        'refresh_token' => env('GDRIVE_REFRESH_TOKEN', ''),
        'folder_id'     => env('GDRIVE_FOLDER_ID', ''),
    ],


    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-5.2'),
        'site_url' => env('OPENROUTER_SITE_URL'),
        'site_name' => env('OPENROUTER_SITE_NAME'),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'base_url' => 'https://api.deepseek.com',
    ],

];
