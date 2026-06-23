# Squelettes d'export

## Points d'entrée publics

| URL | Squelette | Comportement |
|---|---|---|
| `?page=article2markdown-voir&id_article=N` | `article2markdown-voir.html` | Affiche le Markdown dans le navigateur (`text/plain`) |
| `?page=article2markdown-telecharger&id_article=N` | `article2markdown-telecharger.html` | Déclenche le téléchargement d'un fichier `.md` |

### Contrôle d'accès

Les deux squelettes acceptent les articles de **tous les statuts** : `publie`, `prop` (proposé), `prepa` (en cours), `refuse`, `poubelle`. Ce choix est intentionnel pour permettre l'export de brouillons et d'articles non publiés.

```html
<BOUCLE_article(ARTICLES){id_article}{statut IN prop,prepa,publie,refuse,poubelle}>
```

---

## `article2markdown-voir.html`

```html
<BOUCLE_article(ARTICLES){id_article}{statut IN prop,prepa,publie,refuse,poubelle}>
#CACHE{0}
[(#HTTP_HEADER{Content-type: text/plain[; charset=(#CHARSET)]})]
#INCLURE{fond=article2markdown-contenu,id_article}
</BOUCLE_article>
```

- `#CACHE{0}` : désactive le cache SPIP (toujours régénéré).
- En-tête HTTP `Content-type: text/plain` : affiche le Markdown brut dans le navigateur.
- Délègue le contenu au squelette `article2markdown-contenu`.

---

## `article2markdown-telecharger.html`

```html
<BOUCLE_article(ARTICLES){id_article}{statut IN prop,prepa,publie,refuse,poubelle}>
#CACHE{0}
[(#HTTP_HEADER{Content-type: text/plain[; charset=(#CHARSET)]})]
[(#HTTP_HEADER{Content-Disposition: attachment; filename="[(#DATE|date_iso|couper{10})]-[(#URL_ARTICLE|replace{.*/,})].md"})]
#INCLURE{fond=article2markdown-contenu,id_article}
</BOUCLE_article>
```

- Ajoute l'en-tête `Content-Disposition: attachment` pour déclencher le téléchargement.
- **Nom de fichier** construit dynamiquement : `YYYY-MM-DD-slug-article.md`
  - `#DATE|date_iso|couper{10}` → les 10 premiers caractères de la date ISO, soit `YYYY-MM-DD`.
  - `#URL_ARTICLE|replace{.*/,}` → extrait la dernière partie de l'URL de l'article (le slug).

---

## `article2markdown-contenu.html` — squelette principal

C'est le squelette central, inclus par les deux points d'entrée. Il génère le fichier Markdown complet.

### Structure du fichier produit

```
---
title:      "Titre de l'article"
date:       2024-03-15
author:     Prénom Nom
lang:       fr
categories: "Catégorie 1", "Catégorie 2"
tags:       "Tag 1", "Tag 2"
logo:       https://example.com/IMG/jpg/logo.jpg
url:        https://example.com/mon-article
id:         42
---

## Surtitre

## Soustitre

Texte du chapô converti en Markdown.

Corps de l'article converti en Markdown.

Post-scriptum converti en Markdown.
```

### YAML Front Matter

Chaque champ du front matter est conditionnel à la configuration. Le délimiteur `---` est toujours présent.

| Champ | Balise SPIP | Filtre appliqué | Clé de config |
|---|---|---|---|
| `title` | `#TITRE**` | aucun (texte brut) | `title` |
| `date` | `#DATE` | `date_iso\|couper{10}` → `YYYY-MM-DD` | `date_iso` |
| `author` | fragment `article2markdown-auteurs` | — | `authors` |
| `lang` | `#LANG` | aucun | `lang` |
| `categories` | fragment `article2markdown-categories` | — | `categories` (id groupes) |
| `tags` | fragment `article2markdown-tags` | — | `tags` (id groupes) |
| `logo` | `#LOGO_ARTICLE` | `extraire_attribut{src}\|url_absolue` | `logo` |
| `url` | `#URL_ARTICLE` | `url_absolue` | `url` |
| `id` | `#ID_ARTICLE` | aucun | `id` |

Le `**` sur `#TITRE**` désactive l'échappement HTML de SPIP (texte brut).

### Corps de l'article

```html
[
## (#SURTITRE**)][

## (#SOUSTITRE**)][

(#CHAPO**|spip2markdown{chapo})][

(#TEXTE**|spip2markdown{texte})][

(#PS**|spip2markdown{ps})
]
```

- Les `[…]` SPIP rendent chaque bloc conditionnel : si le champ est vide, rien n'est affiché.
- Le filtre `spip2markdown` est appliqué avec un argument de contexte (`chapo`, `texte`, `ps`) pour différencier les notes de bas de page générées dans chaque champ.
- Le surtitre et le sous-titre sont convertis en titres Markdown `## …` directement dans le squelette (pas via le filtre).

---

## Fragments partiels

### `article2markdown-auteurs.html`

```html
<BOUCLE_article_auteur(AUTEURS){id_article}{", "}>#NOM**</BOUCLE_article_auteur>
```

Liste les noms des auteurs de l'article, séparés par `", "`. Le `{", "}` est le séparateur de boucle SPIP.

### `article2markdown-categories.html`

```html
[<BOUCLE_article_mots(MOTS){id_article}{id_groupe IN #CONFIG{spip2markdown/categories}}{", "}>"#TITRE**"</BOUCLE_article_mots>]
```

- Filtre les mots-clefs de l'article selon les groupes configurés (clé `spip2markdown/categories`).
- La valeur de config est une liste d'IDs de groupes séparés par des virgules.
- Si la config est vide, la boucle ne produit rien (le `[…]` conditionnel masque le champ).
- Chaque mot est mis entre guillemets dans le YAML.

### `article2markdown-tags.html`

Identique à `article2markdown-categories.html` mais utilise la clé de config `spip2markdown/tags`.
