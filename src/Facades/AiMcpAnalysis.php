<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Facades;

use Illuminate\Support\Facades\Facade;

class AiMcpAnalysis extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-mcp-analysis';
    }
}
