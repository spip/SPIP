# Changelog

## [Unreleased]

### Changed

- #5156 Les squelettes des formulaires d’édition (`formulaires/editer_xxx.html`) ne reçoivent plus l’ensemble du contenu de `spip_meta` dans l’entrée d’environnement `config`. Utiliser `#CONFIG` dedans si besoin pour cela. Seules les données spécifiques au formulaire sont transmises (par les fonctions `xxx_edit_config()`)
- #5042 Introduction de `README.md` et `LICENSE` (en remplacement de `INSTALL.txt` et `COPYING.txt`)
- #4881 suppression des globales `flag_*` et adaptation ou nettoyage en conséquence du code.
- #5108 `id_table_objet()` typé comme `objet_type()` que la fonction appelle

### Fixed

- #5121 CHANGELOG.md dans un format markdown suivant https://keepachangelog.com/fr/1.0.0/
- #5115 éviter un warning lors de l'appel avec un tableau à `produire_fond_statique()`

### Removed

- Ticket #5110 : Depuis #5018, le fichier `prive/transmettre.html` n’a plus lieu d’être.
