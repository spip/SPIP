# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]

### Changed

- #4881 suppression des globales flag_* et adaptation ou nettoyage en conséquence du code.
- #5108 id_table_objet typé comme objet_type que la fonction appelle

### Fixed

- #5115 éviter un warning lors de l'appel avec un tableau à produire_fond_statique


## [4.1.1] - 2022-04-01

### Added

- Report chaines de langues
### Changed

- #5109 Il est recommandé de mettre les fichiers cachés en 404 (via le htaccess)

### Fixed

- #5109 bloquer l’accès aux fichiers de définition Composer (via le htaccess)
- Coquille dans _SPIP_VERSION_ID Nous sommes en version 4.1 ici, pas 41…
- Éviter des deprecated (null sur str*) lors de l’utilisation de `#CHEMIN{#ENV{absent}}`


## [4.1.0] - 2022-03-25

### Changed

- #5074 extension Sodium requise
- #5093 Revert partiel de #5048 (seul le fichier image svg d’objet générique est conservé)

### Fixed

- Simplification de `queue_start_job()` avec le spread operateur
- #5080 éviter que les paginations débordent sur petit écran
- #5076 réparer le retour de `lister_objets_lies()` sur des objets liés au même type d’objet
- #5100 JS pour eviter une double exécution sur un bouton action sur les doubles clic
- #5089 réparer l’affichage du mode debug en PHP 8 dans l’espace privé
- Éviter un warning sur `decompiler_criteres()`
- #5090 Afficher les logos auteurs et visiteurs de la même façon dans l’espace privé
- #5079 Suppression de la déclaration de la fonction `imagepalettetotruecolor()`
- Correction des pagination des boites qui listent les sous-rubriques
- #5088 Réparer le critère `compteur_articles_filtres` & sa balise `#COMPTEUR_ARTICLE`
- #5085 : Réparer le lien Afficher tout dans les paginations des boites de sous-rubriques


## [4.1.0-rc] - 2022-03-05

### Added

- #5050 Fournir un fichier `.editorconfig`
- #5048 Icone d’objet générique `objet-generique-xx.svg`
- #5030 Indiquer la version max de PHP
- #5064 Champ `backup_cles` sur la table `spip_auteurs`
- #5064 Un fichier `config/cles.php` est généré
- Répertoire `ecrire/src` (en prévision d’un autoloader)
- Report des chaines de langues
### Changed

- **Important** #5064 (Issues #5059 #4927 #3824 #2109) : changement de logique du login : plus de hashage + sel côté client, car les algos js sont pénibles à gérér et https est maintenant un standard de securité ;  côté serveur on utilise les fonctions modernes de PHP pour la gestion des mots de passe (sel, poivre, hashage et vérification) ; nécessite l’extension Sodium de PHP
- #5064 Les jetons d’action utilisent `hash_hmac` et `hash_equals` pour le calcul et la verification
- #5064 Les jetons en bdd sont hashés
- Homogénéisation des retours des filtres de dates : les filtres `|mois`, `|minutes`, `|annee` retournent toujours un string
- #4519 Nomages des paramètres et variables de listes d’inclusions et d’exclusions. On utilise `include list` et `exclude list` en anglais, et `liste d’inclusion` et `liste d’exclusion` en français
- #5048 `http_img_pack()` peut recevoir une clé `'alternative'` dans son tableau d'option. Cela indique une icone alternative à utiliser si jamais on ne retrouve pas l'image.

### Fixed

- #5069 les critères de date tel que `annee_nomprecis` fonctionnent aussi si le champ existe, mais qu’aucun champ `date` n’est déclaré.
- Capturer les `\ParseError` dans `evaluer_page()`
- `supprimer_tags()` acceptait des array contrairement à ce que supposait son phpdoc
- return manquant dans `spip_substr()`
- Différents warnings et notices corrigées
- Différentes simplifications de code avec PHP > 7.4 (notamment utilisation de variadics)
- Différents phpdoc complétés
- #5067 ne pas se rabattre sur les urls pages si un type d'url a été demandé explicitement

### Removed

- `lister_configurer()` et `lister_formulaires_configurer()`, Code mort depuis 11 ans (avant SPIP 3.0)


## [4.1.0-beta] - 2022-02-18

## [4.1.0-alpha] - 2022-02-08

