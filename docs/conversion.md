# Conversion SPIP → Markdown

## Vue d'ensemble

La conversion est réalisée par la fonction `spip2markdown($text, $context = '')` définie dans `spip2markdown_options.php`. Ce fichier est un `_options` SPIP, chargé automatiquement avant la compilation des squelettes, ce qui rend `spip2markdown` disponible comme filtre (`|spip2markdown`) dans tous les squelettes.

### Pipeline d'exécution

```
spip2markdown($text, $context)
  │
  ├─ 1. normaliser_retours_chariot   \r\n et \n\r → \n
  ├─ 2. extraire_code                Extrait <code>…</code> → placeholders @CODE@N@CODE@
  ├─ 3. liens                        [libellé->url] → [libellé](url) ou <url>
  ├─ 4. notes                        [[note]] → [^contextN] + définitions en fin de texte
  ├─ 5. intertitres                  {{{Titre}}} → ## Titre
  ├─ 6. gras                         {{gras}} → **gras**
  ├─ 7. italiques                    {italique} → *italique*
  ├─ 8. citations                    <quote>…</quote> → > …
  ├─ 9. listes_non_ordonnees         - / -* / -** … → listes Markdown indentées
  ├─ 10. listes_ordonnees            -# / -## … → 1. indentées
  ├─ 11. documents                   <docN> / <imgN> / <embN> → figure/picture ou lien
  ├─ 12. reinserer_code              Réinsère le code extrait + convertit en ``` et `
  ├─ 13. youtube (optionnel)         <iframe youtube> → {% youtube ID %}
  ├─ 14. twitter (optionnel)         <blockquote twitter> → {% twitter oembed URL %}
  └─ 15. nettoyer                    Trim newlines, supprime <CENTER>
```

Le code est extrait en premier pour éviter que les transformations suivantes ne modifient son contenu.

---

## 1. Normalisation des retours chariot

```php
$text = preg_replace("/\r\n/u", "\n", $text);
$text = preg_replace("/\n\r/u", "\n", $text);
```

Uniformise les fins de ligne Windows (`\r\n`) et les variantes (`\n\r`) en `\n` Unix.

---

## 2. Extraction du code

**Entrée** : `<code>…</code>` ou `<code class="lang">…</code>`

**Technique** : Les balises `<code>` sont remplacées par des marqueurs Unicode `☞` / `☜`, puis le contenu entre marqueurs est stocké dans un tableau `$code[]` et remplacé par `@CODE@N@CODE@` dans le texte. Ces placeholders sont opaques aux transformations suivantes.

```
<code class="php">echo "hello";</code>
→ @CODE@0@CODE@    (pendant le traitement)
→ ```php            (à la réinsertion, étape 12)
  echo "hello";
  ```
```

**Réinsertion (étape 12)** :
- Code en bloc (nouvelle ligne avant et après) : `` ``` `` avec la classe comme identifiant de langage
- Code en ligne : `` ` `` simple
- `<code class="php">\ncontenu\n</code>` → ` ```php\ncontenu\n``` `
- `<code>inline</code>` → `` `inline` ``

---

## 3. Liens

**Fichier** : `spip2markdown_liens()`  
**Pattern** : `\[([^\]]*)->([^\]]+)\]`

### Cas 1 — Lien vers un article SPIP interne

Cible de la forme `aN`, `artN`, `articleN` (N = ID numérique) :

```
[Mon article->art42]
```

1. Requête SQL sur `spip_articles` pour récupérer `titre` et `date`.
2. Requête SQL sur `spip_urls` pour récupérer le slug de l'URL (la plus récente, tri par `date DESC`).
3. Construction de l'URL : `/YYYY/MM/slug.html` (format blog Jekyll).
4. Si le libellé est vide, le titre de l'article est utilisé.

```
[Mon article->art42]  →  [Mon article](/2023/04/mon-article.html)
[->art42]             →  [Titre de l'article](/2023/04/mon-article.html)
```

### Cas 2 — Lien externe avec libellé

```
[Texte du lien->https://example.com]  →  [Texte du lien](https://example.com)
```

### Cas 3 — Lien sans libellé (ou libellé = URL)

```
[->https://example.com]  →  <https://example.com>
[https://example.com->https://example.com]  →  <https://example.com>
```

### Cas 4 — Libellé avec indicateur de langue

Format SPIP : `[libellé{fr}->url]`

```
[Site français{fr}->https://example.fr]  →  [Site français (fr)](https://example.fr)
```

Le code de langue entre accolades est converti en texte entre parenthèses.

---

## 4. Notes de bas de page

**Entrée** : `[[contenu de la note]]`  
**Sortie Kramdown** : `[^contextN]` dans le texte + `[^contextN]: contenu` en fin de document

```
Le texte[[La note de bas de page.]] continue.
```
```
Le texte[^texte1] continue.

[^texte1]: La note de bas de page.
```

Le paramètre `$context` (valeurs : `chapo`, `texte`, `ps`) préfixe les identifiants de notes pour éviter les collisions quand plusieurs champs sont convertis dans le même fichier Markdown.

**Technique** : Même mécanisme `☞`/`☜` que pour le code, pour ne pas interférer avec les crochets normaux.

---

## 5. Intertitres

```
{{{Mon titre de section}}}  →  ## Mon titre de section
```

La regex évite le déclenchement sur des accolades SPIP imbriquées (gras/italique) grâce au contexte `(^|[^{])` et `([^}]|$)`. Les retours à la ligne superflus autour du titre sont normalisés.

