<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Services;

class AiMcpAnalysisService
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'AiMcpAnalysis';
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Just a first test method
     *
     * @param array $data
     * @return array
     */
    public function processData(array $data): array
    {
        return array_map('strtoupper', $data);
    }

    /**
     * Another example test method
     *
     * @param string $message
     * @return string
     */
    public function formatMessage(string $message): string
    {
        return '[AiMcpAnalysis] ' . $message;
    }
}
