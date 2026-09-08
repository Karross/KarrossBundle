# How to contribute

Contributions, issues and feature requests are welcome.

## GitHub workflow

- Report bugs or propose ideas via [GitHub Issues](https://github.com/Karross/KarrossBundle/issues)
- To change code or documentation: fork the repository, work on a dedicated
  branch, open a pull request against `main`
- Describe what the PR changes and why; keep it focused and reviewable

## Working locally on the bundle

All commands run from the `KarrossBundle/` directory, inside Docker (the host
PHP lacks `ext-intl`).

```bash
make all-fix        # auto-fix code style (php-cs-fixer)
make all-check      # full check: style → phpstan → tests (must be green)
make test           # phpunit suites (incl. E2E Playwright)
make serve          # serve the demo apps (Ctrl-C to stop)
```

`make all-check` must be green before submitting a PR.

### Demo apps and local URLs

`make serve` starts the bundle's test apps:

| URL | App |
|---|---|
| `http://127.0.0.1:8000/admin/article` | default (no Karross config, out of the box) |
| `http://127.0.0.1:8080/fr/dashboard/article` | with config (prefix `dashboard` + locale) |
| `http://127.0.0.1:8080/en/dashboard/article` | same, English locale |

## Working locally on the documentation

The documentation sources live in the `docs/` repository (Markdown + MkDocs),
written in English.

```bash
cd docs
mkdocs serve                  # preview on http://127.0.0.1:8001/
mkdocs build                  # regenerate site/
```

Open a pull request against `Karross/docs` with your changes.