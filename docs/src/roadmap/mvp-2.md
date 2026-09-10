# MVP 2 — Complex properties & fine-grained customization

## Complex properties <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> Index/show already cover simple scalars and read-only associations; an embedded-fields metadata base is in place.</td></tr>
    <tr><td><strong>Expected.</strong> Arrays, objects and all Doctrine association types handled in forms and display; embedded fields deepened.</td></tr>
    <tr><td><strong>Prerequisites.</strong> MVP 1 — CRUD lane (TypeGuesser rework, forms, delete action).</td></tr>
  </tbody>
</table>

## Fine-grained form customization <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Per-property form customization (widget, constraints, labels), configurable like the formatters.</td></tr>
    <tr><td><strong>Prerequisites.</strong> MVP 1 — TypeGuesser rework (type graph).</td></tr>
  </tbody>
</table>

## Richer formatters <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Four small stories: address-to-map, color picker, calendar, multi-select.</td></tr>
    <tr><td><strong>Prerequisites.</strong> MVP 1 — Minimal design decision: they require JS, blocked by the design lane.</td></tr>
  </tbody>
</table>

## JSON output <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> HTML via the twig responders; the headless JSON rendering is already an interface in germ.</td></tr>
    <tr><td><strong>Expected.</strong> JSON endpoints — scope (endpoints, shape, media-type) and timing to decide.</td></tr>
    <tr><td><strong>Prerequisites.</strong> MVP 1 — CRUD lane.</td></tr>
  </tbody>
</table>

## Navigation between related entities <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Navigate from one entity to its related ones (UX open: breadcrumbs? linked pages?).</td></tr>
    <tr><td><strong>Prerequisites.</strong> MVP 1 — CRUD lane.</td></tr>
  </tbody>
</table>

## In-admin documentation <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Developer help embedded in the admin pages.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Coupled to the design lane — to decide.</td></tr>
  </tbody>
</table>

## Reference documentation — update <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> The reference page created in MVP 1 covers the base configuration surface (routes, formatters, type overrides, template overrides).</td></tr>
    <tr><td><strong>Expected.</strong> Extend the reference with the MVP-2 surface: per-property form customization, widget renderers, richer formatters, JSON output.</td></tr>
    <tr><td><strong>Prerequisites.</strong> This MVP's Fine-grained form customization + Richer formatters: the reference documents the config surface once it stabilizes.</td></tr>
  </tbody>
</table>