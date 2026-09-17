<?php

namespace Karross\Metadata\Collect;

use Karross\Actions\Action;

/**
 * Resolves, for a given action, the bundle-defined entity page templates
 * (index → items/no_items → item) from the raw analysis facts (slug,
 * embedded fields). The Metadata layer only knows this contract: the
 * renderer-specific candidates and the physical existence resolution happen
 * in the implementing layer. The result is a renderer-scoped projection of
 * the analysis, in the same spirit as the formatter and the property
 * templates.
 */
interface EntityTemplateResolverInterface
{
    /**
     * @return array<string, string> role → resolved template name; empty when
     *                               the action defines no entity templates
     */
    public function resolve(Action $action, string $slug, bool $hasEmbeddedFields): array;
}
