# MVP 1 — Full CRUD on simple entities

<details class="k-ticket k-ticket--blue">
  <summary>1. Dead code &amp; deprecated methods <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Phantom <code>PropertyInterface</code> — only an <code>@param</code> annotation in <code>EntityMetadata</code>; no such class, the real property types (<code>FieldMetadata</code>/<code>AssociationMetadata</code>) inherit from <code>PropertyMetadata</code>. Deprecated <code>getTypeOfField()</code>/<code>getTypeOfAssociation()</code> in <code>EntityMetadata</code> — pure wrappers of <code>getPropertyType()-&gt;value</code>, sole caller <code>TemplateResolver:44</code>, where <code>$property-&gt;type-&gt;value</code> is strictly equivalent. Note: <code>getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is the Doctrine <code>ClassMetadata</code> method — untouched. No released version: the "backward compatibility" rationale is void.</td></tr>
      <tr><th>Expected.</th><td>Dead code gone, zero behaviour change, all-check green.</td></tr>
      <tr><th>Plan.</th><td><ol><li><code>EntityMetadata</code>: fix the phantom docblock (<code>PropertyInterface[]</code> → <code>PropertyMetadata[]</code>; the real collections are <code>FieldMetadata</code>/<code>AssociationMetadata</code>, both extending <code>PropertyMetadata</code>).</li><li><code>EntityMetadata</code>: drop <code>getTypeOfField()</code>, <code>getTypeOfAssociation()</code> <em>and</em> <code>getPropertyType()</code>. Code survey: no consumer of "type by name" — the type is consumed at build (<code>EntityMetadataBuilder</code> → <code>FormatterResolver::resolve()</code> to pick the formatter) and at resolution (<code>TemplateResolver</code>, which already holds <code>$property</code>); runtime rendering uses the precomputed <code>$property-&gt;formatter</code> class-string (<code>k_formatted_value</code>); templates never touch the type. The "nature × type → formatter/widget" logic this API prefigured (link around an association value, several links for a collection) is deferred to the <strong>Collect &amp; Computed</strong> foundation — a formatter concern, not a type-string lookup. (The <strong>Collect &amp; Computed</strong> foundation later replaces <code>$property-&gt;type-&gt;value</code> at <code>TemplateResolver:44</code> with the <code>templateKey</code> deduction, computed at build time from the facts — the <code>type</code> field disappears.)</li><li><code>TemplateResolver:44</code>: replace the wrapper call with <code>$property-&gt;type-&gt;value</code> (strictly equivalent; the field/association split already relies on <code>instanceof</code> at <code>TemplateResolver:41</code>).</li><li>Grep <code>src/</code> and <code>tests/</code> for <code>PropertyInterface|getTypeOfField|getTypeOfAssociation|getPropertyType</code> → zero remaining (Doctrine's <code>ClassMetadata::getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is untouched).</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>2. Cache wiring (metadata + templates) <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>TemplateRegistry::all()</code> (double <code>return</code>, the second unreachable) and <code>EntityMetadataRegistry::all()</code> (commented-out cache) inject and wire a <code>CacheInterface</code> that is never used; real per-request cost (Twig <code>resolveTemplate()</code> per pattern behind <code>k_template</code>, metadata rebuilt several times per request).</td></tr>
      <tr><th>Expected.</th><td>Caches never active in dev, always active in prod; identical behaviour in dev; all-check green.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Make <code>EntityMetadata</code> a pure data object: capture <code>fqcn</code> (from <code>classMetadata->getName()</code>) and <code>identifier</code> (from <code>classMetadata->getIdentifier()</code>) in <code>EntityMetadataBuilder</code> and pass them as constructor values; delete <code>getValue()</code> and <code>isEmbedded()</code> (no callers — runtime value reading already lives in the Twig extensions via <code>PropertyAccessor</code>, do not reimplement); <code>getFqcn()</code>/<code>getIdentifier()</code> return the stored values; remove the <code>ClassMetadata</code> property.</li><li>Gate: a <code>bool $cacheEnabled</code> computed once from <code>!kernel.debug</code> (each registry receives <code>kernel.debug</code> as a constructor argument via <code>services.php</code> and negates it — deliberately avoids the <code>symfony/expression-language</code> hard dependency that a container <code>expr()</code> would force); given to both registries.</li><li>Metadata cache: <code>EntityMetadataRegistry::all()</code> returns <code>cache.get('karross.metadata', build)</code> when enabled, fresh build when not (replacing the double-return / commented paths).</li><li>Template cache: <code>TemplateRegistry::all()</code> — same pattern on the resolver result, replacing the unreachable second <code>return</code>.</li><li>New integration test: with a debug kernel the registries rebuild every call; with a prod-like (debug=false) kernel the builder is invoked once and the cached result deep-equals a cold rebuild; the gate toggles correctly.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>3. Complexity measure in CI <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No automated complexity measure; the PHPStan baseline (139) mostly blames over-fed arrays.</td></tr>
      <tr><th>Expected.</th><td>Deterministic AST-based cyclomatic measure over <code>src/</code>, baseline + threshold, run in CI and locally via <code>make</code>.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Install <strong>ast-metrics</strong> in the Docker image: fetch the pinned release binary (version + SHA256 in the Dockerfile) into <code>/usr/local/bin</code>; confirm the version targets PHP 8.5. Pinned <code>v0.43.0</code> (supports PHP ≤ 8.5, single static binary, works offline).</li><li>Config <code>.ast-metrics.yaml</code> (<code>ast-metrics init</code> then trimmed), rulesets <code>complexity</code> + <code>volume</code> over <code>./src</code> with the REC-confirmed thresholds: <code>max_cyclomatic: 10</code>, <code>max_loc: 30</code>, <code>max_logical_loc: 20</code>. Note: <code>max_nesting</code> only exists in ast-metrics' <code>golang</code> ruleset — there is no PHP nesting rule, so the plan drops it.</li><li>Freeze the current state: <code>ast-metrics baseline src</code>, commit the snapshot (mirrors <code>phpstan-baseline.neon</code>); only new violations fail.</li><li>Local flow: add <code>make qa</code> → <code>ast-metrics lint</code> (exit 1 on any regression beyond the baseline); include it in <code>all-check</code> so local and CI share the same gate.</li><li>CI: add a <code>make qa</code> step in <code>ci.yml</code> after phpstan — regressions beyond the baseline block the pipeline.</li><li>Docs: document <code>make qa</code> and the baseline-regeneration rule (after cleanup sprints) in <code>docs/src/contribute.md</code>.</li></ol> <strong>Realized.</strong> Validation was revealing: in v0.43 the two <em>by-method</em> volume rules only scan top-level functions (<code>file.Stmts.StmtFunction</code>) — PHP class methods live in <code>StmtClass[].StmtFunction</code> — so <code>max_loc_by_method</code> / <code>max_logical_loc_by_method</code> never fire on class-based code (zero violation at threshold 1). The logical-lines rule (<code>max_logical_loc</code>) reports PHP accurately. <code>max_loc</code> (physical lines) was tried then dropped: docblocks inflated it (49 physical lines for 27 code lines — noise). Final gate: <code>max_cyclomatic: 10</code> + <code>max_logical_loc: 20</code>, by-method keys kept declared (inert on classes); baseline freezes <strong>11</strong> pre-existing violations (6 cyclomatic + 5 logical-lines).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>4. Browser CSS compatibility gate in CI <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Zero CSS today, no defined browser support.</td></tr>
      <tr><th>Expected.</th><td>A CI gate failing on any CSS unsupported by the target browsers.</td></tr>
      <tr><th>Prerequisites.</th><td>Decide the supported browser set.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Pin the npm tooling: a minimal <code>package.json</code> with <code>stylelint</code> + <code>stylelint-plugin-use-baseline</code> (Node is already in the container for Playwright). Exact version pins, no committed lock — mirrors the composer philosophy; <code>node_modules</code> lives on the host via the volume, like <code>vendor/</code>.</li><li><code>.stylelintrc.mjs</code>: <code>plugin/use-baseline</code> policy set to <code>widely</code> first (most compatible — REC choice), relaxed to <code>newly</code> later only if a theme feature justifies it.</li><li>Fix the canonical asset path <code>src/Resources/public/css/karross.css</code> (Symfony bundle convention, host-replaceable) — the gate lints the bundle's own single CSS file; delivery (compression, cache, hashing) stays out of bundle scope.</li><li><code>make css-check</code>: stylelint gate, exit 1 on any unsupported CSS at the target level (warnings treated as errors via <code>--max-warnings 0</code>); wired into the CI pipeline.</li><li>Docs: <code>contribute.md</code> — the <code>make css-check</code> command and the Baseline policy.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>5. Reorganize Metadata folder — Collect/Computed <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The <code>Metadata</code> namespace mixes analysis machinery (<code>PropertyTypeDetector</code>, <code>EntityMetadataBuilder</code>) and output read-models (<code>EntityMetadata</code>, <code>PropertyMetadata</code>, <code>FieldMetadata</code>, <code>AssociationMetadata</code>, registry). The DTO <code>PropertyTypeInfo</code> (no consumer) sits in <code>Karross\Formatters</code>, unused.</td></tr>
      <tr><th>Expected.</th><td>Two clean, non-crossing namespaces:
      <ul>
        <li><strong><code>Metadata\Collect</code></strong>: machinery only — <code>PropertyTypeDetector</code> (type detection), <code>EntityMetadataBuilder</code> (orchestrator that constructs the read-models).</li>
        <li><strong><code>Metadata\Computed</code></strong>: read-models — <code>EntityMetadata</code>, <code>PropertyMetadata</code>, <code>FieldMetadata</code>, <code>AssociationMetadata</code>, <code>FieldLabel</code>, <code>EntityMetadataRegistry</code> (the access layer).</li>
      </ul>
      Dependency rule (corrected): the pure read-models (Computed) never import Collect machinery — they are inert data. The only seam is the registry (Computed) consuming the builder (Collect), while the builder constructs the read-models (so Collect → Computed exists by construction). <code>PropertyType</code> stays at the <code>Karross\Metadata</code> root (shared vocabulary between both sides and the Formatters) — it is meant to disappear with the "Collect & Computed" refactor. The DTO <code>PropertyTypeInfo</code> (no consumer) is deleted.</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Create <code>src/Metadata/Collect/</code> and <code>src/Metadata/Computed/</code>.</li><li>Move the machinery (<code>PropertyTypeDetector</code>, <code>EntityMetadataBuilder</code>) into <code>Collect/</code>.</li><li>Move the read-models (<code>EntityMetadata</code>, <code>PropertyMetadata</code>, <code>FieldMetadata</code>, <code>AssociationMetadata</code>, <code>FieldLabel</code>) into <code>Computed/</code>.</li><li>Move <code>EntityMetadataRegistry</code> into <code>Computed/</code> (the access layer over the read-models).</li><li>Leave <code>PropertyType</code> at the <code>Karross\Metadata</code> root — shared vocabulary, destined to disappear with the "Collect & Computed" refactor.</li><li>Delete <code>PropertyTypeInfo</code> (DTO with no consumer).</li><li>Update every import: <code>services.php</code>, <code>EntityMetadataRegistry</code>, <code>EntityMetadata</code> (creates <code>FieldLabel</code>), Twig extensions, Routes, Pages, Actions, tests.</li><li>Docs: architecture context and <code>docs/src</code> note the rule — Computed read-models stay inert (no Collect import); the seam is the registry → builder.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>6. Collect &amp; Computed <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Collection was reductive: <code>PropertyTypeDetector::detect()</code> forced every property into one case of a bundle-made semantic enum (<code>PropertyType</code> — string, integer, date…), interpreted before any use from the PHP type, the Doctrine type and the PHPDoc. Every consumer hooked on that single case: the formatter through a one-type-one-formatter mapping (<code>FormatterResolver::resolve()</code>), the template keys through <code>_type_{propertyType}</code>. The raw facts (length, precision, scale, nullable…) were lost — the <code>PropertyTypeInfo</code> DTO meant to carry them had no consumer. The enum also mixed <em>nature</em> (string, integer, date…) and <em>structure</em> (single/multiple values), though the structure is already known to Doctrine (<code>isSingleValuedAssociation()</code>/<code>isCollectionValuedAssociation()</code>) and the templates already tell a field from an association.</td></tr>
      <tr><th>Expected.</th><td>Collection gathers the facts without interpreting them; every useful deduction (formatter, resolved renderer templates, tomorrow the widget) is computed at build time, in a single pass over the Doctrine <code>ClassMetadata</code>, each by its own usage mechanism, each replaceable by the host config. The <code>PropertyType</code> enum, the <code>PropertyTypeInfo</code> DTO and the metadata <code>type</code> field disappear: no forced semantic vocabulary, no nature/structure distinction in an enum. No consumer re-derives semantics at render time — the renderer composes candidates from its own vocabulary and only resolves the physical file existence (<code>resolveTemplate()</code>).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Dependencies (REC decision 2026-09-13)</strong>: <code>doctrine/orm</code> and <code>doctrine/dbal</code> move to <code>require</code> (the bundle targets Symfony + Doctrine ORM — the host already provides them). The builder types against <code>Doctrine\ORM\Mapping\ClassMetadata</code> behind a <strong>single runtime assertion</strong> at the top of its loop (<code>getAllMetadata()</code> returns the persistence interface; the real instance is the ORM one — guaranteed since <code>doctrine/orm</code> is required).</li>
        <li><strong>Collector</strong>: <code>PropertyTypeDetector</code> becomes <code>Metadata\Collect\ComputedMetadataBuilder</code> (ex-<code>EntityMetadataBuilder</code>; the ex-<code>PropertyCollector</code> statics merged in at review 2026-09-16 — no dedicated service without proven need). All deductions are private methods: <code>resolvePhpType()</code> (named type, first non-null member of a union), <code>resolveReflectionProperty()</code> (embedded navigation <code>identity.firstname</code>), <code>resolveFormatter()</code> (config override above the resolver), <code>resolveSlug()</code> (<code>EntityShortnameException</code> on collisions), <code>resolveActions()</code>, <code>resolveActionTemplates()</code>. One projection pass: <code>buildAssociations()</code> + <code>buildFields()</code> emit the frozen read-models; the raw sources are consumed and discarded — never stored.</li>
        <li><strong>Computed read-models (all <code>readonly</code>)</strong>: <code>PropertyMetadata</code> is the abstract base (name, fqcn, formatter, formatterOptions, templates, entitySlug) with <code>isField()</code>/<code>isAssociation()</code> derived by <code>instanceof</code>. <code>FieldMetadata</code> is a <strong>pure marker</strong> — no field-specific state; the column facts of the original plan (length, precision, scale, <code>enumType</code>, <code>unsigned</code>, <code>fixed</code>, nullable, id/version/generated) had <strong>zero render consumers</strong> and were dropped in review. <code>AssociationMetadata</code> carries the resolved formatter and the target entity's <code>identifier</code> column names (read by <code>UrlBuilderExtension::getUrl()</code>); <code>identifier</code> was first hoisted into the base then moved back to the association only (review 2026-09-17 — a field has no identifier, the base must not carry an empty <code>[]</code> for half its cases). Cardinality is <strong>not stored</strong>: <code>isCollectionValuedAssociation()</code> crosses the renderer seam as a transient <code>bool $isToMany</code>; the <code>Cardinality</code> enum and the conflict flag were removed (no runtime consumers).</li>
        <li><strong>FormatterResolver</strong>: deterministic chain — the PHP type wins when it exists, except the datetime family refined by the Doctrine type (date/time/datetime; the mutable/immutable variant is dropped); <code>enumType</code> → <code>EnumFormatter</code>; <code>UnitEnum</code> and <code>__toString</code> classes handled; <code>NotAvailableFormatter</code> is the universal fallback — the build never refuses a pair. Host config (<code>entityPropertyFormatter</code>/<code>entityPropertyFormatterOptions</code>) wins above the facts.</li>
        <li><strong>Template resolution — two seams replace <code>TemplateResolver</code>/<code>TemplateRegistry</code>/<code>TemplateRegistryExtension</code> (<code>k_template</code>)/<code>karross.templates</code>, all deleted</strong>: <code>Metadata\Collect\PropertyTemplateResolverInterface</code> (per-property cells) and <code>Metadata\Collect\EntityTemplateResolverInterface</code> (entity pages) hand the renderer layer only raw facts (PHP type, <code>FieldMapping</code>, cardinality, slug, embedded-field presence); the renderer-specific vocabulary lives in the implementing layer and is <strong>never stored on the read-models</strong>. The Twig implementations resolve at build time the maps carried by the read-models — <code>PropertyMetadata::$templates</code> (action → resolved template) and <code>EntityMetadata::$templates</code> (action → role → resolved: index → items/no_items → item). The property vocabulary is a <strong>fixed contract table</strong> (<code>Twig\PropertyTemplateResolver::DOCTRINE_HIERARCHIE</code>, decided upstream — date/time → the datetime umbrella, enum → enum/string, guid/ascii_string → string, blob → blob/text/string, integer/smallint/bigint → int/number…); candidate patterns root at <code>@Karross/{action}/…</code> derived from the action value. <code>EntityTemplateResolver</code> implements the page hierarchy (<code>{action}_entity_{slug}</code>, the <code>_embedded</code> variant, generics).</li>
        <li><strong>Rendering reads the read-models</strong>: <code>index.html.twig</code>/<code>items.html.twig</code> render the entity page templates from <code>EntityMetadata</code>; <code>item.html.twig</code> renders each cell through <code>property->templates[action]</code>; <code>TwigResponder</code> resolves via <code>EntityMetadataRegistry::getBySlug($slug)</code> (added) — fixing the pre-existing bug where <code>items.html.twig</code> and <code>items_embedded.html.twig</code> hard-coded <code>item.html.twig</code> and made the <code>item_entity_{slug}</code> tier inert for plain and embedded entities alike.</li>
        <li><strong>Enum and stray surface</strong>: <code>PropertyType</code> and <code>PropertyTypeInfo</code> deleted; <code>KarrossExtension::prepend()</code> removed (the Twig override path is now wired through the bundle paths, host override always wins).</li>
        <li><strong>Fill rules, as built</strong>: the PHP declaration wins when it exists; without a PHP type, Doctrine decides; unions take the first non-null member; datetime is the single Doctrine-refined family; <strong>conflicts are not flagged — PHP wins</strong> (the "conflict flag" proposal was dropped for lack of consumers); the host config overrides everything; the PHPDoc is never used.</li>
        <li><strong>Tests &amp; gates</strong>: <code>MetadataCollectTest</code> (merge rules, resolved property/entity templates, <code>getBySlug</code>, unhandled → <code>NotAvailableFormatter</code>), a <code>TemplateOverride</code> kernel with fixtures (<code>field_type_datetime</code>, <code>items_entity_article</code>) + the e2e <code>IndexTemplateOverrideTest</code> (host override wins per type), the <code>doctrine_unhandled</code> config, <code>CacheWiringTest</code> adapted. <strong>Realized.</strong>: <code>all-check</code> green — 40 tests / 238 assertions, PHPStan baseline 92, ast-metrics 10. Docs: <code>customization/templates.md</code> gained the cell vocabulary table and the "For pages" section.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>7. Formatter resolvers — chain of responsibility with a Boolean link <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>FormatterResolver::resolve()</code> centralizes the formatter decision in a single <code>match</code> (PHP type wins, the datetime family is refined by Doctrine, enum, <code>__toString</code>, Doctrine fallback). Boolean is one case among others: <code>phpType 'bool'</code> → <code>TrueFalseFormatter</code>, <code>doctrineType boolean</code> → <code>TrueFalseFormatter</code>. Every new type case fattens the <code>match</code>.</td></tr>
      <tr><th>Expected.</th><td>The type decision is broken down into responsibility links: each type family gets its own resolver under <code>Formatters\Resolvers</code>, exposing <code>accept()</code> (is this case mine?) and <code>resolve()</code> (the formatter, with no failure risk). <code>FormatterResolver</code> queries the registered links (<code>tagged_iterator('karross.formatter.resolver')</code>, same mechanism as the formatters); the first accepting link wins, otherwise the current logic applies, with <code>NotAvailableFormatter</code> as the last resort. Rendering behaviour stays unchanged.<br>
        The first link, <code>BooleanFormatterResolver</code>, covers every way of declaring a boolean (PHP <code>bool</code>, nullable or not; Doctrine <code>boolean</code>) and resolves to <code>TrueFalseFormatter</code>. It is the template for the following families. Configuration override (<code>entityPropertyFormatter</code>) remains priority.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed (facts <code>phpType</code>/<code>fieldMapping</code> flowing through the build).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li>Create <code>src/Formatters/Resolvers/FormatterResolverInterface</code>: <code>accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool</code> and <code>resolve(...): class-string&lt;ValueFormatterInterface&gt;</code>.</li>
        <li>Create the first link <code>src/Formatters/Resolvers/BooleanFormatterResolver</code>: <code>accept()</code> = <code>'bool' === phpType</code> or <code>doctrineType boolean</code>; <code>resolve()</code> → <code>TrueFalseFormatter::class</code>.</li>
        <li>Refactor <code>FormatterResolver</code>: new constructor parameter <code>iterable $resolvers</code> (tagged links); <code>resolve()</code> iterates — first <code>accept()</code> win → <code>resolve()</code>, otherwise the current private logic minus the two boolean cases, <code>NotAvailableFormatter</code> as the last resort. <code>get()</code> untouched.</li>
        <li>Wire the DI in <code>src/Config/services.php</code>: tag <code>karross.formatter.resolver</code> on the link and <code>arg('$resolvers', tagged_iterator('karross.formatter.resolver'))</code> on the resolver. Formatters are declared with explicit <code>set()</code> (no folder <code>load()</code>) → <code>BooleanFormatterResolver</code> must also be <code>set()</code> for the tag to apply.</li>
        <li>Test <strong>functionally only</strong> (no unit tests): <code>MetadataCollectTest</code> guards the real formatter map (non-nullable bool + nullable <code>premium</code> → <code>TrueFalseFormatter</code>, residual for the other types); E2E covers the three states of the nullable <code>premium</code>: <code>true</code>/<code>false</code>/<code>null</code> → <code>true</code>/<code>false</code>/empty by default, <code>Oui</code>/<code>Non</code>/empty via the configured <code>YesNoFormatter</code>.</li>
        <li><strong>Realized.</strong> <code>all-check</code> green — 43 tests / 282 assertions, PHPStan 84, ast-metrics 11. Functional-only coverage (review guidance): <code>MetadataCollectTest</code> keeps the real map; E2E exercises the <code>?bool</code> <code>premium</code> in its three states, out of the box and via the configured <code>YesNoFormatter</code>, while <code>published</code> keeps the default <code>true</code>/<code>false</code>. Pitfall fixed: a tagged link needs an explicit <code>set()</code>. The <code>null</code> contract (formatters pass it through) is fixed; the Twig-side rendering mechanism stays a separate rework lead.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>8. Integer — integer formatting rule <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>FormatterResolver</code> fallback matches <code>'float'</code> PHP (line 71) and Doctrine <code>decimal</code>/<code>float</code> (line 112) → <code>IntlNumberFormatter</code>. The <code>'int'</code> PHP type falls through to the default branch → <code>resolvePhpClass()</code> → <code>NotAvailableFormatter</code> (no <code>__toString</code> on integers). The boolean match (<code>'bool'</code>) was removed by ticket 7, but <code>'int'</code> still gets no number formatting.</td></tr>
      <tr><th>Expected.</th><td>An <code>IntegerFormatterResolver</code> link placed after <code>BooleanFormatterResolver</code> in the chain: accepts <code>'int'</code> PHP type (including nullable <code>?int</code>) and any non-boolean, non-enum Doctrine integer type (<code>smallint</code>, <code>integer</code>, <code>bigint</code>); resolves to <code>IntlNumberFormatter::class</code>. The fallback <code>'float'</code> match remains for the decimal family. <strong>Zero behaviour change</strong> for existing code — integers that were silently formatted via the fallback now go through an explicit link.</td></tr>
      <tr><th>Prerequisites.</th><td>Formatter resolvers — chain of responsibility with a Boolean link.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li>Create <code>src/Formatters/Resolvers/IntegerFormatterResolver</code> (tag <code>karross.formatter.resolver</code>). <code>accept()</code>: refuse <code>enumType</code> non-null; accept <code>'int'</code> PHP (nullable <code>?int</code> is a value modifier, not a family change); refuse <code>'bool'</code>; refuse <code>'string'</code>/<code>'float'</code>; accept null/no PHP type + Doctrine <code>smallint</code>/<code>integer</code>/<code>bigint</code>. <code>resolve()</code>: return <code>IntlNumberFormatter::class</code>.</li>
        <li>Create <code>tests/Integration/Formatters/IntegerFormatterResolverTest</code>: accepted cases (<code>int</code> pure, <code>?int</code>, <code>int</code> on integer, <code>string</code> on integer, no type on integer); refused cases (<code>bool</code>, <code>string</code>, <code>float</code>, <code>enumType</code>, <code>string</code> on boolean, no type on boolean).</li>
        <li>Register the service in <code>src/Config/services.php</code>: <code>-&gt;set(IntegerFormatterResolver::class)</code>.</li>
        <li>Update <code>FormatterResolverTest::chainCases()</code>: add integer cases; remove the <code>'int'</code> case from the fallback test.</li>
        <li>Verify: <code>make all-fix</code> then <code>make all-check</code>. No new errors, no baseline regeneration.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>9. Float — float formatting rule &amp; scale <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>IntlNumberFormatter</code> serves decimals through the <strong>residual fallback</strong> of <code>FormatterResolver</code>: the PHP match <code>'float'</code> (line 71) and the Doctrine match <code>decimal</code>/<code>float</code> (line 112). The column <code>scale</code> is never exploited at render time.</td></tr>
      <tr><th>Expected.</th><td>Standalone <code>FloatFormatterResolver</code> link placed after Integer in the chain, with the same exclusivity guards as Integer but for the fractional family. <code>resolve()</code> returns <code>IntlNumberFormatter::class</code>. The <code>scale</code> carried by the Doctrine <code>FieldMapping</code> is forwarded to the formatter via <code>formatterOptions</code>: the number of displayed decimals follows the column precision, not an arbitrary default. A <code>price</code> declared <code>scale: 2</code> displays <code>19,9</code>; an <code>amount</code> declared <code>scale: 0</code> displays <code>42</code>. Host config can override with <code>formatter_options</code> (<code>minimum_fraction_digits</code>, <code>maximum_fraction_digits</code>).</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed + Formatter resolvers — chain of responsibility with a Boolean link.</td></tr>
      <tr><th>Plan.</th><td><strong>1. Create <code>FloatFormatterResolver</code></strong> (<code>src/Formatters/Resolvers/FloatFormatterResolver.php</code>, tag <code>karross.formatter.resolver</code>). <code>accept()</code>: refuse non-null <code>enumType</code>; accept <code>'float'</code> PHP pure (including <code>?float</code> — nullable is a value modifier, not a family change); refuse any union containing <code>float</code>; refuse non-<code>float</code> PHP other than <code>null</code>/<code>string</code>; accept <code>null</code>/<code>string</code> PHP + Doctrine <code>decimal</code>/<code>float</code>. <code>resolve()</code>: return <code>IntlNumberFormatter::class</code>. <strong>2. Wire scale in <code>ComputedMetadataBuilder::buildFields()</code></strong>: when the resolved formatter is <code>IntlNumberFormatter</code> and host config provides no <code>formatter_options</code>, add <code>'maximum_fraction_digits' => $fieldMapping->scale</code>. Host config remains priority. <strong>3. Support <code>minimum_fraction_digits</code></strong> via <code>formatter_options</code>: add <code>minimumFractionDigits</code> to <code>FormattingContext</code>, forward it in <code>IntlNumberFormatter</code>, extract it in <code>PropertyAccessorExtension</code>. Add the node in <code>Configuration.php</code>. <strong>4. Update <code>FormatterResolverTest::chainCases()</code></strong>: add float cases, clean fallback. <strong>5. Seed data</strong>: enrich to 5 articles covering all formatter families (boolean true/false/null, integer 0–158, decimal with scale 2, datetime/date, enum DRAFT/PUBLISHED/ARCHIVED, tags empty/populated). <strong>6. Documentation</strong>: update <code>docs/src/customization/formatting-values.md</code> with <code>formatter_options</code> table and trailing-zeros example.</td></tr>
      <tr><th>Realized.</th><td><code>all-check</code> green — 72 tests / 392 assertions. Chain cleaned: <code>'float'</code> and <code>decimal</code>/<code>float</code> removed from <code>FormatterResolver</code> fallback. <code>FormatterResolverTest::chainCases()</code> updated. <code>NumberFormatterConfigTest</code> validates scale auto-wire, config override, and <code>minimum_fraction_digits</code>. <code>MetadataCollectTest</code> updated (<code>price</code> → <code>IntlNumberFormatter</code>). Unit tests removed per project rule. Validation of <code>formatter_options</code> is handled by Symfony <code>Configuration</code> (no custom guard needed). Baselines: PHPStan 83 (1 entry updated for docblock change), ast-metrics 11 (same violations, metrics shifted).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>String — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>string</code> → <code>PropertyType::String</code> → <code>StringFormatter</code> ; length, <code>fixed</code>, JSON-ness jetés ; une colonne Doctrine <code>json</code> typée <code>string</code> en PHP résout String (priorité PHP d'abord — fausse).</td></tr>
      <tr><th>Expected.</th><td>Traits <code>length</code>, <code>fixed</code>, <code>json</code>-ness portés par le <code>PropertyMetadata</code> ; Doctrine <code>json/jsonb/json_object</code> → distinction <strong>Json</strong> (Doctrine plus informatif que PHP) ; <code>guid</code> ; futur widget text <code>maxlength</code>.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>La priorité PHP qui cache un <code>json</code> derrière <code>string</code> devient une règle explicite : Doctrine gagne quand il est plus informatif (json, enum, decimal…). Troncature et rendu monospace atterrissent avec ce maillon.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>DateTime — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>DateTimeInterface</code> → <code>Date</code>/<code>Time</code>/<code>DateTime</code> via le raffinement Doctrine ; gestion du timezone partielle.</td></tr>
      <tr><th>Expected.</th><td>Sous-type raffiné par Doctrine (<code>date</code>/<code>time</code>/<code>datetime</code>/<code>datetimetz</code>) porté par le <code>PropertyMetadata</code> ; timezone appliqué au rendu ; cas nullable et non typé ; formatteurs existants réutilisés ; futurs pickers date/time/datetime.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>PHP ne peut pas distinguer date/time/datetime — Doctrine raffinne sans conflit. Les maillons Date/Time/DateTime sont minces : les formatteurs existants suffisent pour l'affichage MVP-1.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Enum — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Classe enum PHP → <code>PropertyType::Enum</code> → <code>EnumFormatter</code> ; cases et labels résolus via la traduction de la valeur, sans <code>enumType</code> Doctrine.</td></tr>
      <tr><th>Expected.</th><td>Le trait <code>enumType</code> Doctrine fournit la classe d'enum exacte → <code>::cases()</code> pour les labels et les options ; non typé avec enum type Doctrine géré ; futur widget select alimenté par les mêmes cases.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Seul Doctrine fournit les cases — PHP seul ne peut pas les inventer. Le maillon Enum lit le <code>enumType</code> porté par le <code>PropertyMetadata</code> ; affichage et futur widget partagent la même liste de cases, de la même source.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Stringable — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Objets avec <code>__toString</code> → <code>PropertyType::String</code> → <code>StringFormatter</code> ; objets génériques sans <code>__toString</code> → <code>Unknown</code>.</td></tr>
      <tr><th>Expected.</th><td>La branche Stringable rendue explicite (interface <code>Stringable</code> ou <code>__toString</code>) ; objets non stringables → <code>NotAvailable</code> sauf si un type Doctrine les mappe ; futur widget text read-only.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Le cas « objet stringable » est une branche nommée du collecteur (fait <code>phpType</code> → <code>Stringable</code>) — le maillon choisit le formatteur de string, sûr pour tout objet <code>__toString</code>.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>DateInterval / BcMath\Number — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Non mappés — <code>dateinterval</code> et <code>number</code> tombent dans <code>Unknown</code> → <code>NotAvailableFormatter</code>.</td></tr>
      <tr><th>Expected.</th><td>Doctrine <code>dateinterval</code> (PHP <code>\DateInterval</code>) → affichage durée ; <code>number</code> (PHP 8.5 <code>\BcMath\Number</code>) → rendu BcMath ; nouveaux formatteurs d'affichage MVP-1.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Deux types PHP 8.5 natifs portés par des types Doctrine dédiés ; leurs maillons enregistrent les formatteurs durée et nombre dans la chaîne.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Array / structured — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>PHP <code>array</code> → <code>PropertyType::Array</code> → <code>NotAvailableFormatter</code> ; Doctrine <code>simple_array</code> et <code>json</code> s'effondrent en Unknown ou String.</td></tr>
      <tr><th>Expected.</th><td>Doctrine <code>simple_array</code> → liste de chips ; <code>json/jsonb</code> → pretty-printer JSON ; type d'item deviné via Doctrine quand possible ; futurs widgets renvoyés en MVP-2.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Les types structurés (non scalaires) n'ont pas de conteneur PHP contraignant fort — Doctrine porte la distinction. Widgets MVP-2.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Association to-one — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>isAssociation</code> → <code>PropertyType::Single</code> ; le template item rend un lien vers l'action show.</td></tr>
      <tr><th>Expected.</th><td>Associations to-one (<code>ManyToOne</code>/<code>OneToOne</code>) résolues depuis la cardinalité portée par le <code>PropertyMetadata</code> ; rendu en lien ; futur widget select renvoyé en MVP-2.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Structure, pas nature : Doctrine <code>isSingleValuedAssociation()</code> répond déjà ; la case <code>Single</code> disparaît du vocabulaire de type.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Association to-many — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>isAssociation</code> + indice collection → <code>PropertyType::Collection</code> ; le template item rend une liste de liens.</td></tr>
      <tr><th>Expected.</th><td>Associations to-many (<code>OneToMany</code>/<code>ManyToMany</code>) résolues depuis la cardinalité portée par le <code>PropertyMetadata</code> ; rendu liste-de-liens ; futur multi-select renvoyé en MVP-2.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Idem Association to-one — la distinction un-versus-plusieurs vient de Doctrine, pas d'un enum de type.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Untyped properties &amp; unions — transversal <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Chaîne de fallback dans <code>PropertyTypeDetector</code> : PHP → Doctrine (le PHPDoc est écarté — source non fiable). Les types union prennent le premier membre non-null.</td></tr>
      <tr><th>Expected.</th><td>Propriété non typée traitée comme chemin de premier ordre (Doctrine seul, puis NotAvailable + override config) ; unions → premier membre non-null ; nullable intégré comme modificateur dans le tableau de cas de chaque autre ticket.</td></tr>
      <tr><th>Prerequisites.</th><td>Tous les tickets de cas ci-dessus (pose les règles transversales par-dessus chaque maillon).</td></tr>
      <tr><th>Analysis.</th><td>Pas un type mais des modificateurs transversaux — la colonne « cas » de chaque ticket les liste déjà ; ce ticket consolide l'ordre de fallback et le nullable dans le collecteur.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>WidgetResolver — form type mapping (chain of responsibility) <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Les faits portés par <code>PropertyMetadata</code> (socle Collect &amp; Computed) alimentent l'affichage/le formatage ; rien ne les mappe vers des types de form Symfony.</td></tr>
      <tr><th>Expected.</th><td>Un <code>WidgetResolver</code> en miroir de la chaîne formatter : chaque maillon lit les faits portés par <code>PropertyMetadata</code> → type de form + options (scale → step, length → maxlength, enum → select des cases, not-null → required) ; override config gagne ; mince pour les scalaires MVP-1, widgets riches en MVP-2.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed (le socle) + au minimum les tickets de cas scalaires dont la lane CRUD a besoin (Boolean, Integer, Float, String).</td></tr>
      <tr><th>Analysis.</th><td>Contrepartie <strong>runtime-impacting</strong> de la chaîne formatter : les mêmes faits pilotent les types de form à l'écriture et leurs contraintes. Même pattern chaîne de responsabilité, vocabulaire propre. Décision REC à trancher : <code>symfony/form</code> non installé — service fourni par l'hôte ou dépendance du bundle. Enabler des tickets CRUD ci-dessous.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Generic create/update forms <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Index/show + home actions only; <code>Action</code> enum holds create/update/delete as TODO.</td></tr>
      <tr><th>Expected.</th><td>Create and update with CSRF, validation, redirect after post, flash, flush/cascade; simple scalars first, embedded/relations deferred to MVP 2.</td></tr>
      <tr><th>Prerequisites.</th><td>WidgetResolver — form type mapping (chain of responsibility).</td></tr>
      <tr><th>Analysis.</th><td>The Action enum keeps <code>CREATE</code>/<code>UPDATE</code>/forms commented as TODO and the classes exist as <strong>empty stubs</strong> (<code>Create</code>, <code>CreateForm</code>, <code>Update</code>, <code>EditForm</code> — all <code>__invoke(): void</code>); routes only cover index/show + home (<code>RouteLoader</code> via <code>RouteGenerator</code>) and <code>Action::httpMethods()</code> is GET-only. Build on the mapping ticket: create/update GET form + POST submit, CSRF, validation, redirect-after-POST, flash, flush/cascade; simple scalars first, embedded/relations deferred to MVP-2. The <code>symfony/form</code> dependency is decided in the mapping ticket.</td></tr>
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
      <tr><th>Analysis.</th><td><code>Delete</code> exists as an empty stub, the enum case is commented, no POST route exists and <code>httpMethods()</code> is GET-only. Build on the forms pipeline (7): POST delete with CSRF, cascade-safe, redirect + flash.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Minimal design decision <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Zero CSS, zero tokens, zero assets; the templates (<code>templates/base.html.twig</code>, <code>home.html.twig</code>, <code>index/*</code>) are plain unstyled markup.</td></tr>
      <tr><th>Expected.</th><td>CSS strategy (design tokens + <code>k-*</code> classes), zero JS until the forms, dark mode?, fonts, asset location, host replacability.</td></tr>
      <tr><th>Prerequisites.</th><td>Browser CSS compatibility gate in CI — guards before any CSS.</td></tr>
      <tr><th>Analysis.</th><td>Decide with REC: vanilla CSS + custom-property tokens over the existing <code>k-*</code> classes (cap from the conversations), zero JS until the forms, dark mode via <code>prefers-color-scheme</code>?, system font stack, asset location, host replaceability (full CSS replacement vs token override), WCAG target (AA). The CI gates of 4 land before any CSS is written. Delivery stays bundle-scoped: ship the CSS ready-to-use (minified) at the canonical path fixed in 4, version it through the Symfony Asset component (<code>asset()</code> in the Twig templates inherits the host's version strategy — no build chain, no Encore); compression and cache headers are the host's responsibility (documented in 12, not built here).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Minimal theme — index/show + responsive <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No CSS anywhere; the index/show/home templates are plain markup.</td></tr>
      <tr><th>Expected.</th><td>Token system, single CSS file, system fonts, validated by the Browser CSS compatibility gate in CI + the responsive budget.</td></tr>
      <tr><th>Prerequisites.</th><td>Minimal design decision.</td></tr>
      <tr><th>Analysis.</th><td>Style the existing index/show markup with the tokens of 9: one CSS file, system fonts, responsive behavior validated by the gate and the responsive budget of 4. Nothing exists today — greenfield styling. The file ships minified as decided in 9, referenced via <code>asset()</code>.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>CRUD forms styling <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No styling; the forms are not yet implemented (7) and the theme is not built (10).</td></tr>
      <tr><th>Expected.</th><td>Thin styling layer over the stable markup of Generic create/update forms and the theme of Minimal theme — index/show + responsive.</td></tr>
      <tr><th>Prerequisites.</th><td>Generic create/update forms + Minimal theme — index/show + responsive.</td></tr>
      <tr><th>Analysis.</th><td>Adds a thin layer on top of the stable form markup (7) and the theme (10): field layout, buttons, focus-visible, error states, WCAG-AA surfaces; no new CSS system, only components. Landed once both prerequisites support the markup.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Reference documentation <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The online docs cover usage (how-to) but offer no systematic reference of the whole configuration surface.</td></tr>
      <tr><th>Expected.</th><td>A reference section in the online docs (à la Sonata) listing every MVP-1 configuration option — routes, formatters, type overrides, template override points — curated or generated, scope to decide.</td></tr>
      <tr><th>Analysis.</th><td>The docs cover usage (presentation/, customization/) but have no systematic reference of the whole configuration surface. Build a reference section (à la Sonata) listing every MVP-1 option — routes, formatters, type overrides, template override points. Include a Delivery strategy section (bundle-scoped): canonical CSS asset path, Asset-component versioning, and the host-side note that compression / cache headers are the app's responsibility. Decide with REC: handwritten vs generated from the code/config; compiled progressively, complete once the MVP-1 config surface is stable.</td></tr>
    </tbody>
  </table>
</details>