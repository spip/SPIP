# Changelog

## [Unreleased]

### Security

- spip-team/securite#3724 #5150 Le core ne génère plus de champ htpass par défaut dans `spip_auteurs`. Utiliser le plugin Htpasswd https://git.spip.net/spip-contrib-extensions/htpasswd pour ce besoin.
- spip-team/securite#4829 Sécuriser le retour de `nettoyer_titre_email()` lorsqu’utilisé en squelette (Louka)
- spip-team/securite#4494 Suite de #54 : masquer aussi les cookies sensibles dans `$_SERVER['HTTP_COOKIE']` et `$_ENV['HTTP_COOKIE']` sur la page d’info
- spip-team/securite#3728 Sécuriser `HTTP_HOST` et `REQUEST_URI` dans `url_de_base()`
- spip-team/securite#3725 Reconnaitre les cookies securisés même si on utilise un `cookie_prefix`
- spip-team/securite#3703 Regexp de `_PROTEGE_BLOCS` plus robuste (`\b` plutôt que `\s`)
- spip-team/securite#3702 Sécuriser dans `parametre_url()` la construction d’une regexp
- spip-team/securite#3700 Éviter dans `preg_files()` une regexp qui retourne tous les fichiers
- spip-team/securite#3698 Sécuriser l’affichage des erreurs de plugins
- spip-team/securite#3609 Sécuriser l'usage des var_mode_xx dans le debuggueur
- spip-team/securite#3730 À l’installation (étape bdd), échapper le nom de la bdd
- spip-team/securite#3597 À l’installation (étape ldap), filtrer l’adresse LDAP et échapper les variables
- spip-team/securite#3596 À l’installation (étape test d’écriture), ne pas accepter `..` lors du test des répertoires

### Added

- #4277 Constante `_MYSQL_ENGINE` pour spécifier l’engine MySQL à utiliser
- Loger l'avancement de la migration des logos (lors d’une mise à jour)
- Permettre de debug (js) les erreurs sur les liens ajax en utilisant le flag `jQuery.spip.debug` pour désactiver la redirection automatique
- spip-team/securite#3725 Permettre d'étendre la liste par défaut des cookies sécurisés via la constante `_COOKIE_SECURE_LIST`

### Changed

- #5203 La balise `#CHAMP_SQL` peut expliciter une jointure tel que `#CHAMP_SQL{rubrique.titre}`
- #5156 Les squelettes des formulaires d’édition (`formulaires/editer_xxx.html`) ne reçoivent plus l’ensemble du contenu de `spip_meta` dans l’entrée d’environnement `config`. Utiliser `#CONFIG` dedans si besoin pour cela. Seules les données spécifiques au formulaire sont transmises (par les fonctions `xxx_edit_config()`)
- Typage de la fonction `spip_affiche_mot_de_passe_masque()`
- Ne pas insérer de balise de fermeture PHP dans le fichier `config/connect.php`
- Accélérer un peu la migration des logos en documents (sur mise à jour vers SPIP 4.0) en désactivant le versionnage et les drapeaux édition pendant cette étape
- #5082 Ne pas autoriser à refuser ses propres articles en tant que rédacteur ou rédactrice.
- spip-team/securite#4336 La fonction `recuperer_infos_distantes()` prend maintenant un tableau d'options en second argument (seul le core utilisait les 2nd et 3e arguments).
- spip-team/securite#4336 La fonction `recuperer_infos_distantes()` accepte une option supplémentaire pour passer une callback de validation de l'URL distante finale (apres suivi des redirections eventuelles)
- spip-team/securite#4336 La fonction `copie_locale()` accèpte un argument `$callback_valider_url` qui permet de tester l'URL finale après récuperation et de refuser la copie locale si l'URL ne valide pas
- #5042 Introduction de `README.md` et `LICENSE` (en remplacement de `INSTALL.txt` et `COPYING.txt`)
- #4881 suppression des globales `flag_*` et adaptation ou nettoyage en conséquence du code.
- #5108 `id_table_objet()` typé comme `objet_type()` que la fonction appelle

### Fixed

- #5204 Fix le login lors de la restauration des cles depuis un compte webmestre
- #5118 Fix les viewbox erronnées lors de la copie locale des SVG sans viewbox
- #5194 Améliorer le comportement du bouton "Ajourd'hui" dans le dateur en surlignant le jour courant + ajout option data-todayhighlight sur les input.date + fix option data-clearbtn
- #4277 Adaptation de `spip_mysql_repair()` à innodb
- #4277 Permettre de spécifier l'engine mysql à utiliser
- #5095 Ne pas casser une meta sérialisée quand un utilisateur saisit un emoji dans un formulaire de configuration
- #5152 Éviter des warning si `spip_log()` est appelé avant l’initialisation (dans mes_options)
- #5162 Erreur d’exécution sur `vider_date()` qui doit renvoyer une chaine
- Correction notice PHP sur `spip_affiche_mot_de_passe_masque()` en absence de mot de passe
- #5169 Éviter une erreur JS sur `$.fn.positionner()` si la sélection est vide
- #5168 Éviter une erreur fatale sur `analyse_fichier_connection()` si le fichier de connexion à analyser n’existe pas
- #5101 Le statut de rubrique par défaut à tester est désormais `prepa` plutôt que `new`
- #5183 Éviter de générer des icones trop grandes dans la liste des articles syndiqués
- #5185 Éviter une double compression des JS de la page login
- Correction de la navigation par initiale sur les listes auteurs et visiteurs (bon markup de pagination)
- Correction warning sur un log dans `logo_supprimer()`
- Correction nom de option `expires` (qui est bien avec un s comme l'entete http qu'on envoie) dans `spip_livrer_fichier()`
- Correction erreur de typage quand on utilise la fonction dépréciée `generer_url_entite_absolue()`
- #5155 Suppression de l'argument `formulaire_action_sign` de l'url d’action des formulaires
- #5148 Centrer l'image de fond de la page login
- #5117 Éviter un warning à l’installation sur l’absence de configuration de 'articles_modif'
- spip-team/securite#4336 La fonction `copie_locale()` ne retourne un chemin local que s’il existe
- #5121 CHANGELOG.md dans un format markdown suivant https://keepachangelog.com/fr/1.0.0/
- #5115 Éviter un warning lors de l'appel avec un tableau à `produire_fond_statique()`

### Removed

- spip-team/securite#3724 #5150 Suppression de la fonction `initialiser_sel()` (qui ne servait que pour la gestion de htpasswd déportée en plugin).
- #5110 Depuis #5018, le fichier `prive/transmettre.html` n’a plus lieu d’être.
