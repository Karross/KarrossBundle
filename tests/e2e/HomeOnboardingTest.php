<?php

namespace E2e;

use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Kernel;

final class HomeOnboardingTest extends PlaywrightTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_home_onboarding', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_empty.php',
        ]);
    }

    public function testWithNoMappedEntityAnOnboardingBlockReplacesTheCards(): void
    {
        $page = $this->visit('/admin');

        self::assertResponseIsSuccessful();
        $page->getByText('Karross administration')->waitFor();

        self::assertSame(0, $page->locator('a.k-card')->count());

        $page->getByRole('heading', ['name' => 'No entities yet'])->waitFor();
        self::assertSame(1, $page->locator('a[href="https://symfony.com/doc/current/doctrine.html"]')->count());
        self::assertSame(1, $page->locator('a[href="https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/attributes-reference.html"]')->count());

        self::assertSame(1, $page->locator('.k-doc-card')->count());
    }
}
