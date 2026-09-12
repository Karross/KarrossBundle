# MVP 1 — Full CRUD on simple entities

<details class="k-ticket k-ticket--blue">
  <summary>1. Dead code &amp; deprecated methods <span class="k-status k-status--blue">Done</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Phantom <code>PropertyInterface</code> — only an <code>@param</code> annotation in <code>EntityMetadata</code>; no such class, the real property types (<code>FieldMetadata</code>/<code>AssociationMetadata</code>) inherit from <code>PropertyMetadata</code>. Deprecated <code>getTypeOfField()</code>/<code>getTypeOfAssociation()</code> in <code>EntityMetadata</code> — pure wrappers of <code>getPropertyType()-&gt;value</code>, sole caller <code>TemplateResolver:44</code>, where <code>$property-&gt;type-&gt;value</code> is strictly equivalent. Note: <code>getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is the Doctrine <code>ClassMetadata</code> method — untouched. No released version: the "backward compatibility" rationale is void.</td></tr>
      <tr><th>Expected.</th><td>Dead code gone, zero behaviour change, all-check green.</td></tr>
      <tr><th>Plan.</th><td><ol><li><code>EntityMetadata</code>: fix the phantom docblock (<code>PropertyInterface[]</code> → <code>PropertyMetadata[]</code>; the real collections are <code>FieldMetadata</code>/<code>AssociationMetadata</code>, both extending <code>PropertyMetadata</code>).</li><li><code>EntityMetadata</code>: drop <code>getTypeOfField()</code>, <code>getTypeOfAssociation()</code> <em>and</em> <code>getPropertyType()</code>. Code survey: no consumer of "type by name" — the type is consumed at build (<code>EntityMetadataBuilder</code> → <code>FormatterResolver::resolve()</code> to pick the formatter) and at resolution (<code>TemplateResolver</code>, which already holds <code>$property</code>); runtime rendering uses the precomputed <code>$property-&gt;formatter</code> class-string (<code>k_formatted_value</code>); templates never touch the type. The "nature × type → formatter/widget" logic this API prefigured (link around an association value, several links for a collection) is deferred to the <strong>Collect &amp; Computed</strong> socle — a formatter concern, not a type-string lookup. (Le socle remplace ensuite <code>$property-&gt;type-&gt;value</code> de <code>TemplateResolver:44</code> par la déduction <code>templateKey</code>, calculée au build à partir des faits — le champ <code>type</code> disparaît.)</li><li><code>TemplateResolver:44</code>: replace the wrapper call with <code>$property-&gt;type-&gt;value</code> (strictly equivalent; the field/association split already relies on <code>instanceof</code> at <code>TemplateResolver:41</code>).</li><li>Grep <code>src/</code> and <code>tests/</code> for <code>PropertyInterface|getTypeOfField|getTypeOfAssociation|getPropertyType</code> → zero remaining (Doctrine's <code>ClassMetadata::getTypeOfField()</code> in <code>EntityMetadataBuilder</code> is untouched).</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--green">
  <summary>2. Cache wiring (metadata + templates) <span class="k-status k-status--green">Ready</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>TemplateRegistry::all()</code> (double <code>return</code>, the second unreachable) and <code>EntityMetadataRegistry::all()</code> (commented-out cache) inject and wire a <code>CacheInterface</code> that is never used; real per-request cost (Twig <code>resolveTemplate()</code> per pattern behind <code>k_template</code>, metadata rebuilt several times per request).</td></tr>
      <tr><th>Expected.</th><td>Caches never active in dev, always active in prod; identical behaviour in dev; all-check green.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Make <code>EntityMetadata</code> a pure data object: capture <code>fqcn</code> (from <code>classMetadata->getName()</code>) and <code>identifier</code> (from <code>classMetadata->getIdentifier()</code>) in <code>EntityMetadataBuilder</code> and pass them as constructor values; delete <code>getValue()</code> and <code>isEmbedded()</code> (no callers — runtime value reading already lives in the Twig extensions via <code>PropertyAccessor</code>, do not reimplement); <code>getFqcn()</code>/<code>getIdentifier()</code> return the stored values; remove the <code>ClassMetadata</code> property.</li><li>Gate: a <code>bool $cacheEnabled</code> computed once from <code>!kernel.debug</code> (injected through <code>services.php</code>), given to both registries.</li><li>Metadata cache: <code>EntityMetadataRegistry::all()</code> returns <code>cache.get('karross.metadata', build)</code> when enabled, fresh build when not (replacing the double-return / commented paths).</li><li>Template cache: <code>TemplateRegistry::all()</code> — same pattern on the resolver result, replacing the unreachable second <code>return</code>.</li><li>New integration test: with a debug kernel the registries rebuild every call; with a prod-like (debug=false) kernel the builder is invoked once and the cached result deep-equals a cold rebuild; the gate toggles correctly.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--green">
  <summary>3. Complexity measure in CI <span class="k-status k-status--green">Ready</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>No automated complexity measure; the PHPStan baseline (139) mostly blames over-fed arrays.</td></tr>
      <tr><th>Expected.</th><td>Deterministic AST-based cyclomatic measure over <code>src/</code>, baseline + threshold, run in CI and locally via <code>make</code>.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Install <strong>ast-metrics</strong> in the Docker image: fetch the pinned release binary (version + SHA256 in the Dockerfile) into <code>/usr/local/bin</code>; confirm the version targets PHP 8.5.</li><li>Config <code>.ast-metrics.yaml</code> (<code>ast-metrics init</code>), <code>complexity</code> + <code>volume</code> rulesets with proposed thresholds — max cyclomatic 10, max nesting 4, max LOC per method 30, max logical LOC per method 20 (REC to confirm).</li><li>Freeze the current state: <code>ast-metrics baseline</code> on <code>src/</code>, commit the snapshot (mirrors <code>phpstan-baseline.neon</code>); only new violations fail.</li><li>Local flow: add <code>make qa</code> → <code>ast-metrics lint</code> (exit 1 on any regression beyond the baseline); include it in <code>all-check</code> so local and CI share the same gate.</li><li>CI: add a <code>make qa</code> step in <code>ci.yml</code> after phpstan — regressions beyond the baseline block the pipeline.</li><li>Docs: document <code>make qa</code> and the baseline-regeneration rule (after cleanup sprints) in <code>docs/src/contribute.md</code>.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--green">
  <summary>4. Browser CSS compatibility gate in CI <span class="k-status k-status--green">Ready</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Zero CSS today, no defined browser support.</td></tr>
      <tr><th>Expected.</th><td>A CI gate failing on any CSS unsupported by the target browsers, plus a responsive budget harness in the E2E suite.</td></tr>
      <tr><th>Prerequisites.</th><td>Decide the supported browser set.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Pin the npm tooling in the image: a minimal <code>package.json</code> with <code>stylelint</code> + <code>stylelint-plugin-use-baseline</code> (Node is already in the container for Playwright).</li><li><code>.stylelintrc.mjs</code>: <code>plugin/use-baseline</code> policy set to <code>widely</code> first (most compatible — REC choice), relaxed to <code>newly</code> later only if a theme feature justifies it.</li><li>Fix the canonical asset path <code>src/Resources/public/css/karross.css</code> (Symfony bundle convention, host-replaceable) — the gate lints the bundle's own single CSS file; delivery (compression, cache, hashing) stays out of bundle scope.</li><li><code>make css-check</code>: stylelint gate, exit 1 on any unsupported CSS at the target level; wired into the CI pipeline.</li><li>Responsive budget harness in the E2E suite: viewport sweep (320 / 375 / 768 / 1024 / 1440) over the index/show pages of both apps, assert no horizontal overflow.</li><li>Docs: <code>contribute.md</code> — the <code>make css-check</code> command, the Baseline policy, and the viewport budget.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--green">
  <summary>5. Reorganize Metadata folder — Collect/Computed <span class="k-status k-status--green">Ready</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The <code>Metadata</code> namespace mixes analysis machinery (<code>PropertyTypeDetector</code>, <code>EntityMetadataBuilder</code>) and output read-models (<code>EntityMetadata</code>, <code>PropertyMetadata</code>, <code>FieldMetadata</code>, <code>AssociationMetadata</code>, registry). The DTO <code>PropertyTypeInfo</code> (no consumer) sits in <code>Karross\Formatters</code>, unused.</td></tr>
      <tr><th>Expected.</th><td>Two clean, non-crossing namespaces:
      <ul>
        <li><strong><code>Metadata\Collect</code></strong> (ex <code>Builder</code>): machinery only — <code>PropertyTypeDetector</code> (facts collector), <code>EntityMetadataBuilder</code> (orchestrator). Dependency direction: Computed → Collect, never the reverse.</li>
        <li><strong><code>Metadata\Computed</code></strong>: read-models — <code>EntityMetadata</code>, <code>PropertyMetadata</code> (carries the facts <em>and</em> the deductions: formatter, templateKey, future widget), <code>FieldMetadata</code>, <code>AssociationMetadata</code>, <code>FieldLabel</code>, registry.</li>
      </ul>
      The DTO <code>PropertyTypeInfo</code> is deleted (the collector builds the final <code>PropertyMetadata</code> directly, no intermediate DTO).</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Create <code>src/Metadata/Collect/</code> and <code>src/Metadata/Computed/</code>.</li><li>Move the machinery (<code>PropertyTypeDetector</code>, <code>EntityMetadataBuilder</code>) into <code>Collect/</code>.</li><li>Move the read-models (<code>EntityMetadata</code>, <code>PropertyMetadata</code>, <code>FieldMetadata</code>, <code>AssociationMetadata</code>, <code>FieldLabel</code>) into <code>Computed/</code>.</li><li>Delete <code>PropertyTypeInfo</code> (DTO with no consumer); the collector builds the <code>PropertyMetadata</code> directly.</li><li>Update <code>services.php</code> + <code>EntityMetadataRegistry</code> (imports).</li><li>Docs: architecture context and <code>docs/src</code> note the rule — <code>Collect</code> = machinery (Computed → Collect dependency), <code>Computed</code> = read-models carrying facts + deductions.</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--orange">
  <summary>Collect &amp; Computed <span class="k-status k-status--orange">Clarified</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Aujourd'hui, la collecte est <strong>réductrice</strong> :
      <ul>
        <li><code>PropertyTypeDetector::detect()</code> tord chaque propriété dans une case d'un enum sémantique maison (<code>PropertyType</code> : string, integer, date…), une interprétation propre au bundle imposée avant tout usage, à partir du type PHP, du type Doctrine et du PHPDoc.</li>
        <li>Tous les consommateurs se raccrochent à cette case : le formatter par une correspondance « un type = un formatter » (<code>FormatterResolver::resolve()</code>, appelé par le builder), les clés de template par <code>_type_{propertyType}</code>.</li>
        <li>Les faits bruts sont perdus : longueur, précision, échelle, nullable… jamais vus. Le DTO <code>PropertyTypeInfo</code>, prévu pour les porter, n'a aucun consommateur.</li>
        <li>L'enum mélange en outre la <em>nature</em> (string, integer, date…) et la <em>structure</em> (une valeur / plusieurs valeurs), alors que la structure est déjà connue : Doctrine sait si une association est to-one ou to-many, et les templates distinguent un champ d'une association.</li>
      </ul></td></tr>
      <tr><th>Expected.</th><td>La collecte <strong>rassemble les faits, sans les interpréter</strong> :
      <ul>
        <li>un <strong>collecteur</strong> (ex-<code>PropertyTypeDetector</code>, dans <code>Metadata\Collect</code>) agrège par propriété les <strong>faits statiques</strong> : <code>phpType</code>, <code>doctrineType</code>, les <code>details</code> de colonne (longueur, précision, nombre de décimales, <code>enumType</code>, <code>unsigned</code>, <code>fixed</code>, <code>nullable</code>, id/version/champ généré), la cardinalité le cas échéant (to-one/to-many) et le drapeau « conflit » entre sources — aucune projection, aucun type sémantique ;</li>
        <li>les <strong>objets construits</strong> (<code>Metadata\Computed</code>) : le <code>PropertyMetadata</code> porte ces faits, <em>et les <strong>déductions</strong> utiles au bundle, calculées au build à partir des faits</em> — le formatter, la clé de template, et demain le widget. Chaque déduction est décidée par son propre mécanisme d'usage, remplaçable par la config hôte ;</li>
        <li>l'enum <code>PropertyType</code> et le DTO <code>PropertyTypeInfo</code> <strong>disparaissent</strong> (ainsi que le champ <code>type</code> des métadonnées) : plus aucun vocabulaire sémantique forcé, ni de distinction nature/structure dans un enum.</li>
      </ul>

Règles de remplissage des faits (posées ici une fois pour toutes) :
      <ul>
        <li>la déclaration PHP fait foi quand elle existe ;</li>
        <li>sans type PHP (propriété non typée), on prend le type Doctrine ;</li>
        <li>si les deux se contredisent (ex. PHP dit <code>string</code>, Doctrine dit <code>json</code>), les faits ne tranchent pas tout seul : les deux valeurs sont gardées et signalées par le drapeau « conflit ». Le ticket du cas concerné décidera laquelle gagne ;</li>
        <li>la config explicite de l'app hôte surcharge tout (les faits comme les déductions) ;</li>
        <li>le PHPDoc n'est <strong>jamais</strong> utilisé : rien ne garantit qu'il soit à jour.</li>
      </ul></td></tr>
      <tr><th>Prerequisites.</th><td>5.</td></tr>
      <tr><th>Plan.</th><td><ol><li>Collecteur : réécrire <code>PropertyTypeDetector</code> en collecteur de faits (<code>Metadata\Collect</code>) — reçoit la propriété + le mapping complet (<code>getFieldMapping()</code>/<code>getAssociationMapping()</code>, passés par <code>EntityMetadataBuilder::buildFields()</code>/<code>buildAssociations()</code>) ; aucune projection, suppression de la branche PHPDoc.</li><li>Computed : <code>PropertyMetadata</code> porte les faits (phpType, doctrineType, details, cardinalité, conflit) <em>et les déductions</em> — <code>formatter</code> (déjà présent), <code>templateKey</code>, et la place du futur <code>widget</code> ; suppression du champ <code>type</code>.</li><li>Usages : <code>FormatterResolver</code> devient une chaîne qui lit les faits (défauts déterministes par type PHP/Doctrine) ; la clé de template devient une déduction calculée au build à partir des faits (champ/assoc + type de colonne), consommée telle quelle par <code>TemplateResolver</code> — aucun template <code>_type_*</code> existant à casser.</li><li>Enum : suppression totale de <code>PropertyType</code>, plus aucun consommateur au build ni au rendu.</li><li>Adressage : read-models → <code>Metadata\Computed</code>, machines → <code>Metadata\Collect</code> (mouvement du ticket 5) ; <code>services.php</code> + <code>EntityMetadataRegistry</code> à jour.</li><li>Tests : règles de fusion (typé / non typé → Doctrine / conflit → drapeau), contenu des faits et des déductions (formatter, clé de template), fidélité cache dev/prod (ticket 2).</li></ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Boolean — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>bool</code> → <code>PropertyType::Boolean</code> → <code>TrueFalseFormatter</code> ; rien de plus (Doctrine boolean n'apporte rien).</td></tr>
      <tr><th>Expected.</th><td>Tous les cas Boolean (typé, nullable, union avec null, non typé, conflit Doctrine, embedded) lisent les mêmes faits (<code>phpType</code> bool, <code>doctrineType</code> boolean) ; formatteur <code>TrueFalseFormatter</code> ; widget futur = checkbox + <code>required</code> ; clé template field.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed (faits + déductions portés par <code>PropertyMetadata</code>).</td></tr>
      <tr><th>Analysis.</th><td>Cas énumérés dans le corps du ticket : le maillon Boolean de la chaîne formatter lit les faits portés par <code>PropertyMetadata</code> ; override config gagne ; données E2E 0 / 1 / null. Doctrine n'ayant rien à refinir, c'est le cas le plus simple — sert de gabarit de rédaction aux autres.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Integer — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>int</code> → <code>PropertyType::Integer</code> → <code>IntlNumberFormatter</code> ; Doctrine <code>smallint/integer/bigint</code> s'effondrent en Integer — taille et signe perdus.</td></tr>
      <tr><th>Expected.</th><td>Traits de taille portés par le <code>PropertyMetadata</code> (<code>smallint</code>/<code>integer</code>/<code>bigint</code> → bornes pour le futur widget number), <code>unsigned</code>, <code>id</code>/<code>version</code>/<code>generated</code> = indicateurs read-only ; scale absente (jamais de décimales) ; non typé → Doctrine seul.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Smallint vs bigint est la distinction que le widget exploitera (contrainte de bornes), pas le fait d'être int — exactement la granularité que la vue 1-to-1 enum→formatter écrasait.</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Float — cas de lecture <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>float</code> → <code>PropertyType::Float</code> → <code>IntlNumberFormatter</code> ; <code>scale</code>/<code>precision</code> DECIMAL jetés.</td></tr>
      <tr><th>Expected.</th><td>Traits <code>scale</code>/<code>precision</code> portés par le <code>PropertyMetadata</code> alimentent <code>IntlNumberFormatter</code> (DECIMAL(10,2) → exactement 2 décimales) ; futur widget number <code>step</code> issu du scale.</td></tr>
      <tr><th>Prerequisites.</th><td>Collect &amp; Computed.</td></tr>
      <tr><th>Analysis.</th><td>Scale = raffinement le plus critique (sans lui, 3.14 ou 3.140000 selon la représentation). Le maillon Float lit le <code>scale</code> porté par le <code>PropertyMetadata</code> — pas de multiplication de cases enum.</td></tr>
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