<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests\Analysis\External;

use Aurire\AiMcpAnalysis\Analysis\External\PHPStanIntegrator;
use Aurire\AiMcpAnalysis\Tests\TestCase;
use Illuminate\Support\Facades\Process;

class PHPStanIntegratorTest extends TestCase
{
    private PHPStanIntegrator $integrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrator = new PHPStanIntegrator();
    }

    /**
     * Test PHPStan availability check
     */
    public function testChecksPHPStanAvailability(): void
    {
        // Mock successful PHPStan check
        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', '--version'])
            ->andReturn($this->createMockProcessResult(true, 'PHPStan 1.10.0'));

        $result = $this->integrator->isAvailable();
        $this->assertTrue($result);
    }

    /**
     * Test PHPStan unavailable scenario
     */
    public function testHandlesPHPStanUnavailable(): void
    {
        // Mock failed PHPStan check
        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', '--version'])
            ->andReturn($this->createMockProcessResult(false, 'Command not found'));

        $result = $this->integrator->isAvailable();
        $this->assertFalse($result);
    }

    /**
     * Test basic PHPStan analysis
     */
    public function testRunsBasicAnalysis(): void
    {
        $mockOutput = json_encode([
            'totals' => [
                'errors' => 5,
                'file_errors' => 3
            ],
            'files' => [
                'app/Models/User.php' => [
                    'errors' => 2,
                    'messages' => [
                        [
                            'message' => 'Property App\Models\User::$name is never read, only written.',
                            'line' => 15,
                            'ignorable' => true
                        ],
                        [
                            'message' => 'Method App\Models\User::getName() has no return type specified.',
                            'line' => 25,
                            'ignorable' => false
                        ]
                    ]
                ]
            ]
        ]);

        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', 'analyse', '--format=json', '--no-progress', 'src/'])
            ->andReturn($this->createMockProcessResult(true, $mockOutput));

        $result = $this->integrator->analyze('src/'); // Use 'src/' which exists

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('files_analyzed', $result);
        $this->assertArrayHasKey('errors_by_file', $result);
        $this->assertArrayHasKey('categorized_issues', $result);

        $this->assertEquals(5, $result['summary']['total_errors']);
        $this->assertEquals(3, $result['summary']['files_with_errors']);
    }

    /**
     * Test analysis with specific level
     */
    public function testRunsAnalysisWithLevel(): void
    {
        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', 'analyse', '--format=json', '--no-progress', '--level=8', 'app/Models/'])
            ->andReturn($this->createMockProcessResult(true, '{"totals":{"errors":0}}'));

        $result = $this->integrator->analyze('app/Models/', ['level' => 8]);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['summary']['total_errors']);
    }

    /**
     * Test analysis with memory limit
     */
    public function testRunsAnalysisWithMemoryLimit(): void
    {
        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', 'analyse', '--format=json', '--no-progress', '--memory-limit=1G', 'app/'])
            ->andReturn($this->createMockProcessResult(true, '{"totals":{"errors":0}}'));

        $result = $this->integrator->analyze('app/', ['memory_limit' => '1G']);
        $this->assertIsArray($result);
    }

    /**
     * Test error categorization
     */
    public function testCategorizesErrors(): void
    {
        $mockOutput = json_encode([
            'totals' => ['errors' => 4],
            'files' => [
                'app/Models/User.php' => [
                    'messages' => [
                        ['message' => 'Property is never read, only written', 'line' => 15],
                        ['message' => 'Method has no return type specified', 'line' => 25],
                        ['message' => 'Cannot access offset on mixed', 'line' => 35],
                        ['message' => 'Dead catch - Exception is never thrown', 'line' => 45]
                    ]
                ]
            ]
        ]);

        Process::shouldReceive('run')
            ->andReturn($this->createMockProcessResult(true, $mockOutput));

        $result = $this->integrator->analyze('app/');

        $categories = $result['categorized_issues'];
        $this->assertArrayHasKey('type_issues', $categories);
        $this->assertArrayHasKey('unused_code', $categories);
        $this->assertArrayHasKey('dead_code', $categories);
        $this->assertArrayHasKey('other', $categories);
    }

    /**
     * Test getting improvement suggestions
     */
    public function testGeneratesImprovementSuggestions(): void
    {
        $mockOutput = json_encode([
            'totals' => ['errors' => 10],
            'files' => [
                'app/Models/User.php' => [
                    'messages' => [
                        ['message' => 'Method has no return type specified', 'line' => 25],
                        ['message' => 'Parameter has no type specified', 'line' => 30]
                    ]
                ]
            ]
        ]);

        Process::shouldReceive('run')
            ->andReturn($this->createMockProcessResult(true, $mockOutput));

        $result = $this->integrator->analyze('app/');
        $suggestions = $this->integrator->generateSuggestions($result);

        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('priority_fixes', $suggestions);
        $this->assertArrayHasKey('quick_wins', $suggestions);
        $this->assertArrayHasKey('long_term_improvements', $suggestions);
    }

    /**
     * Test analysis performance tracking
     */
    public function testTracksAnalysisPerformance(): void
    {
        Process::shouldReceive('run')
            ->andReturn($this->createMockProcessResult(true, '{"totals":{"errors":0}}'));

        $startTime = microtime(true);
        $result = $this->integrator->analyze('app/');
        $endTime = microtime(true);

        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('analysis_time_ms', $result['performance']);
        $this->assertGreaterThan(0, $result['performance']['analysis_time_ms']);
        $this->assertLessThan(($endTime - $startTime) * 1000 + 100, $result['performance']['analysis_time_ms']);
    }

    /**
     * Test analysis with config file
     */
    public function testUsesConfigFile(): void
    {
        Process::shouldReceive('run')
            ->with(['vendor/bin/phpstan', 'analyse', '--format=json', '--no-progress', '--configuration=phpstan.neon', 'app/'])
            ->andReturn($this->createMockProcessResult(true, '{"totals":{"errors":0}}'));

        $result = $this->integrator->analyze('app/', ['config' => 'phpstan.neon']);
        $this->assertIsArray($result);
    }

    /**
     * Test error handling for invalid paths
     */
    public function testHandlesInvalidPaths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path does not exist or is not readable');

        $this->integrator->analyze('/nonexistent/path');
    }

    /**
     * Test error handling for PHPStan failures
     */
    public function testHandlesPHPStanFailures(): void
    {
        Process::shouldReceive('run')
            ->andReturn($this->createMockProcessResult(false, 'PHPStan analysis failed'));

        $result = $this->integrator->analyze('app/');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('PHPStan analysis failed', $result['error']);
    }

    /**
     * Test integration with project analyzer
     */
    public function testIntegratesWithProjectAnalyzer(): void
    {
        Process::shouldReceive('run')
            ->andReturn($this->createMockProcessResult(true, '{"totals":{"errors":2}}'));

        $analysisResult = [
            'project_info' => ['type' => 'api_service'],
            'structure_analysis' => []
        ];

        $result = $this->integrator->enhanceProjectAnalysis($analysisResult);

        $this->assertArrayHasKey('external_analysis', $result);
        $this->assertArrayHasKey('phpstan_results', $result['external_analysis']);
        $this->assertArrayHasKey('integration_insights', $result);
    }

    /**
     * Helper method to create mock process results
     */
    private function createMockProcessResult(bool $successful, string $output): object
    {
        return new class($successful, $output) {
            public function __construct(private bool $successful, private string $output) {}
            public function successful(): bool { return $this->successful; }
            public function output(): string { return $this->output; }
            public function errorOutput(): string { return $this->successful ? '' : $this->output; }
        };
    }
}
