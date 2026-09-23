# MVP 2 — Complex properties & fine-grained customization

<details class="k-ticket k-ticket--red">
  <summary>Complex properties <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Index/show already cover simple scalars and read-only associations; an embedded-fields metadata base is in place.</td></tr>
      <tr><th>Expected.</th><td>Arrays, objects and all Doctrine association types handled in forms and display; embedded fields deepened.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — CRUD lane (type detection rework, WidgetResolver form mapping, forms, delete action).</td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Fine-grained form customization <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Expected.</th><td>Per-property form customization (widget, constraints, labels), configurable like the formatters.</td></tr>
      <tr><th>Prerequisites.</th><td>MVP 1 — Collect &amp; Computed foundation and WidgetResolver (facts carried by <code>PropertyMetadata</code>).</td></tr>
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
  <summary>Pagination <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td><code>Index::__invoke()</code> appelle <code>$repository->findAll()</code> — toutes les entités sont chargées en mémoire, sans limite ni offset. Pas de paramètre de page dans l'URL, pas de contrôle de la taille de page.</td></tr>
      <tr><th>Expected.</th><td>La page index affiche les entités par pages. La taille de page est configurable par entité (config <code>karross</code>) avec une valeur par défaut raisonnable (25). L'état de la page courante est dans l'URL (<code>?page=2</code> ou <code>/page/2</code>) — bookmarkable, partageable. Le repository utilise <code>Query::setMaxResults()/setFirstResult()</code> au lieu de <code>findAll()</code>. Les liens previous/next sont rendus dans le template.</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Config</strong> : ajouter un nœud <code>entities.{FQCN}.page_size</code> (int, défaut 25) dans <code>Configuration.php</code>.</li>
        <li><strong>Index action</strong> : extraire le paramètre <code>page</code> de la requête (défaut 1), calculer l'offset (<code>($page - 1) * $pageSize</code>), utiliser un <code>Query</code> avec <code>setMaxResults($pageSize)</code> et <code>setFirstResult($offset)</code> au lieu de <code>findAll()</code>. Compter le total (<code>COUNT</code>) pour savoir s'il y a une page suivante.</li>
        <li><strong>Route</strong> : ajouter un paramètre optionnel <code>page</code> dans le pattern de route index (ou le garder en query string — REC decision).</li>
        <li><strong>Template</strong> : ajouter un partial <code>templates/index/pagination.html.twig</code> avec liens previous/next, numéro de page courant, rendu conditionnel (pas de pagination si une seule page).</li>
        <li><strong>Tests</strong> : test d'intégration vérifiant le comportement pagination (page 1 avec 25 résultats, page 2 avec le reste). Test E2E vérifiant la navigation entre pages.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Property visibility &amp; ordering <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Toutes les propriétés Doctrine (champs + associations) sont toujours affichées, dans l'ordre de <code>ClassMetadata::getFieldNames()</code> puis <code>getAssociationNames()</code>. Pas de mécanisme pour masquer une propriété, en afficher certaines uniquement en index ou en show, ou changer l'ordre des colonnes. Les templates itèrent <code>entityMetadata.getProperties()</code> sans filtre.</td></tr>
      <tr><th>Expected.</th><td>Par entité, contrôle des propriétés affichées en index et en show, et de leur ordre. Le défaut raisonnable reste « tout afficher dans l'ordre Doctrine » — l'override est optionnel. La config porte un tableau ordonné de noms de propriétés ; seules les propriétés listées sont rendues, dans l'ordre donné. Un écran « password » ou « hashedToken » peut être masqué de l'index tout en restant présent en show.</td></tr>
      <tr><th>Prerequisites.</th><td>None.</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Config</strong> : ajouter <code>entities.{FQCN}.index_properties</code> (string array, nullable) et <code>entities.{FQCN}.show_properties</code> (string array, nullable) dans <code>Configuration.php</code>. <code>null</code> = toutes les propriétés (comportement actuel).</li>
        <li><strong>EntityMetadata</strong> : ajouter deux propriétés <code>readonly array $indexProperties</code> et <code>readonly array $showProperties</code> (listes de noms de propriétés, ou vide = toutes). Les filtrer depuis <code>EntityMetadataBuilder</code> en fonction de la config.</li>
        <li><strong>Templates index/show</strong> : itérer <code>entityMetadata.getIndexProperties()</code> (ou <code>getShowProperties()</code>) au lieu de <code>getProperties()</code> pour les en-têtes et les cellules. Quand la liste est vide, fallback sur <code>getProperties()</code>.</li>
        <li><strong>Tests</strong> : test d'intégration vérifiant qu'avec une config <code>index_properties: ['title', 'published']</code>, seules ces colonnes apparaissent dans le HTML rendu.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Database sort <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Aucun tri — les entités sont rendues dans l'ordre de <code>findAll()</code> (ordre d'insertion/ID par défaut Doctrine). Pas de liens de tri dans les en-têtes de colonne, pas de paramètre de tri dans l'URL.</td></tr>
      <tr><th>Expected.</th><td>Tri par colonne en index, au niveau DB via Doctrine QueryBuilder (pas en mémoire). L'état du tri est dans l'URL (query string : <code>?sort=title&direction=asc</code>) — bookmarkable. Les en-têtes de colonne sont des liens cliquables qui basculent asc/desc. Tri sur les champs scalaires ; les associations et les colonnes composées restent non triables par défaut.</td></tr>
      <tr><th>Prerequisites.</th><td>Pagination (le tri est architecturalement coupled au paginated query).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>Config</strong> : optionnel — <code>entities.{FQCN}.sortable</code> (string array, nullable) pour restreindre les colonnes triables. Défaut = tous les champs scalaires.</li>
        <li><strong>Index action</strong> : extraire les paramètres <code>sort</code> et <code>direction</code> de la requête, valider le <code>sort</code> contre les propriétés triables, appliquer <code>Query::orderBy()</code> dans le QueryBuilder. Utiliser les paramètres liés (<code>setParameter()</code>) si le tri est sur une colonne Doctrine.</li>
        <li><strong>Templates</strong> : dans <code>items.html.twig</code>, rendre les en-têtes de colonne scalaires comme des liens <code>?sort={name}&direction={asc|desc}</code> avec une flèche d'indication. Les en-têtes d'association ne sont pas cliquables.</li>
        <li><strong>Tests</strong> : test d'intégration vérifiant que <code>?sort=title&direction=asc</code> retourne les entités triées par titre. Test E2E vérifiant le clic sur un en-tête de colonne.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Per-property filters <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>Aucun filtre — l'index affiche toutes les entités sans mechanisme de sélection. Pas de <code>FilterResolver</code>, pas de chaîne de résolution pour les filtres comme il en existe pour les formatters et les templates.</td></tr>
      <tr><th>Expected.</th><td>Filtres par propriété en index, avec un <code>FilterResolver</code> en 3e chaîne parallèle (après <code>FormatterResolver</code> + <code>PropertyTemplateResolver</code>). Chaque type Doctrine sait produire un filtre : <code>boolean</code> → select Oui/Non/Tous ; <code>string</code> → texte ; <code>integer</code>/<code>decimal</code> → plage ; <code>datetime</code> → plage de dates ; <code>enum</code> → select des cases. L'état des filtres est dans l'URL (query string) — bookmarkable. Les filtres sont combinés avec AND. La config peut restreindre les propriétés filtrables par entité.</td></tr>
      <tr><th>Prerequisites.</th><td>Pagination + Collect &amp; Computed (les faits portés par <code>PropertyMetadata</code> pilotent la résolution du filtre).</td></tr>
      <tr><th>Plan.</th><td><ol>
        <li><strong>FilterResolverInterface</strong> : créer <code>src/Filters/Resolvers/FilterResolverInterface</code> avec <code>accept(?string $phpType, ?FieldMapping $fieldMapping): bool</code> et <code>resolve(...): FilterInterface</code>. Le <code>FilterInterface</code> expose <code>buildQuery(QueryBuilder $qb, string $alias, string $property, $value): void</code> et <code>renderForm(PropertyMetadata $property): string</code> (le HTML du champ de filtre).</li>
        <li><strong>Filtres par type</strong> : <code>BooleanFilterResolver</code> (select Oui/Non/Tous), <code>StringFilterResolver</code> (LIKE), <code>IntegerFilterResolver</code> (plage min/max), <code>DecimalFilterResolver</code> (plage), <code>DateTimeFilterResolver</code> (plage de dates), <code>EnumFilterResolver</code> (select des cases). Chaque resolver est un service taggé <code>karross.filter.resolver</code>.</li>
        <li><strong>Config</strong> : <code>entities.{FQCN}.filterable</code> (string array, nullable) pour restreindre les propriétés filtrables. Défaut = toutes les propriétés scalaires.</li>
        <li><strong>Index action</strong> : extraire les paramètres de filtre de la requête, les combiner avec AND dans le QueryBuilder via les filtres résolus.</li>
        <li><strong>Template</strong> : ajouter un partial <code>templates/index/filters.html.twig</code> au-dessus du tableau, rendant le formulaire de filtres. Le formulaire soumet en GET avec les paramètres de filtre dans la query string.</li>
        <li><strong>Tests</strong> : test d'intégration vérifiant qu'un filtre <code>?published=1</code> retourne uniquement les entités publiées. Test E2E vérifiant l'interaction filtre.</li>
      </ol></td></tr>
    </tbody>
  </table>
</details>

<details class="k-ticket k-ticket--red">
  <summary>Reference documentation — update <span class="k-status k-status--red">Proposed</span></summary>

  <table class="k-ticket">
    <tbody>
      <tr><th>Existing.</th><td>The reference page created in MVP 1 covers the base configuration surface (routes, formatters, type overrides, template overrides).</td></tr>
      <tr><th>Expected.</th><td>Extend the reference with the MVP-2 surface: per-property form customization, widget renderers, richer formatters, JSON output, pagination, visibility, sort, filters.</td></tr>
      <tr><th>Prerequisites.</th><td>This MVP's Fine-grained form customization + Richer formatters: the reference documents the config surface once it stabilizes.</td></tr>
    </tbody>
  </table>
</details>