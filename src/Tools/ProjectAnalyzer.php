<?php

declare(strict_types=1);

namespace AuriRe\AiMcpAnalysis\Tools;

use AuriRe\AiMcpAnalysis\Adapters\AbstractProjectAdapter;
use AuriRe\AiMcpAnalysis\Core\ParameterNormalizer;
use InvalidArgumentException;
use Exception;

/**
 * ProjectAnalyzer - Main MCP tool interface for the AI MCP Analysis Library
 * 
 * Provides a unified interface for project analysis using domain-specific adapters.
 * Handles parameter normalization and delegates to appropriate adapters.
 */
class ProjectAnalyzer
{
    private AbstractProjectAdapter $adapter;

    public function __construct(AbstractProjectAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Analyze a single file
     */
    public function analyzeFile(string $filePath): array
    {
        try {
            return $this->adapter->analyzeFile($filePath);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'adapter_type' => $this->adapter->getProjectType()
            ];
        }
    }

    /**
     * List files in directory with filtering
     */
    public function listFiles(string $directory = 'app', ?string $filter = null): array
    {
        return $this->adapter->listFiles($directory, $filter);
    }

    /**
     * Search for domain-specific patterns
     */
    public function searchPatterns(string $directory = 'app'): array
    {
        return $this->adapter->searchDomainPatterns($directory);
    }

    /**
     * Get analysis recommendations
     */
    public function getRecommendations(array $analysis): array
    {
        return $this->adapter->getRecommendations($analysis);
    }

    /**
     * Generate project summary
     */
    public function generateSummary(string $directory = 'app'): array
    {
        return $this->adapter->generateProjectSummary($directory);
    }

    /**
     * Check adapter compatibility
     */
    public function checkCompatibility(string $projectPath = ''): array
    {
        $projectPath = $projectPath ?: base_path();
        $score = $this->adapter->getCompatibilityScore($projectPath);
        
        return [
            'adapter_type' => $this->adapter->getProjectType(),
            'compatibility_score' => $score,
            'is_compatible' => $this->adapter->isCompatible($projectPath),
            'project_path' => $projectPath
        ];
    }

    /**
     * Get adapter information
     */
    public function getAdapterInfo(): array
    {
        return [
            'project_type' => $this->adapter->getProjectType(),
            'allowed_paths' => $this->adapter->getAllowedPaths(),
            'restricted_files' => $this->adapter->getRestrictedFiles(),
            'domain_patterns' => $this->adapter->getDomainPatterns(),
            'config' => $this->adapter->getConfig()
        ];
    }

    /**
     * Update adapter configuration
     */
    public function updateConfig(array $config): void
    {
        $normalizedConfig = ParameterNormalizer::normalizeParameters($config);
        $this->adapter->updateConfig($normalizedConfig);
    }

