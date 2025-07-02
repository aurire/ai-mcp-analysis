<?php

declare(strict_types=1);

namespace AuriRe\AiMcpAnalysis\Adapters;

/**
 * GenericLaravelAdapter - Default Laravel project support
 * 
 * Provides standard Laravel project analysis without domain-specific expertise.
 * Serves as the fallback adapter and example for creating new adapters.
 */
class GenericLaravelAdapter extends AbstractProjectAdapter
{
    public function getProjectType(): string
    {
        return 'generic_laravel';
    }

    public function getAllowedPaths(): array
    {
        return [
            'app/',
            'config/',
            'routes/',
            'database/migrations/',
            'database/seeders/',
            'resources/views/',
            'resources/js/',
            'resources/css/',
            'tests/',
            'storage/framework/',
        ];
    }

    public function getRestrictedFiles(): array
    {
        return [
            '.env',
            '.env.local',
            '.env.production',
            'config/database.php',
            'config/services.php',
            'config/auth.php',
            'storage/oauth-private.key',
            'storage/oauth-public.key',
        ];
    }

    public function getDomainPatterns(): array
    {
        return [
            'mvc_controller' => [
                'class_name_contains' => 'Controller',
                'extends' => ['Controller', 'BaseController'],
                'methods' => ['index', 'show', 'store', 'update', 'destroy']
            ],
            'eloquent_model' => [
                'class_name_contains' => 'Model',
                'extends' => ['Model', 'Authenticatable'],
                'properties' => ['fillable', 'guarded', 'casts']
            ],
            'service_class' => [
                'class_name_contains' => 'Service',
                'methods' => ['handle', 'execute', 'process']
            ],
            'repository_pattern' => [
                'class_name_contains' => 'Repository',
                'methods' => ['find', 'create', 'update', 'delete']
            ],
            'middleware' => [
                'class_name_contains' => 'Middleware',
                'methods' => ['handle'],
                'implements' => ['MiddlewareInterface']
            ],
            'form_request' => [
                'extends' => ['FormRequest'],
                'methods' => ['rules', 'authorize']
            ],
            'job_class' => [
                'implements' => ['ShouldQueue'],
                'methods' => ['handle']
            ],
            'event_class' => [
                'class_name_contains' => 'Event'
            ],
            'listener_class' => [
                'class_name_contains' => 'Listener',
                'methods' => ['handle']
            ]
        ];
    }

    public function getCompatibilityScore(string $projectPath): float
    {
        $score = 0.0;
        
        // Check for standard Laravel structure
        $laravelPaths = [
            'app/Http/Controllers',
            'app/Models',
            'config/app.php',
            'routes/web.php',
            'artisan'
        ];
        
        foreach ($laravelPaths as $path) {
            if (file_exists(base_path($path))) {
                $score += 0.15;
            }
        }
        
        // Check for composer.json with Laravel
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            if (isset($composer['require']['laravel/framework'])) {
                $score += 0.25;
            }
        }
        
