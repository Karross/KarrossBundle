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

    public function get(string $slug, string $action): string
    {
        //dd($this->all(), $slug, $action);
        return $this->all()[$slug][$action];
    }
}

