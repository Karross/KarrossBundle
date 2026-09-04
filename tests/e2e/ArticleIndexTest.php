<?php

namespace E2e;

use Doctrine\ORM\EntityManagerInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TestedApp\Kernel;

final class ArticleIndexTest extends PlaywrightTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('e2e', true, [
            __DIR__ . '/../../tests/Integration/TestedApp/config/doctrine_no_shortname_entity_conflicts.php',
        ]);
    }

    public function testIndexRendersTheAdminListing(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

        $page = $this->visit('/admin/article');

        self::assertResponseIsSuccessful();
        $this->assertPageContains('<h1>Index article</h1>');
        $page->getByRole('heading', ['name' => 'Index article'])->waitFor();
    }
}
