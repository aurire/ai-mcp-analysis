<?php
namespace Aurire\AiMcpAnalysis;

use Aurire\AiMcpAnalysis\Commands\AiMcpAnalysisCommand;
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
    }
}
