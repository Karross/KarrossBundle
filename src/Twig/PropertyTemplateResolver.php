<?php

namespace Karross\Twig;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Actions\Action;
use Karross\Metadata\Collect\PropertyTemplateResolverInterface;
use Twig\Environment;

/**
 * Twig-side implementation of the property template resolution: maps the
 * doctrine column type to its semantic type chain (fixed contract table),
 * composes the candidate patterns and resolves the first physically existing
 * file through Twig. Returns a map of action → resolved template name, with
 * only the actions that yield a template.
 */
readonly class PropertyTemplateResolver implements PropertyTemplateResolverInterface
{
    /**
     * Semantic type chain per doctrine column type, most specific first.
     * The chain is a fixed contract decided upstream — not a runtime
     * derivation. Date/time columns resolve to a semantic key ('date' /
     * 'time') then to the 'datetime' umbrella (the raw doctrine variant —
     * mutable or immutable — is intentionally dropped), enums get their own
     * 'enum' key ('string' being their storage), guid/ascii_string share the
     * plain 'string' slot (storage constraint, not a rendering difference),
     * and blob (binary) gets its own slot above the 'text' fallback.
     */
    private const DOCTRINE_HIERARCHY = [
        Types::DATE_MUTABLE => ['date', 'datetime'],
        Types::DATE_IMMUTABLE => ['date', 'datetime'],
        Types::TIME_MUTABLE => ['time', 'datetime'],
        Types::TIME_IMMUTABLE => ['time', 'datetime'],
        Types::DATETIME_MUTABLE => ['datetime'],
        Types::DATETIME_IMMUTABLE => ['datetime'],
        Types::DATETIMETZ_MUTABLE => ['datetime'],
        Types::DATETIMETZ_IMMUTABLE => ['datetime'],
        Types::STRING => ['string'],
        Types::ASCII_STRING => ['string'],
        Types::GUID => ['string'],
        Types::TEXT => ['text', 'string'],
        Types::BLOB => ['blob', 'text', 'string'],
        Types::INTEGER => ['int', 'number'],
        Types::SMALLINT => ['int', 'number'],
        Types::BIGINT => ['int', 'number'],
        Types::FLOAT => ['float', 'number'],
        Types::DECIMAL => ['decimal', 'string'],
        Types::BOOLEAN => ['boolean', 'bool'],
        Types::JSON => ['json', 'array'],
    ];

    /**
     * Fallback chains for unmapped properties (no column) or columns whose
     * doctrine type is not part of the contract table.
     */
    private const PHP_HIERARCHY = [
        'bool' => ['bool'],
        'int' => ['int', 'number'],
        'float' => ['float', 'number'],
        'string' => ['string'],
        'array' => ['array'],
    ];

    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param string|null $phpType the reflected property type, or null when
     *                             no type could be resolved
     *
     * @return array<string, string>
     */
    public function resolveField(
        string $entitySlug,
        string $fieldName,
        ?string $phpType,
        ?FieldMapping $fieldMapping,
    ): array {
        return $this->resolve($entitySlug, $fieldName, true, $this->buildTypeHierarchy($phpType, $fieldMapping));
    }

    /**
     * @return array<string, string>
     */
    public function resolveAssociation(
        string $entitySlug,
        string $associationName,
        bool $isToMany,
    ): array {
        return $this->resolve($entitySlug, $associationName, false, $this->associationTypeHierarchy($isToMany));
    }

    /**
     * Shared resolution pipeline: composes the candidate patterns for each
     * action and resolves the first physically existing template.
     *
     * @param list<string> $typeHierarchy
     *
     * @return array<string, string>
     */
    private function resolve(
        string $entitySlug,
        string $propertyName,
        bool $isField,
        array $typeHierarchy,
    ): array {
        $templates = [];

        foreach (Action::cases() as $action) {
            $patterns = $this->getPropertyPatterns($action, $typeHierarchy);
            if ([] === $patterns) {
                continue;
            }

            $templates[$action->value] = $this->twig->resolveTemplate(array_map(
                static fn (string $templatePattern): string => strtr(
                    $templatePattern,
                    [
                        '{fieldOrAssociation}' => $isField ? 'field' : 'association',
                        '{entitySlug}' => $entitySlug,
                        '{propertyName}' => str_replace('.', '_', $propertyName),
                    ]
                ),
                $patterns
            ))->getTemplateName();
        }

        return $templates;
    }

    /**
     * Per-property candidate patterns, ordered from the most specific to the
     * most generic, all rooted at the action's single-typed template folder
     * (`@Karross/{action}/…`). The type motifs expand over the ordered
     * typeHierarchy (each entry owns a dedicated override slot). Principle
     * only — no per-case override logic here.
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

        $base = \sprintf('@Karross/%s/{fieldOrAssociation}', $action->value);

        $patterns = [\sprintf('%s_{propertyName}_entity_{entitySlug}.html.twig', $base)];

        foreach ($typeHierarchy as $typeName) {
            $patterns[] = \sprintf('%s_type_%s_entity_{entitySlug}.html.twig', $base, $typeName);
        }

        $patterns[] = \sprintf('%s_{propertyName}.html.twig', $base);

        foreach ($typeHierarchy as $typeName) {
            $patterns[] = \sprintf('%s_type_%s.html.twig', $base, $typeName);
        }

        $patterns[] = \sprintf('%s.html.twig', $base);

        return $patterns;
    }

    /**
     * Renderer-scoped type hierarchy: ordered chain of candidate type names
     * for the override slots, from the most specific to the most generic.
     * Read from the fixed contract tables — the php type is only consulted
     * for enums and for the fallback cases (unmapped property, or column
     * whose doctrine type is outside the contract).
     *
     * @return list<string>
     */
    private function buildTypeHierarchy(?string $phpType, ?FieldMapping $fieldMapping): array
    {
        if (null !== $fieldMapping?->enumType || (null !== $phpType && is_a($phpType, \UnitEnum::class, true))) {
            return ['enum', 'string'];
        }

        $doctrineType = $fieldMapping?->type;

        if (null === $doctrineType) {
            return null !== $phpType ? (self::PHP_HIERARCHY[$phpType] ?? []) : [];
        }

        $hierarchy = self::DOCTRINE_HIERARCHY[$doctrineType] ?? null;

        if (null === $hierarchy) {
            $hierarchy = [$doctrineType];
            if (null !== $phpType) {
                $hierarchy = [...$hierarchy, ...(self::PHP_HIERARCHY[$phpType] ?? [])];
            }
        }

        return $hierarchy;
    }

    /**
     * Transient semantic key of an association for template candidate
     * composition: 'one' for to-one associations, 'many' for to-many
     * (collection) ones. Derived from the raw cardinality fact, never stored
     * on the read-models.
     *
     * @return list<string>
     */
    private function associationTypeHierarchy(bool $isToMany): array
    {
        return [$isToMany ? 'many' : 'one'];
    }
}
