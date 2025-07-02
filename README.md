# AI MCP Analysis Library

A powerful, extensible framework for AI-powered architectural analysis using MCP (Model Context Protocol). This library provides secure, domain-aware analysis of codebases with pluggable adapters for different project types.

## 🎯 Features

- **Security-First Design**: Battle-tested path validation and content sanitization
- **Domain Expertise**: Pluggable adapters for different project types (Payments, E-commerce, Generic Laravel)
- **MCP Integration**: Native support for MCP tools with parameter normalization
- **Knowledge Management**: Entity and relationship tracking for progressive analysis
- **Extensible Architecture**: Easy to add new project types and analysis patterns

## 🚀 Quick Start

### Installation

```bash
# Install the library (when published)
composer require aurire/ai-mcp-analysis

# Or for development, symlink to your project
ln -s /path/to/ai-mcp-analysis app/McpLib/ai-mcp-analysis
```

### Basic Usage

```php
use AuriRe\AiMcpAnalysis\Tools\ProjectAnalyzer;
use AuriRe\AiMcpAnalysis\Adapters\HPaymentsAdapter;

// Create analyzer with payment domain expertise
$adapter = new HPaymentsAdapter();
$analyzer = new ProjectAnalyzer($adapter);

// Analyze a single file
$analysis = $analyzer->analyzeFile('app/Service/PaymentGateway/StripeGateway.php');

// Get project summary
$summary = $analyzer->generateSummary();

// Get recommendations
$recommendations = $analyzer->getRecommendations($analysis);
```

### Configuration

Copy the configuration file to your Laravel project:

```bash
cp app/McpLib/ai-mcp-analysis/config/ai-mcp-analysis.php config/
```

Set environment variables:

```env
MCP_ADAPTER=hpayments
MCP_DEVELOPER_MODE=false
MCP_COMPLEXITY_THRESHOLD=10
MCP_CONFIDENCE_THRESHOLD=0.7
```

## 🏗️ Architecture

### Core Components

1. **SecurityValidation**: Path validation and content sanitization
2. **ParameterNormalizer**: MCP parameter handling and type conversion
3. **StructureAnalyzer**: PHP file analysis engine
4. **AbstractProjectAdapter**: Base class for domain-specific adapters

### Available Adapters

- **HPaymentsAdapter**: Payment processing domain expertise
  - 20+ payment gateway patterns
  - PCI compliance checks
  - Webhook security analysis
  - Fraud detection patterns

- **GenericLaravelAdapter**: Standard Laravel project analysis
  - MVC pattern detection
  - Laravel convention checks
  - Best practice recommendations

## 🔒 Security Features

### Path Validation
- Whitelist-based path access control
- Dangerous pattern detection
- Developer mode for safe development

### Content Sanitization
- Automatic masking of sensitive data
- Password and API key protection
- Credit card number detection

### Example Security Configuration

```php
'security' => [
    'allowed_paths' => [
        'app/',
        'config/',
        'routes/',
    ],
    'restricted_files' => [
        '.env',
        'config/database.php',
    ],
    'developer_mode' => false,
],
```

## 📊 Analysis Features

### File Analysis
- PHP structure parsing (classes, methods, functions)
- Complexity scoring
- Category classification
- Domain-specific pattern detection

### Project Analysis
- Compatibility scoring
- Architecture pattern detection
- Best practice recommendations
- Quality metrics

### Example Analysis Output

```php
[
    'path' => 'app/Service/PaymentGateway/StripeGateway.php',
    'metadata' => [
        'category' => 'PaymentGateway',
        'complexity_score' => 12,
        'lines' => 245
    ],
    'analysis' => [
        'classes' => [
            ['name' => 'StripeGateway', 'type' => 'Gateway']
        ],
        'methods' => [
            ['name' => 'authorize', 'visibility' => 'public'],
            ['name' => 'capture', 'visibility' => 'public']
        ]
    ],
    'domain_insights' => [
        'patterns_found' => ['payment_gateway'],
        'security_score' => 0.9
    ]
]
```

## 🔧 Creating Custom Adapters

### Basic Adapter

```php
use AuriRe\AiMcpAnalysis\Adapters\AbstractProjectAdapter;

class MyCustomAdapter extends AbstractProjectAdapter
{
    public function getProjectType(): string
    {
        return 'my_project_type';
    }

    public function getAllowedPaths(): array
    {
        return ['app/', 'custom/'];
    }

    public function getRestrictedFiles(): array
    {
        return ['.env', 'config/secrets.php'];
    }

    public function getDomainPatterns(): array
    {
        return [
            'my_pattern' => [
                'class_name_contains' => 'MyClass',
                'methods' => ['myMethod']
            ]
        ];
    }

    public function getCompatibilityScore(string $projectPath): float
    {
        // Logic to determine if this adapter fits the project
        return 0.8;
    }
}
```

---
