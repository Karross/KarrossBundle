<?php

namespace E2e;

use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Kernel;

final class HomeConfiguredTest extends PlaywrightTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_home_config', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_standard.php',
            __DIR__.'/../../tests/Integration/TestedApp/config/karross_custom.php',
            __DIR__.'/../../tests/Integration/TestedApp/config/framework_locales.php',
        ]);
    }

    public function testHomeServesTheLocalizedPortalUnderTheConfiguredPrefix(): void
    {
        $page = $this->visit('/fr/dashboard');

        self::assertResponseIsSuccessful();
        $page->getByText('Administration Karross')->waitFor();

        foreach (['/fr/dashboard/article', '/fr/dashboard/category'] as $href) {
            self::assertSame(1, $page->locator('a.k-card[href="'.$href.'"]')->count());
        }

        self::assertSame(1, $page->locator('.k-doc-card')->count());
    }
}
