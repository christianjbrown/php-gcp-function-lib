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
    public function testResolve(bool $debug, bool $allowLocalOrigins, ?string $requestOrigin, string $expected): void
    {
        $resolver = new AllowOriginResolver();

        self::assertSame($expected, $resolver->resolve('https://pinned.example', $requestOrigin, $debug, $allowLocalOrigins));
    }

    /**
     * @return iterable<string, array{bool, bool, ?string, string}>
     */
    public static function provideResolveCases(): iterable
    {
        // No request origin at all: the configured origin is pinned.
        yield 'null request origin' => [true, true, null, 'https://pinned.example'];
        // Neither flag set: the configured origin is always pinned, never reflected.
        yield 'both off pins localhost' => [false, false, 'http://localhost:3000', 'https://pinned.example'];
        // Debug on, but the origin is not a loopback host: still pinned.
        yield 'debug empty origin' => [true, false, '', 'https://pinned.example'];
        yield 'debug malformed origin' => [true, false, 'not-a-url', 'https://pinned.example'];
        yield 'debug non-local host' => [true, false, 'https://app.example.com', 'https://pinned.example'];
        yield 'debug userinfo trick' => [true, false, 'http://localhost@example.com', 'https://pinned.example'];
        // Debug on with a genuine loopback origin: reflected as a development convenience.
        yield 'debug localhost reflected' => [true, false, 'http://localhost:3000', 'http://localhost:3000'];
        yield 'debug loopback ip reflected' => [true, false, 'http://127.0.0.1:8080', 'http://127.0.0.1:8080'];
        // Allow-local-origins flag reflects loopback origins independently of debug.
        yield 'allow local localhost reflected' => [false, true, 'http://localhost:3000', 'http://localhost:3000'];
        yield 'allow local 0.0.0.0 reflected' => [false, true, 'http://0.0.0.0:4000', 'http://0.0.0.0:4000'];
        yield 'allow local ipv6 loopback reflected' => [false, true, 'http://[::1]:4000', 'http://[::1]:4000'];
        // Allow-local-origins still pins a non-loopback origin.
        yield 'allow local non-local pinned' => [false, true, 'https://app.example.com', 'https://pinned.example'];
    }
}
