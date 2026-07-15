<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\AbstractJsonResponse;
use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\JsonSuccessResponse;
use ChristianBrown\GcpFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractJsonResponse::class)]
#[CoversClass(JsonSuccessResponse::class)]
final class JsonSuccessResponseTest extends TestCase
{
    public function test(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 123);

        self::assertSame(123, $jsonResponse->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));
        self::assertSame('test-origin', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertArrayNotHasKey('error', $json);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame(['test-data'], $json['data']);
        self::assertTrue($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame('test-krevision', $json['version']);
    }

    public function testEmptyRequiredOrigin(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 200);

        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));
    }

    public function testJsonError(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $jsonResponse = new JsonSuccessResponse($functionConfig, ["\xC3\x28"], 123);

        self::assertSame(500, $jsonResponse->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));
        self::assertSame('test-origin', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertArrayHasKey('error', $json);
        self::assertArrayNotHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame(ResponseInterface::ERROR_JSON_ENCODING, $json['error']);
        self::assertFalse($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame('test-krevision', $json['version']);
    }

    public function testNoCacheTtls(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 200);

        self::assertSame('test-origin', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertTrue($json['success']);
    }

    public function testNoFunctionConfig(): void
    {
        $jsonResponse = new JsonSuccessResponse(null, ['test-data'], 123);

        self::assertSame(123, $jsonResponse->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertArrayNotHasKey('error', $json);
        self::assertArrayNotHasKey('version', $json);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);

        self::assertSame(['test-data'], $json['data']);
        self::assertTrue($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
    }

    #[DataProvider('provideRequestOriginResolvesAllowOriginCases')]
    public function testRequestOriginResolvesAllowOrigin(bool $debug, string $requestOrigin, string $expectedAllowOrigin): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getDebug')
            ->willReturn($debug);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 200, $requestOrigin);

        self::assertSame($expectedAllowOrigin, $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
    }

    /**
     * @return iterable<string, array{bool, string, string}>
     */
    public static function provideRequestOriginResolvesAllowOriginCases(): iterable
    {
        // With debug on, a localhost/loopback origin is reflected as a development convenience.
        yield 'debug localhost with port' => [true, 'http://localhost:3000', 'http://localhost:3000'];
        yield 'debug loopback ip with port' => [true, 'http://127.0.0.1:8080', 'http://127.0.0.1:8080'];
        yield 'debug localhost without port' => [true, 'http://localhost', 'http://localhost'];
        yield 'debug https localhost' => [true, 'https://localhost:5173', 'https://localhost:5173'];
        yield 'debug non-localhost origin' => [true, 'https://app.example.com', 'test-origin'];
        yield 'debug localhost lookalike host' => [true, 'http://localhost.example.com', 'test-origin'];
        yield 'debug userinfo trick' => [true, 'http://localhost@example.com', 'test-origin'];
        yield 'debug malformed origin' => [true, 'not-a-url', 'test-origin'];
        yield 'debug empty origin' => [true, '', 'test-origin'];
        // With debug off (production), the configured origin is always pinned, never reflected.
        yield 'no-debug localhost pinned' => [false, 'http://localhost:3000', 'test-origin'];
        yield 'no-debug non-localhost pinned' => [false, 'https://app.example.com', 'test-origin'];
    }
}
