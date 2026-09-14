<?php

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\Collect\EntityTemplateResolverInterface;
use Twig\Environment;

/**
 * Twig-side implementation of the entity template resolution: composes the
 * candidate patterns for each role (index → items/no_items → item, with
 * the embedded variant and the entity-scope refinement) and resolves the
 * first physically existing file through Twig. Returns a map of role →
 * resolved template name.
 */
readonly class EntityTemplateResolver implements EntityTemplateResolverInterface
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @return array<string, string>
     */
    public function resolve(Action $action, string $slug, bool $hasEmbeddedFields): array
    {
        $patterns = $this->getPatterns($action, $hasEmbeddedFields);
        if ([] === $patterns) {
            return [];
        }

        $templates = [];
        foreach ($patterns as $role => $candidates) {
            $templates[$role] = $this->twig->resolveTemplate(array_map(
                static fn (string $pattern): string => strtr($pattern, ['{slug}' => $slug]),
                $candidates,
            ))->getTemplateName();
        }

        return $templates;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getPatterns(Action $action, bool $hasEmbeddedFields): array
    {
        $embedded = $hasEmbeddedFields ? '_embedded' : '';

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
}
