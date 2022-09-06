# Changelog

## [Unreleased]

### Security

- spip-team/securite#3724 #5150 Le core ne génère plus de champ htpass par défaut dans `spip_auteurs`. Utiliser le plugin Htpasswd https://git.spip.net/spip-contrib-extensions/htpasswd pour ce besoin.

### Added

- #5271 Fonction `is_html_safe()`
- #4877 La balise `#TRI` permet d'alterner le sens du critère `tri`

### Changed

- #5271 Refactoring de la mise en sécurité des textes dans ecrire et public
- #5189 Ne plus forcer l'Engine MySQL à l'installation
- #5273 Le critère `par_ordre_liste` rejette à la fin les éléments de la boucle absents de la liste
- #5016 Généralisation du traitement des balises dynamiques dans un modèle
- #5272 Compatibilité avec PHP 8.2
- #5025 Prise en charge de l'utf8 pour le filtre `|match` en appliquant par défaut le modificateur u (PCRE_UTF8)
- spip-team/securite#4835 utiliser json_decode au lieu de serialize pour _oups dans le formulaire d'edition de liens
- #5203 La balise `#CHAMP_SQL` peut expliciter une jointure tel que `#CHAMP_SQL{rubrique.titre}`
- #5156 Les squelettes des formulaires d’édition (`formulaires/editer_xxx.html`) ne reçoivent plus l’ensemble du contenu de `spip_meta` dans l’entrée d’environnement `config`. Utiliser `#CONFIG` dedans si besoin pour cela. Seules les données spécifiques au formulaire sont transmises (par les fonctions `xxx_edit_config()`)
- Ne pas insérer de balise de fermeture PHP dans le fichier `config/connect.php`
- #5082 Ne pas autoriser à refuser ses propres articles en tant que rédacteur ou rédactrice.
- #5042 Introduction de `README.md` et `LICENSE` (en remplacement de `INSTALL.txt` et `COPYING.txt`)
- #4881 suppression des globales `flag_*` et adaptation ou nettoyage en conséquence du code.
- #5108 `id_table_objet()` typé comme `objet_type()` que la fonction appelle

### Fixed

- spip/medias#4905 Utiliser une déclaration moins prioritaire pour les traitements sur les champs
- #5303 Ne pas déclencher l'ouverture du datepicker quand la date n'est pas en édition
- #5312 Bug sur la sélection rapide par id dans le selecteur générique
- #4016 Utiliser de préférence `#VALEUR{index}` dans le core
- #5157 Deprecated en moins sur `_couleur_hex_to_dec()`, `couleur_html_to_hex()` & `url_absolue()`  quand on leur passe une valeur nulle
- #5287 Éviter un échec de migration de certains logos au format SVG.
- #5283 Loger les erreurs de squelettes lorsqu’elles ne sont pas affichées sur la page
- #5016 Collecte des arguments sur les formulaires dans un modèle
- #5274 Homogénéiser les labels des listes

### Removed

- #5285 Supprimer le lien obsolète vers `page=distrib` dans la page Suivre la vie du site
- #5278 Globales obsolètes `spip_ecran` et `spip_display`
- spip-team/securite#3724 #5150 Suppression de la fonction `initialiser_sel()` (qui ne servait que pour la gestion de htpasswd déportée en plugin).
