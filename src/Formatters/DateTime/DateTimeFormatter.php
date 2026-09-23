<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class DateTimeFormatter extends AbstractDateTimeFormatter
{
    protected function resolvePattern(?FormattingContext $context): array
    {
        // Priority: dateTimeFormat, then (dateFormat + timeFormat), then
        // length presets, then the localized default (date MEDIUM / time SHORT)
        if (null !== $context?->dateTimeFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->dateTimeFormat];
        }

        if (null !== $context?->dateFormat && null !== $context?->timeFormat) {
            return [
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                $context->dateFormat.' '.$context->timeFormat,
            ];
        }

        $dateType = self::lengthToInt($context?->dateFormatPreset) ?? \IntlDateFormatter::MEDIUM;
        $timeType = self::lengthToInt($context?->timeFormatPreset) ?? \IntlDateFormatter::SHORT;

        return [$dateType, $timeType, null];
    }
}
