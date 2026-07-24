<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction\Tests;

use ChristianBrown\CloudRunFunction\FunctionConfigInterface;
use ChristianBrown\CloudRunFunction\ResponseBodyBuilder;
use ChristianBrown\CloudRunFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use function gmdate;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

#[CoversClass(ResponseBodyBuilder::class)]
final class ResponseBodyBuilderTest extends TestCase
{
    private const int TIME = 1000000000;

    /**
     * @throws Exception
     */
    public function testBuildIncludesEveryOptionalSectionSorted(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');

        $builder = new ResponseBodyBuilder();

        $expected = [
            ResponseInterface::RESPONSE_API_KEY_DATA => ['test-data'],
            ResponseInterface::RESPONSE_API_KEY_ERROR => 'test-error',
            ResponseInterface::RESPONSE_API_KEY_SUCCESS => false,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => gmdate('c', self::TIME),
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX => self::TIME,
            ResponseInterface::RESPONSE_API_KEY_VERSION => 'test-krevision',
        ];

        self::assertSame($expected, $builder->build(['test-data'], $functionConfig, false, 'test-error', self::TIME));
    }

    public function testBuildOmitsOptionalSectionsWhenAbsent(): void
    {
        $builder = new ResponseBodyBuilder();

        $expected = [
            ResponseInterface::RESPONSE_API_KEY_SUCCESS => true,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => gmdate('c', self::TIME),
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX => self::TIME,
        ];

        self::assertSame($expected, $builder->build([], null, true, null, self::TIME));
    }

    public function testEncodeFallsBackToErrorWhenEncodingFails(): void
    {
        $builder = new ResponseBodyBuilder();

        // Invalid UTF-8 under the RESPONSE_API_KEY_DATA key forces a JsonException.
        $bodyJson = [
            ResponseInterface::RESPONSE_API_KEY_DATA => "\xC3\x28",
            ResponseInterface::RESPONSE_API_KEY_SUCCESS => true,
        ];

        [$body, $success, $statusCode] = $builder->encode($bodyJson, true, 200);

        self::assertFalse($success);
        self::assertSame(500, $statusCode);

        $decoded = json_decode($body, true);

        self::assertIsArray($decoded);
        self::assertFalse($decoded[ResponseInterface::RESPONSE_API_KEY_SUCCESS]);
        self::assertSame(ResponseInterface::ERROR_JSON_ENCODING, $decoded[ResponseInterface::RESPONSE_API_KEY_ERROR]);
        self::assertArrayNotHasKey(ResponseInterface::RESPONSE_API_KEY_DATA, $decoded);
    }

    public function testEncodeReturnsJsonUnchangedOnSuccess(): void
    {
        $builder = new ResponseBodyBuilder();

        $bodyJson = [
            ResponseInterface::RESPONSE_API_KEY_SUCCESS => true,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX => self::TIME,
        ];

        [$body, $success, $statusCode] = $builder->encode($bodyJson, true, 200);

        self::assertTrue($success);
        self::assertSame(200, $statusCode);
        self::assertSame(json_encode($bodyJson, JSON_THROW_ON_ERROR + JSON_PRETTY_PRINT), $body);
    }
}
