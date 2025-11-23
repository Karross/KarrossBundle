<?php

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\EntityMetadata;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Metadata\FieldMetadata;
use Twig\Environment;
use Twig\TemplateWrapper;

readonly class TemplateResolver
{
    public function __construct(private Environment $twig, private EntityMetadataRegistry $entityMetadataRegistry) {}

    /**
     * @return array<string, array<TemplateWrapper>
     */
    public function resolveAll(): array
    {
        $templatesMap = [];
        foreach ($this->entityMetadataRegistry->all() as $entityMetadata) {
            foreach ($entityMetadata->actions as $action) {
                if ($this->hasTemplate($action)) {
                    foreach ($this->getTemplatePatternsHierarchy($action, $entityMetadata) as $templateBasename => $templatePatterns) {
                            if ($templateBasename !== 'property') {
                                $templatesMap[$entityMetadata->slug][$action->value][$templateBasename] = $this->twig->resolveTemplate(array_map(
                                    function ($templatePattern) use ($entityMetadata) {
                                        return strtr($templatePattern, ['{slug}' => $entityMetadata->slug,]);
                                    }, $templatePatterns)
                                )->getTemplateName();
                            } else {
                                foreach ($entityMetadata->getProperties() as $property) {
                                    $templatesMap[$entityMetadata->slug][$action->value][$templateBasename][$property->name] = $this->twig->resolveTemplate(array_map(
                                        function ($templatePattern) use ($entityMetadata, $property) {
                                            return strtr(
                                                $templatePattern,
                                                [
                                                    '{fieldOrAssociation}' => $property instanceof FieldMetadata ? 'field' : 'association',
                                                    '{entitySlug}' => $entityMetadata->slug,
                                                    '{propertyName}' => str_replace('.', '_', $property->name),
                                                    '{propertyType}' => $property instanceof FieldMetadata ? $entityMetadata->getTypeOfField($property->name) : $entityMetadata->getTypeOfAssociation($property->name)
                                                ]);
                                        }, $templatePatterns)
                                    )->getTemplateName();
                                }
                            }
                        }
                }
            }
        }

        return $templatesMap;
    }

    public function getTemplatePatternsHierarchy(Action $action, EntityMetadata $entityMetadata): array
    {
        $embedded = $entityMetadata->hasEmbeddedField() ? '_embedded' : '';

        return match($action) {
            Action::INDEX => [
                'index' => [
                    '@Karross/index/index_entity_{slug}.html.twig',
                    '@Karross/index/index.html.twig',
                ],
                'items' => [
                    sprintf('@Karross/index/items%s_entity_{slug}.html.twig', $embedded),
                    sprintf('@Karross/index/items%s.html.twig', $embedded),
                ],
                'no_items' => [
                    '@Karross/index/no_items_entity_{slug}.html.twig',
                    '@Karross/index/no_items.html.twig',
                ],
                'item' => [
                    '@Karross/index/item_entity_{slug}.html.twig',
                    '@Karross/index/item.html.twig',
                ],
                'property' => [
                    "@Karross/index/{fieldOrAssociation}_{propertyName}_entity_{entitySlug}.html.twig",
                    "@Karross/index/{fieldOrAssociation}_type_{propertyType}_entity_{entitySlug}.html.twig",
                    "@Karross/index/{fieldOrAssociation}_{propertyName}.html.twig",
                    "@Karross/index/{fieldOrAssociation}_type_{propertyType}.html.twig",
                    "@Karross/index/{fieldOrAssociation}.html.twig",
                ],
            ],
            default => [],
        };
    }

    public function hasTemplate(Action $action): bool
    {
        return \in_array($action, [Action::INDEX, Action::SHOW, Action::CREATE_FORM, Action::EDIT_FORM]);
    }
}
