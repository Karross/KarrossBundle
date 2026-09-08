# Overriding templates

## Out of the box

Karross renders the admin through Twig templates shipped in the bundle
(namespace `@Karross`). The listing page is a table: header from the entity
metadata (embedded fields get a grouped header), one row per entity, one cell
per property, associations link to the related row, and an empty list renders
a message.

## Overriding templates

Create a file with the same name under
`templates/bundles/KarrossBundle/` in your project. Karross uses your file for
every matching case.

### Existing templates

| Template | Purpose |
|---|---|
| `index/index.html.twig` | page layout for the index action |
| `index/items.html.twig` | the row list container |
| `index/items_embedded.html.twig` | list with embedded fields (header with colspan/rowspan) |
| `index/item.html.twig` | one row |
| `index/no_items.html.twig` | empty list message |
| `index/field.html.twig` | a single cell |
| `index/association.html.twig` | a cell linking to a related row |

### Specific variants

Append `_entity_<slug>` for an entity, and for cells append the field/type
discriminators. Resolution order, most specific first:

1. `field_<property>_entity_<slug>.html.twig`
2. `field_type_<type>_entity_<slug>.html.twig`
3. `field_<property>.html.twig`
4. `field_type_<type>.html.twig`
5. default `field.html.twig`

Examples:

```text
templates/bundles/KarrossBundle/index/items.html.twig                # all lists
templates/bundles/KarrossBundle/index/items_entity_article.html.twig # only Article
templates/bundles/KarrossBundle/index/field_title_entity_article.html.twig
templates/bundles/KarrossBundle/index/field_type_string.html.twig
```

*How it works: `TemplateResolver` builds the resolution map once at boot. The
`k_template` Twig function picks the best match for each field.*