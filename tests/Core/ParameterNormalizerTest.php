<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Tests\Core;

use Aurire\AiMcpAnalysis\Core\ParameterNormalizer;
use Aurire\AiMcpAnalysis\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionException;

class ParameterNormalizerTest extends TestCase
{
    /**
     * Test string 'null' normalization
     *
     * @return void
     */
    public function testStringNullNormalization(): void
    {
        $result = ParameterNormalizer::normalize('null', 'test_param');
        $this->assertNull($result);
    }

    /**
     * Test string 'undefined' normalization
     *
     * @return void
     */
    public function testStringUndefinedNormalization(): void
    {
        $result = ParameterNormalizer::normalize('undefined', 'test_param');
        $this->assertNull($result);
    }

    /**
     * Test wrapped array unwrapping
     *
     * @return void
     */
    public function testWrappedArrayUnwrapping(): void
    {
        $wrappedArray = [['test' => 'value']];
        $result = ParameterNormalizer::normalize($wrappedArray, 'test_param');

        $this->assertEquals(['test' => 'value'], $result);
        $this->assertIsArray($result);
    }

    /**
     * Test JSON string decoding
     *
     * @return void
     */
    public function testJsonStringDecoding(): void
    {
        $jsonString = '{"key": "value", "number": 123}';
        $result = ParameterNormalizer::normalize($jsonString, 'test_param');

        $expected = ['key' => 'value', 'number' => 123];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test JSON array string decoding
     *
     * @return void
     */
    public function testJsonArrayStringDecoding(): void
    {
        $jsonString = '["item1", "item2", "item3"]';
        $result = ParameterNormalizer::normalize($jsonString, 'test_param');

        $expected = ['item1', 'item2', 'item3'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that normal parameters pass through unchanged
     *
     * @return void
     */
    public function testNormalParametersPassThrough(): void
    {
        $testCases = [
            'string_value',
            123,
            12.34,
            true,
            false,
            ['normal', 'array'],
            ['key' => 'value']
        ];

        foreach ($testCases as $testCase) {
            $result = ParameterNormalizer::normalize($testCase, 'test_param');
            $this->assertEquals($testCase, $result);
        }
    }

    /**
     * Test invalid JSON string handling
     *
     * @return void
     */
    public function testInvalidJsonStringHandling(): void
    {
        $invalidJson = '{"invalid": json}';
        $result = ParameterNormalizer::normalize($invalidJson, 'test_param');

        // Should return original string since JSON decode failed
        $this->assertEquals($invalidJson, $result);
    }

    /**
     * Test normalization with logging
     *
     * @return void
     */
    public function testNormalizationWithLogging(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Parameter normalized', Mockery::type('array'));

        $result = ParameterNormalizer::normalizeWithLogging('null', 'test_param');
        $this->assertNull($result);
    }

    /**
     * Test no logging when no transformation occurs
     *
     * @return void
     */
    public function testNoLoggingWhenNoTransformation(): void
    {
        Log::shouldReceive('debug')->never();

        $result = ParameterNormalizer::normalizeWithLogging('normal_string', 'test_param');
        $this->assertEquals('normal_string', $result);
    }

    /**
     * Test batch normalization
     *
     * @return void
     */
    public function testBatchNormalization(): void
    {
        $parameters = [
            'null_param' => 'null',
            'undefined_param' => 'undefined',
            'wrapped_param' => [['test' => 'value']],
            'json_param' => '{"key": "value"}',
            'normal_param' => 'stays_same'
        ];

        $result = ParameterNormalizer::normalizeMultiple($parameters);

        $this->assertNull($result['null_param']);
        $this->assertNull($result['undefined_param']);
        $this->assertEquals(['test' => 'value'], $result['wrapped_param']);
        $this->assertEquals(['key' => 'value'], $result['json_param']);
        $this->assertEquals('stays_same', $result['normal_param']);
    }

    /**
     * Test needs normalization detection
     *
     * @return void
     */
    public function testNeedsNormalizationDetection(): void
    {
        // Parameters that need normalization
        $this->assertTrue(ParameterNormalizer::needsNormalization('null'));
        $this->assertTrue(ParameterNormalizer::needsNormalization('undefined'));
        $this->assertTrue(ParameterNormalizer::needsNormalization([['wrapped']]));
        $this->assertTrue(ParameterNormalizer::needsNormalization('{"json": "string"}'));
        $this->assertTrue(ParameterNormalizer::needsNormalization('["json", "array"]'));

        // Parameters that don't need normalization
        $this->assertFalse(ParameterNormalizer::needsNormalization('normal_string'));
        $this->assertFalse(ParameterNormalizer::needsNormalization(123));
        $this->assertFalse(ParameterNormalizer::needsNormalization(['normal', 'array']));
        $this->assertFalse(ParameterNormalizer::needsNormalization(true));
    }

    /**
     * Test transformation type detection
     *
     * @return void
     * @throws ReflectionException
     */
    public function testTransformationTypeDetection(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass(ParameterNormalizer::class);
        $method = $reflection->getMethod('getTransformationType');
        $method->setAccessible(true);

        // Test string to null transformation
        $result = $method->invokeArgs(null, ['null', null]);
        $this->assertEquals('string_to_null', $result);

        // Test wrapped array transformation
        $result = $method->invokeArgs(null, [[['test']], ['test']]);
        $this->assertEquals('wrapped_array_unwrapped', $result);

        // Test JSON string transformation
        $result = $method->invokeArgs(null, ['{"key":"value"}', ['key' => 'value']]);
        $this->assertEquals('json_string_decoded', $result);

        // Test no transformation
        $result = $method->invokeArgs(null, ['same', 'same']);
        $this->assertEquals('no_transformation', $result);
    }

    /**
     * Test edge cases
     *
     * @return void
     */
    public function testEdgeCases(): void
    {
        // Empty string
        $result = ParameterNormalizer::normalize('', 'empty_string');
        $this->assertEquals('', $result);

        // Empty array
        $result = ParameterNormalizer::normalize([], 'empty_array');
        $this->assertEquals([], $result);

        // Actual null
        $result = ParameterNormalizer::normalize(null, 'actual_null');
        $this->assertNull($result);

        // String that looks like JSON but isn't
        $result = ParameterNormalizer::normalize('{not json}', 'fake_json');
        $this->assertEquals('{not json}', $result);
    }
}
