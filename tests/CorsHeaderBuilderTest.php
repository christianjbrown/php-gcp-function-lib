<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\AllowOriginResolverInterface;
use ChristianBrown\GcpFunction\CorsHeaderBuilder;
use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(CorsHeaderBuilder::class)]
final class CorsHeaderBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testAddsResolvedOriginAndVaryWithRequiredHeaderKey(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('https://required.example');
        $functionConfig->method('getDebug')
            ->willReturn(false);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn('X-Api-Key');

        $allowOriginResolver = self::createStub(AllowOriginResolverInterface::class);
        $allowOriginResolver->method('resolve')
            ->willReturn('https://resolved.example');

        $builder = new CorsHeaderBuilder($allowOriginResolver);

        $headers = $builder->build(ResponseInterface::HEADERS, $functionConfig, 'https://app.example.com');

        self::assertSame('https://resolved.example', $headers[ResponseInterface::HEADER_KEY_ALLOW_ORIGIN]);
        self::assertSame('Accept-Encoding,Origin,X-Api-Key', $headers[ResponseInterface::HEADER_KEY_VARY]);
    }

    /**
     * @throws Exception
     */
    public function testAddsVaryWithoutRequiredHeaderKey(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('https://required.example');
        $functionConfig->method('getDebug')
            ->willReturn(false);
        $functionConfig->method('getRequiredHeaderKey')
            ->willReturn(null);

        $allowOriginResolver = self::createStub(AllowOriginResolverInterface::class);
        $allowOriginResolver->method('resolve')
            ->willReturn('https://resolved.example');

        $builder = new CorsHeaderBuilder($allowOriginResolver);

        $headers = $builder->build(ResponseInterface::HEADERS, $functionConfig, null);

        self::assertSame('https://resolved.example', $headers[ResponseInterface::HEADER_KEY_ALLOW_ORIGIN]);
        self::assertSame('Accept-Encoding,Origin', $headers[ResponseInterface::HEADER_KEY_VARY]);
    }

    /**
     * @throws Exception
     */
    public function testEmptyRequiredOriginLeavesHeadersUnchanged(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $functionConfig->method('getRequiredOrigin')
            ->willReturn('');

        $builder = new CorsHeaderBuilder(self::createStub(AllowOriginResolverInterface::class));

        self::assertSame(ResponseInterface::HEADERS, $builder->build(ResponseInterface::HEADERS, $functionConfig, 'https://app.example.com'));
    }

    public function testNoFunctionConfigLeavesHeadersUnchanged(): void
    {
        $builder = new CorsHeaderBuilder(self::createStub(AllowOriginResolverInterface::class));

        self::assertSame(ResponseInterface::HEADERS, $builder->build(ResponseInterface::HEADERS, null, 'https://app.example.com'));
    }
}