    /**
     * Analyze multiple files in batch
     */
    public function batchAnalyze(array $filePaths): array
    {
        $results = [];
        $errors = [];
        
        foreach ($filePaths as $filePath) {
            try {
                $results[$filePath] = $this->analyzeFile($filePath);
            } catch (Exception $e) {
                $errors[$filePath] = $e->getMessage();
            }
        }
        
        return [
            'total_files' => count($filePaths),
            'successful_analyses' => count($results),
            'failed_analyses' => count($errors),
            'results' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Search across all files in directory
     */
    public function searchInFiles(string $searchTerm, string $directory = 'app', bool $caseSensitive = false): array
    {
        // This would use the structure analyzer from the adapter
        // For now, we'll delegate to the adapter's structure analyzer
        $structureAnalyzer = $this->adapter->getStructureAnalyzer();
        
        // Note: This method would need to be added to StructureAnalyzer
        // For the MVP, we'll return a placeholder
        return [
            'search_term' => $searchTerm,
            'directory' => $directory,
            'case_sensitive' => $caseSensitive,
            'adapter_type' => $this->adapter->getProjectType(),
            'note' => 'Search functionality to be implemented in StructureAnalyzer'
        ];
    }

    /**
     * Validate project structure
     */
    public function validateStructure(): array
    {
        $compatibility = $this->checkCompatibility();
        $summary = $this->generateSummary();
        $recommendations = [];
        
        // Generate structural recommendations
        if ($compatibility['compatibility_score'] < 0.8) {
            $recommendations[] = [
                'type' => 'structure',
                'priority' => 'medium',
                'message' => 'Project structure does not fully match expected patterns for ' . $this->adapter->getProjectType()
            ];
        }
        
        if ($summary['total_files'] < 5) {
            $recommendations[] = [
                'type' => 'coverage',
                'priority' => 'low',
                'message' => 'Very few files detected. Check if analysis directory is correct.'
            ];
        }
        
        return [
            'validation_results' => [
                'structure_valid' => $compatibility['is_compatible'],
                'compatibility_score' => $compatibility['compatibility_score'],
                'file_count' => $summary['total_files'],
                'category_distribution' => $summary['category_distribution'] ?? []
            ],
            'recommendations' => $recommendations,
            'adapter_type' => $this->adapter->getProjectType()
        ];
    }

    /**
     * Get detailed project metrics
     */
    public function getProjectMetrics(): array
    {
        $summary = $this->generateSummary();
        $compatibility = $this->checkCompatibility();
        
        return [
            'project_info' => [
                'type' => $this->adapter->getProjectType(),
                'compatibility_score' => $compatibility['compatibility_score'],
                'total_files' => $summary['total_files'],
                'total_size_kb' => $summary['total_size_kb'],
                'average_complexity' => $summary['average_complexity']
            ],
            'distribution' => [
                'by_category' => $summary['category_distribution'] ?? [],
                'by_domain' => $summary['domain_distribution'] ?? []
            ],
            'quality_metrics' => [
                'complexity_threshold' => $this->adapter->getConfig()['complexity_threshold'] ?? 10,
                'high_complexity_files' => $this->countHighComplexityFiles($summary)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Count files with high complexity
     */
    private function countHighComplexityFiles(array $summary): int
    {
        // This would require analyzing individual files
        // For MVP, return estimated count based on average
        $threshold = $this->adapter->getConfig()['complexity_threshold'] ?? 10;
        $avgComplexity = $summary['average_complexity'] ?? 0;
        
        if ($avgComplexity > $threshold) {
            return (int) round($summary['total_files'] * 0.3); // Estimate 30% are high complexity
        }
        
        return 0;
    }

    /**
     * Create analysis report
     */
    public function createReport(string $directory = 'app', bool $includeRecommendations = true): array
    {
        $report = [
            'report_meta' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'adapter_type' => $this->adapter->getProjectType(),
                'directory_analyzed' => $directory,
                'library_version' => '1.0.0'
            ],
            'project_summary' => $this->generateSummary($directory),
            'compatibility_analysis' => $this->checkCompatibility(),
            'structure_validation' => $this->validateStructure(),
            'project_metrics' => $this->getProjectMetrics()
        ];
        
        if ($includeRecommendations) {
            // Collect recommendations from various analyses
            $allRecommendations = [];
            
            // Add structural recommendations
            $allRecommendations = array_merge(
                $allRecommendations,
                $report['structure_validation']['recommendations'] ?? []
            );
            
            $report['recommendations'] = [
                'total_recommendations' => count($allRecommendations),
                'by_priority' => $this->groupRecommendationsByPriority($allRecommendations),
                'details' => $allRecommendations
            ];
        }
        
        return $report;
    }

    /**
     * Group recommendations by priority
     */
    private function groupRecommendationsByPriority(array $recommendations): array
    {
        $grouped = ['high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($recommendations as $rec) {
            $priority = $rec['priority'] ?? 'medium';
            if (isset($grouped[$priority])) {
                $grouped[$priority]++;
            }
        }
        
        return $grouped;
    }
}

