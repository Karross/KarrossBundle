<?php

declare(strict_types=1);

namespace Karross\Twig;

use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class TemplateRegistryExtension
{
    public function __construct(readonly private TemplateRegistry $templateRegistry) {}

    #[AsTwigFunction('getEntityTemplate')]
    public function getEntityTemplate(string $slug, string $action, string $templateBaseName): string
    {
        return $this->templateRegistry->getEntityTemplate($slug, $action, $templateBaseName);
    }

    #[AsTwigFunction('getFieldTemplate')]
    public function getFieldTemplate(string $slug, string $action, string $templateBaseName, string $fieldName): string
    {
        return $this->templateRegistry->getFieldTemplate($slug, $action, $templateBaseName, $fieldName);
    }
}
