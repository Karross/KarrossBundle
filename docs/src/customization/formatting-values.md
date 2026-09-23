# Formatting values

This page is the single home for everything about the rendering of property
values. Pages that display values (listing, detail) reference it instead of
repeating it.

## Out of the box

Each property is rendered automatically from its detected type:

| Type | Default rendering |
|---|---|
| boolean | `true` / `false` (raw, lowercase) |
| enum | the raw enum value |
| date / time / datetime | localized with ICU (MEDIUM date / SHORT time) |
| integer / float | localized number |
| `null` | empty cell |
| non-null unsupported value | `N/A` |
| currency | needs configuration - see the currency formatter below |

## Example: a nullable boolean renders `Oui`/`Non` (or stays empty)

The `premium` property of `Article` is a nullable boolean and renders
`true`/`false` by default (empty when `null`). A French UI may want
`Oui`/`Non` instead.

1. Point the property to the `YesNoFormatter` and capitalize the first letter:
   ```yaml
   karross:
     entities:
       App\Entity\Article:
         properties:
           premium:
             formatter: Karross\Formatters\Boolean\YesNoFormatter
             formatter_options:
               ucfirst: true
   ```
2. Result: the `premium` cell renders `Oui` / `Non` (French locale) instead
   of `true` / `false`, and stays empty when the value is `null`.

## Example: a price with trailing zeros

A `decimal` column with `scale: 2` displays `19,9` by default (no trailing
zeros). To display `19,90` with exactly two decimals, set
`minimum_fraction_digits`:

```yaml
karross:
  entities:
    App\Entity\Article:
      properties:
        price:
          formatter_options:
            minimum_fraction_digits: 2
            maximum_fraction_digits: 2
```

*How it works: the Doctrine column `scale` sets `maximum_fraction_digits`
automatically when no `formatter_options` are provided. Adding
`minimum_fraction_digits` pads the output with trailing zeros. Both options
are forwarded to `commerceguys/intl` `NumberFormatter`.*

### Formatter options

| Option | Applies to | Purpose |
|---|---|---|
| `currency` | `IntlCurrencyFormatter` | currency code (e.g. `EUR`) |
| `ucfirst` | all formatters | capitalize the first letter of translated values |
| `minimum_fraction_digits` | `IntlNumberFormatter` | minimum decimals to display (default: none) |
| `maximum_fraction_digits` | `IntlNumberFormatter` | maximum decimals to display (default: Doctrine column `scale`, if set) |
| `datetime_format` | date / time / datetime formatters | name of a format declared under `karross.datetime_formats` |

## Named date/time formats

Date, time and datetime properties are rendered with ICU lengths out of the
box (MEDIUM date, SHORT time, MEDIUM date + SHORT time). To reuse a custom
pattern across properties, declare a **named format** once and reference it
per property.

### The 12 built-in names

| Name | ICU mapping |
|---|---|
| `k_short_date` | date SHORT |
| `k_medium_date` | date MEDIUM *(default for date properties)* |
| `k_long_date` | date LONG |
| `k_full_date` | date FULL |
| `k_short_time` | time SHORT *(default for time properties)* |
| `k_medium_time` | time MEDIUM |
| `k_long_time` | time LONG |
| `k_full_time` | time FULL |
| `k_short_datetime` | date SHORT + time SHORT |
| `k_medium_datetime` | date MEDIUM + time SHORT *(default for datetime properties)* |
| `k_long_datetime` | date LONG + time MEDIUM |
| `k_full_datetime` | date FULL + time LONG |

A property with no `datetime_format` option uses the implicit name of its
formatter (`k_medium_date`, `k_short_time`, or `k_medium_datetime`). Overriding
one of those names changes every matching property.

### Example: a custom business format

```yaml
karross:
  datetime_formats:
    my_custom_datetime_format:
      fr: "d MMMM yyyy 'à' HH:mm"
      en: "MMMM d, yyyy 'at' HH:mm"
      default: "yyyy-MM-dd HH:mm"
  entities:
    App\Entity\Article:
      properties:
        createdAt:
          formatter_options:
            datetime_format: my_custom_datetime_format
```

Result: `createdAt` renders `14 septembre 2026 à 15:30` in French,
`September 14, 2026 at 15:30` in English, and `2026-09-14 15:30` when the
request locale has no entry (fallback to `default`).

The value can also be a single pattern (applies through the `default` key):

```yaml
karross:
  datetime_formats:
    compact_date: "yyyy-MM-dd"
```

At render time the pattern is picked in this order: exact request locale,
then language only (`fr_CA` → `fr`), then `default`. If no pattern matches,
the built-in ICU length for that name applies (or the formatter default).

### Example: customize a built-in name

```yaml
karross:
  datetime_formats:
    k_medium_date:
      fr: "d MMMM yyyy"
```

Every date property without an explicit `datetime_format` uses
`k_medium_date`. In French they render `14 septembre 2026`. Other locales keep
the ICU MEDIUM length.

*How it works: names are resolved once when the metadata is built. The
resolved patterns and ICU lengths are embedded in the property metadata; the
render only picks the entry for the request locale. No format registry is
consulted at render time.*

## Formatter catalog

| Formatter | Purpose |
|---|---|
| `Karross\Formatters\StringFormatter` | plain string |
| `Karross\Formatters\Boolean\TrueFalseFormatter` | `true` / `false` (default for booleans) |
| `Karross\Formatters\Boolean\YesNoFormatter` | `yes` / `no` (translated) |
| `Karross\Formatters\EnumFormatter` | raw enum value |
| `Karross\Formatters\IntlNumberFormatter` | localized number |
| `Karross\Formatters\IntlCurrencyFormatter` | localized currency (needs `currency`) |
| `Karross\Formatters\DateTime\{Date,Time,DateTime}Formatter` | localized date/time |
| `Karross\Formatters\NotAvailableFormatter` | `N/A` |

## Translations of cell values

Translated values are resolved through cascading keys (see
[Translations](translations.md)). The bundle ships English and French defaults
for `true`/`false` and `yes`/`no`, in lowercase.

*How it works: formatters are Symfony services tagged `karross.formatter`.
Metadata resolves the formatter class once at boot; a resolver returns the
instance at render time. Dates and numbers use ICU, currency uses
`commerceguys/intl`. The `Formatters\Boolean\TrueFalseFormatter` is the default
resolver for boolean properties; picking another formatter is done per property
via configuration.*