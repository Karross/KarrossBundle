<?php

namespace Karross\Metadata;

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;
use Karross\Formatters\BooleanFormatter;
use Karross\Formatters\NoFormatter;
use Karross\Formatters\NotAvailableFormatter;

class EntityMetadataBuilder
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly KarrossConfig $config
    ) {}

    /**
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        $fqcnToSlugMap = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                $slug = $this->resolveSlug($classMetadata, $this->config, $fqcnToSlugMap);
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $this->resolveActions($this->config),
                    properties: $this->buildAssociations($classMetadata) + $this->buildFields($classMetadata),
                    classMetadata: $classMetadata,
                );
                $fqcnToSlugMap[$classMetadata->getName()] = $slug;
            }
        }

        return $entities;
    }

    private function buildAssociations(ClassMetadata $classMetadata): array
    {
        $associations = [];

        foreach($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            if ($associationClass === null) {
                throw new \LogicException('Association class not found for ' . $associationName);
            }
            $associationMetadata = $this->managerRegistry->getManagerForClass($associationClass)->getClassMetadata($associationClass);

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                identifier: $associationMetadata->getIdentifier(),
                fqcn: $associationClass,
                formatter: NotAvailableFormatter::class
            );
        }

        return $associations;
    }

    private function buildFields(ClassMetadata $classMetadata): array
    {
        $fields = [];
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $type = $classMetadata->getTypeOfField($fieldName);
            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
                formatter: $this->getValueFormatter($type),
            );
        }

        return $fields;
    }

    /**
     * @throws EntityShortnameException
     */
    private function resolveSlug(ClassMetadata $classMetadata, KarrossConfig $config, array $fqcnToSlugMap): string
    {
        $fqcn = $classMetadata->getName();
        $shortname = strtolower($classMetadata->getReflectionClass()->getShortName());
        $slug = $config->entitySlug($fqcn) ?? $shortname;

        if (in_array($slug, $fqcnToSlugMap)) {
            if ($this->config->entitySlug($fqcn)) {
                throw new EntityShortnameException(
                    resource: $fqcn,
                    message: sprintf(
                        "The slug you have provided for %s is already in use with %s",
                        $fqcn,
                        array_search($this->config->entitySlug($fqcn), $fqcnToSlugMap),
                    ),
                );
            }
            throw new EntityShortnameException(
                resource: $fqcn,
                message: sprintf(
                    "Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts",
                    $fqcn,
                    array_search($slug, $fqcnToSlugMap),
                    $slug
                )
            );
        }

        return $slug;
    }

    /**
     * @return Action[]
     */
    private function resolveActions(KarrossConfig $config): array
    {
        return Action::cases();
    }

    private function getValueFormatter($doctrineType): string {

        return match ($doctrineType) {

            Types::SMALLINT,
            Types::INTEGER,
            Types::BIGINT,
            Types::DECIMAL,
            Types::NUMBER,
            Types::FLOAT,
            Types::STRING,
            Types::ASCII_STRING,
            Types::TEXT,
            Types::GUID,
            Types::SMALLFLOAT => NoFormatter::class,

            Types::BOOLEAN => BooleanFormatter::class,

        //
        //    // dates
        //    Types::DATE_MUTABLE,
        //    Types::DATE_IMMUTABLE => [DateFormatter::class, 'format'],
        //
        //    Types::DATETIME_MUTABLE,
        //    Types::DATETIME_IMMUTABLE,
        //    Types::DATETIMETZ_MUTABLE,
        //    Types::DATETIMETZ_IMMUTABLE => [DateTimeFormatter::class, 'format'],
        //
        //    Types::TIME_MUTABLE,
        //    Types::TIME_IMMUTABLE => [TimeFormatter::class, 'format'],
        //
        //    Types::DATEINTERVAL => [DateIntervalFormatter::class, 'format'],
        //
        //    // structurés
        //    Types::SIMPLE_ARRAY,
        //    Types::JSON,
        //    Types::JSONB => [JsonFormatter::class, 'format'],
        //
        //    // binaires
        //    Types::BINARY,
        //    Types::BLOB => [BinaryFormatter::class, 'format'],
        //
        //    // énumérations
        //    Types::ENUM => [EnumFormatter::class, 'format'],
        //

            default => NotAvailableFormatter::class,
        };
    }
}
