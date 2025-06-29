# AI MCP Analysis for laravel

A Laravel library package for AI Laravel project analysis using MCP.

## Installation

You can install the package via composer:

```bash
composer require aurire/ai-mcp-analysis
```
# Configuration
Publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-mcp-analysis-config
```
## Usage
```php
use Aurire\AiMcpAnalysis\Facades\AiMcpAnalysis;

// Get library info
echo AiMcpAnalysis::getName();
echo AiMcpAnalysis::getVersion();

// Process data
$result = AiMcpAnalysis::processData(['hello', 'world']);

// Format message
echo AiMcpAnalysis::formatMessage('Hello World');
```
## Testing

```bash
composer test
```
## License
The MIT License (MIT).

## Tips for Development
### Create tags for versions:

Version Tags: Always tag your releases (v1.0.0, v1.1.0, etc.)
```bash
git tag v1.0.0
git push origin v1.0.0
```

### Semantic Versioning
Follow SemVer for version numbering

### Testing
Use Orchestra Testbench for Laravel package testing

### Documentation
Keep README.md updated with usage examples

### Changelog
Maintain a CHANGELOG.md for version history
