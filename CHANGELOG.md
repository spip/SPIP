# Changelog

## [Unreleased]

### Security

- spip-team/securite#4831 Correction des traitements des balises `#INFO_` dans l’espace privé
- spip-team/securite#3724 #5150 Le core ne génère plus de champ htpass par défaut dans `spip_auteurs`. Utiliser le plugin Htpasswd https://git.spip.net/spip-contrib-extensions/htpasswd pour ce besoin.

### Changed

- #5203 La balise `#CHAMP_SQL` peut expliciter une jointure tel que `#CHAMP_SQL{rubrique.titre}`
- #5156 Les squelettes des formulaires d’édition (`formulaires/editer_xxx.html`) ne reçoivent plus l’ensemble du contenu de `spip_meta` dans l’entrée d’environnement `config`. Utiliser `#CONFIG` dedans si besoin pour cela. Seules les données spécifiques au formulaire sont transmises (par les fonctions `xxx_edit_config()`)
- Ne pas insérer de balise de fermeture PHP dans le fichier `config/connect.php`
- #5082 Ne pas autoriser à refuser ses propres articles en tant que rédacteur ou rédactrice.
- #5042 Introduction de `README.md` et `LICENSE` (en remplacement de `INSTALL.txt` et `COPYING.txt`)
- #4881 suppression des globales `flag_*` et adaptation ou nettoyage en conséquence du code.
- #5108 `id_table_objet()` typé comme `objet_type()` que la fonction appelle

### Fixed

- spip-contrib-extensions/agenda#57 Éviter une erreur de typage à l’enregistrement dans certains formulaires
- #5223 Éviter une erreur fatale sur `sql_selectdb()` sur une base inexistante en mysql
- #5218 Éviter l’autocomplétion d’identifiants email en éditant un auteur
- #5209 Fonctionnement de Imagick sous Windows
- #5206 Échouer en minipres si on ne peut pas écrire le fichier des clés lors du login
- #5213 Prendre en compte le sens du critère `tri` en présence d’un tri `multi`
- #5190 Dans le formulaire de configuration de l'email de suivi, pouvoir indiquer une liste d'emails séparés par des virgules
- #5204 Fix le login lors de la restauration des cles depuis un compte webmestre
- #5118 Fix les viewbox erronnées lors de la copie locale des SVG sans viewbox
- #5194 Améliorer le comportement du bouton "Ajourd'hui" dans le dateur en surlignant le jour courant + ajout option data-todayhighlight sur les input.date + fix option data-clearbtn

### Added

- #4877 La balise `#TRI` permet d'alterner le sens du critère `tri`

### Removed

- spip-team/securite#3724 #5150 Suppression de la fonction `initialiser_sel()` (qui ne servait que pour la gestion de htpasswd déportée en plugin).
