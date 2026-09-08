# Customization

Three levers: configuration, templates, translations. Every customization
keeps a reasonable default - configure only what you need.

## Out of the box

With no configuration, Karross still delivers a working admin:

| What | Default |
|---|---|
| Routes | `/admin/{slug}` and `/admin/{slug}/{identifiers}` for every entity |
| Rendering | HTML pages via Twig (Twig templates found in `@Karross`) |
| Value formatting | automatic by detected type (dates/numbers ICU, booleans, enums) |
| Translations | English and French cell values (`true`/`false`, `yes`/`no`) |
| API | JSON responses enabled (`output.api: true`) |

Customization only overrides one of these defaults - it never replaces the
mechanism.

## The three levers

| Lever | Page |
|---|---|
| Configuration | [Routes](routes.md), [Formatting values](formatting-values.md), [Output](output.md) |
| Templates | [Templates](templates.md) |
| Translations | [Translations](translations.md) |

## Configuration overview

All options live under the `karross` key. There is no required option: what
you omit keeps the default behavior.

```yaml
karross:
  output:
    api: true      # JSON responses (default: true)
    html: twig     # HTML renderer (default: twig)
  routes:
    prefix: admin                       # global prefix (example: dashboard)
    index: /{prefix}/{slug}             # default index pattern
    show: /{prefix}/{slug}/{identifiers} # default show pattern
  entities:
    App\Entity\Article:
      slug: articles                    # optional: override the entity slug
      properties:
        price:
          formatter: Karross\Formatters\IntlCurrencyFormatter
          formatter_options:
            currency: EUR
        published:
          formatter: Karross\Formatters\Boolean\YesNoFormatter
          formatter_options:
            ucfirst: true
```