<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class DateFormatter extends AbstractDateTimeFormatter
{
    protected function resolvePattern(?FormattingContext $context): array
    {
        if (null !== $context?->dateFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->dateFormat];
        }

        $dateType = self::lengthToInt($context?->dateFormatPreset) ?? \IntlDateFormatter::MEDIUM;

        return [$dateType, \IntlDateFormatter::NONE, null];
    }
}
