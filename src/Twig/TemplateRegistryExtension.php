<?php

namespace Karross\Twig;

use Twig\Attribute\AsTwigFunction;

class TemplateRegistryExtension
{
    public function __construct(private readonly TemplateRegistry $templateRegistry)
    {
    }

    #[AsTwigFunction('k_template')]
    public function getTemplate(string $slug, string $action, ?string $templateBaseName = null, ?string $propertyName = null): string
    {
        return $this->templateRegistry->getTemplate($slug, $action, $templateBaseName, $propertyName);
    }
}
