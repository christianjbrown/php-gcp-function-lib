<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\CacheHeaderBuilder;
use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheHeaderBuilder::class)]
final class CacheHeaderBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testAddsCacheAndSurrogateControlWhenTtlsSet(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(3600);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(7200);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(259200);

        $builder = new CacheHeaderBuilder();

        $headers = $builder->build(ResponseInterface::HEADERS, $functionConfig, true);

        self::assertSame('s-maxage=3600, max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $headers[ResponseInterface::HEADER_KEY_CACHE_CONTROL]);
        self::assertSame('max-age=3600, stale-while-revalidate=7200, stale-if-error=259200', $headers[ResponseInterface::HEADER_KEY_SURROGATE_CONTROL]);
    }

    /**
     * @throws Exception
     */
    public function testAddsSurrogateKeyWhenSet(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getSurrogateKey')
            ->willReturn('get-historical-climate-data');

        $builder = new CacheHeaderBuilder();

        $headers = $builder->build(ResponseInterface::HEADERS, $functionConfig, true);

        self::assertSame('get-historical-climate-data', $headers[ResponseInterface::HEADER_KEY_SURROGATE_KEY]);
    }

    /**
     * @throws Exception
     */
    public function testFailureLeavesHeadersUnchanged(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);

        $builder = new CacheHeaderBuilder();

        self::assertSame(ResponseInterface::HEADERS, $builder->build(ResponseInterface::HEADERS, $functionConfig, false));
    }

    public function testNoFunctionConfigLeavesHeadersUnchanged(): void
    {
        $builder = new CacheHeaderBuilder();

        self::assertSame(ResponseInterface::HEADERS, $builder->build(ResponseInterface::HEADERS, null, true));
    }

    /**
     * @throws Exception
     */
    public function testNoTtlsAddsNoCacheHeaders(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getUseCacheTtl')
            ->willReturn(null);
        $functionConfig->method('getUseCacheButRequestTtl')
            ->willReturn(null);
        $functionConfig->method('getUseCacheIfErrorTtl')
            ->willReturn(null);

        $builder = new CacheHeaderBuilder();

        $headers = $builder->build(ResponseInterface::HEADERS, $functionConfig, true);

        self::assertArrayNotHasKey(ResponseInterface::HEADER_KEY_CACHE_CONTROL, $headers);
        self::assertArrayNotHasKey(ResponseInterface::HEADER_KEY_SURROGATE_CONTROL, $headers);
        self::assertArrayNotHasKey(ResponseInterface::HEADER_KEY_SURROGATE_KEY, $headers);
    }
}
