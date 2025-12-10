<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\AssociationMetadata;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Routes\RouteGenerator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

class UrlBuilderExtension
{
    private PropertyAccessor $accessor;
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[AsTwigFunction('getUrl')]
    public function getUrl(string $action, AssociationMetadata $association, $entity): string
    {
        try {
            $associationEntity = $this->accessor->getValue($entity, $association->name);
            $parameters = [];
            foreach ($association->identifier as $identifier) {
                $parameters[$identifier] = $this->accessor->getValue($associationEntity, $identifier);
            }
        } catch (\Throwable $e) {
            return 'N/A';
        }

        return $this->urlGenerator->generate(
            RouteGenerator::routeName($association->fqcn, Action::from($action)),
            $parameters
        );
    }
}
