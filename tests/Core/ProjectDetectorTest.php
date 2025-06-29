<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests\Core;

use Aurire\AiMcpAnalysis\Core\ProjectDetector;
use Aurire\AiMcpAnalysis\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ProjectDetectorTest extends TestCase
{
    private ProjectDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ProjectDetector();
    }

    /**
     * Test detection of generic Laravel project
     */
    public function testDetectsGenericLaravelProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/generic-project',
                'require' => ['laravel/framework' => '^10.0']
            ],
            'directories' => ['app']
        ]);

        $result = $this->detector->detectProjectType();
        $this->assertEquals('generic_laravel', $result);
    }

    /**
     * Test detection of e-commerce project patterns
     */
    public function testDetectsECommerceProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/shop-project',
                'require' => ['laravel/framework' => '^10.0']
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

        $result = $this->detector->detectProjectType();
        $this->assertEquals('ecommerce', $result);
    }

    /**
     * Test detection of API project patterns
     */
    public function testDetectsApiProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/api-service',
                'require' => [
                    'laravel/framework' => '^10.0',
                    'laravel/sanctum' => '^3.0'
                ]
            ],
            'files' => ['routes/api.php'],
            'directories' => ['app/Http/Controllers/API']
        ]);

        $result = $this->detector->detectProjectType();
        $this->assertEquals('api_service', $result);
    }

    /**
     * Test detection of admin/dashboard project patterns
     */
    public function testDetectsAdminProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/admin-panel',
                'require' => ['laravel/framework' => '^10.0']
            ],
            'files' => ['app/Http/Controllers/AdminController.php'],
            'directories' => ['resources/views/admin']
        ]);

        $result = $this->detector->detectProjectType();
        $this->assertEquals('admin_dashboard', $result);
    }

    /**
     * Test getting project characteristics
     */
    public function testGetsProjectCharacteristics(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/test-project',
                'description' => 'A test Laravel project',
                'require' => [
                    'laravel/framework' => '^10.0',
                    'laravel/sanctum' => '^3.0'
                ]
            ],
            'files' => ['routes/api.php', 'routes/web.php'],
            'directories' => ['app/Http/Controllers/API'],
            'additional_directories' => [
                'database/migrations',
                'routes'
            ]
        ]);

        $characteristics = $this->detector->getProjectCharacteristics();

        $this->assertIsArray($characteristics);
        $this->assertArrayHasKey('name', $characteristics);
        $this->assertArrayHasKey('type', $characteristics);
        $this->assertArrayHasKey('features', $characteristics);
        $this->assertArrayHasKey('directories', $characteristics);
        $this->assertEquals('company/test-project', $characteristics['name']);
    }

    /**
     * Test detection confidence scoring
     */
    public function testDetectionConfidenceScoring(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/strong-indicators',
                'require' => ['laravel/framework' => '^10.0']
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

        $confidence = $this->detector->getDetectionConfidence();

        $this->assertIsFloat($confidence);
        $this->assertGreaterThanOrEqual(0.0, $confidence);
        $this->assertLessThanOrEqual(1.0, $confidence);
    }

    /**
     * Test detection of non-Laravel project
     */
    public function testDetectsNonLaravelProject(): void
    {
        $this->mockProjectStructure([
            'type' => 'non-laravel',
            'composer' => [
                'name' => 'company/non-laravel-project',
                'require' => ['symfony/framework' => '^6.0']
            ]
        ]);

        $result = $this->detector->detectProjectType();
        $this->assertEquals('unknown', $result);
    }

    /**
     * Test detection patterns configuration
     */
    public function testDetectionPatternsConfiguration(): void
    {
        $patterns = $this->detector->getDetectionPatterns();

        $this->assertIsArray($patterns);
        $this->assertArrayHasKey('ecommerce', $patterns);
        $this->assertArrayHasKey('api_service', $patterns);
        $this->assertArrayHasKey('admin_dashboard', $patterns);

        // Each pattern should have indicators
        foreach ($patterns as $pattern) {
            $this->assertArrayHasKey('indicators', $pattern);
            $this->assertIsArray($pattern['indicators']);
        }
    }

    /**
     * Test custom detection rules
     */
    public function testCustomDetectionRules(): void
    {
        $customRules = [
            'custom_type' => [
                'indicators' => [
                    'files' => ['app/Models/CustomModel.php'],
                    'directories' => ['app/Services/Custom'],
                    'composer_dependencies' => ['custom/package']
                ],
                'confidence_weight' => 0.8
            ]
        ];

        $this->detector->addCustomDetectionRules($customRules);
        $patterns = $this->detector->getDetectionPatterns();

        $this->assertArrayHasKey('custom_type', $patterns);
    }

    /**
     * Test detection results caching
     */
    public function testDetectionResultsCaching(): void
    {
        // Mock for first call only
        File::shouldReceive('exists')
            ->with(base_path('artisan'))
            ->once()
            ->andReturn(true);

        File::shouldReceive('get')
            ->with(base_path('composer.json'))
            ->once()
            ->andReturn(json_encode([
                'name' => 'company/cached-project',
                'require' => ['laravel/framework' => '^10.0']
            ]));

        // These will be called during the pattern scoring, but only once due to caching
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('isDirectory')->andReturn(false);

        // First call should trigger detection
        $result1 = $this->detector->detectProjectType();

        // Second call should use cached result (no additional File calls)
        $result2 = $this->detector->detectProjectType();

        $this->assertEquals($result1, $result2);
        $this->assertEquals('generic_laravel', $result1);
    }

    /**
     * Test detection with missing composer.json
     */
    public function testDetectionWithMissingComposerJson(): void
    {
        File::shouldReceive('exists')
            ->with(base_path('artisan'))
            ->andReturn(true);

        File::shouldReceive('get')
            ->with(base_path('composer.json'))
            ->andThrow(new \Exception('File not found'));

        // Mock minimal file system calls for error case
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('isDirectory')->andReturn(false);

        $result = $this->detector->detectProjectType();
        $this->assertEquals('unknown', $result);
    }

    /**
     * Test getting suggested adapter class
     */
    public function testGetsSuggestedAdapterClass(): void
    {
        $this->mockProjectStructure([
            'type' => 'laravel',
            'composer' => [
                'name' => 'company/ecommerce-project',
                'require' => ['laravel/framework' => '^10.0']
            ],
            'files' => [
                'app/Models/Cart.php',
                'app/Models/Product.php'
            ],
            'directories' => ['app/Models']
        ]);

        $adapterClass = $this->detector->getSuggestedAdapterClass();
        $this->assertEquals('ExampleECommerceAdapter', $adapterClass);
    }

    /**
     * Enhanced helper method to mock project structure
     */
    private function mockProjectStructure(array $config): void
    {
        $expectSingleCall = $config['expect_single_call'] ?? false;
        $callExpectation = $expectSingleCall ? 'once' : 'andReturn';

        // Mock Laravel check
        if ($expectSingleCall) {
            File::shouldReceive('exists')
                ->with(base_path('artisan'))
                ->once()
                ->andReturn($config['type'] === 'laravel');
        } else {
            File::shouldReceive('exists')
                ->with(base_path('artisan'))
                ->andReturn($config['type'] === 'laravel');
        }

        // Mock composer.json
        if ($expectSingleCall) {
            File::shouldReceive('get')
                ->with(base_path('composer.json'))
                ->once()
                ->andReturn(json_encode($config['composer'] ?? []));
        } else {
            File::shouldReceive('get')
                ->with(base_path('composer.json'))
                ->andReturn(json_encode($config['composer'] ?? []));
        }

        // Mock file existence with comprehensive coverage
        File::shouldReceive('exists')
            ->andReturnUsing(function ($path) use ($config) {
                // Handle artisan check
                if ($path === base_path('artisan')) {
                    return $config['type'] === 'laravel';
                }

                // Handle configured files
                $files = $config['files'] ?? [];
                foreach ($files as $file) {
                    if ($path === base_path($file)) {
                        return true;
                    }
                }

                // Handle common Laravel files that might be checked
                $commonFiles = [
                    'routes/web.php',
                    'routes/api.php',
                    'app/Jobs',
                ];

                foreach ($commonFiles as $commonFile) {
                    if ($path === base_path($commonFile)) {
                        return in_array($commonFile, $files);
                    }
                }

                return false;
            });

        // Mock directory existence with comprehensive coverage
        $mockDirectoryCall = function ($path) use ($config) {
            // Basic Laravel directories that should always exist
            $basicLaravelDirs = ['app', 'app/Models'];

            // Configured directories
            $configuredDirs = array_merge(
                $basicLaravelDirs,
                $config['directories'] ?? [],
                $config['additional_directories'] ?? []
            );

            foreach ($configuredDirs as $dir) {
                if ($path === base_path($dir)) {
                    return true;
                }
            }

            // Common directories that might be checked during feature detection
            $commonDirectories = [
                'database/migrations',
                'routes',
                'resources/views',
                'app/Http/Controllers',
                'app/Services'
            ];

            foreach ($commonDirectories as $commonDir) {
                if ($path === base_path($commonDir)) {
                    return in_array($commonDir, $configuredDirs);
                }
            }

            return false;
        };

        if ($expectSingleCall) {
            File::shouldReceive('isDirectory')
                ->with(base_path('app'))
                ->once()
                ->andReturn(true);

            // For other directory calls during caching test
            File::shouldReceive('isDirectory')
                ->andReturnUsing($mockDirectoryCall);
        } else {
            File::shouldReceive('isDirectory')
                ->andReturnUsing($mockDirectoryCall);
        }
    }
}
