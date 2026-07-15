<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction\Tests;

use ChristianBrown\CloudFunction\AbstractJsonResponse;
use ChristianBrown\CloudFunction\FunctionConfigInterface;
use ChristianBrown\CloudFunction\JsonSuccessResponse;
use ChristianBrown\CloudFunction\ResponseInterface;
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

        self::assertSame('*', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
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
        self::assertSame('*', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
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
    public function testRequestOriginResolvesAllowOrigin(string $requestOrigin, string $expectedAllowOrigin): void
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

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 200, $requestOrigin);

        self::assertSame($expectedAllowOrigin, $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRequestOriginResolvesAllowOriginCases(): iterable
    {
        yield 'localhost with port' => ['http://localhost:3000', 'http://localhost:3000'];
        yield 'loopback ip with port' => ['http://127.0.0.1:8080', 'http://127.0.0.1:8080'];
        yield 'localhost without port' => ['http://localhost', 'http://localhost'];
        yield 'https localhost' => ['https://localhost:5173', 'https://localhost:5173'];
        yield 'non-localhost origin' => ['https://app.example.com', 'test-origin'];
        yield 'localhost lookalike host' => ['http://localhost.example.com', 'test-origin'];
        yield 'userinfo trick' => ['http://localhost@example.com', 'test-origin'];
        yield 'malformed origin' => ['not-a-url', 'test-origin'];
        yield 'empty origin' => ['', 'test-origin'];
    }
}
