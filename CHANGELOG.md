# Changelog

## [Unreleased]

### Security

- spip-team/securite#4836 Refactoring de la gestion du oups du formulaire editer_liens
- spip-team/securite#3724 #5150 Le core ne génère plus de champ htpass par défaut dans `spip_auteurs`. Utiliser le plugin Htpasswd https://git.spip.net/spip-contrib-extensions/htpasswd pour ce besoin.

### Added

- #5056 Chargement de l’autoloader de Composer aux points d’entrées de SPIP
- #5056 Intégration de dépendances à des librairies PHP via composer.json (notamment les polyfill PHP 8.0 8.1 et 8.2 ainsi que le polyfill mbstring)
- #5323 Ajout de liens de retour vers le site public + déconnexion dans un des écrans d'erreur d'accès à l'espace privé
- #5302 Afficher la langue des utilisateurs sur leur page et permettre aussi de l'éditer
- #5271 Fonction `is_html_safe()`
- #4877 La balise `#TRI` permet d'alterner le sens du critère `tri`

### Changed

- #5056 SPIP nécessite certaines dépendances via Composer (son archive zip contiendra le nécessaire)
- #5056 Les (quelques) classes PHP de SPIP sont déplacées dans `ecrire/src` sous le namespace `Spip`
- #5361 Image `loader.svg` dans un style plus moderne
- #5351 Balisage html généré par la balise raccourci `<code>`
- #5321 Refactoring de la collecte et echappement des liens
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

- #5362 Agrandir le formulaire de recherche des listes dans l’espace privé
- #5357 Utiliser le terme "Se connecter" valider le formulaire de login
- #5355 Correction d'un Warning si le débogueur est appelé sans être connecté
- #5316 Les PDF envoyés avec un CSP Sandbox ne sont pas visibles dans Safari ou Chrome, on fait donc une exception pour ces fichiers
- #5326 Utilisation de PHP_AUTH avec d'autres méthodes d'identification que LDAP
- #5284 Éviter que les titres longs dépassent de leur bloc dans l'espace privé
- #5329 Rétablir la collecte des doublons par la fonction `traiter_modeles()`
- #5308 Correction de la valeur affichée pour le dernier item de pagination en mode naturel
- #5132 Correction d'un deprecated sur `recuperer_numero()` quand on lui passe une valeur nulle
- spip-contrib-extensions/mailsubscribers#23 La séléction de la langue du visiteur doit se limiter à la config `langues_multilingue` dans le public
- spip/medias#4905 Utiliser une déclaration moins prioritaire pour les traitements sur les champs
- #5303 Ne pas déclencher l'ouverture du datepicker quand la date n'est pas en édition
- #5312 Bug sur la sélection rapide par id dans le selecteur générique
- #4016 Utiliser de préférence `#VALEUR{index}` dans le core
- #5157 Deprecated en moins sur `_couleur_hex_to_dec()`, `couleur_html_to_hex()` & `url_absolue()`  quand on leur passe une valeur nulle
- #5287 Éviter un échec de migration de certains logos au format SVG.
- #5283 Loger les erreurs de squelettes lorsqu’elles ne sont pas affichées sur la page
- #5016 Collecte des arguments sur les formulaires dans un modèle
- #5274 Homogénéiser les labels des listes

### Deprecated

- #5056 Les classes de nœud du compilateur (Champ, Boucle, Critere...) sont déplacées dans le namespace `Spip\Compilateur\Noeud\` (l’appel sans namespace est déprécié)

### Removed

- #5285 Supprimer le lien obsolète vers `page=distrib` dans la page Suivre la vie du site
- #5278 Globales obsolètes `spip_ecran` et `spip_display`
- spip-team/securite#3724 #5150 Suppression de la fonction `initialiser_sel()` (qui ne servait que pour la gestion de htpasswd déportée en plugin).
