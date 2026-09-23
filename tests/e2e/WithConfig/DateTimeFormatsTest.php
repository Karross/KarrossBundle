<?php

namespace E2e\WithConfig;

use Doctrine\ORM\EntityManagerInterface;
use E2e\Shared\DatabaseFixture;
use E2e\Shared\TableCellComparisons;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class DateTimeFormatsTest extends PlaywrightTestCase
{
    use DatabaseFixture;
    use TableCellComparisons;

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e_datetime_formats', true, [
            __DIR__.'/../../../tests/Integration/TestedApp/config/doctrine_standard.php',
            __DIR__.'/../../../tests/Integration/TestedApp/config/karross_custom.php',
            __DIR__.'/../../../tests/Integration/TestedApp/config/karross_datetime_formats.php',
            __DIR__.'/../../../tests/Integration/TestedApp/config/framework_locales.php',
        ]);
    }

    public function testCustomNamedFormatRendersPerRequestLocale(): void
    {
        $this->resetSchema();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist(
            new Article()
                ->setTitle('Format nommé')
                ->setCreatedAt(new \DateTimeImmutable('2026-03-05 15:30:00'))
                ->setScheduledDate(new \DateTimeImmutable('2026-06-01'))
                ->setStatus(Status::DRAFT)
        );
        $em->flush();

        $this->assertCellEquals(
            $this->visit('/fr/dashboard/article'),
            'Createdat',
            '5 mars 2026 à 15:30',
        );
        $this->assertCellEquals(
            $this->visit('/en/dashboard/article'),
            'Createdat',
            'March 5, 2026 at 15:30',
        );
    }

    public function testHostOverrideOfMediumDateAffectsImplicitDateProperties(): void
    {
        $this->resetSchema();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine EntityManager not available.');
        }
        $em->persist(
            new Article()
                ->setTitle('Date implicite')
                ->setCreatedAt(new \DateTimeImmutable('2026-03-05 15:30:00'))
                ->setScheduledDate(new \DateTimeImmutable('2026-06-01'))
                ->setStatus(Status::DRAFT)
        );
        $em->flush();

        // scheduledDate has no datetime_format option → implicit k_medium_date.
        // Without a host override it stays ICU MEDIUM; with karross_datetime_formats
        // only createdAt is configured, so scheduledDate still uses the default medium.
        $this->assertCellEquals(
            $this->visit('/en/dashboard/article'),
            'Scheduleddate',
            $this->formatDate(new \DateTimeImmutable('2026-06-01'), dateOnly: true),
        );
    }
}
