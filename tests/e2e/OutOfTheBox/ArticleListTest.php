<?php

namespace E2e\OutOfTheBox;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use E2e\Shared\DatabaseFixture;
use E2e\Shared\TableCellComparisons;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class ArticleListTest extends PlaywrightTestCase
{
    use BlackBox;
    use DatabaseFixture;
    use TableCellComparisons;

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e', true, [
            __DIR__.'/../../../tests/Integration/TestedApp/config/doctrine_standard.php',
        ]);
    }

    public function testIndexRendersTheAdminListing(): void
    {
        $this->resetSchema();
        $page = $this->visit('/admin/article');

        self::assertResponseIsSuccessful();
        $page->getByRole('heading', ['name' => 'Index article'])->waitFor();
    }

    public function testIndexFormatsEachColumnAgainstItsPredictableFormatter(): void
    {
        $this->resetSchema();

        $article = new Article()
            ->setTitle('Découverte de la Provence')
            ->setContent('Un joli contenu.')
            ->setPublished(true)
            ->setViewCount(42)
            ->setSortOrder('1234')
            ->setBigCounter('12345')
            ->setPrice('19.90')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-05 15:30:00'))
            ->setStatus(Status::PUBLISHED)
            ->setTags(['tourisme', 'nature'])
            ->setScheduledDate(new \DateTimeImmutable('2026-06-01'))
            ->setPublishedAt(new \DateTimeImmutable('2026-03-06 09:00:00'));
        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        $em->persist($article);
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'Découverte de la Provence');
        $this->assertCellEquals($page, 'Published', 'true');
        $this->assertCellEquals($page, 'Viewcount', '42');
        $this->assertCellEquals($page, 'Sortorder', '1,234');
        $this->assertCellEquals($page, 'Bigcounter', '12,345');
        $this->assertCellEquals($page, 'Status', 'published');

        $this->assertCellEquals(
            $page,
            'Createdat',
            $this->formatDate(new \DateTimeImmutable('2026-03-05 15:30:00'))
        );
        $this->assertCellEquals(
            $page,
            'Scheduleddate',
            $this->formatDate(new \DateTimeImmutable('2026-06-01'), dateOnly: true)
        );
    }

    public function testViewCountCellMatchesOracleForAnyInteger(): void
    {
        $this->forAll(Set::integers())->then(function (int $viewCount): void {
            $this->resetSchema();

            $article = new Article()
                ->setTitle('Compteur variable')
                ->setViewCount($viewCount)
                ->setStatus(Status::PUBLISHED)
                ->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'));

            $registry = self::getContainer()->get('doctrine');
            \assert($registry instanceof ManagerRegistry);
            $em = $registry->getManager();
            \assert($em instanceof EntityManagerInterface);
            $em->persist($article);
            $em->flush();

            $page = $this->visit('/admin/article');

            $this->assertCellEquals($page, 'Title', 'Compteur variable');
            $this->assertCellEquals($page, 'Viewcount', $this->formatCount($viewCount));
        });
    }

    #[DataProvider('booleanProvider')]
    public function testBooleanCellRendersTrueOrFalseForRawValues(bool $published): void
    {
        $this->resetSchema();

        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        $em->persist(new Article()->setTitle('Boolean')->setPublished($published)->setStatus(Status::DRAFT)->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00')));
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'Boolean');
        $this->assertCellEquals($page, 'Published', $published ? 'true' : 'false');
    }

    #[DataProvider('nullableBooleanProvider')]
    public function testNullableBooleanRendersEmptyForNullAndTrueOrFalseOtherwise(?bool $premium): void
    {
        $this->resetSchema();

        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        $em->persist(new Article()->setTitle('Nullable')->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'))->setStatus(Status::DRAFT)->setPremium($premium));
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'Nullable');
        $this->assertCellEquals($page, 'Premium', null === $premium ? '' : ($premium ? 'true' : 'false'));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function booleanProvider(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
    }

    /**
     * @return iterable<string, array{?bool}>
     */
    public static function nullableBooleanProvider(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
    }
}
