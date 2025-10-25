<?php

namespace Karross\Twig;

use Karross\Exceptions\EntityShortnameException;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\TemplateWrapper;

class TemplateRegistry
{
    public function __construct(
        private CacheInterface $cache,
        private TemplateResolver $templateResolver
    ) {}

    public function all(): array
    {
        return $this->cache->get('karross.templates', fn () => $this->templateResolver->resolveAll());
    }

    public function getEntityTemplate(string $slug, string $action, ?string $templateBaseName = null): string
    {
        return $this->all()[$slug][$action]['entity'][$templateBaseName ?? $action];
    }

    public function getFieldTemplate(string $slug, string $action, string $templateBaseName, string $fieldName): string
    {
        return $this->all()[$slug][$action]['property'][$templateBaseName][$fieldName];
    }
}

