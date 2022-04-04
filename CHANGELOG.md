# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]

### Changed

- #4881 suppression des globales `flag_*` et adaptation ou nettoyage en conséquence du code.
- #5108 `id_table_objet()` typé comme `objet_type()` que la fonction appelle

### Fixed

- #5115 éviter un warning lors de l'appel avec un tableau à `produire_fond_statique()`


## [4.1.1] - 2022-04-01

### Added

- Report chaines de langues
### Changed

- #5109 Il est recommandé de mettre les fichiers cachés en 404 (via le htaccess)

### Fixed

- #5109 bloquer l’accès aux fichiers de définition Composer (via le htaccess)
- Coquille dans `_SPIP_VERSION_ID` Nous sommes en version 4.1 ici, pas 41…
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

### Security

- Bien appliquer l’autorisation dans `formulaires_editer_objet_charger()` (g0uz)

### Added

- Report des chaines de langues

### Fixed

- #5040 Utiliser une fonction `lire_fichier_langue()` pour charger un fichier de langue et vérifier qu’il est correct. On loge une erreur sinon.
- Différents warnings, notices ou deprecated
- Optimisations et nettoyages pour PHP 7.4+, dont remplacement des call_user_func et call_user_func_array par des `$func($param)` ou `$func(...$params)`
- #5032 `ini_set()` peut être désactivé sur les hébergements web.

### Removed

- #5038 suppression de `signaler_conflits_edition()`, code mort depuis SPIP 3.0

## [4.1.0-alpha] - 2022-02-08

### Security

- #5017 Bien vérifier le droit de modifier le login dans le `formulaire_editer_auteur()`
### Added

- Compatibilité PHP 8.1
- Des traductions depuis trad.spip.net
- #5018 Fonction `generer_url_api()` pour generer une url vers une action api
- #5018 Action d’api transmettre à utiliser sous la forme `transmettre.api/id_auteur/cle/format/fond?...` pour remplacer le vieux `transmettre.html` et les flux RSS lowsec
- #5018 Fonction `generer_url_api_low_sec()` pour faciliter la generation d'une url low_sec vers transmettre.api
- #5018 Fonction `securiser_acces_low_sec()` (renommage de `securiser_acces()`)
- #5018 Filtre `filtre_securiser_acces_dist()` (assure la retro-compatibilité des squelettes avec l’ancien nom de `securiser_acces_low_sec()`)
- #5010 Filtre `header_silencieux()` pour masquer la version de SPIP si la globale `spip_header_silencieux` le demande
- Une fonction `infos_image()` qui fait un peu plus que `taille_image()` en recupererant en meme temps le poids du fichier si possible (0 sinon), y compris en faisant une copie locale.
- Un filtre `poids_image()` retourne le poids d’une image (en s’appuyant sur `infos_image()`).
- #5000 Fonction `generer_objet_info()` (remplace `generer_info_entite()`) qui delegue maintenant à des fonctions `generer_objet_TRUC()` ou `generer_TYPE_TRUC()`
- #5000 Fonction `generer_objet_lien()` (remplace `generer_lien_entite()`)
- #5000 Fonction `generer_objet_introduction()` (remplace `generer_introduction_entite()`)
- #5000 Fonction `generer_objet_url()` (remplace `generer_url_entite()`) et on en profite pour separer public et connect en 2 arguments distincts
- #5000 Fonction `generer_objet_url_absolue()` (remplace `generer_url_entite_absolue()`)
- #5000 Fonction `generer_objet_url_ecrire()` (remplace `generer_url_ecrire_objet()`)
- #5000 Fonction `generer_objet_url_ecrire_edit()` (remplace `generer_url_ecrire_entite_edit()`)


### Changed

- Nécessite PHP 7.4 minimum.
- Nécessite les extensions PHP Phar, Zip et Zlib (dépendances du plugin Archiviste 2.1+)
- #5019 Le filtre `alterner` peut recevoir un compteur negatif
- Typage sur certains arguments et retours de fonctions (dont `autoriser()`)
- #5018 Déplacement des squelettes de prive/rss vers prive/transmettre/rss
- #5000 #3311 Fonctions de calcul et décodage d’URL réimplémentées
- Le composer.json (non utilisé encore en dehors du dev) indique la dépendance à PHP et ses extensions
- Mise a jour de jQuery Forms en version 4.3.0

### Fixed

- PHP 8.1 : Capturer les exceptions mysqli
- #5015 Amélioration du message d’erreur sur charger_fonction si le fichier est trouvé mais pas la fonction
- #5011 Toujours afficher les articles refusés d'une rubrique
- #5021 #5022 Correction d’échappements SQLite
- #5018 Fix `minipres()` quand appelée sur une URL avec un path (url arbo, ou url d'une action api)
- Correction du fichier de DTD de paquet.xml
- #4910 Boucles DATA : permettre d'utiliser les caractères `:_-.` dans un critère `{par xxx}`
- #4945 Autoriser d'autres espaces que l'espace dans les critères `{a,b}`
- #4986 Refactoring de la gestion des options headers/datas de `recuperer_url()` pour mieux gérer certaines redirections
- Différents warnings, notices ou deprecated
- Différents PHPDoc complétés
- Différents nettoyages de code pour PHP 7.4+

