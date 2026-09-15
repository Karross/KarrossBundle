<?php

namespace E2e;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\TemplateOverride\Kernel;

final class IndexTemplateOverrideTest extends PlaywrightTestCase
{
    /** @param array<string, mixed> $options */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_template_override', true, [
            __DIR__.'/../../tests/Integration/TestedApp/config/doctrine_standard.php',
        ]);
    }

    public function testHostOverrideTemplateWinsForDatetimeCellsOnly(): void
    {
        $this->createSchema();

        $article = (new Article())
            ->setTitle('Découverte de la Provence')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-05 15:30:00'))
            ->setScheduledDate(new \DateTimeImmutable('2026-06-01'))
            ->setStatus(Status::PUBLISHED);

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $em->persist($article);
        $em->flush();

        $page = $this->visit('/admin/article');

        self::assertResponseIsSuccessful();
        self::assertSame('Index article', $page->getByRole('heading', ['name' => 'Index article'])->innerText());

        $createdAtCell = $this->cellForColumn($page, 'Createdat');
        $createdAtWrapper = $createdAtCell->locator('.datetime-cell');
        $createdAtWrapper->waitFor();
        self::assertSame(
            $this->formatDate(new \DateTimeImmutable('2026-03-05 15:30:00')),
            trim($createdAtWrapper->innerText())
        );

        $scheduledDateCell = $this->cellForColumn($page, 'Scheduleddate');
        $scheduledDateCell->locator('.datetime-cell')->waitFor();

        $titleCell = $this->cellForColumn($page, 'Title');
        self::assertSame(0, $titleCell->locator('.datetime-cell')->count());
    }

    private function createSchema(): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        \assert($em instanceof EntityManagerInterface);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    private function cellForColumn(PageInterface $page, string $columnName): LocatorInterface
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

        return $row->locator('td')->nth($index);
    }

    private function formatDate(\DateTimeImmutable $value): string
    {
        $formatter = new \IntlDateFormatter('en_US', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);

        return $formatter->format($value) ?: 'N/A';
    }
}
