# Changelog

## Unreleased

### Security

- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Added

- #5301 Permettre de fournir le nom de l’attachement à `spip_livrer_fichier()`

### Changed

- #5258 Retrait de toute mention à GD1 dans la configuration des vignettes

### Fixed

- #5485 Correction d’erreurs des traitements d’image si la balise `img` n’a pas d’attribut `src`
- #5426 Correction des filtres de date lorsque l’entrée ne précise pas le jour tel qu’avec `2023-03`
