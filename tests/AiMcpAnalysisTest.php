<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests;

use Aurire\AiMcpAnalysis\Facades\AiMcpAnalysis;
use Illuminate\Contracts\Container\BindingResolutionException;

class AiMcpAnalysisTest extends TestCase
{
    /**
     * @return void
     */
    public function testItCanGetLibraryName(): void
    {
        $this->assertEquals('AiMcpAnalysis', AiMcpAnalysis::getName());
    }

    /**
     * @return void
     */
    public function testArtisanCommand(): void
    {
        $this->artisan('ai-mcp-analysis:info')
            ->expectsOutput('Library: AiMcpAnalysis')
            ->expectsOutput('Version: 1.0.0')
            ->assertExitCode(0);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testCommandIsRegistered(): void
    {
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        $this->assertArrayHasKey('ai-mcp-analysis:info', $commands);
    }
}
