<?php

namespace E2e;

use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Kernel;

final class HomeTest extends PlaywrightTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_home', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_standard.php',
        ]);
    }

    public function testHomeRendersOneCardPerEntityAndTheDocumentationCard(): void
    {
        $page = $this->visit('/admin');

        self::assertResponseIsSuccessful();
        $page->getByText('Karross administration')->waitFor();

        foreach (['/admin/article', '/admin/category'] as $href) {
            self::assertSame(1, $page->locator('a.k-card[href="'.$href.'"]')->count());
        }

        self::assertSame(1, $page->locator('.k-doc-card')->count());
        $page->getByRole('heading', ['name' => 'Documentation'])->waitFor();
    }

    public function testTrailingSlashRedirectsToTheCanonicalHomeUrl(): void
    {
        $page = $this->visit('/admin/');

        self::assertSame('/admin', parse_url($page->url(), \PHP_URL_PATH));
        $page->getByText('Karross administration')->waitFor();
    }
}
