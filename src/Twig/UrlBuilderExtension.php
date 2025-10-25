<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Actions\Action;
use Karross\Metadata\Association;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Routes\RouteGenerator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class UrlBuilderExtension
{
    private PropertyAccessor $accessor;
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[AsTwigFunction('getUrl')]
    public function getUrl(string $action, Association $association, $entity): string
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
