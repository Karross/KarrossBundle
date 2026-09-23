<?php

namespace Karross\Metadata\Collect;

use Karross\Actions\Action;

/**
 * Resolves the page templates for one entity action.
 *
 * The Metadata layer only hands over raw facts (slug, embedded flag).
 * The implementing layer picks the candidates and checks file existence.
 *
 * Example for slug "article", action INDEX, no embedded:
 *   returns ["index" => "@Karross/index/index.html.twig",
 *            "items" => "@Karross/index/items.html.twig",
 *            "no_items" => "@Karross/index/no_items.html.twig",
 *            "item" => "@Karross/index/item.html.twig"]
 *
 * Example for action SHOW:
 *   returns [] (SHOW has no entity page templates)
 */
interface EntityTemplateResolverInterface
{
    /**
     * Returns role → resolved template name, or [] for actions without templates.
     *
     * Example:
     *   resolve(INDEX, "article", false) returns the 4 index roles
     *   resolve(SHOW, "article", false) returns []
     *
     * @return array<string, string> role → resolved template name
     */
    public function resolve(Action $action, string $slug, bool $hasEmbeddedFields): array;
}
