<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Analysis;

class RecommendationEngine
{
    /**
     * Generate recommendations based on analysis results
     *
     * @param array $analysisResults
     * @return array
     */
    public function generateRecommendations(array $analysisResults): array
    {
        return [
            'priority_actions' => $this->getPriorityActions($analysisResults),
            'architectural_improvements' => $this->getArchitecturalImprovements($analysisResults),
            'security_recommendations' => $this->getSecurityRecommendations($analysisResults),
            'performance_optimizations' => $this->getPerformanceOptimizations($analysisResults)
        ];
    }

    /**
     * Get priority actions
     *
     * @param array $results
     * @return array
     */
    private function getPriorityActions(array $results): array
    {
        $actions = [];

        // Check Laravel version
        if (isset($results['project_info']['dependency_versions']['laravel/framework'])) {
            $versionConstraint = $results['project_info']['dependency_versions']['laravel/framework'];
            $laravelVersion = $this->extractVersionFromConstraint($versionConstraint);

            if ($laravelVersion && version_compare($laravelVersion, '10.0', '<')) {
                $actions[] = [
                    'priority' => 'high',
                    'action' => 'Upgrade Laravel Framework',
                    'description' => "Current version ({$laravelVersion}) is outdated. Consider upgrading to Laravel 10+",
                    'effort' => 'medium'
                ];
            }
        }

        // Check structure health
        if (isset($results['structure_analysis']['structure_health']['score'])) {
            $score = $results['structure_analysis']['structure_health']['score'];
            if ($score < 70) {
                $actions[] = [
                    'priority' => 'medium',
                    'action' => 'Improve Project Structure',
                    'description' => "Structure health score is {$score}%. Consider organizing code better",
                    'effort' => 'low'
                ];
            }
        }

        return $actions;
    }

    /**
     * Get architectural improvements
     *
     * @param array $results
     * @return array
     */
    private function getArchitecturalImprovements(array $results): array
    {
        $improvements = [];

        // Check for service layer
        if (!isset($results['structure_analysis']['directories']['app/Services']['exists']) ||
            !$results['structure_analysis']['directories']['app/Services']['exists']) {
            $improvements[] = [
                'category' => 'architecture',
                'recommendation' => 'Add Service Layer',
                'description' => 'Consider adding app/Services directory for business logic',
                'benefit' => 'Better separation of concerns and testability'
            ];
        }

        // Check for repositories
        if (!isset($results['structure_analysis']['directories']['app/Repositories']['exists']) ||
            !$results['structure_analysis']['directories']['app/Repositories']['exists']) {
            $improvements[] = [
                'category' => 'architecture',
                'recommendation' => 'Add Repository Pattern',
                'description' => 'Consider implementing repository pattern for data access',
                'benefit' => 'Better abstraction and testability of data layer'
            ];
        }

        return $improvements;
    }

    /**
     * Get security recommendations
     *
     * @param array $results
     * @return array
     */
    private function getSecurityRecommendations(array $results): array
    {
        $recommendations = [];

        // API authentication check
        if (isset($results['project_info']['type']) && $results['project_info']['type'] === 'api_service') {
            if (!isset($results['domain_specific_analysis']['authentication_analysis']['sanctum_present']) ||
                !$results['domain_specific_analysis']['authentication_analysis']['sanctum_present']) {
                $recommendations[] = [
                    'category' => 'security',
                    'recommendation' => 'Add API Authentication',
                    'description' => 'API service should have authentication (Laravel Sanctum recommended)',
                    'severity' => 'high'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get performance optimizations
     *
     * @param array $results
     * @return array
     */
    private function getPerformanceOptimizations(array $results): array
    {
        $optimizations = [];

        // Check for caching
        $optimizations[] = [
            'category' => 'performance',
            'recommendation' => 'Implement Caching Strategy',
            'description' => 'Consider implementing Redis or file-based caching for better performance',
            'impact' => 'high'
        ];

        return $optimizations;
    }

    /**
     * @param string $constraint
     * @return string|null
     */
    private function extractVersionFromConstraint(string $constraint): ?string
    {
        // Handle various constraint formats: "^9.0", "~9.0", "9.*", ">=9.0"
        if (preg_match('/[\^~>=]*(\d+)\./', $constraint, $matches)) {
            return $matches[1] . '.0';
        }

        return null;
    }
}
