<?php

declare(strict_types=1);

namespace Karross\Twig;

use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class TemplateRegistryExtension
{
    public function __construct(readonly private TemplateRegistry $templateRegistry) {}

    #[AsTwigFunction('k_template')]
    public function getTemplate(string $slug, string $action, ?string $templateBaseName = null, ?string $propertyName = null): string
    {
        return $this->templateRegistry->getTemplate($slug, $action, $templateBaseName, $propertyName);
    }
}
