# Changelog

## Unreleased

### Security

- #5552 Appliquer l’option `httponly` sur la plupart des cookies internes à SPIP
- #5552 Appliquer l’option `secure` sur les cookies lorsqu’on est en HTTPS
- spip-team/securite#4840 Éviter `unserialize` dans l’écran de sécurité
- spip-team/securite#4840 Limiter la profondeur de recursion de `protege_champ`
- spip-team/securite#4840 Inclure l’écran de sécurité avant l’autoloader
- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Added

- #5565 Critère `{collate}` (remplace `{collecte}`)
- #5565 Critères `{groupby}` et `{groupby_supprimer}` (remplace `{fusion}` et `{fusion_supprimer}`)
- #5565 Critère `{having}`
- #5586 Attributs `data-objet`, `data-id_objet` et `data-objet-source` sur le formulaire d’édition de liens, pour usage en JS à toutes fins utiles.
- #5535 Log des dépréciations, via la fonction `trigger_deprecation` (de symfony/deprecations-contracts).
- Les plugins-dist et le squelettes-dist s'intallent avec composer
- #5301 Permettre de fournir le nom de l’attachement à `spip_livrer_fichier()`

### Changed

- #5552 Signature de `spip_setcookie` (reprend la signature de php `setcookie`)
- Mise à jour de Sortable.js (1.14.0 => 1.15.0)
- #5542 Refacto page de contrôle et boîtes des tâches de fond
- #5540 Les fonctions `extraire_balise` et `extraire_balises` peuvent gérer des balises imbriquées
- Les logos historiques (migrés en documents à partir de SPIP 4.0) ne sont plus utilisés s’il en restait.
- Nécessite PHP 8.1 minimum

### Fixed

- spip-galaxie/trad.spip.net#8 Calcul d’URL de `generer_url_api` sans précision du paramètre `public`
- #5563 Filtre `couper` erroné dans certains cas avec des caractères utf8 multi bytes.
- Formulaire de configuration du multilinguisme (suite à spip-team/securite#4841)
- #5584 Présentation de l’input de recherche dans l’ajout d’auteurs liés
- #5580 Recherche et navigation dans le sélecteur de rubriques dépliant
- #5496 Typage en entrée de certains paramètres des fonctions `generer_*` lorsque l’objet n’existe pas.
- #5571 Retrouver correctement les anciens itérateurs
- #5576 Rétablir l'insertion du script de protection sandbox sur la prévisu d'un objet
- #5543 Indiquer la bonne fonction dans le message d'erreur de `_imagecreatefrom_func`
- #5317 Animation plus douce des formulaires resoumis hors ajax
- #5485 Correction d’erreurs des traitements d’image si la balise `img` n’a pas d’attribut `src`
- #5426 Correction des filtres de date lorsque l’entrée ne précise pas le jour tel qu’avec `2023-03`
- #5541 Notices PHP en moins sur la page de contrôle des tâches de fond

### Deprecated

- #5552 Constantes `_COOKIE_SECURE` et `_COOKIE_SECURE_LIST` (utiliser les options `secure` et/ou `httponly` de `spip_setcookie`)
- #5565 Critère `{collecte}`. Utiliser `{collate}`
- #5565 Critères `{fusion}` et `{fusion_supprimer}`. Utiliser `{groupby}` et `{groupby_supprimer}`
- Fonction `logo_migrer_en_base()` (utilisable jusqu’en SPIP 5.y pour migrer les logos en documents)
- Fonction `spip_sha256` (utiliser `hash('sha256', $str)`)

### Removed

- Javascript `jquery.placeholder-label` (qui simulait l’attribut placeholder sur des vieux navigateurs)
- Suppression du test sur [mbstring.overload](https://www.php.net/manual/en/mbstring.configuration.php#ini.mbstring.func-overload) à l'install
- Suppression de l’usage de la classe `no_image_filtrer` (utiliser la classe `filtre_inactif` qui l’a remplacé)
- Fichier `plugins-dist.json` (on utilise `composer.json` maintenant)
- Boucle `POUR` (utiliser une boucle DATA tel que `<BOUCLE_x(DATA){source tableau, ...}>`)
- Filtre `foreach` (utiliser une boucle `<BOUCLE_x(DATA){source table, #GET{tableau}}>...`)
- Action `super_cron` (utiliser l’action `cron`, tel que `spip.php?action=cron`)
- Fonction `critere_par_joint` (utiliser `calculer_critere_par_champ` si besoin)
- Fonction `http_status` (utiliser `http_response_code`)
- Fonction `generer_url_ecrire_objet` (utiliser `generer_objet_url_ecrire`)
- Fonction `generer_lien_entite` (utiliser `generer_objet_lien`)
- Fonction `generer_introduction_entite` (utiliser `generer_objet_introduction`)
- Fonction `generer_info_entite` (utiliser `generer_objet_info`)
- Fonction `lignes_longues` (utiliser un style CSS tel que `word-wrap:break-word;`)
- Fonction `extraire_date`
- Fonction `exporter_csv_ligne` (utiliser `exporter_csv_ligne_numerotee`)
- Fonctions `cvtmulti_formulaire_charger` et  `cvtmulti_formulaire_verifier` (voir `cvtmulti_formulaire_charger_etapes` et `cvtmulti_formulaire_verifier_etapes`)
- Fonction `auteurs_article` (utiliser `auteurs_objets`)
- Fonction `param_low_sec` (utiliser `generer_url_api_low_sec`)
- Fonction `spip_connect_ldap` (utiliser `auth_ldap_connect`)
- Fonction `_nano_sha256` (utiliser `hash('sha256', $str)`)
- #5505 Fonctions `verif_butineur()`, `editer_texte_recolle()` et environnement `_texte_trop_long` des formulaires (Inutilisé — servait pour IE !)
- #5258 Retrait de toute mention à GD1 dans la configuration des vignettes