### Deprecated

- #5018 Fonction `param_low_sec()` (Utiliser `generer_url_api_low_sec()`)
- #5000 Fonction `generer_info_entite` (Utiliser `generer_objet_info()`)
- #5000 Fonction `generer_lien_entite` (Utiliser `generer_objet_lien()`)
- #5000 Fonction `generer_introduction_entite` (Utiliser `generer_objet_introduction()`)
- #5000 Fonction `generer_url_entite` (Utiliser `generer_objet_url()`)
- #5000 Fonction `generer_url_entite_absolue` (Utiliser `generer_objet_url_absolue()`)
- #5000 Fonction `generer_url_ecrire_objet` (Utiliser `generer_objet_url_ecrire()`)
- #5000 Fonction `generer_url_ecrire_entite_edit` (Utiliser `generer_objet_url_ecrire_edit()`)

### Removed

- Compatibilité PHP 7.3
- #5018 Suppression de prive/rss.html
- Fichiers `prive/transmettre/forum_article.html` et `prive/transmettre/signatures_article.html`, code mort depuis 10 ans
- Code mort : on n’a plus à gerer l'appel des vieilles fonction d'URL SPIP < 2
- #4875 Retrait de `#FORMULAIRE_CONFIGURER_METAS` (code mort depuis SPIP 3)

### To parse

