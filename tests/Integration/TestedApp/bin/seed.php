<?php

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use TestedApp\Entity\Article;
use TestedApp\Entity\Category;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

require __DIR__.'/../../../../vendor/autoload.php';

$kernel = new Kernel('serve', true, [
    __DIR__.'/../config/doctrine_serve.php',
]);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

$schemaTool = new SchemaTool($em);
$schemaTool->dropSchema($em->getMetadataFactory()->getAllMetadata());
$schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

$category = (new Category())->setName('Lifestyle');

$articles = [
    (new Article())
        ->setTitle('Découverte de la Provence')
        ->setContent('Un joli contenu.')
        ->setPublished(true)
        ->setViewCount(42)
        ->setPrice('19.90')
        ->setCreatedAt(new DateTimeImmutable('2026-03-05 15:30:00'))
        ->setStatus(Status::PUBLISHED)
        ->setTags(['tourisme', 'nature'])
        ->setScheduledDate(new DateTimeImmutable('2026-06-01'))
        ->setPublishedAt(new DateTimeImmutable('2026-03-06 09:00:00')),
    (new Article())
        ->setTitle('Week-end à Aix-en-Provence')
        ->setContent('Culture et calissons.')
        ->setPublished(false)
        ->setViewCount(7)
        ->setCreatedAt(new DateTimeImmutable('2026-08-20 10:00:00'))
        ->setStatus(Status::DRAFT),
];

$em->persist($category);
foreach ($articles as $article) {
    $em->persist($article);
}
$em->flush();

echo 'Seeded '.count($articles).' articles and 1 category into var/tested-app.sqlite.'.\PHP_EOL;
