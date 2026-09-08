# Translations

## Out of the box

Cell values are translated through a cascade of keys (domain `Karross`), most
specific first; the first translated key wins, otherwise the raw value is
shown:

```text
k_value.<entitySlug>.<propertyName>.<value>
k_value.<propertyName>.<value>
k_value.<value>
```

The bundle ships **English and French** defaults (`true`/`false`, `yes`/`no`,
lowercase). For another locale, set
`framework.translator.fallbacks: ['en']` and the bundle falls back to English.

## Overriding translations

Override any key in your own translation files (domain `Karross`):

```yaml
# translations/Karross.en.yaml
k_value:
  yes: "yes"
```

A more specific key overrides a generic one. Keys are always lowercase - use
`formatter_options.ucfirst: true` (see
[Formatting values](formatting-values.md)) for capitalized presentation
values.

*How it works: a translator service uses the host translator and locale. The
cascade is resolved at render time, from the most specific to the most generic
key.*