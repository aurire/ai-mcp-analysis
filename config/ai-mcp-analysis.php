<?php

/**
 * AI MCP Analysis Library Configuration
 * 
 * Configuration file for the AI MCP Analysis Library.
 * Copy this to your Laravel config directory as ai-mcp-analysis.php
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Adapter
    |--------------------------------------------------------------------------
    |
    | The default project adapter to use when none is specified.
    | Available adapters: 'hpayments', 'generic', 'custom'
    |
    */

    'default_adapter' => env('MCP_ADAPTER', 'generic'),

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for file access and analysis.
    |
    */

    'security' => [
        'allowed_paths' => [
            'app/',
            'config/',
            'routes/',
            'database/migrations/',
            'resources/views/',
            'tests/',
        ],

        'restricted_files' => [
            '.env',
            '.env.local',
            '.env.production',
            'config/database.php',
            'config/services.php',
            'storage/oauth-private.key',
            'storage/oauth-public.key',
        ],

        'dangerous_patterns' => [
            'WEB-INF',
            'web.xml',
            '../',
            '..\\',
            '/etc/',
            '/var/',
            '/usr/',
            'passwd',
            'shadow',
        ],

        // Enable developer mode to bypass path restrictions (development only)
        'developer_mode' => env('MCP_DEVELOPER_MODE', false),

        // Maximum file size to analyze (in MB)
        'max_file_size_mb' => env('MCP_MAX_FILE_SIZE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that control how analysis is performed.
    |
    */

    'analysis' => [
        // Complexity score threshold for recommendations
        'complexity_threshold' => env('MCP_COMPLEXITY_THRESHOLD', 10),

        // Confidence threshold for pattern matching
        'confidence_threshold' => env('MCP_CONFIDENCE_THRESHOLD', 0.7),

        // Enable caching of analysis results
        'enable_caching' => env('MCP_ENABLE_CACHING', true),

        // Cache TTL in minutes
        'cache_ttl' => env('MCP_CACHE_TTL', 60),

        // Maximum number of files to analyze in batch
        'batch_limit' => env('MCP_BATCH_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adapter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for specific project adapters.
    |
    */

    'adapters' => [
        'hpayments' => [
            'class' => \AuriRe\AiMcpAnalysis\Adapters\HPaymentsAdapter::class,
            'config' => [
                'complexity_threshold' => 15, // Higher threshold for payment systems
                'confidence_threshold' => 0.8,
                'enable_security_analysis' => true,
                'pci_compliance_checks' => true,
            ],
        ],

        'generic' => [
            'class' => \AuriRe\AiMcpAnalysis\Adapters\GenericLaravelAdapter::class,
            'config' => [
                'complexity_threshold' => 10,
                'confidence_threshold' => 0.7,
                'check_laravel_conventions' => true,
            ],
        ],

        // Example custom adapter configuration
        'ecommerce' => [
            'class' => \AuriRe\AiMcpAnalysis\Adapters\ECommerceAdapter::class,
            'config' => [
                'complexity_threshold' => 12,
                'confidence_threshold' => 0.75,
                'check_product_patterns' => true,
                'check_order_patterns' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Control how the library logs its operations.
    |
    */

    'logging' => [
        // Enable detailed logging
        'enabled' => env('MCP_LOGGING_ENABLED', true),

        // Log level (debug, info, warning, error)
        'level' => env('MCP_LOG_LEVEL', 'info'),

        // Log channel to use
        'channel' => env('MCP_LOG_CHANNEL', 'default'),

        // Log security events
        'log_security_events' => env('MCP_LOG_SECURITY', true),

        // Log performance metrics
        'log_performance' => env('MCP_LOG_PERFORMANCE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for external tool integrations.
    |
    */

    'integrations' => [
        'phpstan' => [
            'enabled' => env('MCP_PHPSTAN_ENABLED', false),
            'config_path' => env('MCP_PHPSTAN_CONFIG', 'phpstan.neon'),
            'level' => env('MCP_PHPSTAN_LEVEL', 5),
        ],

        'slack' => [
            'enabled' => env('MCP_SLACK_ENABLED', false),
            'webhook_url' => env('MCP_SLACK_WEBHOOK_URL'),
            'channel' => env('MCP_SLACK_CHANNEL', '#dev'),
            'notify_on_errors' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management
    |--------------------------------------------------------------------------
    |
    | Settings for knowledge base integration (if available).
    |
    */

    'knowledge' => [
        // Enable knowledge base integration
        'enabled' => env('MCP_KNOWLEDGE_ENABLED', true),

        // Entity confidence threshold
        'entity_confidence_threshold' => 0.8,

        // Relationship strength threshold
        'relationship_strength_threshold' => 0.5,

        // Auto-create entities from analysis
        'auto_create_entities' => env('MCP_AUTO_CREATE_ENTITIES', true),

        // Auto-create relationships
        'auto_create_relationships' => env('MCP_AUTO_CREATE_RELATIONSHIPS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance for large projects.
    |
    */

    'performance' => [
        // Enable parallel processing
        'parallel_processing' => env('MCP_PARALLEL_PROCESSING', false),

        // Maximum number of parallel processes
        'max_processes' => env('MCP_MAX_PROCESSES', 4),

        // Memory limit for analysis (in MB)
        'memory_limit_mb' => env('MCP_MEMORY_LIMIT', 512),

        // Timeout for individual file analysis (in seconds)
        'analysis_timeout' => env('MCP_ANALYSIS_TIMEOUT', 30),
    ],

];

