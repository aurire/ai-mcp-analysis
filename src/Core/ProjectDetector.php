<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Core;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProjectDetector
{
    private ?array $composerData = null;
    private ?string $cachedProjectType = null;
    private array $customRules = [];

    /**
     * Default detection patterns
     */
    private array $detectionPatterns = [
        'ecommerce' => [
            'indicators' => [
                'files' => [
                    'app/Models/Cart.php',
                    'app/Models/Product.php',
                    'app/Models/Order.php'
                ],
                'directories' => [
                    'app/Services/Cart',
                    'app/Services/Payment',
                    'app/Services/Product'
                ],
                'composer_dependencies' => [
                    'stripe/stripe-php',
                    'paypal/paypal-checkout-sdk',
                    'laravel/cashier'
                ]
            ],
            'confidence_weight' => 1.0,
            'adapter_class' => 'ExampleECommerceAdapter'
        ],
        'api_service' => [
            'indicators' => [
                'files' => [
                    'routes/api.php'
                ],
                'directories' => [
                    'app/Http/Controllers/API',
                    'app/Http/Resources',
                    'app/Http/Requests/API'
                ],
                'composer_dependencies' => [
                    'laravel/sanctum',
                    'laravel/passport',
                    'tymon/jwt-auth'
                ]
            ],
            'confidence_weight' => 0.8,
            'adapter_class' => 'GenericLaravelAdapter'
        ],
        'admin_dashboard' => [
            'indicators' => [
                'files' => [
                    'app/Http/Controllers/AdminController.php'
                ],
                'directories' => [
                    'resources/views/admin',
                    'app/Http/Controllers/Admin',
                    'app/Http/Middleware/Admin'
                ],
                'composer_dependencies' => [
                    'laravel/nova',
                    'filament/filament',
                    'backpack/crud'
                ]
            ],
            'confidence_weight' => 1.0,
            'adapter_class' => 'GenericLaravelAdapter'
        ]
    ];

    /**
     * Detect the project type
     */
    public function detectProjectType(): string
    {
        if ($this->cachedProjectType !== null) {
            return $this->cachedProjectType;
        }

        // Check if it's a Laravel project
        if (!$this->isLaravelProject()) {
            return $this->cachedProjectType = 'unknown';
        }

        // Load composer data - if this fails, return unknown
        if (!$this->loadComposerData()) {
            return $this->cachedProjectType = 'unknown';
        }

        // Calculate scores for each pattern
        $scores = [];
        $allPatterns = array_merge($this->detectionPatterns, $this->customRules);

        foreach ($allPatterns as $type => $pattern) {
            $score = $this->calculatePatternScore($pattern);
            if ($score > 0) {
                $scores[$type] = $score;
            }
        }

        // Find the highest scoring pattern
        if (empty($scores)) {
            return $this->cachedProjectType = 'generic_laravel';
        }

        $maxScore = max($scores);
        $detectedType = array_search($maxScore, $scores);

        // Lower threshold for better detection
        if ($maxScore < 0.1) {
            $detectedType = 'generic_laravel';
        }

        return $this->cachedProjectType = $detectedType;
    }

    /**
     * Get project characteristics
     */
    public function getProjectCharacteristics(): array
    {
        $this->loadComposerData();

        return [
            'name' => $this->composerData['name'] ?? 'unknown',
            'description' => $this->composerData['description'] ?? '',
            'type' => $this->detectProjectType(),
            'features' => $this->detectFeatures(),
            'directories' => $this->getExistingDirectories(),
            'dependencies' => array_keys($this->composerData['require'] ?? []),
            'dev_dependencies' => array_keys($this->composerData['require-dev'] ?? []),
            'dependency_versions' => $this->composerData['require'] ?? [],
        ];
    }

    /**
     * Get detection confidence score
     */
    public function getDetectionConfidence(): float
    {
        $projectType = $this->detectProjectType();

        if ($projectType === 'unknown') {
            return 0.0;
        }

        if ($projectType === 'generic_laravel') {
            return 0.5;
        }

        $allPatterns = array_merge($this->detectionPatterns, $this->customRules);

        if (isset($allPatterns[$projectType])) {
            return $this->calculatePatternScore($allPatterns[$projectType]);
        }

        return 0.5;
    }

    /**
     * Get detection patterns
     */
    public function getDetectionPatterns(): array
    {
        return array_merge($this->detectionPatterns, $this->customRules);
    }

    /**
     * Add custom detection rules
     */
    public function addCustomDetectionRules(array $rules): void
    {
        $this->customRules = array_merge($this->customRules, $rules);
        // Clear cache when rules change
        $this->cachedProjectType = null;
    }

    /**
     * Get suggested adapter class
     */
    public function getSuggestedAdapterClass(): string
    {
        $projectType = $this->detectProjectType();
        $allPatterns = array_merge($this->detectionPatterns, $this->customRules);

        if (isset($allPatterns[$projectType]['adapter_class'])) {
            return $allPatterns[$projectType]['adapter_class'];
        }

        return 'GenericLaravelAdapter';
    }

    /**
     * Check if this is a Laravel project
     */
    private function isLaravelProject(): bool
    {
        try {
            return File::exists(base_path('artisan'));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load composer.json data
     */
    private function loadComposerData(): bool
    {
        if ($this->composerData !== null) {
            return true;
        }

        try {
            $composerContent = File::get(base_path('composer.json'));
            $this->composerData = json_decode($composerContent, true) ?? [];
            return true;
        } catch (\Exception $e) {
            $this->composerData = [];
            return false;
        }
    }

    /**
     * Calculate pattern matching score
     */
    private function calculatePatternScore(array $pattern): float
    {
        $score = 0.0;
        $totalChecks = 0;

        $indicators = $pattern['indicators'] ?? [];
        $weight = $pattern['confidence_weight'] ?? 0.5;

        // Check files
        if (isset($indicators['files'])) {
            foreach ($indicators['files'] as $file) {
                $totalChecks++;
                try {
                    if (File::exists(base_path($file))) {
                        $score += 1.0;
                    }
                } catch (\Exception $e) {
                    // Continue checking other files
                }
            }
        }

        // Check directories
        if (isset($indicators['directories'])) {
            foreach ($indicators['directories'] as $directory) {
                $totalChecks++;
                try {
                    if (File::isDirectory(base_path($directory))) {
                        $score += 1.0;
                    }
                } catch (\Exception $e) {
                    // Continue checking other directories
                }
            }
        }

        // Check composer dependencies
        if (isset($indicators['composer_dependencies'])) {
            $dependencies = array_merge(
                array_keys($this->composerData['require'] ?? []),
                array_keys($this->composerData['require-dev'] ?? [])
            );

            foreach ($indicators['composer_dependencies'] as $dependency) {
                $totalChecks++;
                if (in_array($dependency, $dependencies)) {
                    $score += 1.0;
                }
            }
        }

        // Calculate percentage score and apply weight
        if ($totalChecks === 0) {
            return 0.0;
        }

        $percentage = $score / $totalChecks;
        return $percentage * $weight;
    }

    /**
     * Detect project features
     */
    private function detectFeatures(): array
    {
        $features = [];

        try {
            // Check for API features
            if (File::exists(base_path('routes/api.php'))) {
                $features[] = 'api';
            }

            // Check for web features
            if (File::exists(base_path('routes/web.php'))) {
                $features[] = 'web';
            }

            // Check for database features
            if (File::isDirectory(base_path('database/migrations'))) {
                $features[] = 'database';
            }

            // Check for authentication
            $dependencies = array_keys($this->composerData['require'] ?? []);
            if (in_array('laravel/sanctum', $dependencies) ||
                in_array('laravel/passport', $dependencies)) {
                $features[] = 'authentication';
            }

            // Check for queues
            if (File::isDirectory(base_path('app/Jobs'))) {
                $features[] = 'queues';
            }
        } catch (\Exception $e) {
            // Return partial features if some checks fail
        }

        return $features;
    }

    /**
     * Get existing directories
     */
    private function getExistingDirectories(): array
    {
        $commonDirectories = [
            'app',
            'app/Models',
            'app/Http/Controllers',
            'app/Http/Controllers/API',
            'app/Services',
            'resources/views',
            'resources/views/admin',
            'database/migrations',
            'routes'
        ];

        $existing = [];
        foreach ($commonDirectories as $dir) {
            try {
                if (File::isDirectory(base_path($dir))) {
                    $existing[] = $dir;
                }
            } catch (\Exception $e) {
                // Continue checking other directories
            }
        }

        return $existing;
    }
}
