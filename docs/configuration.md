# Configuration

## Accès à la page de configuration

Dans l'espace privé SPIP : `?exec=configurer_spip2markdown`

L'accès est protégé par `#AUTORISER{configurer,_depublies}` (administrateurs uniquement).

## Paramètres

Les valeurs sont stockées dans la table `spip_meta` sous le préfixe `spip2markdown/` et lues via `lire_config('spip2markdown/cle')` ou `#CONFIG{spip2markdown/cle}` dans les squelettes.

### Groupe 1 — YAML Front Matter

Ces options contrôlent quels champs sont inclus dans l'en-tête YAML du fichier Markdown exporté.

| Clé de config | Type | Défaut | Description |
|---|---|---|---|
| `title` | oui/non | `oui` | Inclure le titre de l'article |
| `date_iso` | oui/non | `oui` | Inclure la date au format ISO (`YYYY-MM-DD`) |
| `authors` | oui/non | `oui` | Inclure la liste des auteurs |
| `lang` | oui/non | `oui` | Inclure le code de langue (`fr`, `en`, etc.) |
| `categories` | texte | `""` (vide) | IDs des groupes de mots-clefs à exporter comme catégories, séparés par des virgules |
| `tags` | texte | `""` (vide) | IDs des groupes de mots-clefs à exporter comme tags, séparés par des virgules |
| `logo` | oui/non | `oui` | Inclure l'URL absolue du logo de l'article |
| `url` | oui/non | `oui` | Inclure l'URL absolue SPIP de l'article |
| `id` | oui/non | `oui` | Inclure l'ID numérique SPIP de l'article |

### Groupe 2 — Plugins Jekyll spécifiques

Ces options activent des conversions supplémentaires vers des syntaxes Liquid.

| Clé de config | Type | Défaut | Description |
|---|---|---|---|
| `youtube` | oui/non | `non` | Convertir les `<iframe>` YouTube en `{% youtube ID %}` |
| `twitter` | oui/non | `non` | Convertir les embeds Twitter en `{% twitter oembed URL %}` |
| `cloudinary` | oui/non | `non` | Utiliser `{% cloudinary %}` au lieu de `<figure>{% picture %}</figure>` pour les images |

## Catégories et tags : fonctionnement

Les champs `categories` et `tags` attendent une liste d'**IDs de groupes de mots-clefs** SPIP.

Exemple : si les catégories sont dans le groupe d'ID 3 et 7 :
- Saisir `3,7` dans le champ "catégories"

Pour chaque article, le squelette `article2markdown-categories.html` boucle sur les mots-clefs de l'article appartenant à ces groupes :

```html
<BOUCLE_article_mots(MOTS){id_article}{id_groupe IN #CONFIG{spip2markdown/categories}}{", "}>"#TITRE**"</BOUCLE_article_mots>
```

Si la valeur de config est vide (`""`), la condition `(#CONFIG{…}|=={""}|non)` dans `article2markdown-contenu.html` empêche le champ d'apparaître dans le YAML.

## Formulaire de configuration

Le formulaire est un **formulaire CVT SPIP** standard nommé `configurer_spip2markdown`. Il utilise le plugin `saisies` pour le rendu des champs.

SPIP gère automatiquement la sauvegarde des valeurs dans `spip_meta` via le mécanisme `formulaire_configurer_*` (convention de nommage).

## Lecture des config dans les squelettes vs PHP

**Dans un squelette** :
```html
[(#CONFIG{spip2markdown/youtube}|=={oui}|oui)
youtube: …]
```

**Dans le code PHP** (`spip2markdown_options.php`) :
```php
if (lire_config('spip2markdown/youtube') == 'oui') {
    $text = spip2markdown_youtube($text);
}
```
