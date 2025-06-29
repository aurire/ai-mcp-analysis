<?php
namespace Aurire\AiMcpAnalysis;

use Aurire\AiMcpAnalysis\Commands\AiMcpAnalysisCommand;
use Aurire\AiMcpAnalysis\Core\ProjectDetector;
use Illuminate\Support\ServiceProvider;

class AiMcpAnalysisServiceProvider extends ServiceProvider
{
    /**
     * Register AI MCP Analysis service
     *
     * @return void
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ai-mcp-analysis.php',
            'ai-mcp-analysis'
        );

        $this->app->singleton('ai-mcp-analysis', function ($app) {
            return new Services\AiMcpAnalysisService();
        });
        $this->app->singleton(Core\ProjectDetector::class);
        $this->app->singleton(Core\ToolRegistry::class);
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/ai-mcp-analysis.php' => config_path('ai-mcp-analysis.php'),
        ], 'ai-mcp-analysis-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AiMcpAnalysisCommand::class,
            ]);
        }

        if (config('ai-mcp-analysis.auto_detect_project')) {
            $this->registerProjectAdapter();
        }
    }

    protected function registerProjectAdapter(): void
    {
        /** @var ProjectDetector $detector */
        $detector = $this->app->make(Core\ProjectDetector::class);
        $projectType = $detector->detectProjectType();

        // Register appropriate adapter based on detection
        // Implementation coming next...
    }
}
