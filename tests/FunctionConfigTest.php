<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionConfig::class)]
final class FunctionConfigTest extends TestCase
{
    public function test(): void
    {
        $functionConfig = new FunctionConfig(123);
        self::assertSame(123, $functionConfig->getKrevision());

        self::assertFalse($functionConfig->getDebug());
        self::assertNull($functionConfig->getRequiredHeaderKey());
        self::assertNull($functionConfig->getRequiredHeaderValue());
        self::assertNull($functionConfig->getRequiredOrigin());

        $functionConfig->setDebug(true);
        $functionConfig->setRequiredHeaderKey('test-required-header-key');
        $functionConfig->setRequiredHeaderValue('test-required-header-value');
        $functionConfig->setRequiredOrigin('test-required-origin');

        self::assertTrue($functionConfig->getDebug());
        self::assertSame('test-required-header-key', $functionConfig->getRequiredHeaderKey());
        self::assertSame('test-required-header-value', $functionConfig->getRequiredHeaderValue());
        self::assertSame('test-required-origin', $functionConfig->getRequiredOrigin());
    }
}
