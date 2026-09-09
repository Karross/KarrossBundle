<?php

namespace Karross\Pages;

use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Routes\RouteGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * The admin portal: one card per mapped entity, plus the documentation card.
 *
 * A global page, outside the per-entity action flow (no ActionContext), see contexts/architecture.md.
 */
final class Home
{
    public function __construct(
        private readonly EntityMetadataRegistry $entityMetadataRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
        private readonly KarrossConfig $config,
    ) {
    }

    public function __invoke(): Response
    {
        $entities = [];
        foreach ($this->entityMetadataRegistry->all() as $fqcn => $entityMetadata) {
            $entities[] = [
                'slug' => $entityMetadata->getSlug(),
                'href' => $this->urlGenerator->generate(RouteGenerator::routeName($fqcn, Action::INDEX)),
            ];
        }

        return new Response($this->twig->render(
            $this->twig->resolveTemplate('@Karross/home.html.twig'),
            [
                'entities' => $entities,
                'showDocumentation' => $this->config->homeShowDocumentation(),
            ],
        ));
    }
}
