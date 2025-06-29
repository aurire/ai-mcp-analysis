<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Adapters;

class ExampleECommerceAdapter
{
    /**
     * @return string
     */
    public function getProjectType(): string
    {
        return 'ecommerce';
    }

    /**
     * @return array
     */
    public function getDomainPatterns(): array
    {
        return ['cart', 'checkout', 'payment', 'inventory'];
    }

    /**
     * @return array
     */
    public function getAnalysisConfig(): array
    {
        return [
            'focus_areas' => ['payment_security', 'cart_persistence'],
            'critical_paths' => ['app/Services/Cart', 'app/Services/Payment'],
        ];
    }
}
