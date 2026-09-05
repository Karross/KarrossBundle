<?php

namespace E2e;

use Doctrine\ORM\EntityManagerInterface;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class ArticleIndexConfiguredTest extends PlaywrightTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_with_config', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_no_shortname_entity_conflicts.php',
            __DIR__.'/../../tests/Integration/TestedApp/config/karross_with_config.php',
        ]);
    }

    public function testConfiguredRoutesServeTheAdminUnderTheDashboardPrefix(): void
    {
        $this->createSchema();
        $page = $this->visit('/fr/dashboard/article');

        self::assertResponseIsSuccessful();
        $page->getByRole('heading', ['name' => 'Index article'])->waitFor();
    }

    public function testFormattersFollowTheRequestLocaleResolvedFromTheUrl(): void
    {
        $this->createSchema();
        $article = (new Article())
            ->setTitle('Découverte de la Provence')
            ->setContent('Un joli contenu.')
            ->setPublished(true)
            ->setViewCount(42)
            ->setPrice('19.90')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-05 15:30:00'))
            ->setStatus(Status::PUBLISHED)
            ->setTags(['tourisme', 'nature']);

        $em = self::getContainer()->get(EntityManagerInterface::class);

        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist($article);
        $em->flush();

        $this->assertUnsupportedCellEquals(
            $this->visit('/fr/dashboard/article'),
            'Non supporté'
        );
        $this->assertUnsupportedCellEquals(
            $this->visit('/en/dashboard/article'),
            'Not supported'
        );

        $page = $this->visit('/fr/dashboard/article');
        $this->assertCellEquals(
            $page,
            'Createdat',
            $this->formatDateWith('fr', new \DateTimeImmutable('2026-03-05 15:30:00'))
        );
        $this->assertStringContainsString('42', $this->cellForColumn($page, 'Viewcount'));
        $this->assertPriceCellUsesLocaleAndCurrency($page, 'fr');
        $this->assertPriceCellUsesLocaleAndCurrency($this->visit('/en/dashboard/article'), 'en');
    }

    private function assertPriceCellUsesLocaleAndCurrency(PageInterface $page, string $locale): void
    {
        $cell = $this->cellForColumn($page, 'Price');
        self::assertStringContainsString('€', $cell, "Price should render the EUR symbol in $locale.");
        self::assertStringContainsString(
            'fr' === $locale ? ',' : '.',
            $cell,
            "Price should use the $locale decimal separator."
        );
    }

    private function createSchema(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    private function assertUnsupportedCellEquals(PageInterface $page, string $expected): void
    {
        $this->assertCellEquals($page, 'Tags', $expected);
    }

    private function assertCellEquals(PageInterface $page, string $columnName, string $expected): void
    {
        self::assertSame($expected, $this->cellForColumn($page, $columnName));
    }

    private function cellForColumn(PageInterface $page, string $columnName): string
    {
        $headers = $page->locator('table.k-table thead th');

        $index = null;
        for ($i = 0, $count = $headers->count(); $i < $count; ++$i) {
            if (trim($headers->nth($i)->innerText()) === $columnName) {
                $index = $i;
                break;
            }
        }

        self::assertNotNull($index, "Column '$columnName' not found in table header");
        $row = $page->locator('table.k-table tbody tr')->first();

        return trim($row->locator('td')->nth($index)->innerText());
    }

    private function formatDateWith(string $locale, \DateTimeImmutable $value): string
    {
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);

        return $formatter->format($value) ?: 'N/A';
    }
}
