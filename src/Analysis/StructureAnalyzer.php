<?php

declare(strict_types=1);

namespace AuriRe\AiMcpAnalysis\Analysis;

use AuriRe\AiMcpAnalysis\Core\SecurityValidation;
use InvalidArgumentException;
use Illuminate\Support\Facades\File;

/**
 * StructureAnalyzer - Core PHP file analysis engine
 * 
 * Extracted from HPaymentsAnalysisTool with enhanced validation and error handling.
 * Provides comprehensive analysis of PHP files including classes, methods, and complexity.
 */
class StructureAnalyzer
{
    private SecurityValidation $security;

    public function __construct(SecurityValidation $security)
    {
        $this->security = $security;
    }

    /**
     * Analyze a PHP file and return comprehensive structure information
     * Extracted from HPaymentsAnalysisTool::analyzePhpFile()
     */
    public function analyzePhpFile(string $content): array
    {
        $analysis = [
            'classes' => [],
            'functions' => [],
            'methods' => [],
            'traits' => [],
            'interfaces' => [],
            'enums' => [],
            'metrics' => [
                'total_lines' => count(explode("\n", $content)),
                'code_lines' => 0,
                'comment_lines' => 0,
                'blank_lines' => 0,
            ],
        ];

        // Calculate line metrics
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                $analysis['metrics']['blank_lines']++;
            } elseif (
                str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') ||
                str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')
            ) {
                $analysis['metrics']['comment_lines']++;
            } else {
                $analysis['metrics']['code_lines']++;
            }
        }

        // Clean content for structure parsing
        $cleanContent = $this->removeStringsAndComments($content);

        // Extract classes with enhanced precision
        $analysis['classes'] = $this->extractClasses($cleanContent, $content);

        // Extract methods with visibility
        if (
            preg_match_all(
                '/(public|private|protected)\\s+(?:static\\s+)?function\\s+(\\w+)\\s*\\([^)]*\\)/i',
                $cleanContent,
                $matches
            )
        ) {
            foreach ($matches[2] as $i => $methodName) {
                if ($this->isValidMethodName($methodName)) {
                    $analysis['methods'][] = [
                        'name' => $methodName,
                        'visibility' => strtolower($matches[1][$i]),
                        'is_static' => str_contains($matches[0][$i], 'static'),
                    ];
                }
            }
        }

        // Extract enums (PHP 8.1+)
        if (preg_match_all('/^\\s*enum\\s+(\\w+)(?:\\s*:\\s*(\\w+))?/mi', $cleanContent, $matches)) {
            foreach ($matches[1] as $i => $enumName) {
                if ($this->isValidClassName($enumName)) {
                    $analysis['enums'][] = [
                        'name' => $enumName,
                        'backed_type' => $matches[2][$i] ?? null,
                    ];
                }
            }
        }

        // Extract standalone functions
        if (preg_match_all('/^\\s*function\\s+(\\w+)\\s*\\(/m', $cleanContent, $matches)) {
            $analysis['functions'] = array_filter(array_unique($matches[1]), [$this, 'isValidFunctionName']);
        }

        // Extract traits
        if (preg_match_all('/^\\s*trait\\s+(\\w+)/mi', $cleanContent, $matches)) {
            $analysis['traits'] = array_filter(array_unique($matches[1]), [$this, 'isValidClassName']);
        }

        // Extract interfaces
        if (preg_match_all('/^\\s*interface\\s+(\\w+)/mi', $cleanContent, $matches)) {
            $analysis['interfaces'] = array_filter(array_unique($matches[1]), [$this, 'isValidClassName']);
        }

        return $analysis;
    }

    /**
     * Read and analyze a specific PHP file with enhanced metadata
     * Extracted from HPaymentsAnalysisTool::readPhpFile()
     */
    public function readPhpFile(string $filePath): array
    {
        $this->security->validatePath($filePath);

        if (!$this->security->isReadableFile($filePath)) {
            throw new InvalidArgumentException('File is not accessible or contains sensitive data');
        }

        $fullPath = base_path($filePath);

        if (!File::exists($fullPath)) {
            throw new InvalidArgumentException("File {$filePath} does not exist");
        }

        if (!str_ends_with($filePath, '.php')) {
            throw new InvalidArgumentException('Only PHP files are allowed');
        }

        $content = File::get($fullPath);
        $sanitizedContent = $this->security->sanitizeFileContent($content);
        $analysis = $this->analyzePhpFile($content);

        return [
            'path' => $filePath,
            'content' => $sanitizedContent,
            'metadata' => [
                'lines' => count(explode("\n", $content)),
                'size_bytes' => strlen($content),
                'size_kb' => round(strlen($content) / 1024, 2),
                'category' => $this->categorizeFile($filePath),
                'complexity_score' => $this->calculateComplexityScore($analysis),
            ],
            'analysis' => $analysis,
        ];
    }

    /**
     * List PHP files in a directory with filtering
     * Extracted from HPaymentsAnalysisTool::listPhpFiles()
     */
    public function listPhpFiles(string $directory = 'app', ?string $filter = null, bool $includeTests = false): array
    {
        $this->security->validatePath($directory);

        $basePath = base_path($directory);
        if (!is_dir($basePath)) {
            throw new InvalidArgumentException("Directory {$directory} does not exist");
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Skip test files unless explicitly requested
            if (!$includeTests && str_contains($file->getPathname(), '/tests/')) {
                continue;
            }

            $relativePath = str_replace(base_path() . '/', '', $file->getPathname());

            // Apply filter if provided
            if ($filter && !str_contains(strtolower($relativePath), strtolower($filter))) {
                continue;
            }

            $files[] = [
                'path' => $relativePath,
                'name' => $file->getBasename(),
                'size' => $file->getSize(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'category' => $this->categorizeFile($relativePath),
            ];
        }

        // Sort by category, then by name
        usort($files, function ($a, $b) {
            return $a['category'] <=> $b['category'] ?: $a['name'] <=> $b['name'];
        });

        return [
            'total_files' => count($files),
            'total_size_kb' => array_sum(array_column($files, 'size_kb')),
            'files' => $files,
        ];
    }

    /**
     * Categorize file based on path
     * Extracted from HPaymentsAnalysisTool::categorizeFile()
     */
    public function categorizeFile(string $filePath): string
    {
        if (str_contains($filePath, '/Models/')) return 'Model';
        if (str_contains($filePath, '/Repositories/')) return 'Repository';
        if (str_contains($filePath, '/Service/')) return 'Service';
        if (str_contains($filePath, '/Controllers/')) return 'Controller';
        if (str_contains($filePath, '/DTO/')) return 'DTO';
        if (str_contains($filePath, '/Enums/')) return 'Enum';
        if (str_contains($filePath, '/Exceptions/')) return 'Exception';
        if (str_contains($filePath, '/Providers/')) return 'Provider';
        if (str_contains($filePath, '/Middleware/')) return 'Middleware';
        if (str_contains($filePath, '/Tests/')) return 'Test';
        if (str_contains($filePath, '/migrations/')) return 'Migration';
        if (str_contains($filePath, '/Gateways/')) return 'Gateway';
        if (str_contains($filePath, '/Factories/')) return 'Factory';
        if (str_contains($filePath, '/Observers/')) return 'Observer';
        if (str_contains($filePath, '/Events/')) return 'Event';
        if (str_contains($filePath, '/Listeners/')) return 'Listener';
        if (str_contains($filePath, '/Jobs/')) return 'Job';
        if (str_contains($filePath, '/Mail/')) return 'Mail';
        if (str_contains($filePath, '/Notifications/')) return 'Notification';
        if (str_contains($filePath, '/Policies/')) return 'Policy';
        if (str_contains($filePath, '/Resources/')) return 'Resource';
        if (str_contains($filePath, '/Rules/')) return 'Rule';
        if (str_contains($filePath, '/Scopes/')) return 'Scope';

        return 'Other';
    }

    /**
     * Calculate complexity score for analysis
     * Extracted from HPaymentsAnalysisTool::calculateComplexityScore()
     */
    public function calculateComplexityScore(array $analysis): int
    {
        $score = 0;
        $score += count($analysis['classes']) * 3;
        $score += count($analysis['methods']) * 2;
        $score += count($analysis['functions']) * 2;
        $score += count($analysis['interfaces']) * 1;
        $score += count($analysis['traits']) * 1;
        $score += count($analysis['enums']) * 1;

        return $score;
    }

    /**
     * Remove strings and comments from content for parsing
     * Extracted from HPaymentsAnalysisTool::removeStringsAndComments()
     */
    private function removeStringsAndComments(string $content): string
    {
        // Remove single-line comments (// and #)
        $content = preg_replace('/\\/\\/.*$/m', '', $content);
        $content = preg_replace('/^\\s*#.*$/m', '', $content);

        // Remove multi-line comments (/* ... */)
        $content = preg_replace('/\\/\\*.*?\\*\\//s', '', $content);

        // Remove string literals while preserving structure
        $content = preg_replace('/\'(?:\\\\.|[^\\\\\'])*\'/', "''", $content);
        $content = preg_replace('/\"(?:\\\\.|[^\\\\\"])*\"/', '""', $content);

        // Remove heredoc/nowdoc
        $content = preg_replace('/<<<[\'"]?(\\w+)[\'"]?.*?^\\1;$/ms', '', $content);

        return $content;
    }

    /**
     * Extract classes with enhanced validation
     * Extracted from HPaymentsAnalysisTool::extractClasses()
     */
    private function extractClasses(string $cleanContent, string $originalContent): array
    {
        $classes = [];

        $pattern = '/^\\s*(?:(abstract|final)\\s+)?class\\s+(\\w+)(?:\\s+extends\\s+(\\w+))?(?:\\s+implements\\s+([\\w,\\s\\\\]+))?\\s*\\{/mi';

        if (preg_match_all($pattern, $cleanContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $className = $match[2];

                if ($this->isValidClassName($className)) {
                    $classes[] = [
                        'name' => $className,
                        'extends' => !empty($match[3]) ? $match[3] : null,
                        'implements' => !empty($match[4]) ? array_map('trim', explode(',', $match[4])) : [],
                        'type' => $this->determineClassType($originalContent, $className, $match[1] ?? null),
                    ];
                }
            }
        }

        return $classes;
    }

    /**
     * Determine class type based on context
     * Extracted from HPaymentsAnalysisTool::determineClassType()
     */
    private function determineClassType(string $content, string $className, ?string $modifier = null): string
    {
        if ($modifier === 'abstract') return 'Abstract Class';
        if ($modifier === 'final') return 'Final Class';

        $classPattern = "/class\\s+{$className}\\s+extends\\s+(\\w+)/i";
        if (preg_match($classPattern, $content, $matches)) {
            $parentClass = $matches[1];
            
            if (str_contains($parentClass, 'Controller')) return 'Controller';
            if (str_contains($parentClass, 'Model')) return 'Model';
            if (str_contains($parentClass, 'Exception')) return 'Exception';
            if (str_contains($parentClass, 'Middleware')) return 'Middleware';
            
            return 'Extended Class';
        }

        // Fallback to name-based detection
        if (str_contains($className, 'Controller')) return 'Controller';
        if (str_contains($className, 'Model')) return 'Model';
        if (str_contains($className, 'Service')) return 'Service';
        if (str_contains($className, 'Repository')) return 'Repository';
        if (str_contains($className, 'Gateway')) return 'Gateway';
        if (str_contains($className, 'Factory')) return 'Factory';

        return 'Regular Class';
    }

    /**
     * Validate class names to prevent false positives
     * Extracted from HPaymentsAnalysisTool::isValidClassName()
     */
    private function isValidClassName(string $name): bool
    {
        $blacklist = [
            'but', 'and', 'or', 'the', 'is', 'was', 'found', 'ownership',
            'class', 'function', 'method', 'property', 'variable', 'array',
            'string', 'int', 'bool', 'float', 'object', 'resource', 'null',
            'true', 'false',
        ];

        return !in_array(strtolower($name), $blacklist) &&
               preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $name) &&
               strlen($name) > 1;
    }

    /**
     * Validate method names
     * Extracted from HPaymentsAnalysisTool::isValidMethodName()
     */
    private function isValidMethodName(string $name): bool
    {
        $blacklist = ['function', 'method', 'call', 'invoke', 'execute'];

        return !in_array(strtolower($name), $blacklist) &&
               preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) &&
               strlen($name) > 1;
    }

    /**
     * Validate function names
     */
    private function isValidFunctionName(string $name): bool
    {
        return $this->isValidMethodName($name);
    }
}

