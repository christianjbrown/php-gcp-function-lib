<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfig;
use ChristianBrown\CloudFunction\FunctionConfigTransformer;
use ChristianBrown\CloudFunction\FunctionConfigTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionConfig::class)]
#[CoversClass(FunctionConfigTransformer::class)]
final class FunctionConfigTransformerTest extends TestCase
{
    #[TestWith([true])]
    #[TestWith([false])]
    public function test(bool $debug): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_DEBUG => $debug ? 'true' : 'false',
            FunctionConfigTransformerInterface::ENV_K_REVISION => '123',
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_KEY => 'test-required-header-key',
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_VALUE => 'test-required-header-value',
            FunctionConfigTransformerInterface::ENV_REQUIRED_ORIGIN => 'test-required-origin',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_TTL => '3600',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_BUT_REQUEST_TTL => '7200',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_IF_ERROR_TTL => '259200',
        ];
        $transformer = new FunctionConfigTransformer();
        $actual = $transformer->transform($env);
        self::assertSame(123, $actual->getKrevision());
        self::assertSame($debug, $actual->getDebug());
        self::assertSame('test-required-header-key', $actual->getRequiredHeaderKey());
        self::assertSame('test-required-header-value', $actual->getRequiredHeaderValue());
        self::assertSame('test-required-origin', $actual->getRequiredOrigin());
        self::assertSame(3600, $actual->getUseCacheTtl());
        self::assertSame(7200, $actual->getUseCacheButRequestTtl());
        self::assertSame(259200, $actual->getUseCacheIfErrorTtl());
    }

    public function testKRevisionNotNumber(): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_K_REVISION => 'abc',
        ];
        $transformer = new FunctionConfigTransformer();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('%s not set or not a number', FunctionConfigTransformerInterface::ENV_K_REVISION));
        $transformer->transform($env);
    }
}
