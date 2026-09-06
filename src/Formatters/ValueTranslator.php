<?php

namespace Karross\Formatters;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ValueTranslator
{
    public const DOMAIN = 'Karross';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * Translate a raw display value through the value-key cascade.
     *
     * Candidate keys, most specific first:
     *   k_value.{entitySlug}.{propertyName}.{value}
     *   k_value.{propertyName}.{value}
     *   k_value.{value}
     *
     * The first translated key wins. When none is translated, the raw value
     * is returned unchanged.
     */
    public function translate(string $raw, ?FormattingContext $context): string
    {
        if (null === $context?->entitySlug || null === $context->propertyName) {
            return $raw;
        }

        $keys = [
            \sprintf('k_value.%s.%s.%s', $context->entitySlug, $context->propertyName, $raw),
            \sprintf('k_value.%s.%s', $context->propertyName, $raw),
            \sprintf('k_value.%s', $raw),
        ];

        foreach ($keys as $key) {
            $translated = $this->translator->trans($key, [], self::DOMAIN, $context->locale);
            if ($translated !== $key) {
                return $context->ucfirst ? ucfirst($translated) : $translated;
            }
        }

        return $raw;
    }
}
