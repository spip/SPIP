# Changelog

## Unreleased

### Security

- spip-team/securite#4840 Éviter `unserialize` dans l’écran de sécurité
- spip-team/securite#4840 Limiter la profondeur de recursion de `protege_champ`
- spip-team/securite#4840 Inclure l’écran de sécurité avant l’autoloader
- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Added

- #5301 Permettre de fournir le nom de l’attachement à `spip_livrer_fichier()`

### Fixed

- #5485 Correction d’erreurs des traitements d’image si la balise `img` n’a pas d’attribut `src`
- #5426 Correction des filtres de date lorsque l’entrée ne précise pas le jour tel qu’avec `2023-03`
