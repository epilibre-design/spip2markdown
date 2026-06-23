# Pipeline et intégration dans l'espace privé

## Pipeline déclaré

Un seul pipeline est déclaré dans `paquet.xml` :

```xml
<pipeline nom="affiche_gauche" action="affiche_gauche" inclure="spip2markdown_pipelines.php" />
```

## Handler : `spip2markdown_affiche_gauche()`

**Fichier** : `spip2markdown_pipelines.php`

```php
function spip2markdown_affiche_gauche($flux) {
    include_spip('inc/presentation');

    if ($flux['args']['exec'] == 'article') {
        $flux['data'] .=
            debut_cadre_relief('', true, '', _T('spip2markdown:spip2markdown')) .
            recuperer_fond('prive/spip2markdown-article', ['id_article' => $flux['args']['id_article']]) .
            fin_cadre_relief(true);
    }
    return $flux;
}
```

### Comportement

- S'active uniquement quand `exec=article` (fiche article dans l'espace privé).
- Ajoute un cadre (box) dans la colonne gauche de la fiche article.
- Le cadre est rendu par le squelette `prive/spip2markdown-article.html`, qui reçoit `id_article` en paramètre.

## Widget article (`prive/spip2markdown-article.html`)

```html
<div id="spip2markdown-article-#ENV{id_article}">
  <p>
    <a href="?page=article2markdown-voir&id_article=#ENV{id_article}">Voir</a>
    ou
    <a href="?page=article2markdown-telecharger&id_article=#ENV{id_article}">télécharger</a>
    le contenu en Markdown.
  </p>
  [<p>
    Télécharger le zip des pièces jointes :
    (#MODELE{zip_doc_article}{id_article})
  </p>]
</div>
```

Le bloc `[…]` (conditionnel SPIP) n'est affiché que si `#MODELE{zip_doc_article}` produit un résultat non vide — c'est-à-dire si l'article possède des pièces jointes. Ce modèle est fourni par le plugin `zippeur`.

## Résultat visuel

Dans l'interface privée SPIP, sur la page d'un article, le plugin ajoute dans la sidebar gauche un encadré « SPIP → Markdown » avec :
- Un lien « Voir le contenu en Markdown » (ouvre dans le navigateur).
- Un lien « Télécharger le contenu en Markdown » (propose le fichier `.md` en téléchargement).
- Si l'article a des pièces jointes : un lien pour télécharger un ZIP de ces fichiers.