---

## 6. Gras

```
{{texte en gras}}  →  **texte en gras**
```

La regex est appliquée **deux fois** pour gérer les occurrences adjacentes dans le même texte (limitation de la regex non-récursive).

---

## 7. Italiques

```
{texte en italique}  →  *texte en italique*
```

Même approche double-passe que pour le gras. La regex `(^|[^{])` évite de capturer `{{…}}` (déjà traité) ou `{{{…}}}`.

---

## 8. Citations

**Entrée** : `<quote>…</quote>`  
**Sortie** : Chaque ligne de contenu préfixée par `> `

```
<quote>
Première ligne.
Deuxième ligne.
</quote>
```
```
> Première ligne.
> Deuxième ligne.
```

---

## 9. Listes non ordonnées

SPIP utilise `-` (premier niveau) et `-*`, `-**`, `-***`, `-****` pour les niveaux suivants.

| Syntaxe SPIP | Markdown produit | Indentation |
|---|---|---|
| `- item` ou `-* item` | `- item` | 0 |
| `-** item` | `    - item` | 4 espaces |
| `-*** item` | `        - item` | 8 espaces |
| `-**** item` | `            - item` | 12 espaces |

Un retour à la ligne est ajouté avant chaque item de premier niveau pour séparer la liste d'un éventuel paragraphe précédent. Les lignes vides superflues entre items consécutifs sont supprimées (passe appliquée deux fois).

---

## 10. Listes ordonnées

| Syntaxe SPIP | Markdown produit | Indentation |
|---|---|---|
| `-# item` | `1. item` | 0 |
| `-## item` | `    1. item` | 4 espaces |
| `-### item` | `        1. item` | 8 espaces |
| `-#### item` | `            1. item` | 12 espaces |

Même logique de nettoyage des lignes vides superflues entre items.

---

## 11. Documents

**Pattern** : `<(doc|img|emb)N>` où N est l'ID du document.

Requête SQL sur `spip_documents` : `titre`, `descriptif`, `fichier`, `mode`, `media`.

### Images (`media = 'image'`)

**Mode par défaut (Jekyll Picture Tag)** :

```html
<figure>
  {% picture nom-du-fichier.jpg %}
  <figcaption>Titre du document. Descriptif du document  </figcaption>
</figure>
```

- La balise `{% picture %}` est celle du plugin Jekyll [jekyll-picture-tag](https://rbuchberger.github.io/jekyll-picture-tag/).
- La légende combine `titre` + `". "` + `descriptif` (si les deux sont présents).
- Si seul le titre ou le descriptif est présent, il est utilisé seul.
- Si ni titre ni descriptif : la `<figcaption>` est omise.

**Mode Cloudinary** (si `spip2markdown/cloudinary = oui`) :

```
{% cloudinary nom-du-fichier.jpg caption="Titre. Descriptif" %}
```

Compatible avec le plugin Jekyll [jekyll-cloudinary](https://github.com/nhoizey/jekyll-cloudinary).

La légende est récursivement passée par `spip2markdown()` (le titre et le descriptif peuvent eux-mêmes contenir du formatage SPIP).

### Autres documents (pdf, zip, etc.)

```
[Titre du document](nom-du-fichier.pdf)
```

Seul le nom de fichier (sans chemin) est utilisé dans l'URL.

---

## 12. Réinsertion du code

Les placeholders `@CODE@N@CODE@` sont remplacés par leur contenu original, puis :

- **Bloc** : `\n<code class="lang">\n…\n</code>\n` → ` ```lang\n…\n``` `
- **Inline** : `<code>…</code>` → `` `…` ``

---

## 13. YouTube (optionnel)

Actif si `spip2markdown/youtube = oui`.

```html
<iframe src="https://www.youtube.com/embed/VIDEO_ID"></iframe>
→
{% youtube VIDEO_ID %}
```

Les paramètres de l'URL (`?autoplay=1`, etc.) sont ignorés. Compatible avec le plugin Jekyll [jekyll-youtube-lazyloading](https://github.com/erossignon/jekyll-youtube-lazyloading).

---

## 14. Twitter (optionnel)

Actif si `spip2markdown/twitter = oui`.

```html
<blockquote class="twitter-tweet">
  …
  <a href="https://twitter.com/user/status/354870574595584000">…</a>
</blockquote>
<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
→
{% twitter oembed https://twitter.com/user/status/354870574595584000 %}
```

- Extrait l'URL du statut depuis le dernier lien dans le `<blockquote>`.
- Supprime aussi la balise `<script>` de Twitter.
- Compatible avec le plugin Jekyll [jekyll-twitter-plugin](https://github.com/rob-murray/jekyll-twitter-plugin).

---

## 15. Nettoyage final

```php
$text = preg_replace("/(^\n+|\n+$)/u", "", $text);  // trim des newlines
$text = preg_replace("/<\/?CENTER>/ui", "", $text);  // supprime <CENTER> et </CENTER>
```

---

## Marqueurs Unicode internes

Le plugin utilise deux caractères Unicode comme délimiteurs temporaires pendant le traitement, pour éviter les collisions avec la syntaxe SPIP :

| Caractère | Unicode | Rôle |
|---|---|---|
| `☞` | U+261E | Délimiteur ouvrant (remplace `<code>`, `[[`, `<quote>`) |
| `☜` | U+261C | Délimiteur fermant (remplace `</code>`, `]]`, `</quote>`) |

Ces caractères ont été choisis car ils ne peuvent pas apparaître dans le contenu SPIP normal.
