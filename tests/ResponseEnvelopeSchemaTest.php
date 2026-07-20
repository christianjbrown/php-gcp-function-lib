<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\ResponseBodyBuilder;
use ChristianBrown\GcpFunction\ResponseInterface;
use OpenApi\Generator;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

use function dirname;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * The library's contract test. It has no HTTP endpoint, so the gate is here: it
 * runs `zircote/swagger-php` over `src/`, asserts the two reusable envelope
 * components (`ErrorEnvelope`, `SuccessEnvelope`) are produced with the expected
 * properties and required fields, then validates that the *real* envelopes built
 * by `ResponseBodyBuilder` conform to those generated schemas — catching envelope
 * drift at its source. It also proves a consuming function can compose
 * `SuccessEnvelope` with its own data schema via `allOf`.
 */
#[CoversClass(ResponseBodyBuilder::class)]
final class ResponseEnvelopeSchemaTest extends TestCase
{
    private const string KEY_DEVICES = 'devices';
    private const string REVISION = 'schema-test-revision';
    private const string SCHEMA_COMPOSED_ENVELOPE = 'ComposedEnvelope';
    private const string SCHEMA_ERROR_ENVELOPE = 'ErrorEnvelope';
    private const string SCHEMA_FUNCTION_DATA = 'FunctionData';
    private const string SCHEMA_SUCCESS_ENVELOPE = 'SuccessEnvelope';
    private const string SCHEMA_URI = 'https://lib.test/openapi.json';
    private const int TIME = 1752580800;
    private stdClass $document;

