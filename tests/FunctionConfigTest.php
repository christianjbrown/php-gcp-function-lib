<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\FunctionConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionConfig::class)]
final class FunctionConfigTest extends TestCase
{
    public function test(): void
    {
        $functionConfig = new FunctionConfig('test-krevision');
        self::assertSame('test-krevision', $functionConfig->getKrevision());

        self::assertFalse($functionConfig->getAllowLocalOrigins());
        self::assertFalse($functionConfig->getAllowUnauthenticated());
        self::assertFalse($functionConfig->getDebug());
        self::assertNull($functionConfig->getRequiredHeaderKey());
        self::assertNull($functionConfig->getRequiredHeaderValue());
        self::assertNull($functionConfig->getRequiredOrigin());
        self::assertNull($functionConfig->getUseCacheTtl());
        self::assertNull($functionConfig->getUseCacheButRequestTtl());
        self::assertNull($functionConfig->getUseCacheIfErrorTtl());

        $functionConfig->setAllowLocalOrigins(true);
        $functionConfig->setAllowUnauthenticated(true);
        $functionConfig->setDebug(true);
        $functionConfig->setRequiredHeaderKey('test-required-header-key');
        $functionConfig->setRequiredHeaderValue('test-required-header-value');
        $functionConfig->setRequiredOrigin('test-required-origin');
        $functionConfig->setUseCacheTtl(3600);
        $functionConfig->setUseCacheButRequestTtl(7200);
        $functionConfig->setUseCacheIfErrorTtl(259200);

        self::assertTrue($functionConfig->getAllowLocalOrigins());
        self::assertTrue($functionConfig->getAllowUnauthenticated());
        self::assertTrue($functionConfig->getDebug());
        self::assertSame('test-required-header-key', $functionConfig->getRequiredHeaderKey());
        self::assertSame('test-required-header-value', $functionConfig->getRequiredHeaderValue());
        self::assertSame('test-required-origin', $functionConfig->getRequiredOrigin());
        self::assertSame(3600, $functionConfig->getUseCacheTtl());
        self::assertSame(7200, $functionConfig->getUseCacheButRequestTtl());
        self::assertSame(259200, $functionConfig->getUseCacheIfErrorTtl());
    }
}
