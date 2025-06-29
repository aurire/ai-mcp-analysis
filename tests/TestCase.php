<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests;

use Aurire\AiMcpAnalysis\AiMcpAnalysisServiceProvider;
use Aurire\AiMcpAnalysis\Facades\AiMcpAnalysis;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AiMcpAnalysisServiceProvider::class,
        ];
    }

    /**
     * @param $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'AiMcpAnalysis' => AiMcpAnalysis::class,
        ];
    }

    /**
     * @param $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }
}
