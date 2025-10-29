<?php

declare(strict_types=1);

namespace Karross\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

class HtmlLocaleExtension
{
    public function __construct(private RequestStack $requestStack) {}

    private function resolveLocale(?string $locale): string
    {
        if ($locale) {
            return $locale;
        }

        if ($request = $this->requestStack->getCurrentRequest()) {
            return $request->getLocale();
        }

        return \Locale::getDefault() ?: 'en';
    }

    #[AsTwigFunction('html_locale')]
    public function htmlLocale(?string $locale = null): string
    {
        $resolved = $this->resolveLocale($locale);
        return str_replace('_', '-', $resolved);
    }

    #[AsTwigFunction('text_direction')]
    public function textDirection(?string $locale = null): string
    {
        $resolved = $this->resolveLocale($locale);
        $lang = \Locale::getPrimaryLanguage($resolved);

        $rtl = ['ar','fa','he','ur','ps','sd','ug','dv','ku','syr','yi'];
        return \in_array($lang, $rtl, true) ? 'rtl' : 'ltr';
    }
}
