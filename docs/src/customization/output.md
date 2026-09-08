# Output

## Out of the box

Karross renders the admin as **HTML pages via Twig** and exposes **JSON
responses** for headless/API use. The output format is selected per request.

## Choosing the output

```yaml
karross:
  output:
    api: true      # JSON responses enabled (default: true)
    html: twig     # HTML renderer (default: twig)
```

| Option | Values | Effect |
|---|---|---|
| `output.api` | `true` (default) / `false` | enable/disable JSON responses |
| `output.html` | `twig` (default) | HTML renderer (other renderers planned) |

*How it works: a responder manager picks the responder that supports the
request format - Twig for HTML, JSON for the API. Only `twig` is implemented
today.*