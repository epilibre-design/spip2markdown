# Architecture et fichiers

## Arborescence

```
spip2markdown/
├── paquet.xml                                     Manifeste du plugin
├── spip2markdown_pipelines.php                    Handler du pipeline affiche_gauche
├── spip2markdown_options.php                      Fonctions de conversion (chargé via _options)
│
├── article2markdown-contenu.html                  Squelette principal : YAML Front Matter + corps
├── article2markdown-voir.html                     Page publique "voir" (text/plain dans le navigateur)
├── article2markdown-telecharger.html              Page publique "télécharger" (attachment .md)
├── article2markdown-auteurs.html                  Fragment : liste des auteurs
├── article2markdown-categories.html              Fragment : liste des catégories
├── article2markdown-tags.html                    Fragment : liste des tags
│
├── prive/
│   ├── spip2markdown-article.html                 Widget injecté dans la fiche article privée
│   └── squelettes/contenu/
│       └── configurer_spip2markdown.html         Page de configuration (espace privé)
│
├── formulaires/
│   └── configurer_spip2markdown.html             Formulaire CVT de configuration
│
└── lang/
    └── spip2markdown_fr.php                      Chaînes de traduction françaises
```

## Rôle de chaque fichier

### `paquet.xml`
Déclare le plugin : version, compatibilité, dépendances, pipeline unique (`affiche_gauche`).
Pas d'attribut `schema` → pas de table SQL propre, pas de `_administrations.php`.

### `spip2markdown_options.php`
Fichier chargé automatiquement par SPIP comme fichier `_options` (chargement anticipé avant la compilation des squelettes). Contient :
- La fonction principale `spip2markdown($text, $context)` utilisée comme filtre dans les squelettes.
- Toutes les fonctions de conversion détaillées dans [conversion.md](conversion.md).

### `spip2markdown_pipelines.php`
Contient le seul handler de pipeline : `spip2markdown_affiche_gauche()`. Voir [pipeline.md](pipeline.md).

### Squelettes publics (`article2markdown-*.html`)
Accessibles via `?page=article2markdown-voir` et `?page=article2markdown-telecharger`. Voir [squelettes.md](squelettes.md).

### `prive/spip2markdown-article.html`
Fragment injecté dans la sidebar gauche de la fiche article en espace privé. Affiche deux liens (voir / télécharger) et un lien de téléchargement ZIP des pièces jointes (via le plugin `zippeur`).

### `formulaires/configurer_spip2markdown.html`
Squelette du formulaire de configuration, rendu par `#FORMULAIRE_CONFIGURER_SPIP2MARKDOWN`. Utilise le plugin `saisies` pour les champs. Voir [configuration.md](configuration.md).

### `prive/squelettes/contenu/configurer_spip2markdown.html`
Page exec de configuration dans l'espace privé. Vérifie l'autorisation `configurer` avant d'afficher le formulaire.
