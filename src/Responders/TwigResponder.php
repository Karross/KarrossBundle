<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Karross\Actions\Index;
use Karross\Actions\Show;
use Karross\Config\KarrossConfig;
use Karross\Twig\TemplateRegistry;
use Karross\Twig\TemplateResolver;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TwigResponder implements ResponderInterface
{
    public function __construct(private Environment $twig, private KarrossConfig $config, private TemplateRegistry $templateRegistry) {}

    public function supports(ActionContext $actionContext): bool
    {
        return $actionContext->request->getRequestFormat() === 'html' && $this->config->htmlRenderer() === 'twig';
    }

    public function getResponse(ActionContext $actionContext, $data): Response
    {
        $template = $this->templateRegistry->get($actionContext->slug, $actionContext->action);

        return new Response($this->twig->render($template, $data));
    }
}
