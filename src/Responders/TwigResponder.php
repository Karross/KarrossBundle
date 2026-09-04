<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Karross\Config\KarrossConfig;
use Karross\Twig\TemplateRegistry;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TwigResponder implements ResponderInterface
{
    public function __construct(private Environment $twig, private KarrossConfig $config, private TemplateRegistry $templateRegistry)
    {
    }

    public function supports(ActionContext $actionContext): bool
    {
        return 'html' === $actionContext->request->getRequestFormat() && 'twig' === $this->config->htmlRenderer();
    }

    public function getResponse(ActionContext $actionContext, $data): Response
    {
        $template = $this->templateRegistry->getTemplate($actionContext->slug, $actionContext->action);

        return new Response($this->twig->render($template, $data + ['actionContext' => $actionContext]));
    }
}
