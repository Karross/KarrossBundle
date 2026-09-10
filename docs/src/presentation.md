# Presentation

Karross is a Symfony bundle that gives you an instant admin interface for your
Doctrine entities - out of the box, and fully customizable.

## Philosophy

Karross works out of the box: no configuration, no boilerplate, no controller
code. It derives an admin interface directly from your Doctrine entities.

Almost every part can then be customized - routes, output, formatters,
templates and translations - without breaking the default behavior.

## Features (current state)

- **Automatic routes** - a home portal plus index (list) and show (detail) pages for every Doctrine entity
- **Two outputs** - HTML (Twig) and JSON for headless/API use, selected per request
- **Table views** - handle embedded fields and associations (links to the related row)
- **Value formatting** - dates/numbers/locale (ICU), currency, booleans;
  `null` renders as empty, unsupported values as `N/A`
- **Translated cell values** - cascading `k_value.*` keys you can override in
  your own translation files (bundle ships English and French defaults)

## Admin pages

### Home page (portal)

`/admin` (or `/admin/`) opens the portal: one card per mapped entity, each
linking to its listing page. A documentation card links to the online
documentation.

With no mapped entity, an onboarding block replaces the entity cards and points
to the Symfony/Doctrine mapping documentation.

Hide the documentation card:

```yaml
karross:
  home:
    show_documentation: false
```

The "Karross administration" header label comes from the templates; add a
logo or restyle the header by overriding them (see
[Overriding templates](customization/templates.md)).

### Listing page (index)

`/admin/{slug}` shows a table of the entity rows: one column per property
(embedded fields get a grouped header), cells formatted by type, associations
link to the related row, and an empty list renders a message.

### Detail page (show)

`/admin/{slug}/{identifiers}` displays a single row. The detail page is still
early-stage; expect its rendering to evolve.

All pages share the same value formatting and templates - they are
customizable through [Formatting values](customization/formatting-values.md)
and [Overriding templates](customization/templates.md).

## Actions

The bundle currently implements two actions:

| Action | Route | Purpose |
|---|---|---|
| `index` | `/admin/{slug}` | list rows |
| `show` | `/admin/{slug}/{identifiers}` | display one row |

Create, update and delete are planned (see [Roadmap](roadmap/index.md)).

## Source code

The bundle code and this documentation live in
[Karross/KarrossBundle](https://github.com/Karross/KarrossBundle).