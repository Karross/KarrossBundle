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
        <li><strong>Collector</strong>: <code>PropertyTypeDetector</code> becomes <code>Metadata\Collect\EntityMetadataBuilder</code> (the ex-<code>PropertyCollector</code> statics merged in at review 2026-09-16 — no dedicated service without proven need). All deductions are private methods: <code>resolvePhpType()</code> (named type, first non-null member of a union), <code>resolveReflectionProperty()</code> (embedded navigation <code>identity.firstname</code>), <code>resolveFormatter()</code> (config override above the resolver), <code>resolveSlug()</code> (<code>EntityShortnameException</code> on collisions), <code>resolveActions()</code>, <code>resolveActionTemplates()</code>. One projection pass: <code>buildAssociations()</code> + <code>buildFields()</code> emit the frozen read-models; the raw sources are consumed and discarded — never stored.</li>
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
      <tr><th>Plan.</th><td><strong>1. Create <code>FloatFormatterResolver</code></strong> (<code>src/Formatters/Resolvers/FloatFormatterResolver.php</code>, tag <code>karross.formatter.resolver</code>). <code>accept()</code>: refuse non-null <code>enumType</code>; accept <code>'float'</code> PHP pure (including <code>?float</code> — nullable is a value modifier, not a family change); refuse any union containing <code>float</code>; refuse non-<code>float</code> PHP other than <code>null</code>/<code>string</code>; accept <code>null</code>/<code>string</code> PHP + Doctrine <code>decimal</code>/<code>float</code>. <code>resolve()</code>: return <code>IntlNumberFormatter::class</code>. <strong>2. Wire scale in <code>EntityMetadataBuilder::buildFields()</code></strong>: when the resolved formatter is <code>IntlNumberFormatter</code> and host config provides no <code>formatter_options</code>, add <code>'maximum_fraction_digits' => $fieldMapping->scale</code>. Host config remains priority. <strong>3. Support <code>minimum_fraction_digits</code></strong> via <code>formatter_options</code>: add <code>minimumFractionDigits</code> to <code>FormattingContext</code>, forward it in <code>IntlNumberFormatter</code>, extract it in <code>PropertyAccessorExtension</code>. Add the node in <code>Configuration.php</code>. <strong>4. Update <code>FormatterResolverTest::chainCases()</code></strong>: add float cases, clean fallback. <strong>5. Seed data</strong>: enrich to 5 articles covering all formatter families (boolean true/false/null, integer 0–158, decimal with scale 2, datetime/date, enum DRAFT/PUBLISHED/ARCHIVED, tags empty/populated). <strong>6. Documentation</strong>: update <code>docs/src/customization/formatting-values.md</code> with <code>formatter_options</code> table and trailing-zeros example.</td></tr>
      <tr><th>Realized.</th><td><code>all-check</code> green — 72 tests / 392 assertions. Chain cleaned: <code>'float'</code> and <code>decimal</code>/<code>float</code> removed from <code>FormatterResolver</code> fallback. <code>FormatterResolverTest::chainCases()</code> updated. <code>NumberFormatterConfigTest</code> validates scale auto-wire, config override, and <code>minimum_fraction_digits</code>. <code>MetadataCollectTest</code> updated (<code>price</code> → <code>IntlNumberFormatter</code>). Unit tests removed per project rule. Validation of <code>formatter_options</code> is handled by Symfony <code>Configuration</code> (no custom guard needed). Baselines: PHPStan 83 (1 entry updated for docblock change), ast-metrics 11 (same violations, metrics shifted).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>10. String — string formatting rule &amp; ucfirst option <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>StringFormatter</code> is the simplest formatter: <code>null</code> → <code>null</code>, otherwise <code>(string) $value</code>. No constructor dependency, no consumption of <code>FormattingContext</code>. String resolution lives in three scattered places inside <code>FormatterResolver::resolveFallback()</code>: <code>phpType='string'</code> (line 75), class with <code>__toString()</code> (line 99), Doctrine <code>string</code>/<code>ascii_string</code>/<code>guid</code>/<code>text</code> (line 115). Boolean, Integer and Float each have their own dedicated resolver; the string family is the last scalar type remaining in the fallback. The <code>ucfirst</code> option exists in <code>FormattingContext</code> and is consumed by <code>ValueTranslator</code> for booleans and enums, but <code>StringFormatter</code> never reads it — <code>ucfirst</code> has no effect on strings.</td></tr>
      <tr><th>Expected.</th><td>An explicit <code>StringFormatterResolver</code> that extracts string resolution from the fallback, as Boolean, Integer and Float do for their families. <code>StringFormatter</code> consumes <code>FormattingContext</code> to apply <code>ucfirst</code> when the option is enabled. By default, no added behaviour — the formatter simply casts and passes <code>null</code> through, as before. No <code>max_length</code> in the formatter (truncation is a template/presentation concern, not a value transformation).</td></tr>
      <tr><th>Prerequisites.</th><td>Formatter resolvers — chain of responsibility (Boolean, Integer, Float).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Create <code>StringFormatterResolver</code></strong> (<code>src/Formatters/Resolvers/StringFormatterResolver.php</code>, tag <code>karross.formatter.resolver</code>). <code>accept()</code>: reject non-null <code>enumType</code>; accept <code>'string'</code> PHP pure (including <code>?string</code> — nullable is a value modifier, not a family change); reject any union containing <code>string</code>; reject any PHP type other than <code>null</code>/<code>string</code>; accept <code>null</code>/<code>string</code> PHP + Doctrine <code>string</code>/<code>text</code>/<code>ascii_string</code>/<code>guid</code>. <code>resolve()</code>: return <code>StringFormatter::class</code>. The <code>__toString()</code> case stays in <code>FormatterResolver::resolvePhpClass()</code>, not in the resolver.</li>
        <li><strong>Enhance <code>StringFormatter</code></strong>: add a constructor dependency <code>FormattingContext $context</code>. When <code>$context->ucfirst</code> is <code>true</code>, apply <code>mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1)</code> after the cast. By default (<code>ucfirst = false</code>), no behaviour change.</li>
        <li><strong>Clean up <code>FormatterResolver::resolveFallback()</code></strong>: remove the <code>'string' =&gt; StringFormatter::class</code> case (line 75) and the <code>Types::STRING</code>, <code>Types::ASCII_STRING</code>, <code>Types::GUID</code>, <code>Types::TEXT</code> entries from <code>resolveDoctrineType()</code> (line 115). The <code>__toString()</code> case stays in <code>resolvePhpClass()</code>.</li>
        <li><strong>Register the service</strong> in <code>src/Config/services.php</code>: <code>-&gt;set(StringFormatterResolver::class)</code> after <code>FloatFormatterResolver</code>.</li>
        <li><strong>Update unit tests</strong>: add <code>StringFormatterResolver</code> to the chain in <code>FormatterResolverTest::setUp()</code>. Add string cases to <code>chainCases()</code>: <code>phpType='string'</code> × 5 doctrineTypes (<code>string</code>, <code>text</code>, <code>ascii_string</code>, <code>guid</code>, no mapping) + <code>phpType=null</code> × 4 doctrineTypes (<code>string</code>, <code>text</code>, <code>ascii_string</code>, <code>guid</code>). Add rejection cases: non-null <code>enumType</code> with <code>phpType='string'</code>. Clean up <code>'string'</code> cases from the fallback test.</li>
      </ol></td></tr>
      <tr><th>Realized.</th><td><code>all-check</code> green — 130 tests / 450 assertions. Chain cleaned: <code>'string'</code> and <code>string</code>/<code>ascii_string</code>/<code>guid</code>/<code>text</code> removed from <code>FormatterResolver</code> fallback. <code>StringFormatterResolver</code> registered in the chain after Float. <code>StringFormatter</code> consumes <code>FormattingContext</code> for <code>ucfirst</code> (opt-in, <code>mb_strtoupper(mb_substr())</code>). Unit tests: 10 string cases added to <code>chainCases()</code> (phpType × doctrineType + null phpType) + 2 enumType rejection cases. Ast-metrics baseline updated (FormatterResolver cyclomatic complexity 28→26). The <code>(string)</code> cast remains as a defensive safety net; <code>__toString()</code> stays in <code>resolvePhpClass()</code>.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--blue">
  <summary>11. Named format registry <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The DateTime formatters (<code>DateFormatter</code>, <code>TimeFormatter</code>, <code>DateTimeFormatter</code>) use fixed ICU lengths: medium date, short time, and medium date with short time for datetime. These values are hardcoded in each formatter. <code>FormattingContext</code> already carries <code>dateFormat</code>, <code>timeFormat</code> and <code>dateTimeFormat</code>, but the render-time context builder never injects them: only <code>currency</code>, <code>ucfirst</code> and <code>maximum_fraction_digits</code> are read from <code>formatterOptions</code>. The <code>datetime_format</code> key does not exist under the <code>formatter_options</code> node of <code>Configuration</code>, so the config rejects it. There is no named-format mechanism: a custom format requires overriding the template or replacing the whole formatter through config.</td></tr>
      <tr><th>Expected.</th><td>The host declares named formats under <code>karross.datetime_formats</code>. A property references one through <code>formatter_options.datetime_format</code>. The bundle ships 12 built-in formats (<code>k_short_date</code> to <code>k_full_datetime</code>) that map to ICU lengths.<br><br>
      Every format decision happens at build time. The property metadata embeds the outcome: either a pattern table (locale to pattern) or ICU length identifiers. Rendering only reads that data and picks the entry matching the request locale — no named-format service runs at render time.<br><br>
      Example — custom format:
      <pre><code>karross:
  datetime_formats:
    my_custom_datetime_format:
        fr: "d MMMM yyyy 'à' HH:mm"
        en: "MMMM d, yyyy 'at' HH:mm"
        default: "yyyy-MM-dd HH:mm"
  entities:
    App\Entity\Article:
      properties:
        createdAt:
          formatter_options:
            datetime_format: my_custom_datetime_format</code></pre>
      Result: <code>createdAt</code> shows <code>14 septembre 2026 à 15:30</code> in French, <code>September 14, 2026 at 15:30</code> in English, and <code>2026-09-14 15:30</code> for a locale with no entry (falls back to <code>default</code>).<br><br>
      Example — override a built-in format:
      <pre><code>karross:
  datetime_formats:
    k_medium_date:
      fr: 'd MMMM yyyy'</code></pre>
      Every date property with no explicit <code>datetime_format</code> option then uses <code>k_medium_date</code>, so in French it shows <code>14 septembre 2026</code>. Other locales keep the ICU medium length.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Config node <code>datetime_formats</code></strong> (<code>src/DependencyInjection/Configuration.php</code>): key = format name, value = a single pattern <strong>or</strong> a <code>locale =&gt; pattern</code> map. Both shapes normalize to the same internal format. Accessor <code>datetime_formats()</code> on <code>KarrossConfig</code> plus docblock update.</li>
        <li><strong>Static table of the 12 built-in formats</strong> (literal table next to the registry, no name deduction): each <code>k_{short|medium|long|full}_{date|time|datetime}</code> entry maps to its ICU equivalent — one length for <code>date</code> and <code>time</code>, a date length plus a time length for <code>datetime</code>; <code>k_medium_datetime</code> maps to medium date and short time, for strict parity with <code>DateTimeFormatter</code>'s current default.</li>
        <li><strong><code>NamedDatetimeFormats</code>, build-time only</strong> (<code>src/Formatters/Options/NamedDatetimeFormats.php</code>): returns the config for a format name — host definition first, then the built-in, else unknown name. No DI tag, no injection into any render service.</li>
        <li><strong><code>datetime_format</code> key on the property</strong>: <code>scalarNode('datetime_format')</code> under <code>formatter_options</code> in <code>Configuration.php</code>. Without this node the config rejects the key.</li>
        <li><strong>Write into the metadata</strong>, in <code>FieldMetadataBuilder</code> and <code>AssociationMetadataBuilder</code>: for each property whose resolved formatter is <code>DateFormatter</code>, <code>TimeFormatter</code> or <code>DateTimeFormatter</code>:
          <ul>
            <li>name to resolve = <code>formatterOptions['datetime_format']</code> when present, else the formatter's implicit name (<code>k_medium_date</code> for date, <code>k_short_time</code> for time, <code>k_medium_datetime</code> for datetime);</li>
            <li>unknown explicit name → <code>\InvalidArgumentException</code> at build, message naming the name and the property;</li>
            <li>resolve through the registry, then replace the config key with a resolved array in <code>formatterOptions['datetime_format']</code>: <code>['patterns' =&gt; ['fr' =&gt; 'd MMMM yyyy', ...]]</code> for a pattern table, <code>['presets' =&gt; ['date' =&gt; 'medium']]</code> for an ICU length, or both when the host definition does not cover all locales.</li>
          </ul>
          Resolving the implicit name of every date property is what makes a <code>k_medium_date</code> override take effect, even with no explicit option.</li>
        <li><strong>Render-time locale choice, in <code>FormattingContextBuilder</code></strong>: read <code>formatterOptions['datetime_format']</code> from the metadata (no registry dependency). Fallback order: exact request locale, then language alone, then <code>default</code>. If no <code>patterns</code> key matches, the <code>presets</code> length applies, else the formatter default. Inject into the formatter slot: <code>dateFormat</code> for <code>DateFormatter</code>, <code>timeFormat</code> for <code>TimeFormatter</code>, <code>dateTimeFormat</code> for <code>DateTimeFormatter</code>.</li>
        <li><strong><code>FormattingContext</code> and the three DateTime formatters</strong>: the context gains <code>dateFormatPreset</code> and <code>timeFormatPreset</code> (ICU lengths). <code>DateTimeFormatter</code> reads both — its date part and its time part — so no third <code>dateTimeFormatPreset</code> field. Each pattern resolution applies the priority: pattern present, then length present, then current default unchanged. Without this tier an unoverridden <code>k_short_date</code> would stay stuck on medium.</li>
        <li><strong>Tests</strong>:
          <ul>
            <li>build: property with <code>datetime_format: my_custom_datetime_format</code> embeds <code>formatterOptions['datetime_format']['patterns']</code> with the three keys; date property with no option embeds <code>['presets' =&gt; ['date' =&gt; 'medium']]</code> (implicit name <code>k_medium_date</code>); host definition of <code>k_medium_date</code> replaces the embed of every date property without <code>datetime_format</code>; unknown name throws at build; both string and map shapes of <code>datetime_formats</code> accepted;</li>
            <li>runtime: locale <code>fr</code> picks the <code>fr</code> key, locale <code>fr_CA</code> falls back to <code>fr</code> then <code>default</code>;</li>
            <li>E2E + property-based (BlackBox): index page in French and English with a date property on a custom format, plus a configured app overriding <code>k_medium_date</code>; generated locale × format-name combinations (built-in and custom), fixed seed, shrinking on failure.</li>
          </ul></li>
        <li><strong>Docs</strong>: the "Named date/time formats" section in <code>docs/src/customization/formatting-values.md</code> — table of the 12 built-in formats, <code>my_custom_datetime_format</code> example, <code>k_medium_date</code> override example, <code>datetime_format</code> key added to the <code>formatter_options</code> table.</li>
      </ol></td></tr>
      <tr><th>Realized.</th><td><code>all-check</code> green — 154 tests / 558 assertions. Delivered: <code>datetime_formats</code> config node, the 12-format static table, <code>NamedDatetimeFormats</code> and <code>DateTimeOptionsResolver</code> in <code>src/Formatters/Options/</code>, the <code>datetime_format</code> scalar node, build-time embedding in the field/association builders, render-time locale pick in <code>FormattingContextBuilder</code>, presets on <code>FormattingContext</code>. Tests: <code>DateTimeFormatConfigTest</code> (7 integration tests), <code>NamedDatetimeFormatsTest</code> and <code>DateTimeFormatterPresetsTest</code> (unit), E2E <code>DateTimeFormatsTest</code> (custom format per locale, host override of <code>k_medium_date</code>). Docs: the "Named date/time formats" section in <code>formatting-values.md</code>.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>12. Formatter option attributes <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Les <code>formatter_options</code> sont un array libre dans la config — aucune validation que les clés correspondent au formatter résolu. <code>date_format</code> sur un <code>IntegerFormatter</code> est silencieusement ignoré. <code>PropertyAccessorExtension::context()</code> ne lit que <code>currency</code>, <code>ucfirst</code> et <code>maximum_fraction_digits</code> — les autres options (date/time/timezone) ne sont jamais injectées. Un formatter custom doit deviner comment se brancher.</td></tr>
      <tr><th>Expected.</th><td>Chaque formatter (natif ou custom) déclare ses options via un attribut PHP <code>#[FormatterOption]</code>. Un compiler pass lit les attributs au compile time. La validation au build vérifie que les clés de <code>formatterOptions</code> sont dans le schema du formatter. <code>PropertyAccessorExtension</code> fusionne les options utilisateur avec les defaults du schema. Un formatter custom se branche en implémentant la classe + attribut — même mécanisme que les natifs.</td></tr>
      <tr><th>Prerequisites.</th><td>Aucun.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Créer l'attribut <code>FormatterOption</code></strong> (<code>src/Formatters/Attribute/FormatterOption.php</code>). Paramètres : <code>string $name</code>, <code>string $type</code> (<code>'string'</code>/<code>'boolean'</code>/<code>'int'</code>/<code>'float'</code>/<code>'array'</code>), <code>mixed $default = null</code>, <code>string $description = ''</code>.</li>
        <li><strong>Créer <code>FormatterOptionReader</code></strong> (<code>src/Formatters/FormatterOptionReader.php</code>). Lit les attributs <code>FormatterOption</code> sur une classe de formatter via <code>(new \ReflectionClass($class))->getAttributes(FormatterOption::class)</code>. Retourne <code>array&lt;string, array{type, default, description}&gt;</code>.</li>
        <li><strong>Ajouter les attributs sur les formatters natifs</strong> : <code>DateTimeFormatter</code> (<code>date_format</code>, <code>time_format</code>, <code>date_time_format</code>, <code>timezone</code>), <code>DateFormatter</code> (<code>date_format</code>, <code>timezone</code>), <code>TimeFormatter</code> (<code>time_format</code>, <code>timezone</code>), <code>StringFormatter</code> (<code>ucfirst</code>), <code>IntlNumberFormatter</code> (<code>minimum_fraction_digits</code>, <code>maximum_fraction_digits</code>), <code>TrueFalseFormatter</code> (aucune option), <code>EnumFormatter</code> (aucune option), <code>NotAvailableFormatter</code> (aucune option).</li>
        <li><strong>Compiler pass</strong> : créer <code>ValidateFormatterOptionsPass</code> qui lit le schema de chaque service taggé <code>karross.formatter</code> et le stocke en attribute sur le service definition (pour usage runtime).</li>
        <li><strong>Validation au build</strong> : dans <code>EntityMetadataBuilder::resolveFormatter()</code>, après avoir résolu le formatter, vérifier que les clés de <code>formatterOptions</code> (config hôte) sont dans le schema du formatter. Lever une <code>\InvalidArgumentException</code> explicite si clé inconnue.</li>
        <li><strong>Fusion defaults dans <code>PropertyAccessorExtension</code></strong> : <code>context()</code> lit le schema du formatter (via <code>FormatterOptionReader</code> ou cache précalculé), fusionne les options utilisateur avec les defaults, et injecte le résultat complet dans <code>FormattingContext</code> (ou un tableau <code>formatterOptions</code> enrichi).</li>
        <li><strong>Tests</strong> : test unitaire de <code>FormatterOptionReader</code> (lit les attributs d'un formatter natif), test de validation au build (clé inconnue → exception), test de fusion defaults (options partielles + defaults → tableau complet).</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>13. DateTime — reading <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>DateTimeInterface</code> → <code>Date</code>/<code>Time</code>/<code>DateTime</code> via le raffinement Doctrine dans le fallback de <code>FormatterResolver</code>. Les trois formatteurs existent et fonctionnent, mais aucun <code>DateTimeFormatterResolver</code> dédié (la résolution vit dans le fallback). Les options de format (<code>date_format</code>, <code>time_format</code>, <code>date_time_format</code>, <code>timezone</code>) ne sont jamais injectées depuis <code>formatterOptions</code>. Pas de formats nommés. Pas de tests unitaires directs pour les formatteurs DateTime.</td></tr>
      <tr><th>Expected.</th><td>      Un <code>DateTimeFormatterResolver</code> explicite qui extrait la résolution DateTime du fallback. Les formats nommés (ticket 11) déjà résolus au build et lus depuis les metadata. Les options déclarées (ticket 12) consommées par les formatteurs. Le fuseau horaire appliqué depuis la config ou le timezone de la requête. Tests unitaires et intégration.</td></tr>
      <tr><th>Prerequisites.</th><td>Named format registry (11) + Formatter option attributes (12).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Créer <code>DateTimeFormatterResolver</code></strong> (<code>src/Formatters/Resolvers/DateTimeFormatterResolver.php</code>, tag <code>karross.formatter.resolver</code>). <code>accept()</code> : accepter les types PHP <code>\DateTime</code>, <code>\DateTimeImmutable</code>, <code>\DateTimeInterface</code> ; accepter les types Doctrine <code>date</code>/<code>time</code>/<code>datetime</code>/<code>datetimetz</code> (et variants immutable). <code>resolve()</code> : retourner le formatteur approprié (<code>DateFormatter</code>, <code>TimeFormatter</code>, ou <code>DateTimeFormatter</code>) selon le type Doctrine.</li>
        <li><strong>Nettoyer <code>FormatterResolver::resolveFallback()</code></strong> : supprimer <code>isDatetime()</code>, <code>resolveDatetime()</code> et les cas DateTime de <code>resolveDoctrineType()</code>. Le resolver dédié intercepte avant le fallback.</li>
        <li><strong>Consommer les formats nommés</strong> : déjà fait au ticket 11 — <code>PropertyAccessorExtension::context()</code> lit <code>formatterOptions['datetime_format']</code> (tableau résolu embarqué au build : <code>patterns</code> et/ou <code>presets</code>) et choisit l'entrée de locale, sans appeler le registre.</li>
        <li><strong>Consommer les options déclarées</strong> : <code>PropertyAccessorExtension::context()</code> fusionne <code>date_format</code>, <code>time_format</code>, <code>date_time_format</code>, <code>timezone</code> depuis <code>formatterOptions</code> dans <code>FormattingContext</code>. Priorité : motif issu de <code>datetime_format</code> (nommé, résolu au build) > options brutes > defaults ICU.</li>
        <li><strong>Gérer le timezone</code> : si <code>formatterOptions['timezone']</code> est défini, l'appliquer sur le <code>IntlDateFormatter</code> (priorité). Sinon, le timezone de la requête est déjà géré par <code>FormattingContext</code>.</li>
        <li><strong>Tests</strong> : test unitaire de <code>DateTimeFormatterResolver</code> (cas PHP type × Doctrine type), test intégration qui lit un <code>datetime_format</code> résolu au build (choix de locale dans les metadata), test fusion options (motif de <code>datetime_format</code> > option brute > défaut ICU).</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>FormattingContext — séparer options statiques et contexte d'exécution <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>FormattingContext</code> cumule deux natures d'infos dans le même objet. Les options statiques — <code>currency</code>, <code>ucfirst</code>, <code>maximum_fraction_digits</code>, motifs et longueurs datetime — sont écrites au build dans <code>PropertyMetadata.formatterOptions</code>, puis recopiées dans le contexte au rendu. La locale de la requête et le choix du motif selon cette locale dépendent de l'exécution. Les formateurs ne reçoivent que <code>(value, context)</code> et n'ont pas accès aux metadata : le contexte sert de sac de transit entre le build et le formatteur. Exemple — <code>Article.price</code> avec <code>currency: EUR</code> : la devise est déjà figée au build, mais elle voyage dans le contexte parce qu'<code>IntlCurrencyFormatter</code> n'a pas d'autre porte d'entrée. Réserve déjà notée dans <code>@contexts/formatters.md</code> : risque de fourre-tout.</td></tr>
      <tr><th>Expected.</th><td>Séparation claire des deux natures. Le contexte d'exécution ne porte que ce qui dépend de la requête (locale, timezone le cas échéant). Les options statiques arrivent par un canal distinct — soit un second paramètre <code>format(value, options, context)</code>, soit une instance de formatteur configurée au build qui ne reçoit que la locale au rendu. Les formateurs restent simples, sans dépendance à <code>Metadata\</code>.</td></tr>
      <tr><th>Prerequisites.</th><td>Formatter option attributes (12) — connaître la surface complète des options avant de redessiner.</td></tr>
      <tr><th>Analysis.</th><td>Trois pistes : (1) scinder en deux paramètres, options statiques d'un côté et contexte d'exécution de l'autre ; (2) lier les options au build, le formatteur étant configuré une fois pour toutes et le rendu ne passant que la locale ; (3) laisser tel quel et surveiller la croissance. La piste (2) colle à la philosophie « toute décision au build » actée au ticket 11 pour les formats datés. Décision REC à trancher. À coordonner avec le ticket 12, qui prévoit d'injecter encore plus d'options via <code>PropertyAccessorExtension::context()</code>.</td></tr>
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
  <summary>SHOW page <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Le contrôleur <code>Show</code> (<code>src/Actions/Show.php</code>) est un stub : il récupère l'entité via Doctrine puis appelle <code>dd()</code>. Pas de templates, pas de résolution de templates pour l'action SHOW, pas de handling par le responder. Le <code>TwigResponder</code> résout uniquement la clé <code>['index']</code> de <code>entityMetadata->templates</code> — SHOW n'est jamais rendu. <code>EntityTemplateResolver</code> et <code>PropertyTemplateResolver</code> ne produisent de patterns que pour l'action INDEX.</td></tr>
      <tr><th>Expected.</th><td>Le contrôleur Show récupère l'entité et délègue au pipeline de responders, qui rend une page de détail : toutes les propriétés affichées avec leurs valeurs formatées, les associations en liens. La résolution de templates fonctionne pour l'action SHOW avec les mêmes cascades que INDEX (par entité, par nom de propriété, par type Doctrine, fallback générique).</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Contrôleur Show</strong> : supprimer le <code>dd()</code>, récupérer le slug via <code>EntityMetadataRegistry::getBySlug()</code>, construire un <code>ActionContext</code>, passer l'entité au <code>ResponderManager</code> comme le fait <code>Index</code>.</li>
        <li><strong>TwigResponder</strong> : adapter la résolution de template pour supporter les actions autres que INDEX — lire la bonne clé d'action dans <code>entityMetadata->templates[$action]</code> (au lieu de la clé hardcodée <code>['index']</code>).</li>
        <li><strong>EntityTemplateResolver</strong> : ajouter les patterns pour l'action SHOW — rôles <code>index</code> (page wrapper), <code>items</code> (pas de table ici, mais le contenu détail), <code>no_items</code> (jamais pour SHOW mais cohérence), <code>item</code> (une propriété). Patterns : <code>{action}_entity_{slug}</code>, <code>{action}.html.twig</code>.</li>
        <li><strong>PropertyTemplateResolver</strong> : ajouter les patterns pour SHOW — même vocabulaire que INDEX (<code>field.html.twig</code>, <code>association.html.twig</code>) avec les slots d'override par nom et par type.</li>
        <li><strong>Templates SHOW</strong> : créer <code>templates/show/show.html.twig</code> (page wrapper), <code>templates/show/field.html.twig</code> (cellule scalaire), <code>templates/show/association.html.twig</code> (cellule lien). Le template show itère <code>entityMetadata.getProperties()</code> et affiche chaque propriété avec son label et sa valeur formatée.</li>
        <li><strong>Tests</strong> : un test d'intégration vérifie que l'action Show rend une réponse HTTP 200 avec le contenu de l'entité. Un test E2E vérifie la navigation depuis l'index vers le show via un lien.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Identifier as navigable link <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>En index, la colonne identifiant (typiquement <code>id</code>) est rendue comme un <code>FieldMetadata</code> ordinaire via <code>field.html.twig</code> — texte brut, pas de lien. La fonction <code>getUrl()</code> de <code>UrlBuilderExtension</code> ne fonctionne qu'avec les <code>AssociationMetadata</code> (elle navigue via l'association pour extraire l'entité liée). L'identifiant de l'entité elle-même (<code>EntityMetadata::getIdentifier()</code>) n'est jamais consommé par un template.</td></tr>
      <tr><th>Expected.</th><td>En index, la valeur de la colonne identifiant est un lien clivable vers la page show de la même entité. Le rendu utilise le même routeur que les associations — le pattern <code>/{prefix}/{slug}/{identifiers}</code> existe déjà. Pas de behavior change pour les autres colonnes.</td></tr>
      <tr><th>Prerequisites.</th><td>SHOW page (le lien doit mener quelque part).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>UrlBuilderExtension</strong> : ajouter une méthode <code>getEntityUrl(string $action, EntityMetadata $entityMetadata, $entity): string</code> qui construit l'URL de l'entité elle-même — elle lit <code>$entityMetadata->getIdentifier()</code>, extrait les valeurs via <code>PropertyAccessor</code>, et génère la route via <code>RouteGenerator::routeName()</code> + <code>UrlGeneratorInterface::generate()</code>. La méthode existante <code>getUrl()</code> reste pour les associations.</li>
        <li><strong>PropertyTemplateResolver</strong> : dans la résolution de patterns pour INDEX, ajouter un slot prioritaire pour l'identifiant — quand <code>$property->name</code> est dans <code>$entityMetadata->getIdentifier()</code>, le pattern <code>field_id_entity_{slug}</code> (ou <code>field_identifier_entity_{slug}</code>) passe avant le pattern générique <code>field.html.twig</code>.</li>
        <li><strong>Template identifier</strong> : créer <code>templates/index/field_identifier.html.twig</code> qui rend <code>&lt;a href="{{ getEntityUrl('show', entityMetadata, item) }}"&gt;{{ k_formatted_value(item, property) }}&lt;/a&gt;</code>. Ce template est le slot par défaut pour l'identifiant — surchargeable par l'hôte comme tous les autres.</li>
        <li><strong>Tests</strong> : un test E2E vérifie que la colonne identifiant dans l'index est un lien et que le clic mène à la page show.</li>
      </ol></td></tr>
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