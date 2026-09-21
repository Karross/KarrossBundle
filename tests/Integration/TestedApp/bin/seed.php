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

$category = new Category()->setName('Lifestyle');

$articles = [
    new Article()
        ->setTitle('Découverte de la Provence')
        ->setContent('Un joli contenu.')
        ->setPublished(true)
        ->setPremium(true)
        ->setViewCount(42)
        ->setSortOrder('1234')
        ->setBigCounter('12345')
        ->setPrice('19.90')
        ->setCreatedAt(new DateTimeImmutable('2026-03-05 15:30:00'))
        ->setPublishedAt(new DateTimeImmutable('2026-03-06 09:00:00'))
        ->setScheduledDate(new DateTimeImmutable('2026-06-01'))
        ->setStatus(Status::PUBLISHED)
        ->setTags(['tourisme', 'nature']),
    new Article()
        ->setTitle('Week-end à Aix-en-Provence')
        ->setContent('Culture et calissons.')
        ->setPublished(false)
        ->setPremium(false)
        ->setViewCount(7)
        ->setSortOrder('7')
        ->setBigCounter('987654')
        ->setCreatedAt(new DateTimeImmutable('2026-08-20 10:00:00'))
        ->setStatus(Status::DRAFT),
    new Article()
        ->setTitle('Les Calanques de Marseille')
        ->setContent('Escapade nature entre terre et mer.')
        ->setPublished(true)
        ->setViewCount(3)
        ->setSortOrder('3')
        ->setBigCounter('500')
        ->setPrice('123.456')
        ->setCreatedAt(new DateTimeImmutable('2026-09-01 09:00:00'))
        ->setPublishedAt(new DateTimeImmutable('2026-09-01 12:00:00'))
        ->setStatus(Status::PUBLISHED),
    new Article()
        ->setTitle('Bouillabaisse marseillaise')
        ->setContent('Recette traditionnelle.')
        ->setPublished(false)
        ->setPremium(null)
        ->setViewCount(0)
        ->setSortOrder('0')
        ->setBigCounter('0')
        ->setCreatedAt(new DateTimeImmutable('2026-09-10 08:00:00'))
        ->setStatus(Status::ARCHIVED),
    new Article()
        ->setTitle('Marché aux puces')
        ->setContent('Buvez un pastis, troquez des timbres.')
        ->setPublished(true)
        ->setPremium(null)
        ->setViewCount(158)
        ->setSortOrder('42')
        ->setBigCounter('1000000')
        ->setPrice('0.01')
        ->setCreatedAt(new DateTimeImmutable('2026-09-15 14:00:00'))
        ->setStatus(Status::PUBLISHED)
        ->setTags([]),
];

$em->persist($category);
foreach ($articles as $article) {
    $em->persist($article);
}
$em->flush();

echo 'Seeded '.count($articles).' articles and 1 category into var/tested-app.sqlite.'.\PHP_EOL;
