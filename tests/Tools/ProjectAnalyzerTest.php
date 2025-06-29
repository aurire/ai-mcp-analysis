<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests\Tools;

use Aurire\AiMcpAnalysis\Tools\ProjectAnalyzer;
use Aurire\AiMcpAnalysis\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ProjectAnalyzerTest extends TestCase
{
    private ProjectAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ProjectAnalyzer();
    }

    /**
     * Test basic project analysis
     */
    public function testAnalyzesBasicProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/test-project',
                'description' => 'Test Laravel project',
                'require' => ['laravel/framework' => '^10.0']
            ],
            'directories' => ['app', 'app/Models', 'app/Http/Controllers'],
            'files' => ['routes/web.php'] // Remove routes/api.php to avoid API detection
        ]);

        $result = $this->analyzer->analyzeProject();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('project_info', $result);
        $this->assertArrayHasKey('structure_analysis', $result);
        $this->assertArrayHasKey('patterns_detected', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('security_considerations', $result); // Add this
        $this->assertArrayHasKey('analysis_metadata', $result);

        // Check project info
        $this->assertEquals('company/test-project', $result['project_info']['name']);
        $this->assertEquals('generic_laravel', $result['project_info']['type']);
    }

    /**
     * Test e-commerce project analysis
     */
    public function testAnalyzesECommerceProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/shop',
                'require' => ['laravel/framework' => '^10.0', 'stripe/stripe-php' => '^8.0']
            ],
            'files' => [
                'app/Models/Cart.php',
                'app/Models/Product.php',
                'app/Models/Order.php'
            ],
            'directories' => [
                'app/Services/Cart',
                'app/Services/Payment'
            ]
        ]);

        $result = $this->analyzer->analyzeProject();

        $this->assertEquals('ecommerce', $result['project_info']['type']);
        $this->assertArrayHasKey('domain_specific_analysis', $result);
        $this->assertArrayHasKey('security_considerations', $result);

        // E-commerce specific checks
        $domainAnalysis = $result['domain_specific_analysis'];
        $this->assertArrayHasKey('payment_security', $domainAnalysis);
        $this->assertArrayHasKey('cart_architecture', $domainAnalysis);
    }

    /**
     * Test API project analysis
     */
    public function testAnalyzesApiProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/api',
                'require' => [
                    'laravel/framework' => '^10.0',
                    'laravel/sanctum' => '^3.0'
                ]
            ],
            'files' => ['routes/api.php'],
            'directories' => ['app/Http/Controllers/API', 'app/Http/Resources']
        ]);

        $result = $this->analyzer->analyzeProject();

        $this->assertEquals('api_service', $result['project_info']['type']);

        // API specific checks
        $domainAnalysis = $result['domain_specific_analysis'];
        $this->assertArrayHasKey('api_structure', $domainAnalysis);
        $this->assertArrayHasKey('authentication_analysis', $domainAnalysis);
        $this->assertArrayHasKey('endpoint_coverage', $domainAnalysis);
    }

    /**
     * Test analysis with external tool integration
     */
    public function testIntegratesWithExternalTools(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/integration-test',
                'require' => ['laravel/framework' => '^10.0']
            ]
        ]);

        $result = $this->analyzer->analyzeProject(['include_external_analysis' => true]);

        $this->assertArrayHasKey('external_tool_results', $result);
        $this->assertArrayHasKey('code_quality_metrics', $result['external_tool_results']);
    }

    /**
     * Test analysis performance and caching
     */
    public function testAnalysisPerformanceAndCaching(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/performance-test',
                'require' => ['laravel/framework' => '^10.0']
            ]
        ]);

        $startTime = microtime(true);
        $result1 = $this->analyzer->analyzeProject();
        $firstRunTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $result2 = $this->analyzer->analyzeProject();
        $secondRunTime = microtime(true) - $startTime;

        // Second run should be faster due to caching
        $this->assertLessThan($firstRunTime, $secondRunTime);
        $this->assertEquals($result1['project_info']['name'], $result2['project_info']['name']);
    }

    /**
     * Test parameter normalization integration
     */
    public function testParameterNormalizationIntegration(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/param-test',
                'require' => ['laravel/framework' => '^10.0']
            ]
        ]);

        // Test with various parameter formats that need normalization
        $testCases = [
            'null',
            'undefined',
            [['include_external_analysis' => true]],
            '{"include_dependencies": true}'
        ];

        foreach ($testCases as $options) {
            $result = $this->analyzer->analyzeProject($options);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('project_info', $result);
        }
    }

    /**
     * Test comprehensive recommendations generation
     */
    public function testGeneratesComprehensiveRecommendations(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/recommendations-test',
                'require' => ['laravel/framework' => '^9.0']
            ],
            'directories' => ['app'],
        ]);

        $result = $this->analyzer->analyzeProject();

        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('priority_actions', $recommendations);
        $this->assertArrayHasKey('architectural_improvements', $recommendations);
        $this->assertArrayHasKey('security_recommendations', $recommendations);
        $this->assertArrayHasKey('performance_optimizations', $recommendations);

        // Should recommend Laravel upgrade
        $allRecommendations = json_encode($recommendations);
        $this->assertStringContainsString('upgrade', strtolower($allRecommendations));
    }

    /**
     * Test error handling and graceful degradation
     */
    public function testHandlesErrorsGracefully(): void
    {
        // Mock file system errors
        File::shouldReceive('exists')->andThrow(new \Exception('Filesystem error'));
        File::shouldReceive('isDirectory')->andThrow(new \Exception('Filesystem error'));
        File::shouldReceive('get')->andThrow(new \Exception('Filesystem error'));

        $result = $this->analyzer->analyzeProject();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_metadata', $result);
        $this->assertArrayHasKey('errors', $result['analysis_metadata']);
        $this->assertNotEmpty($result['analysis_metadata']['errors']);
    }

    /**
     * Helper method to mock project structure
     */
    private function mockProjectStructure(array $config): void
    {
        // Mock Laravel check
        File::shouldReceive('exists')
            ->with(base_path('artisan'))
            ->andReturn($config['type'] === 'laravel');

        // Mock composer.json
        File::shouldReceive('get')
            ->with(base_path('composer.json'))
            ->andReturn(json_encode($config['composer'] ?? []));

        // Mock file existence
        File::shouldReceive('exists')
            ->andReturnUsing(function ($path) use ($config) {
                if ($path === base_path('artisan')) {
                    return $config['type'] === 'laravel';
                }

                $files = $config['files'] ?? [];
                foreach ($files as $file) {
                    if ($path === base_path($file)) {
                        return true;
                    }
                }
                return false;
            });

        // Mock directory existence
        File::shouldReceive('isDirectory')
            ->andReturnUsing(function ($path) use ($config) {
                $directories = array_merge(
                    ['app', 'app/Models'], // Basic Laravel dirs
                    $config['directories'] ?? []
                );

                foreach ($directories as $dir) {
                    if ($path === base_path($dir)) {
                        return true;
                    }
                }

                return false;
            });
    }
}
