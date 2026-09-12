<?php

namespace Karross\Twig;

use Symfony\Contracts\Cache\CacheInterface;

class TemplateRegistry
{
    private bool $cacheEnabled;

    public function __construct(
        private CacheInterface $cache,
        private TemplateResolver $templateResolver,
        bool $debug,
    ) {
        $this->cacheEnabled = !$debug;
    }

    public function all(): array
    {
        return $this->cacheEnabled
            ? $this->cache->get('karross.templates', fn () => $this->templateResolver->resolveAll())
            : $this->templateResolver->resolveAll();
    }

    public function getTemplate(string $slug, string $action, ?string $templateBaseName = null, ?string $propertyName = null): string
    {
        if (null === $propertyName) {
            return $this->all()[$slug][$action][$templateBaseName ?? $action];
        }

        return $this->all()[$slug][$action][$templateBaseName ?? $action][$propertyName];
    }
}
