<?php

namespace E2e\WithConfig;

use Doctrine\ORM\EntityManagerInterface;
use E2e\Shared\DatabaseFixture;
use E2e\Shared\TableCellComparisons;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('booleanFormatterProvider')]
    public function testBooleanFormatterFollowsLocale(bool $published, string $locale): void
    {
        $this->resetSchema();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist(new Article()->setTitle('BoolLocale')->setPublished($published)->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'))->setStatus(Status::DRAFT));
        $em->flush();

        $page = $this->visit('/'.$locale.'/dashboard/article');
        $this->assertCellEquals($page, 'Published', $published ? ('fr' === $locale ? 'vrai' : 'true') : ('fr' === $locale ? 'faux' : 'false'));
    }

    #[DataProvider('nullableBooleanFormatterProvider')]
    public function testNullableBooleanConfiguredWithYesNoRendersThreeStates(?bool $premium, string $locale): void
    {
        $this->resetSchema();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist(new Article()->setTitle('Nullable')->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'))->setStatus(Status::DRAFT)->setPremium($premium));
        $em->flush();

        $url = '/'.$locale.'/dashboard/article';
        $page = $this->visit($url);
        $expected = match ([$premium, $locale]) {
            [null, 'fr'] => '',
            [null, 'en'] => '',
            [true, 'fr'] => 'Oui',
            [true, 'en'] => 'Yes',
            [false, 'fr'] => 'Non',
            [false, 'en'] => 'No',
            default => throw new \LogicException('Unhandled case: '.var_export([$premium, $locale], true)),
        };
        $this->assertCellEquals($page, 'Premium', $expected);
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function booleanFormatterProvider(): iterable
    {
        yield 'true-fr' => [true, 'fr'];
        yield 'true-en' => [true, 'en'];
        yield 'false-fr' => [false, 'fr'];
        yield 'false-en' => [false, 'en'];
    }

    /**
     * @return iterable<string, array{?bool, string}>
     */
    public static function nullableBooleanFormatterProvider(): iterable
    {
        yield 'true-fr' => [true, 'fr'];
        yield 'true-en' => [true, 'en'];
        yield 'false-fr' => [false, 'fr'];
        yield 'false-en' => [false, 'en'];
        yield 'null-fr' => [null, 'fr'];
        yield 'null-en' => [null, 'en'];
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
