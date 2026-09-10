# MVP 2 — Complex properties & fine-grained customization

<details class="k-ticket k-ticket--red">
  <summary>Complex properties <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Index/show already cover simple scalars and read-only associations; an embedded-fields metadata base is in place.</td></tr>
      <tr><th>Expected.</th><td>Arrays, objects and all Doctrine association types handled in forms and display; embedded fields deepened.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — CRUD lane (TypeGuesser rework, forms, delete action).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Fine-grained form customization <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Per-property form customization (widget, constraints, labels), configurable like the formatters.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — TypeGuesser rework (type graph).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Richer formatters <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Four small stories: address-to-map, color picker, calendar, multi-select.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — Minimal design decision: they require JS, blocked by the design lane.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>JSON output <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>HTML via the twig responders; the headless JSON rendering is already an interface in germ.</td></tr>
      <tr><th>Expected.</th><td>JSON endpoints — scope (endpoints, shape, media-type) and timing to decide.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — CRUD lane.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Navigation between related entities <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Navigate from one entity to its related ones (UX open: breadcrumbs? linked pages?).</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — CRUD lane.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>In-admin documentation <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Developer help embedded in the admin pages.</td></tr>
      <tr><th>Prerequisites.</th><td>Coupled to the design lane — to decide.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Reference documentation — update <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The reference page created in MVP 1 covers the base configuration surface (routes, formatters, type overrides, template overrides).</td></tr>
      <tr><th>Expected.</th><td>Extend the reference with the MVP-2 surface: per-property form customization, widget renderers, richer formatters, JSON output.</td></tr>
      <tr><th>Prerequisites.</th><td>This MVP's Fine-grained form customization + Richer formatters: the reference documents the config surface once it stabilizes.</td></tr>
    </tbody>
  </table>
</details>