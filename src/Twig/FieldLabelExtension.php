<?php

namespace Karross\Twig;

use Karross\Actions\ActionContext;
use Karross\Metadata\EntityMetadata;
use Karross\Metadata\FieldLabel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

class FieldLabelExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    #[AsTwigFunction('colspan')]
    public function colspan(FieldLabel $fieldLabel): mixed
    {
        return $fieldLabel->numberOfLeaves;
    }

    #[AsTwigFunction('rowspan')]
    public function rowspan(FieldLabel $fieldLabel, EntityMetadata $entityMetadata): mixed
    {
        return $fieldLabel->isLeaf
            ? $entityMetadata->getMaxEmbeddedDepth() - $fieldLabel->depth + 1
            : 1;
    }

    #[AsTwigFunction('translate')]
    public function translate(FieldLabel $fieldLabel, ActionContext $actionContext): mixed
    {
        $key = \sprintf('k_%s_%s.%s', $actionContext->action, $actionContext->slug, $fieldLabel->path);

        $translated = $this->translator->trans(id: $key, domain: 'karross', locale: $actionContext->request->getLocale());

        return $translated === $key ? $fieldLabel->label : $translated;
    }
}
