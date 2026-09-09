# Configuring routes

## Out of the box

Every mapped entity is exposed with two routes:

| Route | Action |
|---|---|
| `/admin/{slug}` | list (index) |
| `/admin/{slug}/{identifiers}` | single row (show) |

`{slug}` is the entity shortname by default (override it with
`entities.<FQCN>.slug`). `{identifiers}` is one segment per identifier -
composite keys are supported.

## Changing the URL structure

All knobs live under `karross.routes`:

```yaml
karross:
  routes:
    prefix: dashboard                        # default: admin
    home: /{prefix}                          # default home pattern
    index: /{prefix}/{slug}                   # default index pattern
    show: /{prefix}/{slug}/{identifiers}      # default show pattern
```

With the configuration above, the admin portal listens at `/dashboard` -
trailing slash (`/dashboard/`) also works - and the entity routes become
`/dashboard/{slug}` and `/dashboard/{slug}/{identifiers}`.

### Recognized tokens

| Token | Meaning |
|---|---|
| `{prefix}` | the global routes prefix |
| `{slug}` | the entity slug |
| `{identifiers}` | one segment per identifier (composite keys supported) |
| `{_locale}` | passed through to Symfony's locale handling |

An unknown token throws an error at route loading time.

*How it works: route patterns are resolved once at load time by the route
loader; the pattern per action comes from the configuration with sane
defaults.*