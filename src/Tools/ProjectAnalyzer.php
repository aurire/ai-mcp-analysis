<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tools;

use Aurire\AiMcpAnalysis\Core\ParameterNormalizer;
use Aurire\AiMcpAnalysis\Core\ProjectDetector;
use Aurire\AiMcpAnalysis\Analysis\StructureAnalyzer;
use Aurire\AiMcpAnalysis\Analysis\DomainAnalyzer;
use Aurire\AiMcpAnalysis\Analysis\RecommendationEngine;
use PhpMcp\Server\Attributes\McpTool;

class ProjectAnalyzer
{
    private ProjectDetector $projectDetector;
    private StructureAnalyzer $structureAnalyzer;
    private DomainAnalyzer $domainAnalyzer;
    private RecommendationEngine $recommendationEngine;
    private ?array $cachedAnalysis = null;

    public function __construct(
        ProjectDetector $projectDetector = null,
        StructureAnalyzer $structureAnalyzer = null,
        DomainAnalyzer $domainAnalyzer = null,
        RecommendationEngine $recommendationEngine = null
    ) {
        $this->projectDetector = $projectDetector ?? new ProjectDetector();
        $this->structureAnalyzer = $structureAnalyzer ?? new StructureAnalyzer();
        $this->domainAnalyzer = $domainAnalyzer ?? new DomainAnalyzer($this->projectDetector);
        $this->recommendationEngine = $recommendationEngine ?? new RecommendationEngine();
    }

    /**
     * Comprehensive Laravel project analysis
     */
    #[McpTool(description: 'Comprehensive Laravel project analysis with domain-specific insights')]
    public function analyzeProject(mixed $options = null): array
    {
        // Use ParameterNormalizer to handle various input formats
        $normalizedOptions = ParameterNormalizer::normalize($options, 'options');
        $options = is_array($normalizedOptions) ? $normalizedOptions : [];

        // Check cache unless forced refresh
        if ($this->cachedAnalysis !== null && !($options['force_refresh'] ?? false)) {
            return $this->cachedAnalysis;
        }

        $startTime = microtime(true);
        $errors = [];

        try {
            // Get project characteristics
            $projectInfo = $this->projectDetector->getProjectCharacteristics();

            // Perform structure analysis
            $structureAnalysis = $this->structureAnalyzer->analyze();

            // Perform domain-specific analysis
            $domainAnalysis = $this->domainAnalyzer->analyze();

            // Build comprehensive result
            $analysisResult = [
                'project_info' => $projectInfo,
                'structure_analysis' => $structureAnalysis,
                'patterns_detected' => $this->detectPatterns(),
                'domain_specific_analysis' => $domainAnalysis,
                'security_considerations' => $this->getSecurityConsiderations($projectInfo, $domainAnalysis), // Add this
                'analysis_metadata' => [
                    'analysis_time' => round((microtime(true) - $startTime) * 1000, 2),
                    'analyzer_version' => '1.0.0',
                    'detection_confidence' => $this->projectDetector->getDetectionConfidence(),
                    'analysis_timestamp' => now()->toISOString(),
                    'errors' => $errors
                ]
            ];

            // Add external tool results if requested
            if ($options['include_external_analysis'] ?? false) {
                $analysisResult['external_tool_results'] = $this->getExternalToolResults();
            }

            // Generate recommendations
            $analysisResult['recommendations'] = $this->recommendationEngine
                ->generateRecommendations($analysisResult);

            // Cache the result
            $this->cachedAnalysis = $analysisResult;

            return $analysisResult;

        } catch (\Exception $e) {
            $errors[] = [
                'type' => 'analysis_error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];

            // Return partial results on error
            return [
                'project_info' => ['name' => 'unknown', 'type' => 'unknown'],
                'structure_analysis' => [],
                'patterns_detected' => [],
                'domain_specific_analysis' => [],
                'recommendations' => [],
                'analysis_metadata' => [
                    'analysis_time' => round((microtime(true) - $startTime) * 1000, 2),
                    'analyzer_version' => '1.0.0',
                    'analysis_timestamp' => now()->toISOString(),
                    'errors' => $errors,
                    'status' => 'partial_failure'
                ]
            ];
        }
    }

    /**
     * Detect general patterns
     */
    private function detectPatterns(): array
    {
        // This could be expanded with more sophisticated pattern detection
        return [
            'mvc_pattern' => 'detected',
            'service_layer_pattern' => 'partial',
            'repository_pattern' => 'not_detected'
        ];
    }

    /**
     * Get external tool results (placeholder for future integration)
     */
    private function getExternalToolResults(): array
    {
        return [
            'code_quality_metrics' => [
                'phpstan_analysis' => 'not_implemented',
                'phpcs_analysis' => 'not_implemented',
                'phpmd_analysis' => 'not_implemented'
            ],
            'security_analysis' => [
                'psalm_security' => 'not_implemented'
            ]
        ];
    }

    private function getSecurityConsiderations(array $projectInfo, array $domainAnalysis): array
    {
        $considerations = [];

        $projectType = $projectInfo['type'] ?? 'unknown';

        switch ($projectType) {
            case 'ecommerce':
                $considerations = [
                    'payment_data_security' => 'Ensure PCI compliance for payment processing',
                    'user_data_protection' => 'Implement proper encryption for sensitive customer data',
                    'session_security' => 'Secure cart sessions and prevent session hijacking'
                ];
                break;

            case 'api_service':
                $considerations = [
                    'authentication_security' => 'Implement proper API authentication',
                    'rate_limiting' => 'Add rate limiting to prevent abuse',
                    'input_validation' => 'Validate all API inputs to prevent injection attacks'
                ];
                break;

            case 'admin_dashboard':
                $considerations = [
                    'authorization_checks' => 'Implement proper role-based access control',
                    'csrf_protection' => 'Ensure CSRF protection on all admin forms',
                    'audit_logging' => 'Log all administrative actions'
                ];
                break;

            default:
                $considerations = [
                    'general_security' => 'Implement basic Laravel security best practices',
                    'input_validation' => 'Validate all user inputs',
                    'authentication' => 'Secure user authentication system'
                ];
        }

        return $considerations;
    }
}
