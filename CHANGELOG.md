# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]

### Changed

- #4881 suppression des globales flag_* et adaptation ou nettoyage en conséquence du code.
- #5108 id_table_objet typé comme objet_type que la fonction appelle

### Fixed

- #5115 éviter un warning lors de l'appel avec un tableau à produire_fond_statique


## [4.1.1] - 2022-04-01

### Changed

- Report chaines de langues (Salvatore)
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

## [4.1.0-beta] - 2022-02-18

## [4.1.0-alpha] - 2022-02-08

