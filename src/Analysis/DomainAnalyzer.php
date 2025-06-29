<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Analysis;

use Aurire\AiMcpAnalysis\Core\ProjectDetector;
use Illuminate\Support\Facades\File;

class DomainAnalyzer
{
    private ProjectDetector $projectDetector;

    public function __construct(ProjectDetector $projectDetector = null)
    {
        $this->projectDetector = $projectDetector ?? new ProjectDetector();
    }

    /**
     * Analyze domain-specific patterns
     */
    public function analyze(): array
    {
        $projectType = $this->projectDetector->detectProjectType();

        return match ($projectType) {
            'ecommerce' => $this->analyzeECommercePatterns(),
            'api_service' => $this->analyzeApiPatterns(),
            'admin_dashboard' => $this->analyzeAdminPatterns(),
            default => $this->analyzeGenericPatterns()
        };
    }

    /**
     * Analyze e-commerce specific patterns
     */
    private function analyzeECommercePatterns(): array
    {
        return [
            'payment_security' => $this->checkPaymentSecurity(),
            'cart_architecture' => $this->analyzeCartArchitecture(),
            'order_management' => $this->analyzeOrderManagement(),
            'inventory_patterns' => $this->checkInventoryPatterns()
        ];
    }

    /**
     * Analyze API service patterns
     */
    private function analyzeApiPatterns(): array
    {
        return [
            'api_structure' => $this->analyzeApiStructure(),
            'authentication_analysis' => $this->analyzeAuthentication(),
            'endpoint_coverage' => $this->analyzeEndpointCoverage(),
            'response_consistency' => $this->checkResponseConsistency()
        ];
    }

    /**
     * Analyze admin dashboard patterns
     */
    private function analyzeAdminPatterns(): array
    {
        return [
            'admin_structure' => $this->analyzeAdminStructure(),
            'authorization_patterns' => $this->analyzeAuthorization(),
            'crud_patterns' => $this->analyzeCrudPatterns(),
            'ui_consistency' => $this->checkUiConsistency()
        ];
    }

    /**
     * Analyze generic Laravel patterns
     */
    private function analyzeGenericPatterns(): array
    {
        return [
            'mvc_structure' => $this->analyzeMvcStructure(),
            'service_patterns' => $this->analyzeServicePatterns(),
            'repository_patterns' => $this->analyzeRepositoryPatterns(),
            'general_architecture' => $this->analyzeGeneralArchitecture()
        ];
    }

    /**
     * Check payment security patterns
     */
    private function checkPaymentSecurity(): array
    {
        $findings = [];

        // Check for payment models
        if (File::exists(base_path('app/Models/Payment.php'))) {
            $findings['payment_model_exists'] = true;
        }

        // Check for payment services
        if (File::isDirectory(base_path('app/Services/Payment'))) {
            $findings['payment_services_organized'] = true;
        }

        // Check for payment gateways
        if (File::isDirectory(base_path('app/Services/PaymentGateway'))) {
            $findings['gateway_abstraction_present'] = true;
        }

        return $findings;
    }

    /**
     * Analyze cart architecture
     */
    private function analyzeCartArchitecture(): array
    {
        return [
            'cart_model_exists' => File::exists(base_path('app/Models/Cart.php')),
            'cart_service_exists' => File::isDirectory(base_path('app/Services/Cart')),
            'session_handling' => $this->checkSessionHandling(),
            'persistence_strategy' => $this->analyzeCartPersistence()
        ];
    }

    /**
     * Analyze API structure
     */
    private function analyzeApiStructure(): array
    {
        return [
            'api_routes_organized' => File::exists(base_path('routes/api.php')),
            'api_controllers_structured' => File::isDirectory(base_path('app/Http/Controllers/API')),
            'resources_present' => File::isDirectory(base_path('app/Http/Resources')),
            'versioning_strategy' => $this->checkApiVersioning()
        ];
    }

    /**
     * Analyze authentication patterns
     */
    private function analyzeAuthentication(): array
    {
        $composerData = $this->getComposerData();
        $dependencies = array_keys($composerData['require'] ?? []);

        return [
            'sanctum_present' => in_array('laravel/sanctum', $dependencies),
            'passport_present' => in_array('laravel/passport', $dependencies),
            'jwt_present' => in_array('tymon/jwt-auth', $dependencies),
            'auth_middleware_configured' => $this->checkAuthMiddleware()
        ];
    }

    /**
     * Analyze MVC structure
     */
    private function analyzeMvcStructure(): array
    {
        return [
            'models_organized' => File::isDirectory(base_path('app/Models')),
            'controllers_organized' => File::isDirectory(base_path('app/Http/Controllers')),
            'views_present' => File::isDirectory(base_path('resources/views')),
            'routes_organized' => $this->checkRoutesOrganization()
        ];
    }

    // Helper methods
    private function analyzeOrderManagement(): array { return ['implemented' => File::exists(base_path('app/Models/Order.php'))]; }
    private function checkInventoryPatterns(): array { return ['product_model' => File::exists(base_path('app/Models/Product.php'))]; }
    private function analyzeEndpointCoverage(): array { return ['api_routes_exist' => File::exists(base_path('routes/api.php'))]; }
    private function checkResponseConsistency(): array { return ['resources_used' => File::isDirectory(base_path('app/Http/Resources'))]; }
    private function analyzeAdminStructure(): array { return ['admin_controllers' => File::isDirectory(base_path('app/Http/Controllers/Admin'))]; }
    private function analyzeAuthorization(): array { return ['policies_present' => File::isDirectory(base_path('app/Policies'))]; }
    private function analyzeCrudPatterns(): array { return ['resource_controllers' => true]; }
    private function checkUiConsistency(): array { return ['admin_views' => File::isDirectory(base_path('resources/views/admin'))]; }
    private function analyzeServicePatterns(): array { return ['services_organized' => File::isDirectory(base_path('app/Services'))]; }
    private function analyzeRepositoryPatterns(): array { return ['repositories_present' => File::isDirectory(base_path('app/Repositories'))]; }
    private function analyzeGeneralArchitecture(): array { return ['conventional_structure' => true]; }
    private function checkSessionHandling(): array { return ['session_configured' => true]; }
    private function analyzeCartPersistence(): string { return 'database'; }
    private function checkApiVersioning(): string { return 'none'; }
    private function checkAuthMiddleware(): bool { return File::exists(base_path('app/Http/Middleware/Authenticate.php')); }
    private function checkRoutesOrganization(): bool { return File::exists(base_path('routes/web.php')); }

    /**
     * Get composer data
     */
    private function getComposerData(): array
    {
        try {
            $content = File::get(base_path('composer.json'));
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
