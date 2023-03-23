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

- Les logos historiques (migrés en documents à partir de SPIP 4.0) ne sont plus utilisés s’il en restait.
- Nécessite PHP 8.1 minimum

### Deprecated

- Fonction `logo_migrer_en_base()` (utilisable jusqu’en SPIP 5.y pour migrer les logos en documents)
- Fonction `spip_sha256` (utiliser `hash('sha256', $str)`)

### Removed

- Fonction `http_status` (utiliser `http_response_code`)
- Fonction `generer_lien_entite` (utiliser `generer_objet_lien`)
- Fonction `generer_introduction_entite` (utiliser `generer_objet_introduction`)
- Fonction `generer_info_entite` (utiliser `generer_objet_info`)
- Filtre `foreach` (utiliser une boucle `<BOUCLE_x(DATA){source table, #GET{tableau}}>...`)
- Fonction `lignes_longues` (utiliser un style CSS tel que `word-wrap:break-word;`)
- Fonction `extraire_date`
- Fonction `exporter_csv_ligne` (utiliser `exporter_csv_ligne_numerotee`)
- Fonctions `cvtmulti_formulaire_charger` et  `cvtmulti_formulaire_verifier` (voir `cvtmulti_formulaire_charger_etapes` et `cvtmulti_formulaire_verifier_etapes`)
- Fonction `auteurs_article` (utiliser `auteurs_objets`)
- Fonction `param_low_sec` (utiliser `generer_url_api_low_sec`)
- Fonction `spip_connect_ldap` (utiliser `auth_ldap_connect`)
- Fonction `_nano_sha256` (utiliser `hash('sha256', $str)`)
- Action `super_cron` (utiliser l’action `cron`, tel que `spip.php?action=cron`)
- #5505 Fonctions `verif_butineur()`, `editer_texte_recolle()` et environnement `_texte_trop_long` des formulaires (Inutilisé — servait pour IE !)
- #5258 Retrait de toute mention à GD1 dans la configuration des vignettes

### Fixed

- #5485 Correction d’erreurs des traitements d’image si la balise `img` n’a pas d’attribut `src`
- #5426 Correction des filtres de date lorsque l’entrée ne précise pas le jour tel qu’avec `2023-03`
