# Overriding templates

## Out of the box

Karross renders the admin through Twig templates shipped in the bundle
(namespace `@Karross`). The listing page is a table: header from the entity
metadata (embedded fields get a grouped header), one row per entity, one cell
per property, associations link to the related row, and an empty list renders
a message.

## Overriding templates

Create a file with the same name under `templates/bundles/KarrossBundle/` in
your project. Karross uses your file for every matching case.

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

`<type>` is the semantic type chain of the column: a specific key first, then
generic fallbacks. Date and time columns intentionally omit the Doctrine
variant (`datetime_immutable` resolves to `datetime`), and enums resolve to a
dedicated `enum` key.

| Column / association | Override files, most specific first |
|---|---|
| `datetime`, `datetime_immutable`, `datetimetz`, `datetimetz_immutable` | `field_type_datetime` |
| `date`, `date_immutable` | `field_type_date` → `field_type_datetime` |
| `time`, `time_immutable` | `field_type_time` → `field_type_datetime` |
| enum column (`enumType`) | `field_type_enum` → `field_type_string` |
| string, guid, ascii_string | `field_type_string` |
| text, blob | `field_type_text` → `field_type_string` |
| integer, smallint, bigint | `field_type_<raw>` → `field_type_int` → `field_type_number` |
| decimal | `field_type_decimal` → `field_type_string` |
| float | `field_type_float` → `field_type_number` |
| boolean / bool | `field_type_boolean` → `field_type_bool` |
| json | `field_type_json` → `field_type_array` |
| to-one association | `association_type_one` |
| to-many association | `association_type_many` |

Example: wrap every datetime cell in a `<time>` element.

```twig
{# templates/bundles/KarrossBundle/index/field_type_datetime.html.twig #}
<time class="datetime-cell">{{ k_formatted_value(item, property) }}</time>
```

*How it works: `TemplateResolver` builds the resolution map once at boot. The
`k_template` Twig function picks the best match for each field.*