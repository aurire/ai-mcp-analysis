<?php

declare(strict_types=1);

namespace AuriRe\AiMcpAnalysis\Core;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

/**
 * ParameterNormalizer - Handle MCP parameter quirks and type conversion
 * 
 * Based on patterns observed in SimpleEnhancedHPaymentsAnalysisTool parameter handling.
 * Solves common MCP issues like wrapped arrays and string 'null' values.
 */
class ParameterNormalizer
{
    /**
     * Normalize parameters from MCP to expected format
     */
    public static function normalizeParameters(array $parameters): array
    {
        $normalized = [];
        
        foreach ($parameters as $key => $value) {
            $normalized[$key] = self::normalizeValue($value);
        }
        
        return $normalized;
    }

    /**
     * Normalize a single parameter value
     */
    public static function normalizeValue(mixed $value): mixed
    {
        // Handle null string values from MCP
        if ($value === 'null' || $value === 'undefined') {
            return null;
        }
        
        // Handle MCP wrapped arrays: [{data}] -> {data}
        if (is_array($value) && count($value) === 1 && is_int(array_key_first($value))) {
            Log::debug('Unwrapping MCP array parameter', [
                'original' => $value,
                'unwrapped' => $value[0]
            ]);
            return self::normalizeValue($value[0]);
        }
        
        // Handle JSON strings
        if (is_string($value) && self::isJsonString($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return self::normalizeValue($decoded);
            }
        }
        
        // Handle boolean strings
        if (is_string($value)) {
            if (strtolower($value) === 'true') {
                return true;
            }
            if (strtolower($value) === 'false') {
                return false;
            }
        }
        
        // Handle numeric strings
        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
        
        return $value;
    }

    /**
     * Normalize update data specifically (handles complex MCP patterns)
     */
    public static function normalizeUpdateData(mixed $updatedData): array
    {
        // Handle JSON string input
        if (is_string($updatedData)) {
            $decoded = json_decode($updatedData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $updatedData = $decoded;
            }
        }

        // Handle wrapped array format from MCP
        if (is_array($updatedData) && count($updatedData) === 1 && is_int(array_key_first($updatedData))) {
            Log::debug('Unwrapping MCP update data array');
            return self::normalizeUpdateData($updatedData[0]);
        }

        // Handle direct array format
        if (is_array($updatedData)) {
            return self::normalizeParameters($updatedData);
        }

        throw new InvalidArgumentException('updatedData must be an array or valid JSON string');
    }

    /**
     * Validate parameter schema against expected structure
     */
    public static function validateSchema(array $parameters, array $schema): array
    {
        $errors = [];
        
        foreach ($schema as $field => $rules) {
            $value = $parameters[$field] ?? null;
            
            // Check required fields
            if (($rules['required'] ?? false) && $value === null) {
                $errors[] = "Required field '{$field}' is missing";
                continue;
            }
            
            // Check type
            if ($value !== null && isset($rules['type'])) {
                $expectedType = $rules['type'];
                $actualType = gettype($value);
                
                if (!self::isCompatibleType($actualType, $expectedType)) {
                    $errors[] = "Field '{$field}' expected {$expectedType}, got {$actualType}";
                }
            }
            
            // Check array elements
            if ($value !== null && isset($rules['array_type']) && is_array($value)) {
                foreach ($value as $index => $item) {
                    $itemType = gettype($item);
                    if (!self::isCompatibleType($itemType, $rules['array_type'])) {
                        $errors[] = "Field '{$field}[{$index}]' expected {$rules['array_type']}, got {$itemType}";
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Generate debug information about parameters
     */
    public static function debugParameters(array $parameters): array
    {
        $debug = [];
        
        foreach ($parameters as $key => $value) {
            $debug[$key] = [
                'type' => gettype($value),
                'value' => is_scalar($value) ? $value : '[complex]',
                'is_array' => is_array($value),
                'array_count' => is_array($value) ? count($value) : null,
                'array_keys' => is_array($value) ? array_keys($value) : null,
                'string_length' => is_string($value) ? strlen($value) : null,
                'is_json' => is_string($value) ? self::isJsonString($value) : false,
            ];
        }
        
        return $debug;
    }

    /**
     * Handle array parameters with proper unwrapping
     */
    public static function handleArrayParameter(mixed $param, string $paramName): array
    {
        if ($param === null || $param === 'null') {
            return [];
        }
        
        if (is_string($param)) {
            $decoded = json_decode($param, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            throw new InvalidArgumentException("{$paramName} must be a valid array or JSON string");
        }
        
        if (!is_array($param)) {
            throw new InvalidArgumentException("{$paramName} must be an array");
        }
        
        // Handle MCP wrapped arrays
        if (count($param) === 1 && is_int(array_key_first($param))) {
            return self::handleArrayParameter($param[0], $paramName);
        }
        
        return $param;
    }

    /**
     * Check if a string appears to be JSON
     */
    private static function isJsonString(string $value): bool
    {
        $trimmed = trim($value);
        return (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
               (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'));
    }

    /**
     * Check if actual type is compatible with expected type
     */
    private static function isCompatibleType(string $actualType, string $expectedType): bool
    {
        if ($actualType === $expectedType) {
            return true;
        }
        
        // Allow integer/double compatibility
        if (($actualType === 'integer' && $expectedType === 'double') ||
            ($actualType === 'double' && $expectedType === 'integer')) {
            return true;
        }
        
        // Allow string representations of numbers
        if ($expectedType === 'string' && in_array($actualType, ['integer', 'double'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Convert MCP tool parameters to internal format
     */
    public static function convertMcpToolParameters(array $mcpParams): array
    {
        $converted = [];
        
        foreach ($mcpParams as $key => $value) {
            // Convert parameter name from MCP format if needed
            $internalKey = self::convertParameterName($key);
            
            // Normalize the value
            $converted[$internalKey] = self::normalizeValue($value);
        }
        
        return $converted;
    }

    /**
     * Convert parameter names from MCP format to internal format
     */
    private static function convertParameterName(string $mcpName): string
    {
        // Convert camelCase to snake_case if needed
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $mcpName));
    }

    /**
     * Create a parameter normalization report
     */
    public static function createNormalizationReport(array $original, array $normalized): array
    {
        $changes = [];
        
        foreach ($original as $key => $value) {
            $normalizedValue = $normalized[$key] ?? null;
            
            if ($value !== $normalizedValue) {
                $changes[$key] = [
                    'original' => [
                        'value' => $value,
                        'type' => gettype($value)
                    ],
                    'normalized' => [
                        'value' => $normalizedValue,
                        'type' => gettype($normalizedValue)
                    ]
                ];
            }
        }
        
        return [
            'total_parameters' => count($original),
            'changes_made' => count($changes),
            'changes' => $changes,
            'success' => true
        ];
    }
}

