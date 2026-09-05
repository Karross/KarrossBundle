<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class TimeFormatter extends AbstractDateTimeFormatter
{
    protected function resolvePattern(?FormattingContext $context): array
    {
        if (null !== $context?->timeFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->timeFormat];
        }

        return [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, null];
    }
}
