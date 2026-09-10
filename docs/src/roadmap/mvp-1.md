# MVP 1 — Full CRUD on simple entities

<details class="k-ticket k-ticket--green" open>
  <summary>1. Dead code &amp; deprecated methods <span class="k-status k-status--green">Ready</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Phantom <code>PropertyInterface</code> — only an <code>@param</code> annotation in <code>EntityMetadata</code>; no such class, the real property types (<code>FieldMetadata</code>/<code>AssociationMetadata</code>) inherit from <code>PropertyMetadata</code>. Deprecated <code>getTypeOfField()</code>/<code>getTypeOfAssociation()</code> in <code>EntityMetadata</code> — pure wrappers of <code>getPropertyType()-&gt;value</code>, sole caller <code>TemplateResolver:44</code>, where <code>$property-&gt;type-&gt;value</code> is strictly equivalent. Note: <code>getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is the Doctrine <code>ClassMetadata</code> method — untouched. No released version: the "backward compatibility" rationale is void.</td></tr>
      <tr><th>Expected.</th><td>Dead code gone, zero behaviour change, all-check green.</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
      <tr><th>Plan.</th><td><ol><li><code>EntityMetadata</code>: fix the phantom docblock (<code>PropertyInterface[]</code> → <code>PropertyMetadata[]</code>; the real collections are <code>FieldMetadata</code>/<code>AssociationMetadata</code>, both extending <code>PropertyMetadata</code>).</li><li><code>EntityMetadata</code>: drop <code>getTypeOfField()</code>, <code>getTypeOfAssociation()</code> <em>and</em> <code>getPropertyType()</code>. Code survey: no consumer of "type by name" — the type is consumed at build (<code>EntityMetadataBuilder</code> → <code>FormatterResolver::resolve()</code> to pick the formatter) and at resolution (<code>TemplateResolver</code>, which already holds <code>$property</code>); runtime rendering uses the precomputed <code>$property-&gt;formatter</code> class-string (<code>k_formatted_value</code>); templates never touch the type. The "nature × type → formatter/widget" logic this API prefigured (link around an association value, several links for a collection) is deferred to the <strong>TypeGuesser rework</strong> — a formatter concern, not a type-string lookup.</li><li><code>TemplateResolver:44</code>: replace the wrapper call with <code>$property-&gt;type-&gt;value</code> (strictly equivalent; the field/association split already relies on <code>instanceof</code> at <code>TemplateResolver:41</code>).</li><li>Grep <code>src/</code> and <code>tests/</code> for <code>PropertyInterface|getTypeOfField|getTypeOfAssociation|getPropertyType</code> → zero remaining (Doctrine's <code>ClassMetadata::getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is untouched).</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Cache wiring (metadata + templates) <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>TemplateRegistry::all()</code> (double <code>return</code>, the second unreachable) and <code>EntityMetadataRegistry::all()</code> (commented-out cache) inject and wire a <code>CacheInterface</code> that is never used; real per-request cost (Twig <code>resolveTemplate()</code> per pattern behind <code>k_template</code>, metadata rebuilt several times per request).</td></tr>
      <tr><th>Expected.</th><td>Caches never active in dev, always active in prod; identical behaviour in dev; all-check green.</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Complexity measure in CI <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No automated complexity measure; the PHPStan baseline (139) mostly blames over-fed arrays.</td></tr>
      <tr><th>Expected.</th><td>Deterministic AST-based cyclomatic measure over <code>src/</code>, baseline + threshold, run in CI and locally via <code>make</code>.</td></tr>
      <tr><th>Prerequisites.</th><td>None — transversal tracking tool, not blocking; added after the dead-code cleanup and the cache ticket.</td></tr>
      <tr><th>Approach.</th><td>A php-parser based analysis wired as a <code>make</code> target + CI step.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Browser CSS compatibility gate in CI <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Zero CSS today, no defined browser support.</td></tr>
      <tr><th>Expected.</th><td>A CI gate failing on any CSS unsupported by the target browsers, plus a responsive budget harness in the E2E suite.</td></tr>
      <tr><th>Prerequisites.</th><td>Decide the supported browser set.</td></tr>
      <tr><th>Approach.</th><td>A deterministic compatibility checker wired as <code>make</code> + CI, and viewport-driven E2E checks.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>TypeGuesser rework <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>PropertyTypeDetector</code> resolves the semantic <code>PropertyType</code>; no mapping to Symfony form types.</td></tr>
      <tr><th>Expected.</th><td>Map <code>PropertyType</code> → Symfony form type + widget decision, config override <code>entities.&lt;FQCN&gt;.properties.&lt;name&gt;.type</code>, one type graph for forms and formatting.</td></tr>
      <tr><th>Approach.</th><td>Build on the detector, add the form-mapping layer, keep the formatter machinery intact.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Generic create/update forms <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Index/show + home actions only; <code>Action</code> enum holds create/update/delete as TODO.</td></tr>
      <tr><th>Expected.</th><td>Create and update with CSRF, validation, redirect after post, flash, flush/cascade; simple scalars first, embedded/relations deferred to MVP 2.</td></tr>
      <tr><th>Prerequisites.</th><td>TypeGuesser rework.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Delete action <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No mutation actions.</td></tr>
      <tr><th>Expected.</th><td>Delete with CSRF protection, cascade-safe, redirect + flash.</td></tr>
      <tr><th>Prerequisites.</th><td>Generic create/update forms — shares its pipeline.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Minimal design decision <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>CSS strategy (design tokens + <code>k-*</code> classes), zero JS until the forms, dark mode?, fonts, asset location, host replacability.</td></tr>
      <tr><th>Prerequisites.</th><td>Browser CSS compatibility gate in CI — guards before any CSS.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Minimal theme — index/show + responsive <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Token system, single CSS file, system fonts, validated by the Browser CSS compatibility gate in CI + the responsive budget.</td></tr>
      <tr><th>Prerequisites.</th><td>Minimal design decision.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>CRUD forms styling <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Thin styling layer over the stable markup of Generic create/update forms and the theme of Minimal theme — index/show + responsive.</td></tr>
      <tr><th>Prerequisites.</th><td>Generic create/update forms + Minimal theme — index/show + responsive.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Reference documentation <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The online docs cover usage (how-to) but offer no systematic reference of the whole configuration surface.</td></tr>
      <tr><th>Expected.</th><td>A reference section in the online docs (à la Sonata) listing every MVP-1 configuration option — routes, formatters, type overrides, template override points — curated or generated, scope to decide.</td></tr>
      <tr><th>Prerequisites.</th><td>None — compiled progressively, complete once the MVP-1 config surface is stable.</td></tr>
    </tbody>
  </table>
</details>