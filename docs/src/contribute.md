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
make all-check      # full check: CSS compat → style → phpstan → complexity → tests (must be green)
make test           # phpunit suites (incl. E2E Playwright)
make qa             # complexity/volume gate only (ast-metrics)
make css-check      # browser CSS compatibility gate only (stylelint + Baseline)
make npm-install    # install the npm tooling (stylelint) — once, after a fresh clone
make serve          # serve the demo apps (Ctrl-C to stop)
```

`make all-check` must be green before submitting a PR.

### Complexity gate (ast-metrics)

`make qa` runs [ast-metrics](https://ast-metrics.dev/) over `src/` with the
thresholds declared in `.ast-metrics.yaml` (max cyclomatic complexity 10, max
20 logical lines of code per file). Like the PHPStan baseline,
`.ast-metrics-baseline.yaml` freezes the violations that already existed when
it was generated: **only new or worsened violations fail**.

> Note: ast-metrics v0.43 only measures `max_loc_by_method` /
> `max_logical_loc_by_method` for top-level functions — PHP methods of a
> class are not covered, so those two rules stay declared but do not fire on
> class-based code. The logical-lines rule (`max_logical_loc`) is the one
> that reports actual PHP code: physical line counts (`max_loc`) were tried
> and dropped because docblocks inflated them (seen up to 49 physical lines
> for 27 code lines); the declared thresholds are those to meet, past
> overruns being frozen in the baseline.

When a cleanup sprint genuinely reduces the complexity of `src/`, regenerate
the baseline so the reduced state becomes the new reference:

```bash
docker compose run --rm php ast-metrics baseline src
```

`make qa` is part of `make all-check` and of the CI pipeline.

### Browser CSS compatibility gate (stylelint + Baseline)

`make css-check` runs [stylelint](https://stylelint.io/) with
[`stylelint-plugin-use-baseline`](https://www.npmjs.com/package/stylelint-plugin-use-baseline)
over the bundle's own stylesheet — `src/Resources/public/css/karross.css`. The
rule (`plugin/use-baseline`, configured in `.stylelintrc.mjs`) enforces the
[Baseline](https://web.dev/baseline) **`widely`** policy: any CSS feature that
is not supported in all Baseline browsers for at least 30 months is a warning,
unless it is wrapped in an `@supports` block. The gate treats warnings as
errors (`--max-warnings 0`), so the bundle cannot silently ship a feature that
the target browsers do not all support.

The npm tooling (stylelint + the Baseline plugin) is pinned to exact versions
in `package.json`; like Composer, there is no committed lock file —
`node_modules` lives on the host through the Docker volume. Install it once
per clone with `make npm-install` (the php-env CI action does the same).

`make css-check` is part of `make all-check` and of the CI pipeline.

### Commit messages

Commit messages follow the conventional single-line style:

```text
type(scope): summary
```

- `type` (lowercase): `feat`, `fix`, `refactor`, `docs`, `test`, `ci`, `chore`, ...
- `scope` (optional, lowercase): the touched area (e.g. `home`, `routes`, `formatters`)
- `summary`: what the commit does, concisely — no body, one line only

`bin/check-commit-message` enforces this rule. Locally it runs as a git hook
(install once per machine):

```bash
make install-hooks       # git config core.hooksPath hooks
```

The standalone target `make check-commit-message` validates the last commit
message and is run by CI on every push / pull request. It is deliberately NOT
part of `make all-check`: you may run the checks before any commit exists.

### Demo apps and local URLs

`make serve` starts the bundle's test apps:

| URL | App |
|---|---|
| `http://127.0.0.1:8000/admin/article` | default (no Karross config, out of the box) |
| `http://127.0.0.1:8080/fr/dashboard/article` | with config (prefix `dashboard` + locale) |
| `http://127.0.0.1:8080/en/dashboard/article` | same, English locale |

## Working locally on the documentation

The documentation sources live in the bundle repository (`docs/`: Markdown +
MkDocs), written in English.

```bash
cd docs
mkdocs serve                  # preview on http://127.0.0.1:8001/
mkdocs build                  # regenerate site/
```

Open a pull request against `Karross/KarrossBundle` with your changes.