<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Karross\Config\EntityConfig;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\Options\DateTimeOptionsResolver;
use Karross\Formatters\ValueFormatterInterface;
use Karross\Metadata\Computed\AssociationMetadata;

/**
 * Turns Doctrine associations into AssociationMetadata, one pass per entity.
 *
 * Example for App\Entity\Article::category (ManyToOne → Category):
 *   AssociationMetadata(name: "category", fqcn: Category,
 *                       identifier: ["id"], formatter: StringFormatter)
 *
 * Example for App\Entity\Article::tags (ManyToMany → Tag):
 *   AssociationMetadata(name: "tags", fqcn: Tag,
 *                       identifier: ["id"], templates include "many")
 */
final readonly class AssociationMetadataBuilder
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityConfig $entityConfig,
        private FormatterResolver $formatterResolver,
        private DateTimeOptionsResolver $datetimeOptionsResolver,
        private PropertyTemplateResolverInterface $propertyTemplateResolver,
    ) {
    }

    /**
     * Builds one AssociationMetadata per Doctrine association of the entity.
     *
     * Example for App\Entity\Article with slug "article":
     *   ["category" => AssociationMetadata(fqcn: Category, identifier: ["id"]),
     *    "author"   => AssociationMetadata(fqcn: User, identifier: ["id"]),
     *    "tags"     => AssociationMetadata(fqcn: Tag, identifier: ["id"])]
     *
     * @param OrmClassMetadata<object> $classMetadata
     *
     * @return array<string, AssociationMetadata>
     */
    public function build(string $entitySlug, OrmClassMetadata $classMetadata): array
    {
        $associations = [];
        $reflectionClass = new \ReflectionClass($classMetadata->getName());
        $fqcn = $classMetadata->getName();

        foreach ($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            $targetManager = $this->managerRegistry->getManagerForClass($associationClass);
            \assert(null !== $targetManager);
            $targetMetadata = $targetManager->getClassMetadata($associationClass);
            $phpType = PhpTypeInspector::phpType($reflectionClass->getProperty($associationName));
            $formatter = $this->resolveFormatter($fqcn, $associationName, $phpType);
            $formatterOptions = $this->datetimeOptionsResolver->resolve(
                $formatter,
                $this->entityConfig->propertyFormatterOptions($fqcn, $associationName),
                $fqcn,
                $associationName,
            );

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                fqcn: $associationClass,
                identifier: $targetMetadata->getIdentifier(),
                templates: $this->propertyTemplateResolver->resolveAssociation(
                    $entitySlug,
                    $associationName,
                    $classMetadata->isCollectionValuedAssociation($associationName),
                ),
                formatter: $formatter,
                formatterOptions: $formatterOptions,
                entitySlug: $entitySlug,
            );
        }

        return $associations;
    }

    /**
     * Picks the formatter: host config wins, then the resolver chain.
     *
     * Example for App\Entity\Article::category with no config override:
     *   returns StringFormatter::class (Category has __toString)
     *
     * @return class-string<ValueFormatterInterface>
     */
    private function resolveFormatter(string $fqcn, string $property, ?string $phpType): string
    {
        /** @var class-string<ValueFormatterInterface>|null $configured */
        $configured = $this->entityConfig->propertyFormatter($fqcn, $property);

        return $configured ?? $this->formatterResolver->resolve($phpType, null);
    }
}
