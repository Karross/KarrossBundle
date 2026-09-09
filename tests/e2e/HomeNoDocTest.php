<?php

namespace E2e;

use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Kernel;

final class HomeNoDocTest extends PlaywrightTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_home_no_doc', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_standard.php',
            __DIR__.'/../../tests/Integration/TestedApp/config/karross_home_no_documentation.php',
        ]);
    }

    public function testHiddenDocumentationCardDoesNotHideTheEntityCards(): void
    {
        $page = $this->visit('/admin');

        self::assertResponseIsSuccessful();
        $page->getByText('Karross administration')->waitFor();

        self::assertSame(0, $page->locator('.k-doc-card')->count());

        foreach (['/admin/article', '/admin/category'] as $href) {
            self::assertSame(1, $page->locator('a.k-card[href="'.$href.'"]')->count());
        }
    }
}
