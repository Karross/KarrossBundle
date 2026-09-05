<?php

namespace Integration\Routes;

use Karross\Routes\RoutePattern;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RoutePatternTest extends TestCase
{
    /**
     * @param string[] $identifiers
     */
    #[DataProvider('resolveProvider')]
    public function testResolve(string $pattern, string $prefix, string $slug, array $identifiers, string $expected): void
    {
        $resolver = new RoutePattern();
        self::assertSame($expected, $resolver->resolve($pattern, $prefix, $slug, $identifiers));
    }

    public static function resolveProvider(): \Generator
    {
        yield 'default index pattern' => [
            '/{prefix}/{slug}', 'admin', 'article', ['id'], '/admin/article',
        ];

        yield 'default show pattern, simple key' => [
            '/{prefix}/{slug}/{identifiers}', 'admin', 'article', ['id'], '/admin/article/{id}',
        ];

        yield 'default show pattern, composite key' => [
            '/{prefix}/{slug}/{identifiers}', 'admin', 'invoice', ['invoiceId', 'lineId'], '/admin/invoice/{invoiceId}/{lineId}',
        ];

        yield 'custom prefix' => [
            '/{prefix}/{slug}', 'dashboard', 'article', ['id'], '/dashboard/article',
        ];

        yield 'locale in path' => [
            '/{_locale}/{prefix}/{slug}', 'admin', 'article', ['id'], '/{_locale}/admin/article',
        ];

        yield 'identifiers not at the end' => [
            '/{slug}/{identifiers}/view', 'admin', 'article', ['id'], '/article/{id}/view',
        ];
    }

    /**
     * @param string[] $patterns
     */
    #[DataProvider('validateProvider')]
    public function testValidate(array $patterns, ?string $expectedExceptionMessage): void
    {
        $resolver = new RoutePattern();

        if (null !== $expectedExceptionMessage) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        foreach ($patterns as $pattern) {
            self::assertSame($pattern, $resolver->validate($pattern));
        }
    }

    public static function validateProvider(): \Generator
    {
        yield 'all known tokens are valid' => [
            ['/{prefix}/{slug}', '/{prefix}/{slug}/{identifiers}', '/{_locale}/{slug}'],
            null,
        ];

        yield 'unknown token throws' => [
            ['/admin/{slug}/{bogus}'],
            'Unknown token {bogus} in Karross route pattern "/admin/{slug}/{bogus}". Allowed tokens: {prefix}, {slug}, {identifiers}, {_locale}.',
        ];
    }
}
