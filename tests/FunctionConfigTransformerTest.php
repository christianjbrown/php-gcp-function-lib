<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction\Tests;

use ChristianBrown\CloudRunFunction\FunctionConfig;
use ChristianBrown\CloudRunFunction\FunctionConfigTransformer;
use ChristianBrown\CloudRunFunction\FunctionConfigTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(FunctionConfig::class)]
#[CoversClass(FunctionConfigTransformer::class)]
final class FunctionConfigTransformerTest extends TestCase
{
    #[TestWith([true])]
    #[TestWith([false])]
    public function test(bool $debug): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_ALLOW_LOCAL_ORIGINS => 'true',
            FunctionConfigTransformerInterface::ENV_ALLOW_UNAUTHENTICATED => 'true',
            FunctionConfigTransformerInterface::ENV_DEBUG => $debug ? 'true' : 'false',
            FunctionConfigTransformerInterface::ENV_K_REVISION => 'test-krevision',
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_KEY => 'test-required-header-key',
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_VALUE => 'test-required-header-value',
            FunctionConfigTransformerInterface::ENV_REQUIRED_ORIGIN => 'test-required-origin',
            FunctionConfigTransformerInterface::ENV_SURROGATE_KEY => 'test-surrogate-key',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_TTL => '3600',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_BUT_REQUEST_TTL => '7200',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_IF_ERROR_TTL => '259200',
        ];
        $transformer = new FunctionConfigTransformer();
        $actual = $transformer->transform($env);
        self::assertSame('test-krevision', $actual->getKrevision());
        self::assertTrue($actual->getAllowLocalOrigins());
        self::assertTrue($actual->getAllowUnauthenticated());
        self::assertSame($debug, $actual->getDebug());
        self::assertSame('test-required-header-key', $actual->getRequiredHeaderKey());
        self::assertSame('test-required-header-value', $actual->getRequiredHeaderValue());
        self::assertSame('test-required-origin', $actual->getRequiredOrigin());
        self::assertSame('test-surrogate-key', $actual->getSurrogateKey());
        self::assertSame(3600, $actual->getUseCacheTtl());
        self::assertSame(7200, $actual->getUseCacheButRequestTtl());
        self::assertSame(259200, $actual->getUseCacheIfErrorTtl());
    }

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([123])]
    public function testKRevisionNotNumber(mixed $value): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_K_REVISION => $value,
        ];
        $transformer = new FunctionConfigTransformer();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('%s not set or not a string', FunctionConfigTransformerInterface::ENV_K_REVISION));
        $transformer->transform($env);
    }

    public function testMinimalEnv(): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_K_REVISION => 'test-krevision',
        ];
        $transformer = new FunctionConfigTransformer();
        $actual = $transformer->transform($env);
        self::assertSame('test-krevision', $actual->getKrevision());
        self::assertFalse($actual->getAllowLocalOrigins());
        self::assertFalse($actual->getAllowUnauthenticated());
        self::assertFalse($actual->getDebug());
        self::assertNull($actual->getRequiredHeaderKey());
        self::assertNull($actual->getRequiredHeaderValue());
        self::assertNull($actual->getRequiredOrigin());
        self::assertNull($actual->getSurrogateKey());
        self::assertNull($actual->getUseCacheTtl());
        self::assertNull($actual->getUseCacheButRequestTtl());
        self::assertNull($actual->getUseCacheIfErrorTtl());
    }

    public function testNonStringOptionalsAreIgnored(): void
    {
        $env = [
            FunctionConfigTransformerInterface::ENV_K_REVISION => 'test-krevision',
            FunctionConfigTransformerInterface::ENV_ALLOW_LOCAL_ORIGINS => 'false',
            FunctionConfigTransformerInterface::ENV_ALLOW_UNAUTHENTICATED => 'false',
            FunctionConfigTransformerInterface::ENV_DEBUG => 'false',
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_KEY => 123,
            FunctionConfigTransformerInterface::ENV_REQUIRED_HEADER_VALUE => 456,
            FunctionConfigTransformerInterface::ENV_REQUIRED_ORIGIN => 789,
            FunctionConfigTransformerInterface::ENV_SURROGATE_KEY => 321,
            FunctionConfigTransformerInterface::ENV_USE_CACHE_TTL => 'not-numeric',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_BUT_REQUEST_TTL => 'not-numeric',
            FunctionConfigTransformerInterface::ENV_USE_CACHE_IF_ERROR_TTL => 'not-numeric',
        ];
        $transformer = new FunctionConfigTransformer();
        $actual = $transformer->transform($env);
        self::assertSame('test-krevision', $actual->getKrevision());
        self::assertFalse($actual->getAllowLocalOrigins());
        self::assertFalse($actual->getAllowUnauthenticated());
        self::assertFalse($actual->getDebug());
        self::assertNull($actual->getRequiredHeaderKey());
        self::assertNull($actual->getRequiredHeaderValue());
        self::assertNull($actual->getRequiredOrigin());
        self::assertNull($actual->getSurrogateKey());
        self::assertNull($actual->getUseCacheTtl());
        self::assertNull($actual->getUseCacheButRequestTtl());
        self::assertNull($actual->getUseCacheIfErrorTtl());
    }
}
