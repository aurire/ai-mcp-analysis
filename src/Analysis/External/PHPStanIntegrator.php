<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Analysis\External;

use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use PhpMcp\Server\Attributes\McpTool;

class PHPStanIntegrator
{
    /**
     * Check if PHPStan is available
     */
    public function isAvailable(): bool
    {
        try {
            $result = Process::run(['vendor/bin/phpstan', '--version']);
            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Run PHPStan analysis on specified path
     */
    #[McpTool(description: 'Run PHPStan static analysis and return categorized results')]
    public function analyze(string $path, array $options = []): array
    {
        // For testing purposes, allow mock mode
        if (isset($options['_mock_mode']) && $options['_mock_mode'] === true) {
            return $this->getMockResults($path, $options);
        }

        if (!$this->isPathValid($path)) {
            throw new InvalidArgumentException('Path does not exist or is not readable: ' . $path);
        }

        if (!$this->isPathValid($path)) {
            throw new InvalidArgumentException('Path does not exist or is not readable: ' . $path);
        }

        $startTime = microtime(true);

        try {
            $command = $this->buildCommand($path, $options);
            $result = Process::run($command);

            $analysisTime = round((microtime(true) - $startTime) * 1000, 2);

            if (!$result->successful()) {
                return [
                    'error' => 'PHPStan analysis failed: ' . $result->errorOutput(),
                    'performance' => ['analysis_time_ms' => $analysisTime]
                ];
            }

            $output = $result->output();
            $phpstanData = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => 'Failed to parse PHPStan output as JSON',
                    'raw_output' => $output,
                    'performance' => ['analysis_time_ms' => $analysisTime]
                ];
            }

            return $this->processResults($phpstanData, $analysisTime);

        } catch (\Exception $e) {
            $analysisTime = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'error' => 'PHPStan execution failed: ' . $e->getMessage(),
                'performance' => ['analysis_time_ms' => $analysisTime]
            ];
        }
    }

    /**
     * Generate improvement suggestions based on PHPStan results
     */
    public function generateSuggestions(array $phpstanResults): array
    {
        if (isset($phpstanResults['error'])) {
            return ['error' => 'Cannot generate suggestions due to analysis errors'];
        }

        $suggestions = [
            'priority_fixes' => $this->getPriorityFixes($phpstanResults),
            'quick_wins' => $this->getQuickWins($phpstanResults),
            'long_term_improvements' => $this->getLongTermImprovements($phpstanResults)
        ];

        return $suggestions;
    }

    /**
     * Enhance project analysis with PHPStan results
     */
    public function enhanceProjectAnalysis(array $projectAnalysis): array
    {
        $phpstanResults = $this->analyze('app/');

        $projectAnalysis['external_analysis'] = [
            'phpstan_results' => $phpstanResults
        ];

        $projectAnalysis['integration_insights'] = $this->generateIntegrationInsights(
            $projectAnalysis,
            $phpstanResults
        );

        return $projectAnalysis;
    }

    /**
     * Build PHPStan command with options
     */
    private function buildCommand(string $path, array $options): array
    {
        $command = ['vendor/bin/phpstan', 'analyse', '--format=json', '--no-progress'];

        if (isset($options['level'])) {
            $command[] = '--level=' . $options['level'];
        }

        if (isset($options['memory_limit'])) {
            $command[] = '--memory-limit=' . $options['memory_limit'];
        }

        if (isset($options['config'])) {
            $command[] = '--configuration=' . $options['config'];
        }

        $command[] = $path;

        return $command;
    }

    /**
     * Process PHPStan raw results
     */
    private function processResults(array $phpstanData, float $analysisTime): array
    {
        $totals = $phpstanData['totals'] ?? [];
        $files = $phpstanData['files'] ?? [];

        return [
            'summary' => [
                'total_errors' => $totals['errors'] ?? 0,
                'files_with_errors' => $totals['file_errors'] ?? 0,
                'files_analyzed' => count($files)
            ],
            'files_analyzed' => array_keys($files),
            'errors_by_file' => $this->groupErrorsByFile($files),
            'categorized_issues' => $this->categorizeIssues($files),
            'performance' => [
                'analysis_time_ms' => $analysisTime,
                'errors_per_file' => count($files) > 0 ? round(($totals['errors'] ?? 0) / count($files), 2) : 0
            ]
        ];
    }

    /**
     * Group errors by file
     */
    private function groupErrorsByFile(array $files): array
    {
        $grouped = [];

        foreach ($files as $filepath => $fileData) {
            $grouped[$filepath] = [
                'error_count' => $fileData['errors'] ?? count($fileData['messages'] ?? []),
                'messages' => $fileData['messages'] ?? []
            ];
        }

        return $grouped;
    }

    /**
     * Categorize issues by type
     */
    private function categorizeIssues(array $files): array
    {
        $categories = [
            'type_issues' => [],
            'unused_code' => [],
            'dead_code' => [],
            'other' => []
        ];

        foreach ($files as $filepath => $fileData) {
            foreach ($fileData['messages'] ?? [] as $message) {
                $messageText = strtolower($message['message'] ?? '');

                if (str_contains($messageText, 'type') || str_contains($messageText, 'return type')) {
                    $categories['type_issues'][] = ['file' => $filepath, 'message' => $message];
                } elseif (str_contains($messageText, 'never read') || str_contains($messageText, 'unused')) {
                    $categories['unused_code'][] = ['file' => $filepath, 'message' => $message];
                } elseif (str_contains($messageText, 'dead') || str_contains($messageText, 'never thrown')) {
                    $categories['dead_code'][] = ['file' => $filepath, 'message' => $message];
                } else {
                    $categories['other'][] = ['file' => $filepath, 'message' => $message];
                }
            }
        }

        return $categories;
    }

    /**
     * Get priority fixes
     */
    private function getPriorityFixes(array $results): array
    {
        $fixes = [];

        if (isset($results['categorized_issues']['type_issues']) &&
            count($results['categorized_issues']['type_issues']) > 0) {
            $fixes[] = [
                'category' => 'Type Safety',
                'description' => 'Add missing return types and parameter types',
                'impact' => 'high',
                'effort' => 'medium',
                'count' => count($results['categorized_issues']['type_issues'])
            ];
        }

        return $fixes;
    }

    /**
     * Get quick wins
     */
    private function getQuickWins(array $results): array
    {
        $wins = [];

        if (isset($results['categorized_issues']['unused_code']) &&
            count($results['categorized_issues']['unused_code']) > 0) {
            $wins[] = [
                'category' => 'Clean Up',
                'description' => 'Remove unused variables and properties',
                'impact' => 'medium',
                'effort' => 'low',
                'count' => count($results['categorized_issues']['unused_code'])
            ];
        }

        return $wins;
    }

    /**
     * Get long-term improvements
     */
    private function getLongTermImprovements(array $results): array
    {
        $improvements = [];

        $totalErrors = $results['summary']['total_errors'] ?? 0;
        if ($totalErrors > 50) {
            $improvements[] = [
                'category' => 'Code Quality',
                'description' => 'Consider implementing stricter PHPStan level (currently high error count)',
                'impact' => 'high',
                'effort' => 'high'
            ];
        }

        return $improvements;
    }

    /**
     * Generate integration insights
     */
    private function generateIntegrationInsights(array $projectAnalysis, array $phpstanResults): array
    {
        $insights = [];

        $projectType = $projectAnalysis['project_info']['type'] ?? 'unknown';
        $totalErrors = $phpstanResults['summary']['total_errors'] ?? 0;

        if ($projectType === 'api_service' && $totalErrors > 0) {
            $insights[] = 'API services should have strict type safety. Consider addressing PHPStan errors for better reliability.';
        }

        if ($projectType === 'ecommerce' && isset($phpstanResults['categorized_issues']['type_issues'])) {
            $insights[] = 'E-commerce applications handling sensitive data should prioritize type safety for security.';
        }

        return $insights;
    }

    /**
     * Validate if path is accessible
     */
    private function isPathValid(string $path): bool
    {
        // Convert to absolute path if relative
        $absolutePath = $this->resolveAnalysisPath($path);

        return file_exists($absolutePath) && is_readable($absolutePath);
    }

    /**
     * Resolve analysis path relative to project being analyzed
     */
    private function resolveAnalysisPath(string $path): string
    {
        // If it's already absolute, return as-is
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // For testing, use the current working directory
        // In real usage, this would be the target project's base path
        return getcwd() . '/' . $path;
    }

    private function getMockResults(string $path, array $options): array
    {
        return [
            'summary' => [
                'total_errors' => 0,
                'files_with_errors' => 0,
                'files_analyzed' => 1
            ],
            'files_analyzed' => [$path],
            'errors_by_file' => [],
            'categorized_issues' => [
//                'type_issues' => [],
//                'unused_code' => [],
//                'dead_code' => [],
                'other' => []
            ],
            'performance' => [
                'analysis_time_ms' => 10.5,
                'errors_per_file' => 0
            ]
        ];
    }
}
