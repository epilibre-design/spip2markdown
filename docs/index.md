# Documentation technique — plugin spip2markdown

## Vue d'ensemble

**spip2markdown** est un plugin SPIP qui permet d'exporter un article SPIP au format Markdown (saveur Kramdown), avec un en-tête YAML Front Matter compatible Jekyll/Hugo.

| Attribut | Valeur |
|---|---|
| Préfixe | `spip2markdown` |
| Version | 0.3.0 |
| Compatibilité SPIP | [3.0.0 ; 4.*] |
| Catégorie | outil |
| Dépendances | `saisies` ≥ 2.1.3, `zippeur` ≥ 4.1.4 |
| Pas de base de données | (aucun attribut `schema` dans paquet.xml) |

## Sommaire

- [Architecture et fichiers](architecture.md)
- [Pipeline et intégration dans l'espace privé](pipeline.md)
- [Squelettes d'export](squelettes.md)
- [Conversion SPIP → Markdown](conversion.md)
- [Configuration](configuration.md)
