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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

    'nvidia' => [
        'key' => env('NVIDIA_NIM_API_KEY'),
        'base_url' => env('NVIDIA_NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        // Main model for analysis & chat — must support tool/function calling
        'model' => env('NVIDIA_NIM_MODEL', 'meta/llama-3.1-8b-instruct'),
        // Cheaper/faster model for lightweight tasks (project summaries, tagging)
        'model_light' => env('NVIDIA_NIM_MODEL_LIGHT', 'meta/llama-3.1-8b-instruct'),
        'timeout' => env('NVIDIA_NIM_TIMEOUT', 15),
        'max_tokens' => env('NVIDIA_NIM_MAX_TOKENS', 1024),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'mistral' => [
        'key' => env('MISTRAL_API_KEY'),
    ],

    'zai' => [
        'key' => env('Z_AI_API_KEY', env('ZAI_API_KEY')),
    ],

];
