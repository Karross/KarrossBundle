<?php

namespace Karross\Twig;

use Karross\Metadata\EntityMetadata;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Routing\Action;
use PHPUnit\Metadata\Metadata;
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
                if ($action->hasTemplate()) {
                    foreach ($action->getTemplatePatternsHierarchy() as $scope => $actionPatterns) {
                        foreach ($actionPatterns as $baseName => $filePatterns) {
                            if ($scope === 'entity') {
                                $templatesMap[$entityMetadata->slug][$baseName] = $this->twig->resolveTemplate(array_map(
                                    function ($filePattern) use ($entityMetadata) {
                                        return strtr($filePattern, ['{slug}' => $entityMetadata->slug,]);
                                    }, $filePatterns)
                                )->getTemplateName();
                            }
                            if ($scope === 'field') {
                                $fieldNames = $entityMetadata->classMetadata->getFieldNames();
                                foreach ($fieldNames as $fieldName) {
                                    $templatesMap[$entityMetadata->slug][$baseName][$fieldName] = $this->twig->resolveTemplate(array_map(
                                        function ($filePattern) use ($entityMetadata, $fieldName) {
                                            return strtr($filePattern, ['{slug}' => $entityMetadata->slug, '{field}' => $fieldName, '{type}' => $entityMetadata->classMetadata->getTypeOfField($fieldName)]);
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
}
