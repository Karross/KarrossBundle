# MVP 1 — Full CRUD on simple entities

## 1. Dead code & deprecated methods <span class="k-status k-status--green">Ready</span>

<table class="k-ticket k-ticket--green">
  <tbody>
    <tr><td><strong>Existing.</strong> Phantom <code>PropertyInterface</code> — only an <code>@param</code> annotation in <code>EntityMetadata</code>; no such class, the real property types (<code>FieldMetadata</code>/<code>AssociationMetadata</code>) inherit from <code>PropertyMetadata</code>. Deprecated <code>getTypeOfField()</code>/<code>getTypeOfAssociation()</code> in <code>EntityMetadata</code> — pure wrappers of <code>getPropertyType()-&gt;value</code>, sole caller <code>TemplateResolver:44</code>, where <code>$property-&gt;type-&gt;value</code> is strictly equivalent. Note: <code>getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is the Doctrine <code>ClassMetadata</code> method — untouched. No released version: the "backward compatibility" rationale is void.</td></tr>
    <tr><td><strong>Expected.</strong> Dead code gone, zero behaviour change, all-check green.</td></tr>
    <tr><td><strong>Prerequisites.</strong> None.</td></tr>
    <tr><td><strong>Plan.</strong> <ol><li><code>EntityMetadata</code>: fix the phantom docblock (<code>PropertyInterface[]</code> → <code>PropertyMetadata[]</code>; the real collections are <code>FieldMetadata</code>/<code>AssociationMetadata</code>, both extending <code>PropertyMetadata</code>).</li><li><code>EntityMetadata</code>: drop <code>getTypeOfField()</code>, <code>getTypeOfAssociation()</code> <em>and</em> <code>getPropertyType()</code>. Code survey: no consumer of "type by name" — the type is consumed at build (<code>EntityMetadataBuilder</code> → <code>FormatterResolver::resolve()</code> to pick the formatter) and at resolution (<code>TemplateResolver</code>, which already holds <code>$property</code>); runtime rendering uses the precomputed <code>$property-&gt;formatter</code> class-string (<code>k_formatted_value</code>); templates never touch the type. The "nature × type → formatter/widget" logic this API prefigured (link around an association value, several links for a collection) is deferred to the <strong>TypeGuesser rework</strong> — a formatter concern, not a type-string lookup.</li><li><code>TemplateResolver:44</code>: replace the wrapper call with <code>$property-&gt;type-&gt;value</code> (strictly equivalent; the field/association split already relies on <code>instanceof</code> at <code>TemplateResolver:41</code>).</li><li>Grep <code>src/</code> and <code>tests/</code> for <code>PropertyInterface|getTypeOfField|getTypeOfAssociation|getPropertyType</code> → zero remaining (Doctrine's <code>ClassMetadata::getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is untouched).</li></ol></td></tr>
  </tbody>
</table>

## Cache wiring (metadata + templates) <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> <code>TemplateRegistry::all()</code> (double <code>return</code>, the second unreachable) and <code>EntityMetadataRegistry::all()</code> (commented-out cache) inject and wire a <code>CacheInterface</code> that is never used; real per-request cost (Twig <code>resolveTemplate()</code> per pattern behind <code>k_template</code>, metadata rebuilt several times per request).</td></tr>
    <tr><td><strong>Expected.</strong> Caches never active in dev, always active in prod; identical behaviour in dev; all-check green.</td></tr>
    <tr><td><strong>Prerequisites.</strong> None.</td></tr>
  </tbody>
</table>

## Complexity measure in CI <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> No automated complexity measure; the PHPStan baseline (139) mostly blames over-fed arrays.</td></tr>
    <tr><td><strong>Expected.</strong> Deterministic AST-based cyclomatic measure over <code>src/</code>, baseline + threshold, run in CI and locally via <code>make</code>.</td></tr>
    <tr><td><strong>Prerequisites.</strong> None — transversal tracking tool, not blocking; added after the dead-code cleanup and the cache ticket.</td></tr>
    <tr><td><strong>Approach.</strong> A php-parser based analysis wired as a <code>make</code> target + CI step.</td></tr>
  </tbody>
</table>

## Browser CSS compatibility gate in CI <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> Zero CSS today, no defined browser support.</td></tr>
    <tr><td><strong>Expected.</strong> A CI gate failing on any CSS unsupported by the target browsers, plus a responsive budget harness in the E2E suite.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Decide the supported browser set.</td></tr>
    <tr><td><strong>Approach.</strong> A deterministic compatibility checker wired as <code>make</code> + CI, and viewport-driven E2E checks.</td></tr>
  </tbody>
</table>

## TypeGuesser rework <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> <code>PropertyTypeDetector</code> resolves the semantic <code>PropertyType</code>; no mapping to Symfony form types.</td></tr>
    <tr><td><strong>Expected.</strong> Map <code>PropertyType</code> → Symfony form type + widget decision, config override <code>entities.&lt;FQCN&gt;.properties.&lt;name&gt;.type</code>, one type graph for forms and formatting.</td></tr>
    <tr><td><strong>Approach.</strong> Build on the detector, add the form-mapping layer, keep the formatter machinery intact.</td></tr>
  </tbody>
</table>

## Generic create/update forms <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> Index/show + home actions only; <code>Action</code> enum holds create/update/delete as TODO.</td></tr>
    <tr><td><strong>Expected.</strong> Create and update with CSRF, validation, redirect after post, flash, flush/cascade; simple scalars first, embedded/relations deferred to MVP 2.</td></tr>
    <tr><td><strong>Prerequisites.</strong> TypeGuesser rework.</td></tr>
  </tbody>
</table>

## Delete action <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> No mutation actions.</td></tr>
    <tr><td><strong>Expected.</strong> Delete with CSRF protection, cascade-safe, redirect + flash.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Generic create/update forms — shares its pipeline.</td></tr>
  </tbody>
</table>

## Minimal design decision <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> CSS strategy (design tokens + <code>k-*</code> classes), zero JS until the forms, dark mode?, fonts, asset location, host replacability.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Browser CSS compatibility gate in CI — guards before any CSS.</td></tr>
  </tbody>
</table>

## Minimal theme — index/show + responsive <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Token system, single CSS file, system fonts, validated by the Browser CSS compatibility gate in CI + the responsive budget.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Minimal design decision.</td></tr>
  </tbody>
</table>

## CRUD forms styling <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Expected.</strong> Thin styling layer over the stable markup of Generic create/update forms and the theme of Minimal theme — index/show + responsive.</td></tr>
    <tr><td><strong>Prerequisites.</strong> Generic create/update forms + Minimal theme — index/show + responsive.</td></tr>
  </tbody>
</table>

## Reference documentation <span class="k-status k-status--red">Proposed</span>

<table class="k-ticket k-ticket--red">
  <tbody>
    <tr><td><strong>Existing.</strong> The online docs cover usage (how-to) but offer no systematic reference of the whole configuration surface.</td></tr>
    <tr><td><strong>Expected.</strong> A reference section in the online docs (à la Sonata) listing every MVP-1 configuration option — routes, formatters, type overrides, template override points — curated or generated, scope to decide.</td></tr>
    <tr><td><strong>Prerequisites.</strong> None — compiled progressively, complete once the MVP-1 config surface is stable.</td></tr>
  </tbody>
</table>