<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Commands;

use Aurire\AiMcpAnalysis\Facades\AiMcpAnalysis;
use Illuminate\Console\Command;

class AiMcpAnalysisCommand extends Command
{
    /**
     * @var string $signature
     */
    protected $signature = 'ai-mcp-analysis:info';

    /**
     * @var string $description
     */
    protected $description = 'AI MCP Analysis Library command information';

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->info('Library: ' . AiMcpAnalysis::getName());
        $this->info('Version: ' . AiMcpAnalysis::getVersion());

        return Command::SUCCESS;
    }
}
