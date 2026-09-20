<?php

namespace E2e\HostOverrides;

use Doctrine\Persistence\ManagerRegistry;
use E2e\Shared\DatabaseFixture;
use E2e\Shared\TableCellComparisons;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\TemplateOverride\Kernel;

final class TemplateOverrideTest extends PlaywrightTestCase
{
    use DatabaseFixture;
    use TableCellComparisons;

    /** @param array<string, mixed> $options */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_template_override', true, [
            __DIR__.'/../../../tests/Integration/TestedApp/config/doctrine_standard.php',
        ]);
    }

    public function testHostOverrideTemplateWinsForDatetimeCellsOnly(): void
    {
        $this->resetSchema();

        $article = new Article()
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

        $createdAtCell = $this->cellLocatorForColumn($page, 'Createdat');
        $createdAtWrapper = $createdAtCell->locator('.datetime-cell');
        $createdAtWrapper->waitFor();
        self::assertSame(
            $this->formatDate(new \DateTimeImmutable('2026-03-05 15:30:00')),
            trim($createdAtWrapper->innerText())
        );

        $scheduledDateCell = $this->cellLocatorForColumn($page, 'Scheduleddate');
        $scheduledDateCell->locator('.datetime-cell')->waitFor();

        $titleCell = $this->cellLocatorForColumn($page, 'Title');
        self::assertSame(0, $titleCell->locator('.datetime-cell')->count());
    }
}
