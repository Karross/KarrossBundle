<?php

namespace E2e\WithConfig;

use Doctrine\ORM\EntityManagerInterface;
use E2e\Shared\DatabaseFixture;
use E2e\Shared\TableCellComparisons;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class ArticleListTest extends PlaywrightTestCase
{
    use DatabaseFixture;
    use TableCellComparisons;

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_with_config', true, [
            __DIR__.'/../../../tests/Integration/TestedApp/config/doctrine_standard.php',
            __DIR__.'/../../../tests/Integration/TestedApp/config/karross_custom.php',
            __DIR__.'/../../../tests/Integration/TestedApp/config/framework_locales.php',
        ]);
    }

    public function testConfiguredRoutesServeTheAdminUnderTheDashboardPrefix(): void
    {
        $this->resetSchema();
        $page = $this->visit('/fr/dashboard/article');

        self::assertResponseIsSuccessful();
        $page->getByRole('heading', ['name' => 'Index article'])->waitFor();
    }

    public function testFormattersFollowTheRequestLocaleResolvedFromTheUrl(): void
    {
        $this->resetSchema();
        $article = new Article()
            ->setTitle('Découverte de la Provence')
            ->setContent('Un joli contenu.')
            ->setPublished(true)
            ->setPremium(true)
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
        // Out-of-the-box boolean formatter: `published` (non-nullable) renders true/false,
        // following the request locale through the bundle's own value keys.
        $this->assertCellEquals($this->visit('/fr/dashboard/article'), 'Published', 'vrai');
        $this->assertCellEquals($this->visit('/en/dashboard/article'), 'Published', 'true');
        // Configured nullable boolean: `premium` renders Oui/Non through YesNoFormatter.
        $this->assertCellEquals($this->visit('/fr/dashboard/article'), 'Premium', 'Oui');
        $this->assertCellEquals($this->visit('/en/dashboard/article'), 'Premium', 'Yes');
    }

    public function testNullableBooleanConfiguredWithYesNoRendersThreeStates(): void
    {
        $this->resetSchema();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist(new Article()->setTitle('Undecided')->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'))->setStatus(Status::DRAFT)->setPremium(null));
        $em->persist(new Article()->setTitle('Premium')->setCreatedAt(new \DateTimeImmutable('2026-01-02 08:00:00'))->setStatus(Status::DRAFT)->setPremium(true));
        $em->persist(new Article()->setTitle('Not premium')->setCreatedAt(new \DateTimeImmutable('2026-01-03 08:00:00'))->setStatus(Status::DRAFT)->setPremium(false));
        $em->flush();

        $expected = [
            '/fr/dashboard/article' => ['', 'Oui', 'Non'],
            '/en/dashboard/article' => ['', 'Yes', 'No'],
        ];
        foreach ($expected as $url => $states) {
            $page = $this->visit($url);
            for ($row = 0; $row < 3; ++$row) {
                $this->assertCellEquals($page, 'Premium', $states[$row], row: $row);
            }
        }
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

    private function assertUnsupportedCellEquals(PageInterface $page, string $expected): void
    {
        $this->assertCellEquals($page, 'Tags', $expected);
    }
}
