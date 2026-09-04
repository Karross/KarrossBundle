<?php

namespace E2e;

use Doctrine\ORM\EntityManagerInterface;
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
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_no_shortname_entity_conflicts.php',
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

    private function createSchema(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
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

    private function formatDate(\DateTimeImmutable $value, bool $dateOnly = false): string
    {
        $dateType = $dateOnly ? \IntlDateFormatter::MEDIUM : \IntlDateFormatter::MEDIUM;
        $timeType = $dateOnly ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT;
        $formatter = new \IntlDateFormatter('en_US', $dateType, $timeType);

        return $formatter->format($value) ?: 'N/A';
    }
}
