<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "mock", "openai", "gemini"
    | Default is "mock" for cost-free, deterministic offline testing and local demo.
    |
    */
    'provider' => env('AI_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'mock' => [
            'model' => 'mock-educational-v1',
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 60),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Versioning & Source Fidelity Defaults
    |--------------------------------------------------------------------------
    */
    'prompt_version' => env('AI_PROMPT_VERSION', 'v1.0'),
    'allow_external_knowledge' => env('AI_ALLOW_EXTERNAL_KNOWLEDGE', false),
];
