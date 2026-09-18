<?php

namespace E2e;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class ArticleIndexTest extends PlaywrightTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_standard.php',
        ]);
    }

    public function testIndexRendersTheAdminListing(): void
    {
        $this->createSchema();
        $page = $this->visit('/admin/article');

        self::assertResponseIsSuccessful();
        $page->getByRole('heading', ['name' => 'Index article'])->waitFor();
    }

    public function testIndexFormatsEachColumnAgainstItsPredictableFormatter(): void
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
            ->setTags(['tourisme', 'nature'])
            ->setScheduledDate(new \DateTimeImmutable('2026-06-01'))
            ->setPublishedAt(new \DateTimeImmutable('2026-03-06 09:00:00'));
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($article);
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'Découverte de la Provence');
        $this->assertCellEquals($page, 'Published', 'true');
        $this->assertCellEquals($page, 'Viewcount', '42');
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

    public function testBooleanCellRendersTrueOrFalseForRawValues(): void
    {
        $this->createSchema();

        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        $em->persist((new Article())->setTitle('First')->setPublished(true)->setStatus(Status::DRAFT)->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00')));
        $em->persist((new Article())->setTitle('Second')->setPublished(false)->setStatus(Status::PUBLISHED)->setCreatedAt(new \DateTimeImmutable('2026-01-02 08:00:00')));
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'First', row: 0);
        $this->assertCellEquals($page, 'Published', 'true', row: 0);
        $this->assertCellEquals($page, 'Title', 'Second', row: 1);
        $this->assertCellEquals($page, 'Published', 'false', row: 1);
    }

    public function testNullableBooleanRendersEmptyForNullAndTrueOrFalseOtherwise(): void
    {
        $this->createSchema();

        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        $em->persist((new Article())->setTitle('Undecided')->setCreatedAt(new \DateTimeImmutable('2026-01-01 08:00:00'))->setStatus(Status::DRAFT)->setPremium(null));
        $em->persist((new Article())->setTitle('Premium')->setCreatedAt(new \DateTimeImmutable('2026-01-02 08:00:00'))->setStatus(Status::DRAFT)->setPremium(true));
        $em->persist((new Article())->setTitle('Not premium')->setCreatedAt(new \DateTimeImmutable('2026-01-03 08:00:00'))->setStatus(Status::DRAFT)->setPremium(false));
        $em->flush();

        $page = $this->visit('/admin/article');

        $this->assertCellEquals($page, 'Title', 'Undecided', row: 0);
        $this->assertCellEquals($page, 'Premium', '', row: 0);
        $this->assertCellEquals($page, 'Title', 'Premium', row: 1);
        $this->assertCellEquals($page, 'Premium', 'true', row: 1);
        $this->assertCellEquals($page, 'Title', 'Not premium', row: 2);
        $this->assertCellEquals($page, 'Premium', 'false', row: 2);
    }

    private function createSchema(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    private function assertCellEquals(PageInterface $page, string $columnName, string $expected, int $row = 0): void
    {
        self::assertSame($expected, $this->cellForColumn($page, $columnName, $row));
    }

    private function cellForColumn(PageInterface $page, string $columnName, int $row = 0): string
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
        $line = $page->locator('table.k-table tbody tr')->nth($row);

        return trim($line->locator('td')->nth($index)->innerText());
    }

    private function formatDate(\DateTimeImmutable $value, bool $dateOnly = false): string
    {
        $dateType = $dateOnly ? \IntlDateFormatter::MEDIUM : \IntlDateFormatter::MEDIUM;
        $timeType = $dateOnly ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT;
        $formatter = new \IntlDateFormatter('en_US', $dateType, $timeType);

        return $formatter->format($value) ?: 'N/A';
    }
}
