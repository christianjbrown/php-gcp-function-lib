<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\AbstractJsonResponse;
use ChristianBrown\CloudFunction\CloudFunction;
use ChristianBrown\CloudFunction\CloudFunctionInterface;
use ChristianBrown\CloudFunction\DataProviderInterface;
use ChristianBrown\CloudFunction\FunctionConfigInterface;
use ChristianBrown\CloudFunction\JsonErrorResponse;
use ChristianBrown\CloudFunction\JsonErrorResponseInterface;
use ChristianBrown\CloudFunction\JsonSuccessResponse;
use ChristianBrown\CloudFunction\JsonSuccessResponseInterface;
use ChristianBrown\CloudFunction\ResponseInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(JsonSuccessResponse::class)]
#[CoversClass(JsonErrorResponse::class)]
#[CoversClass(AbstractJsonResponse::class)]
#[CoversClass(CloudFunction::class)]
final class CloudFunctionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testNoSuccessfulUnauthorised(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('test-header-key')
            ->willReturn(true)
        ;
        $request->method('getHeaderLine')
            ->with('test-header-key')
            ->willReturn('test-header-value-wrong')
        ;

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data'])
        ;

        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, CloudFunctionInterface::ERROR_NOT_AUTHORIZED, 401, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    public function testNotSuccessfulFriendlyException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('test-header-key')
            ->willReturn(true)
        ;
        $request->method('getHeaderLine')
            ->with('test-header-key')
            ->willReturn('test-header-value')
        ;

        // Cannot mock getMessage in Exception because it is final, need to use a real class
        $userFriendlyException = new UserFriendlyException('test-friendly-error-message');

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willThrowException($userFriendlyException)
        ;

        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseError($actual, 'test-friendly-error-message', 500, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testNotSuccessfulThrowable(bool $debug): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('test-header-key')
            ->willReturn(true)
        ;
        $request->method('getHeaderLine')
            ->with('test-header-key')
            ->willReturn('test-header-value')
        ;

        // Cannot mock getMessage in Exception because it is final, need to use a real class
        $exception = new RuntimeException('test-exception-message');

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willThrowException($exception)
        ;

        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;
        $functionConfig->method('getDebug')
            ->willReturn($debug)
        ;

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
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('test-header-key')
            ->willReturn(true)
        ;
        $request->method('getHeaderLine')
            ->with('test-header-key')
            ->willReturn('test-header-value')
        ;

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data'])
        ;

        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('test-header-key')
        ;
        $functionConfig->method('getRequiredHeaderValue')
            ->willReturn('test-header-value')
        ;
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseSuccess($actual, ['test-data'], 200, 'test-origin', 'Accept-Encoding,Origin,test-header-key');
    }

    /**
     * @throws Exception
     */
    public function testSuccessNoAuth(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn(['test-data'])
        ;

        $functionConfig = $this->createMock(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('test-origin')
        ;
        $functionConfig->method('getKrevision')
            ->willReturn(42)
        ;

        $cloudFunction = new CloudFunction($dataProvider, $functionConfig);

        $actual = $cloudFunction->run($request);

        self::assertResponseSuccess($actual, ['test-data'], 200, 'test-origin', 'Accept-Encoding,Origin');
    }

    private static function assertResponseError(ResponseInterface $response, string $expectedError, int $statusCode, ?string $expectedOrigin, ?string $expectedVary, ?int $expectedVersion = 42): void
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

        self::assertArrayHasKey('error', $json);
        self::assertArrayNotHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame($expectedError, $json['error']);
        self::assertFalse($json['success']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame($expectedVersion, $json['version']);
    }

    private static function assertResponseSuccess(ResponseInterface $response, array $expectedData, int $statusCode, ?string $expectedOrigin, ?string $expectedVary, ?int $expectedVersion = 42): void
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
        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=259200, stale-if-error=259200', $response->getHeaderLine(ResponseInterface::HEADER_KEY_CACHE_CONTROL));
        self::assertSame('max-age=3600, stale-while-revalidate=259200, stale-if-error=259200', $response->getHeaderLine(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL));

        $json = json_decode($response->getBody()->getContents(), true);

        self::assertArrayNotHasKey('error', $json);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('success', $json);
        self::assertArrayHasKey('timestamp_iso8601', $json);
        self::assertArrayHasKey('timestamp_unix', $json);
        self::assertArrayHasKey('version', $json);

        self::assertSame($expectedData, $json['data']);
        self::assertTrue($json['success']);
        self::assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00#', $json['timestamp_iso8601']);
        self::assertIsInt($json['timestamp_unix']);
        self::assertSame($expectedVersion, $json['version']);
    }
}
