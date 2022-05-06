# Changelog

## [Unreleased]

### Security

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

- Loger l'avancement de la migration des logos (lors d’une mise à jour)
- Permettre de debug (js) les erreurs sur les liens ajax en utilisant le flag `jQuery.spip.debug` pour désactiver la redirection automatique
- spip-team/securite#3725 Permettre d'étendre la liste par défaut des cookies sécurisés via la constante `_COOKIE_SECURE_LIST`

### Changed

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

- #4277 adaptation de `spip_mysql_repair()` à innodb
- #4277 Permettre de spécifier l'engine mysql à utiliser
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

- Ticket #5110 : Depuis #5018, le fichier `prive/transmettre.html` n’a plus lieu d’être.
