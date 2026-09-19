<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Karross\Config\KarrossConfig;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TwigResponder implements ResponderInterface
{
    public function __construct(private readonly Environment $twig, private readonly KarrossConfig $config, private readonly EntityMetadataRegistry $entityMetadataRegistry)
    {
    }

    public function supports(ActionContext $actionContext): bool
    {
        return 'html' === $actionContext->request->getRequestFormat() && 'twig' === $this->config->htmlRenderer();
    }

    public function getResponse(ActionContext $actionContext, $data): Response
    {
        $template = $this->entityMetadataRegistry->getBySlug($actionContext->slug)->templates[$actionContext->action]['index'];

        return new Response($this->twig->render($template, $data + ['actionContext' => $actionContext]));
    }
}
