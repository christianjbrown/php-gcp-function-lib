<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction\Tests;

use ChristianBrown\GcpFunction\AllowOriginResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllowOriginResolver::class)]
final class AllowOriginResolverTest extends TestCase
{
    #[DataProvider('provideResolveCases')]
    public function testResolve(bool $debug, ?string $requestOrigin, string $expected): void
    {
        $resolver = new AllowOriginResolver();

        self::assertSame($expected, $resolver->resolve('https://pinned.example', $requestOrigin, $debug));
    }

    /**
     * @return iterable<string, array{bool, ?string, string}>
     */
    public static function provideResolveCases(): iterable
    {
        // No request origin at all: the configured origin is pinned.
        yield 'null request origin' => [true, null, 'https://pinned.example'];
        // Production (debug off): the configured origin is always pinned, never reflected.
        yield 'debug off pins localhost' => [false, 'http://localhost:3000', 'https://pinned.example'];
        // Debug on, but the origin is not a loopback host: still pinned.
        yield 'debug empty origin' => [true, '', 'https://pinned.example'];
        yield 'debug malformed origin' => [true, 'not-a-url', 'https://pinned.example'];
        yield 'debug non-local host' => [true, 'https://app.example.com', 'https://pinned.example'];
        yield 'debug userinfo trick' => [true, 'http://localhost@example.com', 'https://pinned.example'];
        // Debug on with a genuine loopback origin: reflected as a development convenience.
        yield 'debug localhost reflected' => [true, 'http://localhost:3000', 'http://localhost:3000'];
        yield 'debug loopback ip reflected' => [true, 'http://127.0.0.1:8080', 'http://127.0.0.1:8080'];
    }
}
