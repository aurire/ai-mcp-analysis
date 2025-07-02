<?php

declare(strict_types=1);

namespace AuriRe\AiMcpAnalysis\Core;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

/**
 * SecurityValidation - Battle-tested security patterns for safe file system access
 * 
 * Extracted from HPaymentsAnalysisTool with enhancements for multi-project support.
 * Provides comprehensive security validation for file access and content sanitization.
 */
class SecurityValidation
{
    /**
     * Default allowed paths for file system access
     * Can be overridden by project adapters
     */
    protected const DEFAULT_ALLOWED_PATHS = [
        'app/',
        'config/',
        'routes/',
        'database/migrations/',
        'resources/views/',
        'tests/',
    ];

    /**
     * Restricted files that should never be accessible
     */
    protected const RESTRICTED_FILES = [
        '.env',
        'config/database.php',
        'config/services.php',
        'storage/oauth-private.key',
        'storage/oauth-public.key',
    ];

    /**
     * Dangerous patterns that should trigger security warnings
     */
    protected const DANGEROUS_PATTERNS = [
        'WEB-INF',
        'web.xml',
        '../',
        '..\\',
        '/etc/',
        '/var/',
        '/usr/',
        'passwd',
        'shadow',
    ];

    private array $allowedPaths;
    private array $restrictedFiles;
    private bool $developerMode;
    private string $projectBasePath;

    public function __construct(
        array $allowedPaths = null,
        array $restrictedFiles = null,
        bool $developerMode = false,
        string $projectBasePath = null
    ) {
        $this->allowedPaths = $allowedPaths ?? self::DEFAULT_ALLOWED_PATHS;
        $this->restrictedFiles = $restrictedFiles ?? self::RESTRICTED_FILES;
        $this->developerMode = $developerMode;
        $this->projectBasePath = $projectBasePath ?? base_path();
    }

    /**
     * Validate path against security rules
     * Extracted and enhanced from HPaymentsAnalysisTool::validatePath()
     */
    public function validatePath(string $path): void
    {
        // Check for dangerous patterns first
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (stripos($path, $pattern) !== false) {
                Log::warning('Dangerous path access attempt', ['path' => $path, 'pattern' => $pattern]);
                throw new InvalidArgumentException("Dangerous path detected: $pattern");
            }
        }

        // Developer mode override for migration/development
        if ($this->developerMode) {
            Log::info('Developer mode: Path validation bypassed', ['path' => $path]);
            return;
        }

        // Check if path starts with any allowed path
        $isAllowed = false;
        foreach ($this->allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            throw new InvalidArgumentException("Path not in allowed list: $path");
        }

        // Check if file exists (for files, not directories)
        $fullPath = $this->projectBasePath . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($fullPath) && !is_dir($fullPath)) {
            throw new InvalidArgumentException("Path does not exist: $path");
        }

        Log::debug('Path access granted', ['path' => $path]);
    }

    /**
     * Check if a file is readable (not restricted)
     * Extracted from HPaymentsAnalysisTool::isReadableFile()
     */
    public function isReadableFile(string $filePath): bool
    {
        foreach ($this->restrictedFiles as $restricted) {
            if (str_ends_with($filePath, $restricted)) {
                Log::info('Access denied to restricted file', ['file' => $filePath]);
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize file content to remove/mask sensitive data
     * Extracted and enhanced from HPaymentsAnalysisTool::sanitizeFileContent()
     */
    public function sanitizeFileContent(string $content): string
    {
        $patterns = [
            // Password patterns
            '/([\'"]password[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            '/([\'"]pwd[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            
            // Secret patterns
            '/([\'"]secret[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            '/([\'"]api_key[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            '/([\'"]api_secret[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            
            // Environment variable patterns
            '/(DB_PASSWORD=)[^\\s]*/i' => '$1***MASKED***',
            '/(API_.*=)[^\\s]*/i' => '$1***MASKED***',
            '/(SECRET_.*=)[^\\s]*/i' => '$1***MASKED***',
            '/(.*_KEY=)[^\\s]*/i' => '$1***MASKED***',
            '/(.*_SECRET=)[^\\s]*/i' => '$1***MASKED***',
            
            // Token patterns
            '/([\'"]token[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
            '/([\'"]access_token[\'"][\\s]*=>[\\s]*[\'"])[^\'"]*([\'"]\\s*[,;])/i' => '$1***MASKED***$2',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $content);
    }

    /**
     * Create a backup of a file before modification
     */
    public function createBackup(string $filePath): string
    {
        $this->validatePath($filePath);
        
        $fullPath = $this->projectBasePath . DIRECTORY_SEPARATOR . $filePath;
        $backupPath = $fullPath . '.backup.' . date('Y-m-d-H-i-s');
        
        if (file_exists($fullPath)) {
            copy($fullPath, $backupPath);
            Log::info('Backup created', ['original' => $filePath, 'backup' => $backupPath]);
        }
        
        return $backupPath;
    }

    /**
     * Validate that content appears to be valid PHP
     */
    public function validatePhpContent(string $content): array
    {
        if (!str_starts_with(trim($content), '<?php')) {
            return [
                'valid' => false,
                'error' => 'Content does not start with <?php tag'
            ];
        }

        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        
        if ($openBraces !== $closeBraces) {
            return [
                'valid' => false,
                'error' => 'Unbalanced braces detected'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get current security configuration
     */
    public function getSecurityConfig(): array
    {
        return [
            'allowed_paths' => $this->allowedPaths,
            'restricted_files' => $this->restrictedFiles,
            'developer_mode' => $this->developerMode,
            'project_base_path' => $this->projectBasePath,
        ];
    }

    /**
     * Update allowed paths (for project adapters)
     */
    public function setAllowedPaths(array $paths): void
    {
        $this->allowedPaths = $paths;
        Log::info('Allowed paths updated', ['paths' => $paths]);
    }

    /**
     * Add additional allowed paths
     */
    public function addAllowedPaths(array $paths): void
    {
        $this->allowedPaths = array_merge($this->allowedPaths, $paths);
        Log::info('Additional paths added', ['new_paths' => $paths]);
    }
}

