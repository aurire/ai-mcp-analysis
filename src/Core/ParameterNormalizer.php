<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Core;

use Illuminate\Support\Facades\Log;

class ParameterNormalizer
{
    /**
     * @param mixed $parameter
     * @param string $parameterName
     * @return mixed
     */
    public static function normalize(mixed $parameter, string $parameterName = 'unknown'): mixed
    {
        // Handle null/undefined issues that plagued the tools at first
        if ($parameter === 'null' || $parameter === 'undefined') {
            return null;
        }

        // Handle wrapped array format from MCP
        if (is_array($parameter) && count($parameter) === 1 && is_int(array_key_first($parameter))) {
            return $parameter[0];
        }

        // Handle JSON string input
        if (is_string($parameter) && self::isJsonString($parameter)) {
            $decoded = json_decode($parameter, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $parameter;
    }

    /**
     * @param string $string
     * @return bool
     */
    private static function isJsonString(string $string): bool
    {
        return (str_starts_with($string, '{') && str_ends_with($string, '}')) ||
            (str_starts_with($string, '[') && str_ends_with($string, ']'));
    }

    /**
     * Normalize parameter with debug logging
     *
     * @param mixed $parameter
     * @param string $parameterName
     * @return mixed
     */
    public static function normalizeWithLogging(mixed $parameter, string $parameterName = 'unknown'): mixed
    {
        $original = $parameter;
        $normalized = self::normalize($parameter, $parameterName);

        if ($original !== $normalized) {
            Log::debug("Parameter normalized", [
                'parameter' => $parameterName,
                'original_type' => gettype($original),
                'normalized_type' => gettype($normalized),
                'transformation' => self::getTransformationType($original, $normalized)
            ]);
        }

        return $normalized;
    }

    /**
     * Determine the type of transformation that occurred
     *
     * @param mixed $original
     * @param mixed $normalized
     * @return string
     */
    private static function getTransformationType(mixed $original, mixed $normalized): string
    {
        // String 'null'/'undefined' to actual null
        if (($original === 'null' || $original === 'undefined') && $normalized === null) {
            return 'string_to_null';
        }

        // Wrapped array unwrapping
        if (is_array($original) && count($original) === 1 && is_int(array_key_first($original))) {
            return 'wrapped_array_unwrapped';
        }

        // JSON string decoding
        if (is_string($original) && is_array($normalized)) {
            return 'json_string_decoded';
        }

        // Type conversion
        if (gettype($original) !== gettype($normalized)) {
            return sprintf('%s_to_%s', gettype($original), gettype($normalized));
        }

        return 'no_transformation';
    }

    /**
     * Batch normalize multiple parameters
     *
     * @param array $parameters
     * @return array
     */
    public static function normalizeMultiple(array $parameters): array
    {
        $normalized = [];

        foreach ($parameters as $key => $value) {
            $normalized[$key] = self::normalize($value, (string)$key);
        }

        return $normalized;
    }

    /**
     * Check if parameter needs normalization
     *
     * @param mixed $parameter
     * @return bool
     */
    public static function needsNormalization(mixed $parameter): bool
    {
        // Check for string null/undefined
        if ($parameter === 'null' || $parameter === 'undefined') {
            return true;
        }

        // Check for wrapped array
        if (is_array($parameter) && count($parameter) === 1 && is_int(array_key_first($parameter))) {
            return true;
        }

        // Check for JSON string
        if (is_string($parameter) && self::isJsonString($parameter)) {
            $decoded = json_decode($parameter, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return true;
            }
        }

        return false;
    }
}
