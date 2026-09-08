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

## Example: `true`/`false` becomes `Oui`/`Non`

The `published` property of `Article` is a boolean and renders `true`/`false`
by default. A French UI may want `Oui`/`Non` instead.

1. Point the property to the `YesNoFormatter` and capitalize the first letter:
   ```yaml
   karross:
     entities:
       App\Entity\Article:
         properties:
           published:
             formatter: Karross\Formatters\Boolean\YesNoFormatter
             formatter_options:
               ucfirst: true
   ```
2. Result: the `published` cell renders `Oui` / `Non` (French locale) instead
   of `true` / `false`.

### Formatter options

| Option | Purpose |
|---|---|
| `currency` | currency code for the currency formatter (e.g. `EUR`) |
| `ucfirst` | capitalize the first letter of translated values |

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