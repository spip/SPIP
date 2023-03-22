# Changelog

## Unreleased

### Security

- spip-team/securite#4840 Éviter `unserialize` dans l’écran de sécurité
- spip-team/securite#4840 Limiter la profondeur de recursion de `protege_champ`
- spip-team/securite#4840 Inclure l’écran de sécurité avant l’autoloader
- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Added

- Log des dépréciations, via la fonction `trigger_deprecation` (de symfony/deprecations-contracts).
- #5301 Permettre de fournir le nom de l’attachement à `spip_livrer_fichier()`

### Changed

- Nécessite PHP 8.1 minimum

### Deprecated

- Fonction `spip_sha256` (utiliser `hash('sha256', $str)`)

### Removed

- Fonction `spip_connect_ldap` (utiliser `auth_ldap_connect`)
- Fonction `_nano_sha256` (utiliser `hash('sha256', $str)`)
- Action `super_cron` (utiliser l’action `cron`, tel que `spip.php?action=cron`)
- #5505 Fonctions `verif_butineur()`, `editer_texte_recolle()` et environnement `_texte_trop_long` des formulaires (Inutilisé — servait pour IE !)
- #5258 Retrait de toute mention à GD1 dans la configuration des vignettes

### Fixed

- #5485 Correction d’erreurs des traitements d’image si la balise `img` n’a pas d’attribut `src`
- #5426 Correction des filtres de date lorsque l’entrée ne précise pas le jour tel qu’avec `2023-03`
