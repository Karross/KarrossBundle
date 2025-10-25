<?php

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\EntityMetadata;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Metadata\Field;
use Twig\Environment;
use Twig\TemplateWrapper;

class TemplateResolver
{
    public function __construct(private readonly Environment $twig, private readonly EntityMetadataRegistry $entityMetadataRegistry) {}

    /**
     * @return array<string, array<TemplateWrapper>
     */
    public function resolveAll(): array
    {
        $templatesMap = [];
        foreach ($this->entityMetadataRegistry->all() as $entityMetadata) {
            foreach ($entityMetadata->actions as $action) {
                if ($this->hasTemplate($action)) {
                    foreach ($this->getTemplatePatternsHierarchy($action, $entityMetadata) as $scope => $actionPatterns) {
                        foreach ($actionPatterns as $baseName => $filePatterns) {
                            if ($scope === 'entity') {
                                $templatesMap[$entityMetadata->slug][$action->value][$scope][$baseName] = $this->twig->resolveTemplate(array_map(
                                    function ($filePattern) use ($entityMetadata) {
                                        return strtr($filePattern, ['{slug}' => $entityMetadata->slug,]);
                                    }, $filePatterns)
                                )->getTemplateName();
                            }
                            if ($scope === 'property') {
                                foreach ($entityMetadata->getProperties() as $property) {
                                    $templatesMap[$entityMetadata->slug][$action->value][$scope][$baseName][$property->name] = $this->twig->resolveTemplate(array_map(
                                        function ($filePattern) use ($entityMetadata, $property) {
                                            return strtr(
                                                $filePattern,
                                                [
                                                    '{fieldOrAssociation}' => $property instanceof Field ? 'field' : 'association',
                                                    '{entitySlug}' => $entityMetadata->slug,
                                                    '{propertyName}' => $property->name,
                                                    '{propertyType}' => $property instanceof Field ? $entityMetadata->getTypeOfField($property->name) : 'association'
                                                ]);
                                        }, $filePatterns)
                                    )->getTemplateName();
                                }
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
                'entity' => [
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
                ],
                'property' => [
                    'property' => [
                        "@Karross/index/{fieldOrAssociation}_{propertyName}_entity_{entitySlug}.html.twig",
                        "@Karross/index/{fieldOrAssociation}_type_{propertyType}_entity_{entitySlug}.html.twig",
                        "@Karross/index/{fieldOrAssociation}_{propertyName}.html.twig",
                        "@Karross/index/{fieldOrAssociation}_type_{propertyType}.html.twig",
                        "@Karross/index/{fieldOrAssociation}.html.twig",
                    ]
                ]
            ],
            default => [],
        };
    }

    public function hasTemplate(Action $action): bool
    {
        return \in_array($action, [Action::INDEX, Action::SHOW, Action::CREATE_FORM, Action::EDIT_FORM]);
    }
}
