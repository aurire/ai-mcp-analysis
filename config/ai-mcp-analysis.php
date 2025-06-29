<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI MCP Analysis Library Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for AiMcpAnalysis package.
    |
    */

    'enabled' => env('AI_MCP_ANALYSIS_ENABLED', true),

    // Project Detection
    'auto_detect_project' => env('AI_MCP_AUTO_DETECT', true),
    'default_project_adapter' => env('AI_MCP_DEFAULT_ADAPTER', 'generic_laravel'),

    // Security Settings
    'allowed_paths' => [
        'app/',
        'config/',
        'routes/',
        'database/migrations/',
        'resources/views/',
        'tests/',
    ],

    // External Integrations
    'integrations' => [
        'slack' => [
            'enabled' => env('AI_MCP_SLACK_ENABLED', false),
            'webhook_url' => env('AI_MCP_SLACK_WEBHOOK'),
            'channel' => env('AI_MCP_SLACK_CHANNEL', '#dev'),
        ],
        'loki' => [
            'enabled' => env('AI_MCP_LOKI_ENABLED', false),
            'endpoint' => env('AI_MCP_LOKI_ENDPOINT'),
            'username' => env('AI_MCP_LOKI_USERNAME'),
            'password' => env('AI_MCP_LOKI_PASSWORD'),
        ],
        'jira' => [
            'enabled' => env('AI_MCP_JIRA_ENABLED', false),
            'domain' => env('AI_MCP_JIRA_DOMAIN'),
            'username' => env('AI_MCP_JIRA_USERNAME'),
            'api_token' => env('AI_MCP_JIRA_API_TOKEN'),
        ],
    ],

    // Analysis Settings
    'analysis' => [
        'cache_results' => env('AI_MCP_CACHE_RESULTS', true),
        'cache_ttl' => env('AI_MCP_CACHE_TTL', 3600),
        'max_file_size' => env('AI_MCP_MAX_FILE_SIZE', 1048576), // 1MB
    ],
];
