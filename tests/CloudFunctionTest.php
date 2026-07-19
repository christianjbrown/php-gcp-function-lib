<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\AbstractJsonResponse;
use ChristianBrown\GcpFunction\AllowOriginResolver;
use ChristianBrown\GcpFunction\CacheHeaderBuilder;
use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\CloudFunctionInterface;
use ChristianBrown\GcpFunction\CorsHeaderBuilder;
use ChristianBrown\GcpFunction\DataProviderInterface;
use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\JsonErrorResponse;
use ChristianBrown\GcpFunction\JsonErrorResponseInterface;
use ChristianBrown\GcpFunction\JsonSuccessResponse;
use ChristianBrown\GcpFunction\JsonSuccessResponseInterface;
use ChristianBrown\GcpFunction\ResponseBodyBuilder;
use ChristianBrown\GcpFunction\ResponseInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

#[CoversClass(JsonSuccessResponse::class)]
#[CoversClass(JsonErrorResponse::class)]
#[CoversClass(AbstractJsonResponse::class)]
#[CoversClass(AllowOriginResolver::class)]
#[CoversClass(CacheHeaderBuilder::class)]
#[CoversClass(CorsHeaderBuilder::class)]
#[CoversClass(ResponseBodyBuilder::class)]
#[CoversClass(CloudFunction::class)]
final class CloudFunctionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testNoSuccessfulHeaderAbsent(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', ''],
                [ResponseInterface::HEADER_KEY_ORIGIN, ''],
            ]);

        $dataProvider = self::createStub(DataProviderInterface::class);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, CloudFunctionInterface::ERROR_NOT_AUTHORIZED, 401, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    #[TestWith(['test-header-key', null])]
    #[TestWith([null, 'test-header-value'])]
    public function testNoSuccessfulPartialGateConfig(?string $requiredHeaderKey, ?string $requiredHeaderValue): void
    {
        $request = self::createStub(ServerRequestInterface::class);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn($requiredHeaderKey);
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn($requiredHeaderValue);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, CloudFunctionInterface::ERROR_NOT_AUTHORIZED, 401, 'test-origin', null);
    }

    /**
     * @throws Exception
     */
    public function testNoSuccessfulUnauthenticatedNotAllowed(): void
    {
        $request = self::createStub(ServerRequestInterface::class);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getAllowUnauthenticated')
            ->willReturn(false);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, CloudFunctionInterface::ERROR_NOT_AUTHORIZED, 401, 'test-origin', 'Accept-Encoding,Origin');
    }

    /**
     * @throws Exception
     */
    public function testNoSuccessfulUnauthorised(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->willReturn(true);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', 'test-header-value-wrong'],
                [ResponseInterface::HEADER_KEY_ORIGIN, ''],
            ]);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, CloudFunctionInterface::ERROR_NOT_AUTHORIZED, 401, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    public function testNotSuccessfulFriendlyException(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->willReturn(true);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', 'test-header-value'],
                [ResponseInterface::HEADER_KEY_ORIGIN, ''],
            ]);

        // Cannot mock getMessage in Exception because it is final, need to use a real class
        $userFriendlyException = new UserFriendlyException('test-friendly-error-message');

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willThrowException($userFriendlyException);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, 'test-friendly-error-message', 500, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    #[TestWith([true])]
    #[TestWith([false])]
    public function testNotSuccessfulThrowable(bool $debug): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->willReturn(true);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', 'test-header-value'],
                [ResponseInterface::HEADER_KEY_ORIGIN, ''],
            ]);

        // Cannot mock getMessage in Exception because it is final, need to use a real class
        $exception = new RuntimeException('test-exception-message');

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willThrowException($exception);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getDebug')
            ->willReturn($debug);
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        if ($debug) {
            self::assertResponseError($actual, 'test-exception-message', 500, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
        } else {
            self::assertResponseError($actual, CloudFunctionInterface::ERROR_UNHANDLED, 500, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
        }
    }

    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->willReturn(true);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', 'test-header-value'],
                [ResponseInterface::HEADER_KEY_ORIGIN, ''],
            ]);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseSuccess($actual, ['test-data'], 200, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    public function testSuccessLocalhostOriginEchoed(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->willReturn(true);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['test-header-key', 'test-header-value'],
                [ResponseInterface::HEADER_KEY_ORIGIN, 'http://localhost:5173'],
            ]);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key');
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value');
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getDebug')
            ->willReturn(true);
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseSuccess($actual, ['test-data'], 200, 'http://localhost:5173', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    public function testSuccessNoAuth(): void
    {
        $request = self::createStub(ServerRequestInterface::class);

        $dataProvider = self::createStub(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data']);

        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getAllowUnauthenticated')
            ->willReturn(true);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin');
        $functionConfig->method('getKrevision')
            ->willReturn('test-krevision');
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseSuccess($actual, ['test-data'], 200, 'test-origin', 'Accept-Encoding,Origin');
    }

    private static function assertResponseError(ResponseInterface $response, string $expectedError, int $statusCode, ?string $expectedOrigin, ?string $expectedVary, ?string $expectedVersion = 'test-krevision'): void
    {
        self::assertInstanceOf(JsonErrorResponseInterface::class, $response);

        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        if ($expectedOrigin) {
            self::assertSame($expectedOrigin, $response->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        }
        if ($expectedVary) {
            self::assertSame($expectedVary, $response->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        }
        self::assertSame('', $response->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('', $response->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($response->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertArrayHasKey('error', $json);
        self::assertArrayNotHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame($expectedError, $json['error']);
        self::assertFalse($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame($expectedVersion, $json['version']);
    }

    private static function assertResponseSuccess(ResponseInterface $response, array $expectedData, int $statusCode, ?string $expectedOrigin, ?string $expectedVary, ?string $expectedVersion = 'test-krevision'): void
    {
        self::assertInstanceOf(JsonSuccessResponseInterface::class, $response);

        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        if ($expectedOrigin) {
            self::assertSame($expectedOrigin, $response->getHeaderLine(ResponseInterface::HEADER_KEY_ALLOW_ORIGIN));
        }
        if ($expectedVary) {
            self::assertSame($expectedVary, $response->getHeaderLine(ResponseInterface::HEADER_KEY_VARY));
        }
        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $response->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $response->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($response->getBody()->getContents(), true);

        self::assertIsArray($json);
        self::assertArrayNotHasKey('error', $json);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame($expectedData, $json['data']);
        self::assertTrue($json['success']);
        self::assertIsString($json['timestamp_iso8601']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame($expectedVersion, $json['version']);
    }
}
