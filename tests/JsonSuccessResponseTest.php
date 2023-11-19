<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\AbstractJsonResponse;
use ChristianBrown\CloudFunction\FunctionConfigInterface;
use ChristianBrown\CloudFunction\JsonSuccessResponse;
use ChristianBrown\CloudFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractJsonResponse::class)]
#[CoversClass(JsonSuccessResponse::class)]
final class JsonSuccessResponseTest extends TestCase
{
    public function test(): void
    {
        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600)
        ;
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200)
        ;
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200)
        ;

        $jsonResponse = new JsonSuccessResponse($functionConfig, ['test-data'], 123);

        self::assertSame(123, $jsonResponse->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));
        self::assertSame('test-origin', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertArrayNotHasKey('error', $json);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame(['test-data'], $json['data']);
        self::assertTrue($json['success']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame(42, $json['version']);
    }

    public function testJsonError(): void
    {
        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600)
        ;
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200)
        ;
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200)
        ;

        $jsonResponse = new JsonSuccessResponse($functionConfig, ["\xC3\x28"], 123);

        self::assertSame(500, $jsonResponse->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));
        self::assertSame('test-origin', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        self::assertSame('Accept-Encoding,Origin,test-header-key', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('', $jsonResponse->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($jsonResponse->getBody()->getContents(), true);

        self::assertArrayHasKey('error', $json);
        self::assertArrayNotHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame(ResponseInterface::ERROR_JSON_ENCODING, $json['error']);
        self::assertFalse($json['success']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame(42, $json['version']);
    }
}
