<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction\Tests;

use ChristianBrown\CloudFunction\AbstractJsonResponse;
use ChristianBrown\CloudFunction\FunctionConfigInterface;
use ChristianBrown\CloudFunction\JsonErrorResponse;
use ChristianBrown\CloudFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractJsonResponse::class)]
#[CoversClass(JsonErrorResponse::class)]
final class JsonErrorResponseTest extends TestCase
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

        $jsonResponse = new JsonErrorResponse($functionConfig, 'test-error', 123);

        self::assertSame(123, $jsonResponse->getStatusCode());
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

        self::assertSame('test-error', $json['error']);
        self::assertFalse($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame('test-krevision', $json['version']);
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

        $jsonResponse = new JsonErrorResponse($functionConfig, "\xC3\x28", 123);

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
}
