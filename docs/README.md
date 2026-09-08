# Documentation (sources MkDocs)

Sources de la documentation de Karross, versionnées **dans le repo du bundle**.
La doc est le contrat : chaque évolution fonctionnelle du bundle est d'abord
décrite ici (workflow doc-first, skill `@spec`), puis convertie en code.

## Structure

```
docs/
├── mkdocs.yml      # config MkDocs (docs_dir = src/, dev sur :8001)
├── README.md       # ce fichier
├── src/*.md        # sources (en anglais) — c'est ici qu'on édite
└── site/           # build local (gitignored)
```

## En développement — préview à la volée

```bash
mkdocs serve        # depuis docs/ → http://127.0.0.1:8001/
mkdocs build        # régénère docs/site/
```

> Le port 8001 est fixé par `dev_addr` ; 8000/8080 restent réservés aux apps
> de démo du bundle (`make serve`). Les deux peuvent tourner en parallèle.

## Publication (auto, au push sur main)

Un workflow GitHub Actions (`.github/workflows/docs-deploy.yml`) build le site
et pousse le résultat vers le repo **`Karross/karross.github.io`** (branche
`main`) via une deploy key SSH — URL inchangée : `https://karross.github.io`.

Ne **jamais** éditer à la main le repo publié : c'est une cible générée.