    protected function setUp(): void
    {
        $openapi = (new Generator())->generate([dirname(__DIR__).'/src'], validate: false);

        $this->document = $this->object(json_decode((string) $openapi?->toJson(), false, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Exception
     */
    public function testErrorEnvelopeSchemaMatchesRealErrorResponse(): void
    {
        $schema = $this->schema(self::SCHEMA_ERROR_ENVELOPE);

        self::assertSame('object', $this->stringProperty($schema, 'type'));
        self::assertFalse($schema->additionalProperties);
        self::assertEqualsCanonicalizing([
            ResponseInterface::RESPONSE_API_KEY_SUCCESS,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601,
            ResponseInterface::RESPONSE_API_KEY_VERSION,
            ResponseInterface::RESPONSE_API_KEY_ERROR,
        ], $this->list($schema->required));
        self::assertSame('boolean', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_SUCCESS));
        self::assertSame('integer', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX));
        self::assertSame('string', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601));
        self::assertSame('string', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_VERSION));
        self::assertSame('string', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_ERROR));

        $errorEnvelope = $this->buildEnvelope([], false, 'An unhandled error occurred');

        $this->assertConforms($errorEnvelope, self::SCHEMA_ERROR_ENVELOPE);

        // A success-shaped envelope (with `data`, no `error`) must not pass the error schema.
        $successEnvelope = $this->buildEnvelope([self::KEY_DEVICES => ['a']], true, null);
        $this->assertViolates($successEnvelope, self::SCHEMA_ERROR_ENVELOPE);
    }

    /**
     * @throws Exception
     */
    public function testSuccessEnvelopeComposesWithFunctionDataViaAllOf(): void
    {
        // This is exactly what a consuming function does: reference SuccessEnvelope and
        // plug its own data schema in via `allOf`.
        $schemas = $this->object($this->object($this->document->components)->schemas);
        $schemas->{self::SCHEMA_FUNCTION_DATA} = $this->object(json_decode((string) json_encode([
            'type' => 'object',
            'required' => [self::KEY_DEVICES],
            'properties' => [self::KEY_DEVICES => ['type' => 'array', 'items' => ['type' => 'string']]],
            'additionalProperties' => false,
        ]), false, 512, JSON_THROW_ON_ERROR));
        $schemas->{self::SCHEMA_COMPOSED_ENVELOPE} = $this->object(json_decode((string) json_encode([
            'allOf' => [
                ['$ref' => '#/components/schemas/'.self::SCHEMA_SUCCESS_ENVELOPE],
                ['type' => 'object', 'properties' => [ResponseInterface::RESPONSE_API_KEY_DATA => ['$ref' => '#/components/schemas/'.self::SCHEMA_FUNCTION_DATA]]],
            ],
        ]), false, 512, JSON_THROW_ON_ERROR));

        $successEnvelope = $this->buildEnvelope([self::KEY_DEVICES => ['living-room', 'hallway']], true, null);

        $this->assertConforms($successEnvelope, self::SCHEMA_COMPOSED_ENVELOPE);

        // A payload whose `data` violates the plugged-in FunctionData schema must fail the composition.
        $wrongData = $this->buildEnvelope([self::KEY_DEVICES => [1, 2]], true, null);
        $this->assertViolates($wrongData, self::SCHEMA_COMPOSED_ENVELOPE);
    }

    /**
     * @throws Exception
     */
    public function testSuccessEnvelopeSchemaMatchesRealSuccessResponse(): void
    {
        $schema = $this->schema(self::SCHEMA_SUCCESS_ENVELOPE);

        self::assertSame('object', $this->stringProperty($schema, 'type'));
        self::assertFalse($schema->additionalProperties);
        self::assertEqualsCanonicalizing([
            ResponseInterface::RESPONSE_API_KEY_SUCCESS,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601,
            ResponseInterface::RESPONSE_API_KEY_VERSION,
            ResponseInterface::RESPONSE_API_KEY_DATA,
        ], $this->list($schema->required));
        self::assertSame('boolean', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_SUCCESS));
        self::assertSame('integer', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX));
        self::assertSame('string', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601));
        self::assertSame('string', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_VERSION));
        // The `data` property is a generic object placeholder each function overrides via `allOf`.
        self::assertSame('object', $this->propertyType($schema, ResponseInterface::RESPONSE_API_KEY_DATA));

        $successEnvelope = $this->buildEnvelope([self::KEY_DEVICES => ['a', 'b']], true, null);

        $this->assertConforms($successEnvelope, self::SCHEMA_SUCCESS_ENVELOPE);
    }

    private function assertConforms(stdClass $value, string $schemaName): void
    {
        $result = $this->validator()->validate($value, (object) ['$ref' => self::SCHEMA_URI.'#/components/schemas/'.$schemaName]);

        self::assertTrue($result->isValid(), $schemaName.' should accept the real envelope.');
    }

    private function assertViolates(stdClass $value, string $schemaName): void
    {
        $result = $this->validator()->validate($value, (object) ['$ref' => self::SCHEMA_URI.'#/components/schemas/'.$schemaName]);

        self::assertFalse($result->isValid(), $schemaName.' should reject a non-conforming envelope.');
    }

    /**
     * @param mixed[] $data
     *
     * @throws Exception
     */
    private function buildEnvelope(array $data, bool $success, ?string $error): stdClass
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn(self::REVISION);

        $builder = new ResponseBodyBuilder();
        $body = $builder->build($data, $functionConfig, $success, $error, self::TIME);
        [$json] = $builder->encode($body, $success, ResponseInterface::STATUS_OK);

        return $this->object(json_decode($json, false, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return mixed[]
     */
    private function list(mixed $value): array
    {
        if (!is_array($value)) {
            self::fail('Expected an array in the generated OpenAPI document.');
        }

        return $value;
    }

    private function object(mixed $value): stdClass
    {
        if (!$value instanceof stdClass) {
            self::fail('Expected an object in the generated OpenAPI document.');
        }

        return $value;
    }

    private function propertyType(stdClass $schema, string $property): string
    {
        return $this->stringProperty($this->object($this->object($schema->properties)->{$property}), 'type');
    }

    private function schema(string $name): stdClass
    {
        return $this->object($this->object($this->object($this->document->components)->schemas)->{$name});
    }

    private function stringProperty(stdClass $object, string $property): string
    {
        $value = $object->{$property};

        if (!is_string($value)) {
            self::fail('Expected a string in the generated OpenAPI document.');
        }

        return $value;
    }

    private function validator(): Validator
    {
        $validator = new Validator();
        $validator->resolver()?->registerRaw($this->document, self::SCHEMA_URI);

        return $validator;
    }
}