* 65c7d7f42 - Alléger aussi le type sur vider_attribut. (Matthieu Marcillaud il y a 3 mois)
* 169128489 - Tolérance pour |inserer_attribut sur les appels avec une source `null` comme dans `[(#CHEMIN{absent}|image_reduire{24}|inserer_attribut{..., ...})]` (Matthieu Marcillaud il y a 3 mois)
* 25861b702 - Avec toutes les modifications faites pour PHP 8.1, il faut invalider le code des squelettes compilé pour être sûr qu’il se mette à jour. (Matthieu Marcillaud il y a 3 mois)
* e4a3b1721 - Suppression de la compatibilité jQuery.cookie ou $.cookie (Matthieu Marcillaud il y a 3 mois)
* af9197cb5 - JS Cookie passe en version 3.0.1 (Matthieu Marcillaud il y a 3 mois)
* 11f8d8862 - jQuery.form.js en version 4.3.0 (Matthieu Marcillaud il y a 3 mois)
* 69d87deca - Sortable.js en version 1.14.0 (Matthieu Marcillaud il y a 3 mois)
* 1b45a2032 - Oups : Rector passe gentiment les constantes en majuscule… mais elles sont sensibles à la casse. Comme celles là sont publiques, on laisse comme avant (Matthieu Marcillaud il y a 3 mois)
* 7c506a022 - Oups… code de debug (Matthieu Marcillaud il y a 3 mois)
* f30f279b7 - Quelques arobases en moins (Matthieu Marcillaud il y a 3 mois)
* 548ed0e4b - PHP 8.1 : Éviter une erreur avec redirect null sur traiter_appels_actions() (Matthieu Marcillaud il y a 3 mois)
* 66ec71803 - Compat PHP 8.1 : éviter une erreur lorsqu’une balise dynamique demande un argument absent On évite d’appeler calculer_balise(null, ...). (Matthieu Marcillaud il y a 3 mois)
* 4ccfda6be - Des corrections suite au passage de Rector. (Matthieu Marcillaud il y a 3 mois)
* 35c4c206c - Rector sur config/ avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 5146d68d5 - Rector sur ecrire/public (le reste) avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 94f4d13c2 - Instruction spécifique sinon Rector essaie d’enlever le reset précédent sans pouvoir, lors de la passe \Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class (Matthieu Marcillaud il y a 3 mois)
* dd3abb34f - Rector sur ecrire/public/composer.php (une partie) avec config PHP 7.3 sans \Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class (Matthieu Marcillaud il y a 3 mois)
* 5caeec4c7 - Rector sur ecrire/public (une partie) avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 33f37523d - Rector sur ecrire/ (racine) avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* dc7eaa81d - Rector sur ecrire/install avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 513b2f10b - Un try/catch sur le json decode de l’iterateur de boucle DATA json. (Matthieu Marcillaud il y a 3 mois)
* cc518f97a - Rector sur ecrire/exec avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* a1df6891f - Rector sur ecrire/urls avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* fb8afd99b - Simplifier urls_page_dist() (PHP 7.4+) (Matthieu Marcillaud il y a 3 mois)
* 568ff4eae - Rector sur ecrire/xml avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* ed2889158 - Rector sur ecrire/{plugins,req} avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 0919b6667 - Rector sur ecrire/{install,iterateur,maj} avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* d27468ab6 - Rector sur ecrire/inc avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 1247a829a - Rector sur ecrire/{exec,genie} avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 8cc7909a0 - Rector sur ecrire/{auth,balise,base} avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* ac4c801d9 - Rector sur ecrire/action avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 2325f0e17 - Rector sur prive/ avec config PHP 7.4 (Matthieu Marcillaud il y a 3 mois)
* 6c993902e - Des simplifications de certains isset. (Matthieu Marcillaud il y a 3 mois)
* 7b18b8be6 - SPIP 4.1 nécessitera php 7.4.0 minimum. (Matthieu Marcillaud il y a 3 mois)
* 924565dda - Être plus tolérant avec le type de |attribut_html : on tolère null en entrée, sinon des choses tel que `[title="(#ENV{_compositions/#ENV{composition}/description}|attribut_html)"]` créent une fatale, car il était accepté de transmettre `null`. (Matthieu Marcillaud il y a 3 mois)
* ce5c5aef5 - Éviter un warning si la globale profondeur_url n’est pas ou pas encore définie. (Matthieu Marcillaud il y a 3 mois)
* b08ca7db1 - Lorsqu’une erreur provient sur l’écriture d’un fichier et que raler_fichier() est appelé, terminer de charger les constantes de SPIP avant de charger minipres(), sinon une fatale est levée car certaines constantes manquent. (Matthieu Marcillaud il y a 3 mois)
* 1a5dae79c - PHP 8.1 #4968 : Deprecated-- sur vider_url et presenter_contexte (Matthieu Marcillaud il y a 3 mois)
* a2637fde0 - PHP 8.1 #4968 : Deprecated-- sur set_spip_doc et get_spip_doc en tolérant une entrée null. Deprecated-- sur controler_contenu lorsque c vaut false, il ne devrait pas être transformé en array automatiquement. (Matthieu Marcillaud il y a 3 mois)
* 1b1a95c14 - PHP 8.1 #4968 : Deprecated-- sur filtre match() avec texte null. (Matthieu Marcillaud il y a 3 mois)
* b52e6f0ed - Pas d’espace avant le : du return type. (Matthieu Marcillaud il y a 3 mois)
* f3361f0ee - Opérateurs d’égalité strict dans trouver_objet_exec() (Matthieu Marcillaud il y a 3 mois)
* d257a7956 - PHP 8.1 #4968 : Deprecated-- sur spip_mysql_cite() si valeur null; (Matthieu Marcillaud il y a 3 mois)
* 6aa0f802c - PHP 8.1 #4968 : Deprecated-- sur controler_md5() si valeur null, + typage. (Matthieu Marcillaud il y a 3 mois)
* 2325632c6 - PHP 8.1 #4968 : Deprecated-- sur suppmirer_tags(). Il peut recevoir null dans certains cas. (Matthieu Marcillaud il y a 3 mois)
* b10af4a76 - Éviter d’appeler lister_tables_objets_sql avec une chaine vide. (Matthieu Marcillaud il y a 3 mois)
* a23333a32 - PHP 8.1 #4968 : Deprecated-- Éviter d’appeler table_objet_sql() avec null. On ajoute aussi un peu de typage. (Matthieu Marcillaud il y a 3 mois)
* 5f13efb4e - PHP 8.1 #4986 : Typer `string` les paramètres $connect dans les fonctions, puisque c’est ce qui est attendu. (Matthieu Marcillaud il y a 3 mois)
* aba5ad38a - PHP 8.1 #4968 : Deprecated-- sur les critères optionnels {truc ?}. On passe par une fonction (is_whereable) pour déterminer si on doit appliquer le where. On pourra trouver un nommage plus adapté. (Matthieu Marcillaud il y a 3 mois)
* 52bb9a36f - PHP 8.1 #4968 : Attraper les exceptions mysqli. Il faudrait aller plus loin en forçant pour PHP < 8.1 les execptions de mysqli via mysqli_report(), et les utiliser à la place de spip_mysql_errno() (Matthieu Marcillaud il y a 3 mois)
* d649660de - PHP 8.1 #4968 : Deprecated-- les classes (ici d’itérateurs) qui étendent certaines méthodes doivent avoir une signature équivalente à la méthode étendue. On ne peut pas utiliser le type 'mixed' (introduit en PHP 8.0) en PHP 7.4… donc #[\ReturnTypeWillChange] est temporairement utilisé. (Matthieu Marcillaud il y a 3 mois)
* 01d6cd4dc - PHP 8.1 #4968 : deprecated-- sur des fonctions de textes (Matthieu Marcillaud il y a 3 mois)
* ce9510b0f - Typage des fonctions d’autorisations (Matthieu Marcillaud il y a 3 mois)
* 520e1ea06 - Typage sur interprete_argument_balise() (Matthieu Marcillaud il y a 3 mois)
* f20bcafd7 - Typage de objet_type() (Matthieu Marcillaud il y a 3 mois)
* 2a86b92f6 - Typage sur ratio_passe_partout() et _image_ratio(). (Matthieu Marcillaud il y a 3 mois)
* f6cf9eddc - PHP 8.1 #4968 : Deprecated-- en relation avec `null` (Matthieu Marcillaud il y a 3 mois)
* 2056af7a2 - fix coding standards (b_b il y a 3 mois)
* 039abd60e - Fix #4980 : eviter des warning sur echec de recuperer_url() (Cerdic il y a 3 mois)
* ae80343e0 - Fix #4982 : tester l'existence de PRIMARY KEY sur arrivee et depart (Cerdic il y a 3 mois)
* 306975690 - Ne pas stocker formulaire_action_sign dans les configurations des plugins... (hum) (Cerdic il y a 3 mois)
* d02042098 - typo de phpdoc (b_b il y a 3 mois)
* 491469b32 - appliquer rawurlencode() aussi sur les tableaux qu'on passe en argument de parametre_url() #4819 (Cerdic il y a 3 mois)
* 3bb7a8473 - la fonction random() n'existe pas (b_b il y a 3 mois)
* e49a3158b - revert !486 & d8d83b4ce5 puisque generer_htpass() delegue à la fonction inc_generer_htpass_dist() si elle existe (b_b il y a 3 mois)
* d8d83b4ce - warning -- sur generer_htpass (b_b il y a 3 mois)
* 23131ebae - Convert to UTF-8 (David Prévot il y a 3 mois)
* 50b3edc1a - Drop executable bit (David Prévot il y a 3 mois)
* 6804184c6 - phpstan baseline, une erreur de moins (b_b il y a 3 mois)
* 59da9492a - déplacer corriger_extension() du plugin medias vers inc/documents du core (b_b il y a 3 mois)
* 10fe4dddf - prise en charge des fichier .jpeg lors de l'ajout d'un document distant (b_b il y a 3 mois)
* 545544616 - Ticket #4974 : proposition pour ne pas créer d’erreurs SQL simplement pour tester la présence d’une table. On ajoute une fonction sql_table_exists('nom_de_la_table') pour cela. (Matthieu Marcillaud il y a 3 mois)
* 8fce1d738 - Comparaisons strictes dans copie_locale() (Matthieu Marcillaud il y a 3 mois)
* 207ba2e83 - Éviter quelques @ dans le code. (Matthieu Marcillaud il y a 3 mois)
* 2bd61ac34 - Indentation (Matthieu Marcillaud il y a 3 mois)
* 5478ae4dc - Fix erreur fatale dans f32c85919 (Matthieu Marcillaud il y a 3 mois)
* 0496670f2 - bugfix : passer la bonne variable à generer_htpass() (b_b il y a 4 mois)
* f32c85919 - Journalisation : ajout de l'id et du nom de l'auteur, correction du message. fix #4913 (#4933) (MathieuAlphamosa il y a 4 mois)
* 9cd19c52a - Eviter une indefinie si get_spip_script() est appelé depuis mes_options, avant que la constante _SPIP_SCRIPT soit definie (Cerdic il y a 4 mois)
* 6e0488a68 - rétablir le traitement _TRAITEMENT_TYPO_SANS_NUMERO (multi, supprimer_numero, etc) sur la balise #NOM des auteurs (b_b il y a 4 mois)
* 1206050cf - sur certains sites on veut absolument garder certains caches on peut donc inhiber la purge de ces répertoires pour eviter tout probleme Il suffit d'y poser un fichier inhib_purger_repertoire.txt (ex: css référencées dans un CDN, images referencees par un moteur de recherche...) (Cerdic il y a 4 mois)
* 24ef44f42 - Ticket #4950 : Éviter que la date modif soit modifiée lors de la migration des logos. (Matthieu Marcillaud il y a 4 mois)
* daa06b4bb - Fix la perte des logos en cas de double migration : il faut repartir de IMG/logo/ deja cree a la premiere migration (cas typique : je reinjecte ma base SQL 3.2 parce que erreur lors de la migration et je rejoue l'upgrade) (Cerdic il y a 4 mois)
* a69bb3388 - Compat PHP 8 pour le filtre `|affdate` (b_b il y a 4 mois)
* ae72bf539 - fixes  #4956 éviter notice balise #SAUTER (+ éviter ligne inutile et variable inutilisée) (jluc il y a 4 mois)
* f8c7e03ec - Garder en mémoire l'état de l'objet avant toute modification, et le passer aux deux pipelines champs un args champs_anciens (tout comme dans l'instituer qui a statut_ancien, date_ancienne, etc) (RastaPopoulos il y a 4 mois)
* a4a09d103 - Ameliorer valider_url_distante() : on utilise filter_var plutot que des regexp et on ajoute un controle sur le TTL du domaine pour que ce que l'on valide soit la meme chose vue dans la suite du hit (Cerdic il y a 4 mois)
* 9b62eb115 - warning en moins sur envoyer_mail (b_b il y a 4 mois)
* acd0fcb95 - Ticket #4895 : fix partiel de label_ponctuer, en forçant un espace insécable en entités. (Matthieu Marcillaud il y a 4 mois)
* 84d72d8fc - (origin/issue_4946) form editer_logo : un timestamp sur lien d'aperçu dans une modale (b_b il y a 5 mois)
* 237699b2f - cohérence, double quotes tout le temps sur les attributs HTML (b_b il y a 5 mois)
* 64930e35e - Ticket #4852 : Apparence unifiée pour les select (MathieuAlphamosa il y a 5 mois)
* 255be8fb0 - Fix un problème recurent de fuite de données lorsque les utilisateur mettent un #FORMULAIRE_TRUC dans un modeles/xxxx.html : le formulaire perd son dynamisme quand il est inclus dans l'appelant, que ce soit via un <xxxx|> ou via un #MODELE{xxx}. On repere le cas est on injecte du PHP qui appelle la fonction executer_balise_dynamique() au lieu d'injecter l'appel a la fonction lui meme (Cerdic il y a 5 mois)
* 4856a41b5 - ne pas utiliser les valeur par défaut du picker en langue anglaise (b_b il y a 5 mois)
* b28807572 - Quelques simplifications d’écritures, toujours dans objet_modifier_champs (Matthieu Marcillaud il y a 5 mois)
* b7ee86856 - Ticket #4916 : petite optimisation de code sur `objet_modifier_champs` (JLuc) (Matthieu Marcillaud il y a 5 mois)
c795 - Éviter une notice PHP sur le débuggeur dans certaines situations. (Matthieu Marcillaud il y a 5 mois)
* e5c422501 - Tester _FILE_LOG avant de l'utiliser - Fixes #4929 (jluc il y a 5 mois)
* 67c3691d6 - Il arrive que le job ne soit déjà plus là (pierretux) + compléments de phpdoc - fixes #4907 (JLuc il y a 5 mois)
* 0f97dc638 - Petite amelioration pour ne rechercher que sur la partie du document rechargee apres un ajaxLoad (Cerdic il y a 5 mois)
* 71321e46a - assurer onAjaxLoad pour surligner les mots même lors d'un rechagrment des elements trouvés lors d'une recherche issue #4217 (touti il y a 5 mois)
* 6807e139d - utiliser charger_fonction() avant d'appeler generer_htpass() (b_b il y a 5 mois)
* 0cb8db67c - Fix l'icone d'alerte a droite en RTL sans erreur (Cerdic il y a 5 mois)
* 4c3c7146b - L'icone d'alerte a droite en RTL (George il y a 5 mois)
* 5213259b0 - Correction en RTL (George il y a 5 mois)
* a07606608 - Support des conditions imbriquees OR/AND dans la traduction du where SQL en filtre sur DATA : - lors de la traduction, on repere les operateurs AND et OR, et on traduit les 2 conditions associees de maniere recursive pour rendre un tableau de filtres precedes d'un AND ou OR (au passage on optimise les cas triviaux de type '...OR true' et '... AND false') - lors de l'assemblage, on concatene de maniere recursive les tableaux de filtres avec l'operateur approprie (le premier niveau etant toujours un AND) (Cerdic il y a 5 mois)
* 0f223c116 - Refactoring de la traduction des conditions SQL du where en filtres applicables sur tableau : tout le travail etait fait dans un double foreach au milieu d'une fonction, on eclate en composer/traduire/assembler qui rendent le code plus comprehensible et plus facile a faire evoluer (Cerdic il y a 5 mois)
* b877eb985 - ajout d'un fichier pour expliquer où trouver l'info pour signaler une faille (b_b il y a 5 mois)
* db979753b - Produire la miniature de 64px quand on edite le logo pour anticiper un eventuel plantage pour cause image trop grosse (Cerdic il y a 5 mois)
* 61d9fcee7 - Quand l'utilisateur mets une image lourde en fond d'écran pour le login, on a pas de background pendant tout le chargement, ce qui peut poser un soucis d'accessibilite. On fixe en ajoutant par defaut une miniature 64px en base64 dans la page de login. Le poids additionnel est de ~2ko, mais on a un rendu immediat avec les bonnes couleurs le temps que l'image HR charge (Cerdic il y a 5 mois)
* 219ac4a4c - Afficher le poids des logos en plus de leur dimension, car on a vite fait de perdre de vue qu'on envoie des logos dont le poids est pas très raisonable (et qui plus est il n'est pas possible d'avoir l'info sauf à télécharger l'image, ce qui est vite lourd) (Cerdic il y a 5 mois)
* 496ac0eff - RWD des svg <emb> : il faut aussi leur imposer un max-width et un height:auto (Luc) (Cerdic il y a 5 mois)
* 3b4ea27ad - Une passe de PSR. (Matthieu Marcillaud il y a 6 mois)
* 91e0b112d - Oups, erreur dans 1b8e4f404 il faut utiliser empty car on poste potentiellement une signature vide (empechait de se loger et sans doute de poster sur tout formulaire anonyme) (Cerdic il y a 6 mois)
* d593dc1c3 - Lors de l'upload de documents, gerer le cas des fichiers avec multiples extensions : on ne laisse que celles qui sont autorisees a l'upload si possible, sinon on ne garde que la derniere (Cerdic il y a 6 mois)
* 53100af75 - Refactoring du formatage du nom de fichier, pour plus de lisibilite (Cerdic il y a 6 mois)
* a418a76dd - Ne pas appliquer file_get_contents() sur une URL par megarde : faire un copie_locale si on reconnait l'URL, et un file_exists dans tous les cas (Cerdic il y a 6 mois)
* 1cf91def1 - Refactoring de distant : - sort dans une fonction dediee le code charge d'identifier l'extension d'un fichier selon les headers http et l'url source d'origine (code issu de la fonction recuperer_infos_distantes()) - on utilise cette nouvelle fonction distant_trouver_extension_selon_headers() pour identifier l'extension d'un fichier apres une copie locale et sanitizer le fichier local si besoin (Cerdic il y a 6 mois)
* ff314b286 - Coquille restante d'un renommage de l'option (Cerdic il y a 6 mois)
* 4ccf90a69 - Quand on fait une copie locale d'une image pour la filtrer ensuite, ne pas oublier de passer un coup de sanitizer si besoin (Cerdic il y a 6 mois)
* 9310cfe48 - Il faut incrementer spip_version_code car tous les formulaires doivent etre recalcules (Cerdic il y a 6 mois)
* d548391d7 - Nom, nom_site et bio etant des champs librement modifiables par les utilisateurs, on les protege comme des forums, via safehtml L'impact perf est reduit ici car dans les listes d'auteur seul le nom apparait, lequel ne contient en general pas de < ce qui passe tres vite dans safehtml (Cerdic il y a 6 mois)
* 1b8e4f404 - Balise #FORMULAIRE : nettoyer du code mort qui ne sert plus, ameliorer la securite en ajoutant une signature des arguments du formulaire dès que l'auteur identifié. A la reception on refuse un formulaire non signé si on a une session ou un formulaire signé si on a pas de session. Si on a une session, la signature doit etre identique. En absence de session on ne signe pas les arguments du formulaire car tout le monde a le droit de l'afficher, et ca permet de garder un cache identique commun a tous les hits anonymes (perf issue) (Cerdic il y a 6 mois)
* 1da3a1dd5 - Avec un plongeur qui fait 50vh, quand on le déplie le bouton Choisir est la plupart du temps sous la ligne de flotaison, et on ne le voit pas, ce qui est assez perturbant. On réduit donc la hauteur en ajoutant un min-height pour les petits ecrans (Cerdic il y a 6 mois)
* e72d55de8 - Accélerer SPIP 4 en retrouvant les fichiers cache au lieu de calculer à chaque fois, ça ira mieux :) (merci à Christophe Imberti pour l'identification du bug et de sa résolution) (Cerdic il y a 6 mois)
* 542facacc - Utilisation de la constante _IMG_ADMIN_MAX_WIDTH (MathieuAlphamosa il y a 6 mois)
* 749346b3f - Utilisation de la constante _IMG_ADMIN_MAX_WIDTH (MathieuAlphamosa il y a 6 mois)
* fdf2203df - Utilisation de la constante _IMG_ADMIN_MAX_WIDTH (MathieuAlphamosa il y a 6 mois)
* 5d9f1a9df - Ajout d'une constante pour définir la largeur maximale des images (MathieuAlphamosa il y a 6 mois)
* f9e98c2ca - réduction des images à 768px (MathieuAlphamosa il y a 6 mois)
* 7cc326eba - réduction des images à 768px (MathieuAlphamosa il y a 6 mois)
* 5aa66ddb3 - réduction des images à 768px (MathieuAlphamosa il y a 6 mois)
* 2b81486f8 - Complement de 413ca3cc58 : _mysql_traite_query() s'appelle recursivement, elle ne doit echapper les textes qu'au premier appel, car ensuite ce n'est plus necessaire et elle risquerait potentiellement de melanger les pourcents de substitutions/remplacement dans query_reinjecte_textes() (Cerdic il y a 6 mois)
* 14d9451e4 - Attention, coquille : defaut sert a remplir ce qui n'est pas dans options (Cerdic il y a 6 mois)
* 02c270729 - définir `$primary` dans `styliser_modele()` (b_b il y a 6 mois)
* 0705e551f - Correction de label_nettoyer() pour ne pas qu’il mange certains caractères utf8. trim() n’est pas multibytes : on doit donc utiliser preg si on veut enlever un éventuel `\u{a0}`. Cela dit ce caractère n’est pas présent dans les chaines de langues SPIP, mais ça permet d’utiliser peut être label_nettoyer() pour d’autres utilisations dont on connait moins la source. (Matthieu Marcillaud il y a 7 mois)
* 2c90e6e0b - Simplifier la regexp, c'est pas plus mal (cfreal) (Cerdic il y a 7 mois)
* 5ba19e1d1 - Permettre de reset les codeEchappements quand on fournit un uniqid, pour les tests unitaires (Cerdic il y a 7 mois)
* 413ca3cc5 - Fix/refactoring query_echappe_textes() qui ne detectait parfois pas completement et correctement les chaines On robustifie la fonction avec controle en amont et en aval, en preferant ne rien faire si on a un doute plutot que de risquer de casser la requete sql. On en profite pour rendre encore moins plausible la presence des sequences d'echappement dans la chaine Et on modernise le code dans query_reinjecte_textes() en utilisant un argument splat (Cerdic il y a 7 mois)
* 1baec0c91 - autocomplete=off n'est plus trop supporte par les browsers modernes sur les champs de login car ils proposent le remplissage via le gestionnaire de password. Il faut utiliser autocomplete=new-password a la place Sans cela, quand on editer un auteur certains browsers remplissaient automatiquement avec le login/pass memorise pour le site et/ou memorisaient le login/pass de l'auteur concerne dans ses propres mots de passe. (Cerdic il y a 7 mois)
* b222799eb - Ticket #4895 : 1 seul espace insécable sur la chaine label_ponctuer. C’est salvatore qui l’a doublé dans daee5e6 ; à voir si ça se reproduit… (Matthieu Marcillaud il y a 7 mois)
* 19c9e7d73 - Corriger quelques problèmes d’intentations non traités par phpcs (Matthieu Marcillaud il y a 7 mois)
* 9f2fe8a26 - Ajout de l’instruction `GREATEST` (de mysql) pour SQLite en la mappant sur `max()` Étonnamment il y avait déjà `LEAST` de traité. On modifie LEAST aussi pour acceper une infinité d’arguments (pas 3 seulement). (Matthieu Marcillaud il y a 7 mois)
* fd7d22fe5 - Essentiellement du PHPDoc sur les fonctions de mapping SQLite (Matthieu Marcillaud il y a 7 mois)
* 19bd3b062 - Nettoyage de commentaires de code (Matthieu Marcillaud il y a 7 mois)
* ec6233ac6 - On remplace http_status par http_response_code disponble depuis php 5.4 (Pierretux il y a 7 mois)
* e7cf72378 - Tag deprecated sur la fonction http_status() (Pierretux il y a 7 mois)
* dae4b3bdc - Mise à jour du code de http_status pour utiliser directement la function de php (Pierretux il y a 7 mois)
* 30a1917f9 - Pas besoin d’être autant `$explicite`. Une seule affectation suffit. (Matthieu Marcillaud il y a 7 mois)
* 00feac867 - feat(phpstan) : Mise en place de l'outil (JamesRezo il y a 7 mois)
* f0f2577c3 - sql_in() ne permet pas de passer le type du champ a quoter, ce qui peut etre un probleme #4862 On introduit la fonction sql_in_quote() plus complete, qui accepte un type, et n'accepte plus non plus de string pour valeurs La fonction sql_in() continue d'exister comme un shorthand ou pour les cas ou valeurs est une string (et donc explicitement une liste de valeurs numeriques) (Cerdic il y a 7 mois)
* 9f0d1ebf2 - inclusion de filtres_images_lib_mini pour pouvoir appeler image_graver (MathieuAlphamosa il y a 7 mois)
* 6b28f6245 - Tenir compte de _PASS_LONGUEUR_MINI pour la génération de mots de passe (Glop il y a 7 mois)
* feea64d72 - Fix #4866 : tous les comptages de rang se font avec un where calcule via la fonction lien_rang_where() qui par defaut calcule un where correspondant a "tous les id_xx associes a objet-id_objet" mais dont le comportement peut etre personalise au cas par cas via une fonction perso lien_rang_where_{$table_lien}() Par ailleurs si un rang_lien est fourni lors de l'appel a objet_associer() il est directement pris en compte pour l'insertion du lien plutot que de calculer un rang automatique, inserer avec ce rang, puis modifier ensuite Enfin, dans ce dernier cas, on appelle pas lien_ordonner() immediatement apres l'insertion, mais on laisse d'abord lien_set() faire son job (a savoir eventuellement mettre le meme rang sur les autres liens id_xx/objet/id_objet identiques mais avec un role different), et finalement finir par un lien_ordonner() (Cerdic il y a 7 mois)

* 7fa313e1a - cs-autofixes (#4868) (JamesRezo il y a 7 mois)
*   1891d33bd - Merge pull request 'feat(spip/coding-standards): Mise en place de l'outil' (#4865) from cs-tool into master (JamesRezo il y a 7 mois)
|\
| * 2ce1f8883 - feat(spip/coding-standards): Mise en place de l'outil (JamesRezo il y a 7 mois)
|/
* f8a0525ab - Fix style des modeles de document qui n'etaient pas complets (ie les players embed collent a gauche dans ecrire/) (Cerdic il y a 7 mois)

* 73df971c4 - Définir boucle->primary aussi pour la création d’une boucle en PHP car des plugins (via le pipeline pre_boucle) s’attendent très logiquement à sa présence, notamment Magnet. (Matthieu Marcillaud il y a 8 mois)
* 55fb31a74 - Oubli dans 2b3d16f0057e105fa5673804dcdf1070d560da16 : il faut aussi prendre en compte la constante _DEBUG_TRACE_QUERIES dans req/mysql pour appeler trace_query_start (Cerdic il y a 8 mois)
* ff2ac0a38 - Eviter des notices sur l'utilisation des balises #GET/#ENV/#SESSION : on utiliser l'operateur ?? et on evite des @ (Cerdic il y a 8 mois)
* 6a6422c06 - Petit bug vicieux sur le bouton de vidage de cache quand on est en mode _CACHE_CONTEXTES_AJAX : - le bouton 'vider le cache' vide les caches, et donc les contextes ajax - la redirection apres action de purge se fait en ajax, mais plus de contexte, donc erreur 400 - l'erreur etait bien traitee sur les simples liens ajax, mais pas sur les boutons actions ajax, ce qu'on corrige donc ici En sus, on ajoute un style sur les .ajaxbloc.invalid ce qui permet d'avoir un retour visuel pendant que la redirection non ajax se fait (Cerdic il y a 8 mois)
* f4bae900f - Des logs un peu plus consistants (Cerdic il y a 8 mois)
* 09b169a31 - l'inclusion de inc/autoriser n'est pas toujours faites en amont (Cerdic il y a 8 mois)
* 7208c33c0 - Utilisons _IS_CLI pour differencier la sortie lors de l'install/maj des plugin ou du core (Cerdic il y a 8 mois)
* e1dd730b7 - inclure les fichiers de fonctions avant d'appliquer les traitements - fixes https://git.spip.net/spip-contrib-extensions/crayons/issues/10 (JLuc il y a 8 mois)
* 04dfc5738 - Bugfix sqlite sur le traitement des cas `SELECT 0 as num + ORDER BY num` : avec l'ajout d'une clause sinum systematique, on avait maintenant un `ORDER BY sinum, num` qui se transformait en `ORDER BY siVIDE(), VIDE()` invalide Cela dit, avec un sqlite 3.28 ce remplacement parait superflu, sa suppression serait peut-être judicieuse ? (Cerdic il y a 8 mois)
* 63ef063b1 - Un argument supplementaire $callback_prefix qui permet d'utiliser des callback prefixees ou dans un namespace sans devoir ecraser le comportement par defaut pour un besoin precis (notamment tests unitaires) (Cerdic il y a 8 mois)
* 515cb3746 - Fix les formulaires qui ont des .editer-groupe dans les .choix, comme facteur ou mailshot, pour afficher des sous-parties conditionnees au choix (Cerdic il y a 9 mois)
* dfa4f25dc - Eviter une indefinie en CLI + utiliser une egalite stricte === (Cerdic il y a 9 mois)
* bc13d2e3c - Suppression d'un debug JS (#4847) (MathieuAlphamosa il y a 9 mois)
* 93a270c85 - Un selecteur CSS + restrictif car sinon cela impacte par exemple tous les .label d'un formidable dans le texte d'un article (mais aussi, c'est peut-être une erreur de formidable d'utiliser un nommage de classe si generique a cet endroit) (Cerdic il y a 9 mois)
* 534a4f78e - On ne gère plus les plugin.xml on a dit. Suppression de quelques reliquats de code. (Matthieu Marcillaud il y a 9 mois)
* 09a95cdf1 - PHPDoc erroné. (Matthieu Marcillaud il y a 9 mois)

* 9e6c6c5d4 - Ici, c'est la future 4.1 en développement. (Matthieu Marcillaud il y a 9 mois)
## [4.0.x]

