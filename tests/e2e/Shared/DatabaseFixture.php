<?php

namespace E2e\Shared;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;

trait DatabaseFixture
{
    private function resetSchema(): void
    {
        $registry = self::getContainer()->get('doctrine');
        \assert($registry instanceof ManagerRegistry);
        $em = $registry->getManager();
        \assert($em instanceof EntityManagerInterface);
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($em->getMetadataFactory()->getAllMetadata());
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
        $em->clear();
    }
}