        return min(1.0, $score);
    }

    /**
     * Categorize file from Laravel perspective
     */
    protected function categorizeDomainFile(string $filePath): string
    {
        // Laravel-specific categorization
        if (str_contains($filePath, '/Http/Controllers/')) {
            if (str_contains($filePath, 'Controller.php')) return 'HttpController';
            if (str_contains($filePath, 'Resource.php')) return 'ApiResource';
        }
        
        if (str_contains($filePath, '/Http/Middleware/')) {
            return 'HttpMiddleware';
        }
        
        if (str_contains($filePath, '/Http/Requests/')) {
            return 'FormRequest';
        }
        
        if (str_contains($filePath, '/Models/')) {
            return 'EloquentModel';
        }
        
        if (str_contains($filePath, '/Jobs/')) {
            return 'QueueableJob';
        }
        
        if (str_contains($filePath, '/Events/')) {
            return 'LaravelEvent';
        }
        
        if (str_contains($filePath, '/Listeners/')) {
            return 'EventListener';
        }
        
        if (str_contains($filePath, '/Providers/')) {
            return 'ServiceProvider';
        }
        
        if (str_contains($filePath, '/Console/Commands/')) {
            return 'ArtisanCommand';
        }
        
        if (str_contains($filePath, '/Observers/')) {
            return 'ModelObserver';
        }
        
        if (str_contains($filePath, '/Policies/')) {
            return 'AuthorizationPolicy';
        }
        
        if (str_contains($filePath, '/Rules/')) {
            return 'ValidationRule';
        }
        
        return parent::categorizeDomainFile($filePath);
    }

    /**
     * Calculate importance score with Laravel conventions
     */
    protected function calculateImportanceScore(array $file): float
    {
        $score = parent::calculateImportanceScore($file);
        
        // Laravel-specific importance scoring
        $criticalCategories = [
            'HttpController' => 0.8,
            'EloquentModel' => 0.8,
            'ServiceProvider' => 0.7,
            'HttpMiddleware' => 0.7,
            'FormRequest' => 0.6,
            'QueueableJob' => 0.6
        ];
        
        $domainCategory = $file['domain_category'] ?? '';
        if (isset($criticalCategories[$domainCategory])) {
            $score = max($score, $criticalCategories[$domainCategory]);
        }
        
        return $score;
    }

    /**
     * Get Laravel-specific recommendations
     */
    protected function getDomainRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // Check for Laravel best practices
        if ($this->isController($analysis)) {
            $recommendations = array_merge($recommendations, $this->checkControllerBestPractices($analysis));
        }
        
        if ($this->isModel($analysis)) {
            $recommendations = array_merge($recommendations, $this->checkModelBestPractices($analysis));
        }
        
        if ($this->isFormRequest($analysis)) {
            $recommendations = array_merge($recommendations, $this->checkFormRequestBestPractices($analysis));
        }
        
        return $recommendations;
    }

    /**
     * Check if analysis represents a controller
     */
    private function isController(array $analysis): bool
    {
        foreach ($analysis['analysis']['classes'] ?? [] as $class) {
            if (str_contains($class['name'], 'Controller')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if analysis represents a model
     */
    private function isModel(array $analysis): bool
    {
        foreach ($analysis['analysis']['classes'] ?? [] as $class) {
            if ($class['extends'] === 'Model' || str_contains($analysis['path'] ?? '', '/Models/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if analysis represents a form request
     */
    private function isFormRequest(array $analysis): bool
    {
        foreach ($analysis['analysis']['classes'] ?? [] as $class) {
            if ($class['extends'] === 'FormRequest') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check controller best practices
     */
    private function checkControllerBestPractices(array $analysis): array
    {
        $recommendations = [];
        
        // Check for proper resource controller methods
        $resourceMethods = ['index', 'show', 'store', 'update', 'destroy'];
        $foundMethods = [];
        
        foreach ($analysis['analysis']['methods'] ?? [] as $method) {
            if (in_array($method['name'], $resourceMethods)) {
                $foundMethods[] = $method['name'];
            }
        }
        
        if (count($foundMethods) >= 3 && count($foundMethods) < 5) {
            $missing = array_diff($resourceMethods, $foundMethods);
            $recommendations[] = [
                'type' => 'best_practice',
                'priority' => 'low',
                'message' => 'Consider implementing missing resource methods: ' . implode(', ', $missing),
                'file' => $analysis['path'] ?? 'unknown'
            ];
        }
        
        // Check for dependency injection
        $hasConstructor = false;
        foreach ($analysis['analysis']['methods'] ?? [] as $method) {
            if ($method['name'] === '__construct') {
                $hasConstructor = true;
                break;
            }
        }
        
        if (!$hasConstructor && count($analysis['analysis']['methods'] ?? []) > 2) {
            $recommendations[] = [
                'type' => 'architecture',
                'priority' => 'medium',
                'message' => 'Consider using dependency injection for better testability',
                'file' => $analysis['path'] ?? 'unknown'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Check model best practices
     */
    private function checkModelBestPractices(array $analysis): array
    {
        $recommendations = [];
        
        // Check for fillable/guarded properties
        // This would require more sophisticated content analysis
        // For now, we'll provide a general recommendation
        
        $recommendations[] = [
            'type' => 'security',
            'priority' => 'medium',
            'message' => 'Ensure fillable or guarded properties are properly configured',
            'file' => $analysis['path'] ?? 'unknown'
        ];
        
        return $recommendations;
    }

    /**
     * Check form request best practices
     */
    private function checkFormRequestBestPractices(array $analysis): array
    {
        $recommendations = [];
        
        $requiredMethods = ['rules', 'authorize'];
        $foundMethods = [];
        
        foreach ($analysis['analysis']['methods'] ?? [] as $method) {
            if (in_array($method['name'], $requiredMethods)) {
                $foundMethods[] = $method['name'];
            }
        }
        
        $missing = array_diff($requiredMethods, $foundMethods);
        if (!empty($missing)) {
            $recommendations[] = [
                'type' => 'implementation',
                'priority' => 'high',
                'message' => 'Form request missing required methods: ' . implode(', ', $missing),
                'file' => $analysis['path'] ?? 'unknown'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Generate Laravel-specific project summary
     */
    public function generateLaravelSummary(): array
    {
        $baseSummary = $this->generateProjectSummary();
        
        // Add Laravel-specific metrics
        $laravelMetrics = [
            'controllers' => $this->countFilesByPattern('*Controller.php'),
            'models' => $this->countFilesByPattern('*/Models/*.php'),
            'middleware' => $this->countFilesByPattern('*/Middleware/*.php'),
            'jobs' => $this->countFilesByPattern('*/Jobs/*.php'),
            'events' => $this->countFilesByPattern('*/Events/*.php'),
            'listeners' => $this->countFilesByPattern('*/Listeners/*.php'),
            'providers' => $this->countFilesByPattern('*/Providers/*.php'),
        ];
        
        return array_merge($baseSummary, [
            'laravel_metrics' => $laravelMetrics,
            'framework' => 'Laravel',
            'architecture_pattern' => 'MVC'
        ]);
    }

    /**
     * Count files matching a pattern
     */
    private function countFilesByPattern(string $pattern): int
    {
        $files = glob(base_path('app/' . $pattern));
        return count($files);
    }
}

