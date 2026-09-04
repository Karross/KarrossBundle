<?php

namespace Karross\Twig;

use Symfony\Contracts\Cache\CacheInterface;

class TemplateRegistry
{
    public function __construct(
        private CacheInterface $cache,
        private TemplateResolver $templateResolver,
    ) {
    }

    public function all(): array
    {
        return $this->templateResolver->resolveAll();

        return $this->cache->get('karross.templates', fn () => $this->templateResolver->resolveAll());
    }

    public function getTemplate(string $slug, string $action, ?string $templateBaseName = null, ?string $propertyName = null): string
    {
        if (null === $propertyName) {
            return $this->all()[$slug][$action][$templateBaseName ?? $action];
        }

        return $this->all()[$slug][$action][$templateBaseName ?? $action][$propertyName];
    }
}
