<?php

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\Computed\EntityMetadata;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Metadata\Computed\FieldMetadata;
use Twig\Environment;

readonly class TemplateResolver
{
    public function __construct(private Environment $twig, private EntityMetadataRegistry $entityMetadataRegistry)
    {
    }

    /**
     * @return array<string, array<string, array<array<string, string>|string>>>
     */
    public function resolveAll(): array
    {
        $templatesMap = [];
        foreach ($this->entityMetadataRegistry->all() as $entityMetadata) {
            foreach ($entityMetadata->actions as $action) {
                if (!$this->hasTemplate($action)) {
                    continue;
                }

                foreach ($this->getTemplatePatternsHierarchy($action, $entityMetadata) as $templateBasename => $templatePatterns) {
                    $templatesMap[$entityMetadata->slug][$action->value][$templateBasename] = $this->twig->resolveTemplate(array_map(
                        static function ($templatePattern) use ($entityMetadata) {
                            return strtr($templatePattern, ['{slug}' => $entityMetadata->slug]);
                        }, $templatePatterns)
                    )->getTemplateName();
                }

                foreach ($entityMetadata->getProperties() as $property) {
                    $propertyPatterns = $this->getPropertyPatterns($action, $property->typeHierarchy);
                    if ([] === $propertyPatterns) {
                        continue;
                    }

                    $templatesMap[$entityMetadata->slug][$action->value]['property'][$property->name] = $this->twig->resolveTemplate(array_map(
                        static function ($templatePattern) use ($entityMetadata, $property) {
                            return strtr(
                                $templatePattern,
                                [
                                    '{fieldOrAssociation}' => $property instanceof FieldMetadata ? 'field' : 'association',
                                    '{entitySlug}' => $entityMetadata->slug,
                                    '{propertyName}' => str_replace('.', '_', $property->name),
                                ]);
                        }, $this->getPropertyPatterns($action, $property->typeHierarchy))
                    )->getTemplateName();
                }
            }
        }

        return $templatesMap;
    }

    public function getTemplatePatternsHierarchy(Action $action, EntityMetadata $entityMetadata): array
    {
        $embedded = $entityMetadata->hasEmbeddedField() ? '_embedded' : '';

        return match ($action) {
            Action::INDEX => [
                'index' => [
                    '@Karross/index/index_entity_{slug}.html.twig',
                    '@Karross/index/index.html.twig',
                ],
                'items' => [
                    \sprintf('@Karross/index/items%s_entity_{slug}.html.twig', $embedded),
                    \sprintf('@Karross/index/items%s.html.twig', $embedded),
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
            default => [],
        };
    }

    public function hasTemplate(Action $action): bool
    {
        return \in_array($action, [Action::INDEX, Action::SHOW]);
    }

    /**
     * Per-property candidate patterns, ordered from the most specific to the
     * most generic. The type motifs expand over the ordered typeHierarchy
     * (each entry owns a dedicated override slot). Principle only — no
     * per-case override logic here.
     *
     * @param list<string> $typeHierarchy
     *
     * @return list<string>
     */
    private function getPropertyPatterns(Action $action, array $typeHierarchy): array
    {
        if (Action::INDEX !== $action) {
            return [];
        }

        $patterns = ['@Karross/index/{fieldOrAssociation}_{propertyName}_entity_{entitySlug}.html.twig'];

        foreach ($typeHierarchy as $typeName) {
            $patterns[] = \sprintf('@Karross/index/{fieldOrAssociation}_type_%s_entity_{entitySlug}.html.twig', $typeName);
        }

        $patterns[] = '@Karross/index/{fieldOrAssociation}_{propertyName}.html.twig';

        foreach ($typeHierarchy as $typeName) {
            $patterns[] = \sprintf('@Karross/index/{fieldOrAssociation}_type_%s.html.twig', $typeName);
        }

        $patterns[] = '@Karross/index/{fieldOrAssociation}.html.twig';

        return $patterns;
    }
}
