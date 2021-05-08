<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Déclaration de filtres pour les squelettes
 *
 * @package SPIP\Core\Filtres
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');
include_spip('inc/filtres_mini');
include_spip('inc/filtres_dates');
include_spip('inc/filtres_selecteur_generique');
include_spip('base/objets');
include_spip('public/fonctions'); // charger les fichiers fonctions

/**
 * Charger un filtre depuis le php
 *
 * - on inclue tous les fichiers fonctions des plugins et du skel
 * - on appelle chercher_filtre
 *
 * Pour éviter de perdre le texte si le filtre demandé est introuvable,
 * on transmet `filtre_identite_dist` en filtre par défaut.
 *
 * @uses filtre_identite_dist() Comme fonction par défaut
 *
 * @param string $fonc Nom du filtre
 * @param string $default Filtre par défaut
 * @return string Fonction PHP correspondante du filtre
 */
function charger_filtre($fonc, $default = 'filtre_identite_dist') {
	include_fichiers_fonctions(); // inclure les fichiers fonctions
	return chercher_filtre($fonc, $default);
}

/**
 * Retourne le texte tel quel
 *
 * @param string $texte Texte
 * @return string Texte
 **/
function filtre_identite_dist($texte) { return $texte; }

/**
 * Cherche un filtre
 *
 * Pour une filtre `F` retourne la première fonction trouvée parmis :
 *
 * - filtre_F
 * - filtre_F_dist
 * - F
 *
 * Peut gérer des appels par des fonctions statiques de classes tel que `Foo::Bar`
 *
 * En absence de fonction trouvée, retourne la fonction par défaut indiquée.
 *
 * @param string $fonc
 *     Nom du filtre
 * @param null $default
 *     Nom du filtre appliqué par défaut si celui demandé n'est pas trouvé
 * @return string
 *     Fonction PHP correspondante du filtre demandé
 */
function chercher_filtre($fonc, $default = null) {
	if (!$fonc) {
		return $default;
	}
	// Cas des types mime, sans confondre avec les appels de fonction de classe
	// Foo::Bar
	// qui peuvent etre avec un namespace : space\Foo::Bar
	if (preg_match(',^[\w]+/,', $fonc)) {
		$nom = preg_replace(',\W,', '_', $fonc);
		$f = chercher_filtre($nom);
		// cas du sous-type MIME sans filtre associe, passer au type:
		// si filtre_text_plain pas defini, passe a filtre_text
		if (!$f and $nom !== $fonc) {
			$f = chercher_filtre(preg_replace(',\W.*$,', '', $fonc));
		}

		return $f;
	}

	include_fichiers_fonctions();
	foreach (array('filtre_' . $fonc, 'filtre_' . $fonc . '_dist', $fonc) as $f) {
		trouver_filtre_matrice($f); // charge des fichiers spécifiques éventuels
		// fonction ou name\space\fonction
		if (is_callable($f)) {
			return $f;
		}
		// méthode statique d'une classe Classe::methode ou name\space\Classe::methode
		elseif (false === strpos($f, '::') and is_callable(array($f))) {
			return $f;
		}
	}

	return $default;
}

/**
 * Applique un filtre s'il existe, sinon retourne une chaîne vide
 *
 * Fonction générique qui prend en argument l’objet (texte, etc) à modifier
 * et le nom du filtre.
 *
 * - À la différence de la fonction `filtrer()`, celle-ci ne lève
 *   pas d'erreur de squelettes si le filtre n'est pas trouvé.
 * - À la différence de la fonction `appliquer_si_filtre()` le contenu
 *   d'origine n'est pas retourné si le filtre est absent.
 *
 * Les arguments supplémentaires transmis à cette fonction sont utilisés
 * comme arguments pour le filtre appelé.
 *
 * @example
 *      ```
 *      [(#BALISE|appliquer_filtre{nom_du_filtre})]
 *      [(#BALISE|appliquer_filtre{nom_du_filtre, arg1, arg2, ...})]
 *
 *      // Applique le filtre minifier si on le trouve :
 *      // - Ne retourne rien si le filtre 'minifier' n'est pas trouvé
 *      [(#INCLURE{fichier.js}|appliquer_filtre{minifier, js})]
 *
 *      // - Retourne le contenu du fichier.js si le filtre n'est pas trouvé.
 *      [(#INCLURE{fichier.js}|appliquer_si_filtre{minifier, js})]
 *      ```
 *
 * @filtre
 * @see filtrer() Génère une erreur si le filtre est absent
 * @see appliquer_si_filtre() Proche : retourne le texte d'origine si le filtre est absent
 * @uses appliquer_filtre_sinon()
 *
 * @param mixed $arg
 *     Texte (le plus souvent) sur lequel appliquer le filtre
 * @param string $filtre
 *     Nom du filtre à appliquer
 * @return string
 *     Texte traité par le filtre si le filtre existe,
 *     Chaîne vide sinon.
 **/
function appliquer_filtre($arg, $filtre) {
	$args = func_get_args();
	return appliquer_filtre_sinon($arg, $filtre, $args, '');
}

/**
 * Applique un filtre s'il existe, sinon retourne le contenu d'origine sans modification
 *
 * Se référer à `appliquer_filtre()` pour les détails.
 *
 * @example
 *      ```
 *      [(#INCLURE{fichier.js}|appliquer_si_filtre{minifier, js})]
 *      ```
 * @filtre
 * @see appliquer_filtre() Proche : retourne vide si le filtre est absent
 * @uses appliquer_filtre_sinon()
 *
 * @param mixed $arg
 *     Texte (le plus souvent) sur lequel appliquer le filtre
 * @param string $filtre
 *     Nom du filtre à appliquer
 * @return string
 *     Texte traité par le filtre si le filtre existe,
 *     Texte d'origine sinon
 **/
function appliquer_si_filtre($arg, $filtre) {
	$args = func_get_args();
	return appliquer_filtre_sinon($arg, $filtre, $args, $arg);
}

/**
 * Retourne la version de SPIP
 *
 * Si l'on retrouve un numéro de révision GIT ou SVN, il est ajouté entre crochets.
 * Si effectivement le SPIP est installé par Git ou Svn, 'GIT' ou 'SVN' est ajouté avant sa révision.
 *
 * @global spip_version_affichee Contient la version de SPIP
 * @uses version_vcs_courante() Pour trouver le numéro de révision
 *
 * @return string
 *     Version de SPIP
 **/
function spip_version() {
	$version = $GLOBALS['spip_version_affichee'];
	if ($vcs_version = version_vcs_courante(_DIR_RACINE)) {
		$version .= " $vcs_version";
	}

	return $version;
}

/**
 * Retourne une courte description d’une révision VCS d’un répertoire
 *
 * @param string $dir Le répertoire à tester
 * @param array $raw True pour avoir les données brutes, false pour un texte à afficher
 * @retun string|array|null
 *    - array|null si $raw = true,
 *    - string|null si $raw = false
 */
function version_vcs_courante($dir, $raw = false) {
	$desc = decrire_version_git($dir);
	if ($desc === null) {
		$desc = decrire_version_svn($dir);
	}
	if ($desc === null or $raw) {
		return $desc;
	}
	// affichage "GIT [master: abcdef]"
	$commit = isset($desc['commit_short']) ? $desc['commit_short'] : $desc['commit'];
	if ($desc['branch']) {
		$commit = $desc['branch'] . ': ' . $commit;
	}
	return "{$desc['vcs']} [$commit]";
}

/**
 * Retrouve un numéro de révision Git d'un répertoire
 *
 * @param string $dir Chemin du répertoire
 * @return array|null
 *      null si aucune info trouvée
 *      array ['branch' => xx, 'commit' => yy] sinon.
 **/
function decrire_version_git($dir) {
	if (!$dir) {
		$dir = '.';
	}

	// version installee par GIT
	if (lire_fichier($dir . '/.git/HEAD', $c)) {
		$currentHead = trim(substr($c, 4));
		if (lire_fichier($dir . '/.git/' . $currentHead, $hash)) {
			return [
				'vcs' => 'GIT',
				'branch' => basename($currentHead),
				'commit' => trim($hash),
				'commit_short' => substr(trim($hash), 0, 8),
			];
		}
	}

	return null;
}


/**
 * Retrouve un numéro de révision Svn d'un répertoire
 *
 * @param string $dir Chemin du répertoire
 * @return array|null
 *      null si aucune info trouvée
 *      array ['commit' => yy, 'date' => xx, 'author' => xx] sinon.
 **/
function decrire_version_svn($dir) {
	if (!$dir) {
		$dir = '.';
	}
	// version installee par SVN
	if (file_exists($dir . '/.svn/wc.db') && class_exists('SQLite3')) {
		$db = new SQLite3($dir . '/.svn/wc.db');
		$result = $db->query('SELECT changed_revision FROM nodes WHERE local_relpath = "" LIMIT 1');
		if ($result) {
			$row = $result->fetchArray();
			if ($row['changed_revision'] != "") {
				return [
					'vcs' => 'SVN',
					'branch' => '',
					'commit' => $row['changed_revision'],
				];
			}
		}
	}
	return null;
}

/**
 * Retrouve un numéro de révision SVN d'un répertoire
 *
 * Mention de la révision SVN courante d'un répertoire
 * /!\ Retourne un nombre négatif si on est sur .svn
 *
 * @deprecated Utiliser version_vcs_courante()
 * @param string $dir Chemin du répertoire
 * @return int
 *
 *     - 0 si aucune info trouvée
 *     - -NN (entier) si info trouvée par .svn/wc.db
 *
 **/
function version_svn_courante($dir) {
	if ($desc = decrire_version_svn($dir)) {
		return -$desc['commit'];
	}
	return 0;
}

// La matrice est necessaire pour ne filtrer _que_ des fonctions definies dans filtres_images
// et laisser passer les fonctions personnelles baptisees image_...
$GLOBALS['spip_matrice']['image_graver'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_select'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire_par'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_passe_partout'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_recadre_avec_fallback'] = true;//'inc/filtres_images_mini.php';

$GLOBALS['spip_matrice']['couleur_html_to_hex'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_hex_to_hsl'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_hex_to_rgb'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_foncer'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_eclaircir'] = 'inc/filtres_images_mini.php';

// ou pour inclure un script au moment ou l'on cherche le filtre
$GLOBALS['spip_matrice']['filtre_image_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_audio_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_video_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_application_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_message_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_multipart_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_csv_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_html_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_audio_x_pn_realaudio'] = 'inc/filtres_mime.php';


/**
 * Charge et exécute un filtre (graphique ou non)
 *
 * Recherche la fonction prévue pour un filtre (qui peut être un filtre graphique `image_*`)
 * et l'exécute avec les arguments transmis à la fonction, obtenus avec `func_get_args()`
 *
 * @api
 * @uses image_filtrer() Pour un filtre image
 * @uses chercher_filtre() Pour un autre filtre
 *
 * @param string $filtre
 *     Nom du filtre à appliquer
 * @return string
 *     Code HTML retourné par le filtre
 **/
function filtrer($filtre) {
	$tous = func_get_args();
	if (trouver_filtre_matrice($filtre) and substr($filtre, 0, 6) == 'image_') {
		return image_filtrer($tous);
	} elseif ($f = chercher_filtre($filtre)) {
		array_shift($tous);
		return call_user_func_array($f, $tous);
	} else {
		// le filtre n'existe pas, on provoque une erreur
		$msg = array('zbug_erreur_filtre', array('filtre' => texte_script($filtre)));
		erreur_squelette($msg);
		return '';
	}
}

/**
 * Cherche un filtre spécial indiqué dans la globale `spip_matrice`
 * et charge le fichier éventuellement associé contenant le filtre.
 *
 * Les filtres d'images par exemple sont déclarés de la sorte, tel que :
 * ```
 * $GLOBALS['spip_matrice']['image_reduire'] = true;
 * $GLOBALS['spip_matrice']['image_monochrome'] = 'filtres/images_complements.php';
 * ```
 *
 * @param string $filtre
 * @return bool true si on trouve le filtre dans la matrice, false sinon.
 */
function trouver_filtre_matrice($filtre) {
	if (isset($GLOBALS['spip_matrice'][$filtre]) and is_string($f = $GLOBALS['spip_matrice'][$filtre])) {
		find_in_path($f, '', true);
		$GLOBALS['spip_matrice'][$filtre] = true;
	}
	return !empty($GLOBALS['spip_matrice'][$filtre]);
}


/**
 * Filtre `set` qui sauve la valeur en entrée dans une variable
 *
 * La valeur pourra être retrouvée avec `#GET{variable}`.
 *
 * @example
 *     `[(#CALCUL|set{toto})]` enregistre le résultat de `#CALCUL`
 *     dans la variable `toto` et renvoie vide.
 *     C'est équivalent à `[(#SET{toto, #CALCUL})]` dans ce cas.
 *     `#GET{toto}` retourne la valeur sauvegardée.
 *
 * @example
 *     `[(#CALCUL|set{toto,1})]` enregistre le résultat de `#CALCUL`
 *      dans la variable toto et renvoie la valeur. Cela permet d'utiliser
 *      d'autres filtres ensuite. `#GET{toto}` retourne la valeur.
 *
 * @filtre
 * @param array $Pile Pile de données
 * @param mixed $val Valeur à sauver
 * @param string $key Clé d'enregistrement
 * @param bool $continue True pour retourner la valeur
 * @return mixed
 */
function filtre_set(&$Pile, $val, $key, $continue = null) {
	$Pile['vars'][$key] = $val;
	return $continue ? $val : '';
}

/**
 * Filtre `setenv` qui enregistre une valeur dans l'environnement du squelette
 *
 * La valeur pourra être retrouvée avec `#ENV{variable}`.
 * 
 * @example
 *     `[(#CALCUL|setenv{toto})]` enregistre le résultat de `#CALCUL`
 *      dans l'environnement toto et renvoie vide.
 *      `#ENV{toto}` retourne la valeur.
 *
 *      `[(#CALCUL|setenv{toto,1})]` enregistre le résultat de `#CALCUL`
 *      dans l'environnement toto et renvoie la valeur.
 *      `#ENV{toto}` retourne la valeur.
 *
 * @filtre
 *
 * @param array $Pile
 * @param mixed $val Valeur à enregistrer
 * @param mixed $key Nom de la variable
 * @param null|mixed $continue Si présent, retourne la valeur en sortie
 * @return string|mixed Retourne `$val` si `$continue` présent, sinon ''.
 */
function filtre_setenv(&$Pile, $val, $key, $continue = null) {
	$Pile[0][$key] = $val;
	return $continue ? $val : '';
}

/**
 * @param array $Pile
 * @param array|string $keys
 * @return string
 */
function filtre_sanitize_env(&$Pile, $keys) {
	$Pile[0] = spip_sanitize_from_request($Pile[0], $keys);
	return '';
}


/**
 * Filtre `debug` qui affiche un debug de la valeur en entrée
 *
 * Log la valeur dans `debug.log` et l'affiche si on est webmestre.
 *
 * @example
 *     `[(#TRUC|debug)]` affiche et log la valeur de `#TRUC`
 * @example
 *     `[(#TRUC|debug{avant}|calcul|debug{apres}|etc)]`
 *     affiche la valeur de `#TRUC` avant et après le calcul,
 *     en précisant "avant" et "apres".
 *
 * @filtre
 * @link https://www.spip.net/5695
 * @param mixed $val La valeur à debugguer
 * @param mixed|null $key Clé pour s'y retrouver
 * @return mixed Retourne la valeur (sans la modifier).
 */
function filtre_debug($val, $key = null) {
	$debug = (
		is_null($key) ? '' : (var_export($key, true) . " = ")
		) . var_export($val, true);

	include_spip('inc/autoriser');
	if (autoriser('webmestre')) {
		echo "<div class='spip_debug'>\n", $debug, "</div>\n";
	}

	spip_log($debug, 'debug');

	return $val;
}


/**
 * Exécute un filtre image
 *
 * Fonction générique d'entrée des filtres images.
 * Accepte en entrée :
 *
 * - un texte complet,
 * - un img-log (produit par #LOGO_XX),
 * - un tag `<img ...>` complet,
 * - un nom de fichier *local* (passer le filtre `|copie_locale` si on veut
 *   l'appliquer à un document distant).
 *
 * Applique le filtre demande à chacune des occurrences
 *
 * @param array $args
 *     Liste des arguments :
 *
 *     - le premier est le nom du filtre image à appliquer
 *     - le second est le texte sur lequel on applique le filtre
 *     - les suivants sont les arguments du filtre image souhaité.
 * @return string
 *     Texte qui a reçu les filtres
 **/
function image_filtrer($args) {
	$filtre = array_shift($args); # enlever $filtre
	$texte = array_shift($args);
	if (!strlen($texte)) {
		return;
	}
	find_in_path('filtres_images_mini.php', 'inc/', true);
	statut_effacer_images_temporaires(true); // activer la suppression des images temporaires car le compilo finit la chaine par un image_graver
	// Cas du nom de fichier local
	$is_file = trim($texte);
	if (strpos(substr($is_file, strlen(_DIR_RACINE)), '..') !== false
		  or strpbrk($is_file, "<>\n\r\t") !== false
		  or strpos($is_file, '/') === 0
	) {
		$is_file = false;
	}
	if ($is_file) {
		$is_local_file = function($path) {
			if (strpos($path, "?") !== false) {
				$path = supprimer_timestamp($path);
				// remove ?24px added by find_in_theme on .svg files
				$path = preg_replace(",\?[[:digit:]]+(px)$,", "", $path);
			}
			return file_exists($path);
		};
		if ($is_local_file($is_file) or tester_url_absolue($is_file)) {
			array_unshift($args, "<img src='$is_file' />");
			$res = call_user_func_array($filtre, $args);
			statut_effacer_images_temporaires(false); // desactiver pour les appels hors compilo
			return $res;
		}
	}

	// Cas general : trier toutes les images, avec eventuellement leur <span>
	if (preg_match_all(
		',(<([a-z]+) [^<>]*spip_documents[^<>]*>)?\s*(<img\s.*>),UimsS',
		$texte, $tags, PREG_SET_ORDER)) {
		foreach ($tags as $tag) {
			$class = extraire_attribut($tag[3], 'class');
			if (!$class or
				(strpos($class, 'filtre_inactif') === false
					// compat historique a virer en 3.2
					and strpos($class, 'no_image_filtrer') === false)
			) {
				array_unshift($args, $tag[3]);
				if ($reduit = call_user_func_array($filtre, $args)) {
					// En cas de span spip_documents, modifier le style=...width:
					if ($tag[1]) {
						$w = extraire_attribut($reduit, 'width');
						if (!$w and preg_match(",width:\s*(\d+)px,S", extraire_attribut($reduit, 'style'), $regs)) {
							$w = $regs[1];
						}
						if ($w and ($style = extraire_attribut($tag[1], 'style'))) {
							$style = preg_replace(",width:\s*\d+px,S", "width:${w}px", $style);
							$replace = inserer_attribut($tag[1], 'style', $style);
							$texte = str_replace($tag[1], $replace, $texte);
						}
					}
					// traiter aussi un eventuel mouseover
					if ($mouseover = extraire_attribut($reduit, 'onmouseover')) {
						if (preg_match(",this[.]src=['\"]([^'\"]+)['\"],ims", $mouseover, $match)) {
							$srcover = $match[1];
							array_shift($args);
							array_unshift($args, "<img src='" . $match[1] . "' />");
							$srcover_filter = call_user_func_array($filtre, $args);
							$srcover_filter = extraire_attribut($srcover_filter, 'src');
							$reduit = str_replace($srcover, $srcover_filter, $reduit);
						}
					}
					$texte = str_replace($tag[3], $reduit, $texte);
				}
				array_shift($args);
			}
		}
	}
	statut_effacer_images_temporaires(false); // desactiver pour les appels hors compilo
	return $texte;
}

/**
 * Retourne les tailles d'une image
 *
 * Pour les filtres `largeur` et `hauteur`
 *
 * @param string $img
 *     Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 * @return array
 *     Liste (hauteur, largeur) en pixels
 **/
function taille_image($img, $force_refresh = false) {

	static $largeur_img = array(), $hauteur_img = array();
	$srcWidth = 0;
	$srcHeight = 0;

	$src = extraire_attribut($img, 'src');

	if (!$src) {
		$src = $img;
	} else {
		$srcWidth = extraire_attribut($img, 'width');
		$srcHeight = extraire_attribut($img, 'height');
	}

	// ne jamais operer directement sur une image distante pour des raisons de perfo
	// la copie locale a toutes les chances d'etre la ou de resservir
	if (tester_url_absolue($src)) {
		include_spip('inc/distant');
		$fichier = copie_locale($src);
		$src = $fichier ? _DIR_RACINE . $fichier : $src;
	}
	if (($p = strpos($src, '?')) !== false) {
		$src = substr($src, 0, $p);
	}

	$srcsize = false;
	if (isset($largeur_img[$src]) and !$force_refresh) {
		$srcWidth = $largeur_img[$src];
	}
	if (isset($hauteur_img[$src]) and !$force_refresh) {
		$srcHeight = $hauteur_img[$src];
	}
	if (!$srcWidth or !$srcHeight) {

		if (file_exists($src)
			and $srcsize = spip_getimagesize($src)
		) {
			if (!$srcWidth) {
				$largeur_img[$src] = $srcWidth = $srcsize[0];
			}
			if (!$srcHeight) {
				$hauteur_img[$src] = $srcHeight = $srcsize[1];
			}
		}
		elseif(strpos($src, "<svg") !== false) {
			include_spip('inc/svg');
			if ($attrs = svg_lire_attributs($src)){
				list($width, $height, $viewbox) = svg_getimagesize_from_attr($attrs);
				if (!$srcWidth){
					$largeur_img[$src] = $srcWidth = $width;
				}
				if (!$srcHeight){
					$hauteur_img[$src] = $srcHeight = $height;
				}
			}
		}
		// $src peut etre une reference a une image temporaire dont a n'a que le log .src
		// on s'y refere, l'image sera reconstruite en temps utile si necessaire
		elseif (@file_exists($f = "$src.src")
			and lire_fichier($f, $valeurs)
			and $valeurs = unserialize($valeurs)
		) {
			if (!$srcWidth) {
				$largeur_img[$src] = $srcWidth = $valeurs["largeur_dest"];
			}
			if (!$srcHeight) {
				$hauteur_img[$src] = $srcHeight = $valeurs["hauteur_dest"];
			}
		}
	}

	return array($srcHeight, $srcWidth);
}


/**
 * Retourne la largeur d'une image
 *
 * @filtre
 * @link https://www.spip.net/4296
 * @uses taille_image()
 * @see  hauteur()
 *
 * @param string $img
 *     Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 * @return int|null
 *     Largeur en pixels, NULL ou 0 si aucune image.
 **/
function largeur($img) {
	if (!$img) {
		return;
	}
	list($h, $l) = taille_image($img);

	return $l;
}

/**
 * Retourne la hauteur d'une image
 *
 * @filtre
 * @link https://www.spip.net/4291
 * @uses taille_image()
 * @see  largeur()
 *
 * @param string $img
 *     Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 * @return int|null
 *     Hauteur en pixels, NULL ou 0 si aucune image.
 **/
function hauteur($img) {
	if (!$img) {
		return;
	}
	list($h, $l) = taille_image($img);

	return $h;
}


/**
 * Échappement des entités HTML avec correction des entités « brutes »
 *
 * Ces entités peuvent être générées par les butineurs lorsqu'on rentre des
 * caractères n'appartenant pas au charset de la page [iso-8859-1 par défaut]
 *
 * Attention on limite cette correction aux caracteres « hauts » (en fait > 99
 * pour aller plus vite que le > 127 qui serait logique), de manière à
 * préserver des eéhappements de caractères « bas » (par exemple `[` ou `"`)
 * et au cas particulier de `&amp;` qui devient `&amp;amp;` dans les URL
 *
 * @see corriger_toutes_entites_html()
 * @param string $texte
 * @return string
 **/
function corriger_entites_html($texte) {
	if (strpos($texte, '&amp;') === false) {
		return $texte;
	}

	return preg_replace(',&amp;(#[0-9][0-9][0-9]+;|amp;),iS', '&\1', $texte);
}

/**
 * Échappement des entités HTML avec correction des entités « brutes » ainsi
 * que les `&amp;eacute;` en `&eacute;`
 *
 * Identique à `corriger_entites_html()` en corrigeant aussi les
 * `&amp;eacute;` en `&eacute;`
 *
 * @see corriger_entites_html()
 * @param string $texte
 * @return string
 **/
function corriger_toutes_entites_html($texte) {
	if (strpos($texte, '&amp;') === false) {
		return $texte;
	}

	return preg_replace(',&amp;(#?[a-z0-9]+;),iS', '&\1', $texte);
}

/**
 * Échappe les `&` en `&amp;`
 *
 * @param string $texte
 * @return string
 **/
function proteger_amp($texte) {
	return str_replace('&', '&amp;', $texte);
}


/**
 * Échappe en entités HTML certains caractères d'un texte
 *
 * Traduira un code HTML en transformant en entités HTML les caractères
 * en dehors du charset de la page ainsi que les `"`, `<` et `>`.
 *
 * Ceci permet d’insérer le texte d’une balise dans un `<textarea> </textarea>`
 * sans dommages.
 *
 * @filtre
 * @link https://www.spip.net/4280
 *
 * @uses echappe_html()
 * @uses echappe_retour()
 * @uses proteger_amp()
 * @uses corriger_entites_html()
 * @uses corriger_toutes_entites_html()
 *
 * @param string $texte
 *   chaine a echapper
 * @param bool $tout
 *   corriger toutes les `&amp;xx;` en `&xx;`
 * @param bool $quote
 *   Échapper aussi les simples quotes en `&#039;`
 * @return mixed|string
 */
function entites_html($texte, $tout = false, $quote = true) {
	if (!is_string($texte) or !$texte
		or strpbrk($texte, "&\"'<>") == false
	) {
		return $texte;
	}
	include_spip('inc/texte');
	$flags = ($quote ? ENT_QUOTES : ENT_NOQUOTES);
	$flags |= ENT_HTML401;
	$texte = spip_htmlspecialchars(echappe_retour(echappe_html($texte, '', true), '', 'proteger_amp'), $flags);
	if ($tout) {
		return corriger_toutes_entites_html($texte);
	} else {
		return corriger_entites_html($texte);
	}
}

/**
 * Convertit les caractères spéciaux HTML dans le charset du site.
 *
 * @exemple
 *     Si le charset de votre site est `utf-8`, `&eacute;` ou `&#233;`
 *     sera transformé en `é`
 *
 * @filtre
 * @link https://www.spip.net/5513
 *
 * @param string $texte
 *     Texte à convertir
 * @return string
 *     Texte converti
 **/
function filtrer_entites($texte) {
	if (strpos($texte, '&') === false) {
		return $texte;
	}
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	$texte = unicode2charset($texte);
	// cas particulier des " et ' qu'il faut filtrer aussi
	// (on le faisait deja avec un &quot;)
	if (strpos($texte, "&#") !== false) {
		$texte = str_replace(array("&#039;", "&#39;", "&#034;", "&#34;"), array("'", "'", '"', '"'), $texte);
	}

	return $texte;
}


if (!function_exists('filtre_filtrer_entites_dist')) {
	/**
	 * Version sécurisée de filtrer_entites
	 * 
	 * @uses interdire_scripts()
	 * @uses filtrer_entites()
	 * 
	 * @param string $t
	 * @return string
	 */
	function filtre_filtrer_entites_dist($t) {
		include_spip('inc/texte');
		return interdire_scripts(filtrer_entites($t));
	}
}


/**
 * Supprime des caractères illégaux
 *
 * Remplace les caractères de controle par le caractère `-`
 *
 * @link http://www.w3.org/TR/REC-xml/#charsets
 *
 * @param string|array $texte
 * @return string|array
 **/
function supprimer_caracteres_illegaux($texte) {
	static $from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	static $to = null;

	if (is_array($texte)) {
		return array_map('supprimer_caracteres_illegaux', $texte);
	}

	if (!$to) {
		$to = str_repeat('-', strlen($from));
	}

	return strtr($texte, $from, $to);
}

/**
 * Correction de caractères
 *
 * Supprimer les caracteres windows non conformes et les caracteres de controle illégaux
 *
 * @param string|array $texte
 * @return string|array
 **/
function corriger_caracteres($texte) {
	$texte = corriger_caracteres_windows($texte);
	$texte = supprimer_caracteres_illegaux($texte);

	return $texte;
}

/**
 * Encode du HTML pour transmission XML notamment dans les flux RSS
 *
 * Ce filtre transforme les liens en liens absolus, importe les entitées html et échappe les tags html.
 *
 * @filtre
 * @link https://www.spip.net/4287
 *
 * @param string $texte
 *     Texte à transformer
 * @return string
 *     Texte encodé pour XML
 */
function texte_backend($texte) {

	static $apostrophe = array("&#8217;", "'"); # n'allouer qu'une fois

	// si on a des liens ou des images, les passer en absolu
	$texte = liens_absolus($texte);

	// echapper les tags &gt; &lt;
	$texte = preg_replace(',&(gt|lt);,S', '&amp;\1;', $texte);

	// importer les &eacute;
	$texte = filtrer_entites($texte);

	// " -> &quot; et tout ce genre de choses
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = preg_replace('/\s{2,}/S' . $u, " ", $texte);
	// ne pas echapper les sinqle quotes car certains outils de syndication gerent mal
	$texte = entites_html($texte, false, false);
	// mais bien echapper les double quotes !
	$texte = str_replace('"', '&#034;', $texte);

	// verifier le charset
	$texte = charset2unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if (isset($GLOBALS['meta']['charset']) and $GLOBALS['meta']['charset'] == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// l'apostrophe curly pose probleme a certains lecteure de RSS
	// et le caractere apostrophe alourdit les squelettes avec PHP
	// ==> on les remplace par l'entite HTML
	return str_replace($apostrophe, "'", $texte);
}

/**
 * Encode et quote du HTML pour transmission XML notamment dans les flux RSS
 *
 * Comme texte_backend(), mais avec addslashes final pour squelettes avec PHP (rss)
 *
 * @uses texte_backend()
 * @filtre
 *
 * @param string $texte
 *     Texte à transformer
 * @return string
 *     Texte encodé et quote pour XML
 */
function texte_backendq($texte) {
	return addslashes(texte_backend($texte));
}


/**
 * Enlève un numéro préfixant un texte
 *
 * Supprime `10. ` dans la chaine `10. Titre`
 *
 * @filtre
 * @link https://www.spip.net/4314
 * @see recuperer_numero() Pour obtenir le numéro
 * @example
 *     ```
 *     [<h1>(#TITRE|supprimer_numero)</h1>]
 *     ```
 *
 * @param string $texte
 *     Texte
 * @return int|string
 *     Numéro de titre, sinon chaîne vide
 **/
function supprimer_numero($texte) {
	return preg_replace(
		",^[[:space:]]*([0-9]+)([.)]|" . chr(194) . '?' . chr(176) . ")[[:space:]]+,S",
		"", $texte);
}

/**
 * Récupère un numéro préfixant un texte
 *
 * Récupère le numéro `10` dans la chaine `10. Titre`
 *
 * @filtre
 * @link https://www.spip.net/5514
 * @see supprimer_numero() Pour supprimer le numéro
 * @see balise_RANG_dist() Pour obtenir un numéro de titre
 * @example
 *     ```
 *     [(#TITRE|recuperer_numero)]
 *     ```
 *
 * @param string $texte
 *     Texte
 * @return int|string
 *     Numéro de titre, sinon chaîne vide
 **/
function recuperer_numero($texte) {
	if (preg_match(
		",^[[:space:]]*([0-9]+)([.)]|" . chr(194) . '?' . chr(176) . ")[[:space:]]+,S",
		$texte, $regs)) {
		return strval($regs[1]);
	} else {
		return '';
	}
}

/**
 * Suppression basique et brutale de tous les tags
 *
 * Supprime tous les tags `<...>`.
 * Utilisé fréquemment pour écrire des RSS.
 *
 * @filtre
 * @link https://www.spip.net/4315
 * @example
 *     ```
 *     <title>[(#TITRE|supprimer_tags|texte_backend)]</title>
 *     ```
 *
 * @note
 *     Ce filtre supprime aussi les signes inférieurs `<` rencontrés.
 *
 * @param string $texte
 *     Texte à échapper
 * @param string $rempl
 *     Inutilisé.
 * @return string
 *     Texte converti
 **/
function supprimer_tags($texte, $rempl = "") {
	$texte = preg_replace(",<(!--|\w|/|!\[endif|!\[if)[^>]*>,US", $rempl, $texte);
	// ne pas oublier un < final non ferme car coupe
	$texte = preg_replace(",<(!--|\w|/).*$,US", $rempl, $texte);
	// mais qui peut aussi etre un simple signe plus petit que
	$texte = str_replace('<', '&lt;', $texte);

	return $texte;
}

/**
 * Convertit les chevrons de tag en version lisible en HTML
 *
 * Transforme les chevrons de tag `<...>` en entité HTML.
 *
 * @filtre
 * @link https://www.spip.net/5515
 * @example
 *     ```
 *     <pre>[(#TEXTE|echapper_tags)]</pre>
 *     ```
 *
 * @param string $texte
 *     Texte à échapper
 * @param string $rempl
 *     Inutilisé.
 * @return string
 *     Texte converti
 **/
function echapper_tags($texte, $rempl = "") {
	$texte = preg_replace("/<([^>]*)>/", "&lt;\\1&gt;", $texte);

	return $texte;
}

/**
 * Convertit un texte HTML en texte brut
 *
 * Enlève les tags d'un code HTML, élimine les doubles espaces.
 *
 * @filtre
 * @link https://www.spip.net/4317
 * @example
 *     ```
 *     <title>[(#TITRE|textebrut) - ][(#NOM_SITE_SPIP|textebrut)]</title>
 *     ```
 *
 * @param string $texte
 *     Texte à convertir
 * @return string
 *     Texte converti
 **/
function textebrut($texte) {
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = preg_replace('/\s+/S' . $u, " ", $texte);
	$texte = preg_replace("/<(p|br)( [^>]*)?" . ">/iS", "\n\n", $texte);
	$texte = preg_replace("/^\n+/", "", $texte);
	$texte = preg_replace("/\n+$/", "", $texte);
	$texte = preg_replace("/\n +/", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = preg_replace("/(&nbsp;| )+/S", " ", $texte);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$texte = str_replace("&#8217;", "'", $texte);

	return $texte;
}


/**
 * Remplace les liens SPIP en liens ouvrant dans une nouvelle fenetre (target=blank)
 *
 * @filtre
 * @link https://www.spip.net/4297
 *
 * @param string $texte
 *     Texte avec des liens
 * @return string
 *     Texte avec liens ouvrants
 **/
function liens_ouvrants($texte) {
	if (preg_match_all(",(<a\s+[^>]*https?://[^>]*class=[\"']spip_(out|url)\b[^>]+>),imsS",
		$texte, $liens, PREG_PATTERN_ORDER)) {
		foreach ($liens[0] as $a) {
			$rel = 'noopener noreferrer ' . extraire_attribut($a, 'rel');
			$ablank = inserer_attribut($a, 'rel', $rel);
			$ablank = inserer_attribut($ablank, 'target', '_blank');
			$texte = str_replace($a, $ablank, $texte);
		}
	}

	return $texte;
}

/**
 * Ajouter un attribut rel="nofollow" sur tous les liens d'un texte
 *
 * @param string $texte
 * @return string
 */
function liens_nofollow($texte) {
	if (stripos($texte, "<a") === false) {
		return $texte;
	}

	if (preg_match_all(",<a\b[^>]*>,UimsS", $texte, $regs, PREG_PATTERN_ORDER)) {
		foreach ($regs[0] as $a) {
			$rel = extraire_attribut($a, "rel");
			if (strpos($rel, "nofollow") === false) {
				$rel = "nofollow" . ($rel ? " $rel" : "");
				$anofollow = inserer_attribut($a, "rel", $rel);
				$texte = str_replace($a, $anofollow, $texte);
			}
		}
	}

	return $texte;
}

/**
 * Transforme les sauts de paragraphe HTML `p` en simples passages à la ligne `br`
 *
 * @filtre
 * @link https://www.spip.net/4308
 * @example
 *     ```
 *     [<div>(#DESCRIPTIF|PtoBR)[(#NOTES|PtoBR)]</div>]
 *     ```
 *
 * @param string $texte
 *     Texte à transformer
 * @return string
 *     Texte sans paraghaphes
 **/
function PtoBR($texte) {
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = preg_replace("@</p>@iS", "\n", $texte);
	$texte = preg_replace("@<p\b.*>@UiS", "<br />", $texte);
	$texte = preg_replace("@^\s*<br />@S" . $u, "", $texte);

	return $texte;
}


/**
 * Assure qu'un texte ne vas pas déborder d'un bloc
 * par la faute d'un mot trop long (souvent des URLs)
 *
 * Ne devrait plus être utilisé et fait directement en CSS par un style
 * `word-wrap:break-word;`
 *
 * @note
 *   Pour assurer la compatibilité du filtre, on encapsule le contenu par
 *   un `div` ou `span` portant ce style CSS inline.
 *
 * @filtre
 * @link https://www.spip.net/4298
 * @link http://www.alsacreations.com/tuto/lire/1038-gerer-debordement-contenu-css.html
 * @deprecated Utiliser le style CSS `word-wrap:break-word;`
 *
 * @param string $texte Texte
 * @return string Texte encadré du style CSS
 */
function lignes_longues($texte) {
	if (!strlen(trim($texte))) {
		return $texte;
	}
	include_spip('inc/texte');
	$tag = preg_match(',</?(' . _BALISES_BLOCS . ')[>[:space:]],iS', $texte) ?
		'div' : 'span';

	return "<$tag style='word-wrap:break-word;'>$texte</$tag>";
}

/**
 * Passe un texte en majuscules, y compris les accents, en HTML
 *
 * Encadre le texte du style CSS `text-transform: uppercase;`.
 * Le cas spécifique du i turc est géré.
 *
 * @filtre
 * @example
 *     ```
 *     [(#EXTENSION|majuscules)]
 *     ```
 *
 * @param string $texte Texte
 * @return string Texte en majuscule
 */
function majuscules($texte) {
	if (!strlen($texte)) {
		return '';
	}

	// Cas du turc
	if ($GLOBALS['spip_lang'] == 'tr') {
		# remplacer hors des tags et des entites
		if (preg_match_all(',<[^<>]+>|&[^;]+;,S', $texte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $n => $match) {
				$texte = str_replace($match[0], "@@SPIP_TURC$n@@", $texte);
			}
		}

		$texte = str_replace('i', '&#304;', $texte);

		if ($regs) {
			foreach ($regs as $n => $match) {
				$texte = str_replace("@@SPIP_TURC$n@@", $match[0], $texte);
			}
		}
	}

	// Cas general
	return "<span style='text-transform: uppercase;'>$texte</span>";
}

/**
 * Retourne une taille en octets humainement lisible
 *
 * Tel que "127.4 ko" ou "3.1 Mo"
 *
 * @example
 *     - `[(#TAILLE|taille_en_octets)]`
 *     - `[(#VAL{123456789}|taille_en_octets)]` affiche `117.7 Mo`
 *
 * @filtre
 * @link https://www.spip.net/4316
 * @param int $taille
 * @return string
 **/
function taille_en_octets($taille) {
	if (!defined('_KILOBYTE')) {
		/**
		 * Définit le nombre d'octets dans un Kilobyte
		 *
		 * @var int
		 **/
		define('_KILOBYTE', 1024);
	}

	if ($taille < 1) {
		return '';
	}
	if ($taille < _KILOBYTE) {
		$taille = _T('taille_octets', array('taille' => $taille));
	} elseif ($taille < _KILOBYTE * _KILOBYTE) {
		$taille = _T('taille_ko', array('taille' => round($taille / _KILOBYTE, 1)));
	} elseif ($taille < _KILOBYTE * _KILOBYTE * _KILOBYTE) {
		$taille = _T('taille_mo', array('taille' => round($taille / _KILOBYTE / _KILOBYTE, 1)));
	} else {
		$taille = _T('taille_go', array('taille' => round($taille / _KILOBYTE / _KILOBYTE / _KILOBYTE, 2)));
	}

	return $taille;
}


/**
 * Rend une chaine utilisable sans dommage comme attribut HTML
 *
 * @example `<a href="#URL_ARTICLE" title="[(#TITRE|attribut_html)]">#TITRE</a>`
 *
 * @filtre
 * @link https://www.spip.net/4282
 * @uses textebrut()
 * @uses texte_backend()
 *
 * @param string $texte
 *     Texte à mettre en attribut
 * @param bool $textebrut
 *     Passe le texte en texte brut (enlève les balises html) ?
 * @return string
 *     Texte prêt pour être utilisé en attribut HTML
 **/
function attribut_html($texte, $textebrut = true) {
	$u = $GLOBALS['meta']['pcre_u'];
	if ($textebrut) {
		$texte = preg_replace(array(",\n,", ",\s(?=\s),msS" . $u), array(" ", ""), textebrut($texte));
	}
	$texte = texte_backend($texte);
	$texte = str_replace(array("'", '"'), array('&#039;', '&#034;'), $texte);

	return preg_replace(array("/&(amp;|#38;)/", "/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/"), array("&", "&#38;"),
		$texte);
}


/**
 * Vider les URL nulles
 *
 * - Vide les URL vides comme `http://` ou `mailto:` (sans rien d'autre)
 * - échappe les entités et gère les `&amp;`
 *
 * @uses entites_html()
 *
 * @param string $url
 *     URL à vérifier et échapper
 * @param bool $entites
 *     `true` pour échapper les entités HTML.
 * @return string
 *     URL ou chaîne vide
 **/
function vider_url($url, $entites = true) {
	# un message pour abs_url
	$GLOBALS['mode_abs_url'] = 'url';
	$url = trim($url);
	$r = ",^(?:" . _PROTOCOLES_STD . '):?/?/?$,iS';

	return preg_match($r, $url) ? '' : ($entites ? entites_html($url) : $url);
}


/**
 * Maquiller une adresse e-mail
 *
 * Remplace `@` par 3 caractères aléatoires.
 *
 * @uses creer_pass_aleatoire()
 *
 * @param string $texte Adresse email
 * @return string Adresse email maquillée
 **/
function antispam($texte) {
	include_spip('inc/acces');
	$masque = creer_pass_aleatoire(3);

	return preg_replace("/@/", " $masque ", $texte);
}

/**
 * Vérifie un accès à faible sécurité
 *
 * Vérifie qu'un visiteur peut accéder à la page demandée,
 * qui est protégée par une clé, calculée à partir du low_sec de l'auteur,
 * et des paramètres le composant l'appel (op, args)
 *
 * @example
 *     `[(#ID_AUTEUR|securiser_acces{#ENV{cle}, rss, #ENV{op}, #ENV{args}}|sinon_interdire_acces)]`
 *
 * @see  bouton_spip_rss() pour générer un lien de faible sécurité pour les RSS privés
 * @see  afficher_low_sec() pour calculer une clé valide
 * @uses verifier_low_sec()
 *
 * @filtre
 * @param int $id_auteur
 *     L'auteur qui demande la page
 * @param string $cle
 *     La clé à tester
 * @param string $dir
 *     Un type d'accès (nom du répertoire dans lequel sont rangés les squelettes demandés, tel que 'rss')
 * @param string $op
 *     Nom de l'opération éventuelle
 * @param string $args
 *     Nom de l'argument calculé
 * @return bool
 *     True si on a le droit d'accès, false sinon.
 **/
function securiser_acces($id_auteur, $cle, $dir, $op = '', $args = '') {
	include_spip('inc/acces');
	if ($op) {
		$dir .= " $op $args";
	}

	return verifier_low_sec($id_auteur, $cle, $dir);
}

/**
 * Retourne le second paramètre lorsque
 * le premier est considere vide, sinon retourne le premier paramètre.
 *
 * En php `sinon($a, 'rien')` retourne `$a`, ou `'rien'` si `$a` est vide.
 * En filtre SPIP `|sinon{#TEXTE, rien}` : affiche `#TEXTE` ou `rien` si `#TEXTE` est vide,
 *
 * @filtre
 * @see filtre_logique() pour la compilation du filtre dans un squelette
 * @link https://www.spip.net/4313
 * @note
 *     L'utilisation de `|sinon` en tant que filtre de squelette
 *     est directement compilé dans `public/references` par la fonction `filtre_logique()`
 *
 * @param mixed $texte
 *     Contenu de reference a tester
 * @param mixed $sinon
 *     Contenu a retourner si le contenu de reference est vide
 * @return mixed
 *     Retourne $texte, sinon $sinon.
 **/
function sinon($texte, $sinon = '') {
	if ($texte or (!is_array($texte) and strlen($texte))) {
		return $texte;
	} else {
		return $sinon;
	}
}

/**
 * Filtre `|choixsivide{vide, pas vide}` alias de `|?{si oui, si non}` avec les arguments inversés
 *
 * @example
 *     `[(#TEXTE|choixsivide{vide, plein})]` affiche vide si le `#TEXTE`
 *     est considéré vide par PHP (chaîne vide, false, 0, tableau vide, etc…).
 *     C'est l'équivalent de `[(#TEXTE|?{plein, vide})]`
 *
 * @filtre
 * @see choixsiegal()
 * @link https://www.spip.net/4189
 *
 * @param mixed $a
 *     La valeur à tester
 * @param mixed $vide
 *     Ce qui est retourné si `$a` est considéré vide
 * @param mixed $pasvide
 *     Ce qui est retourné sinon
 * @return mixed
 **/
function choixsivide($a, $vide, $pasvide) {
	return $a ? $pasvide : $vide;
}

/**
 * Filtre `|choixsiegal{valeur, sioui, sinon}`
 *
 * @example
 *     `#LANG_DIR|choixsiegal{ltr,left,right}` retourne `left` si
 *      `#LANG_DIR` vaut `ltr` et `right` sinon.
 *
 * @filtre
 * @link https://www.spip.net/4148
 *
 * @param mixed $a1
 *     La valeur à tester
 * @param mixed $a2
 *     La valeur de comparaison
 * @param mixed $v
 *     Ce qui est retourné si la comparaison est vraie
 * @param mixed $f
 *     Ce qui est retourné sinon
 * @return mixed
 **/
function choixsiegal($a1, $a2, $v, $f) {
	return ($a1 == $a2) ? $v : $f;
}

//
// Export iCal
//

/**
 * Adapte un texte pour être inséré dans une valeur d'un export ICAL
 *
 * Passe le texte en utf8, enlève les sauts de lignes et échappe les virgules.
 *
 * @example `SUMMARY:[(#TITRE|filtrer_ical)]`
 * @filtre
 *
 * @param string $texte
 * @return string
 **/
function filtrer_ical($texte) {
	#include_spip('inc/charsets');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, $GLOBALS['meta']['charset'], 1), 'utf-8');
	$texte = preg_replace("/\n/", " ", $texte);
	$texte = preg_replace("/,/", "\,", $texte);

	return $texte;
}


/**
 * Transforme les sauts de ligne simples en sauts forcés avec `_ `
 *
 * Ne modifie pas les sauts de paragraphe (2 sauts consécutifs au moins),
 * ou les retours à l'intérieur de modèles ou de certaines balises html.
 *
 * @note
 *     Cette fonction pouvait être utilisée pour forcer les alinéas,
 *     (retours à la ligne sans saut de paragraphe), mais ce traitement
 *     est maintenant automatique.
 *     Cf. plugin Textwheel et la constante _AUTOBR
 *
 * @uses echappe_html()
 * @uses echappe_retour()
 *
 * @param string $texte
 * @param string $delim
 *      Ce par quoi sont remplacés les sauts
 * @return string
 **/
function post_autobr($texte, $delim = "\n_ ") {
	if (!function_exists('echappe_html')) {
		include_spip('inc/texte_mini');
	}
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);

	if (preg_match(",\n+$,", $texte, $fin)) {
		$texte = substr($texte, 0, -strlen($fin = $fin[0]));
	} else {
		$fin = '';
	}

	$texte = echappe_html($texte, '', true);

	// echapper les modeles
	if (strpos($texte, "<") !== false) {
		include_spip('inc/lien');
		if (defined('_PREG_MODELE')) {
			$preg_modeles = "@" . _PREG_MODELE . "@imsS";
			$texte = echappe_html($texte, '', true, $preg_modeles);
		}
	}

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-' . $suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t - 1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car <> '-') and ($car <> '_') and ($car <> "\n") and ($car <> "|") and ($car <> "}")
			and !preg_match(',^\s*(\n|</?(quote|div|dl|dt|dd)|$),S', ($suite))
			and !preg_match(',</?(quote|div|dl|dt|dd)> *$,iS', $debut)
		) {
			$debut .= $delim;
		} else {
			$debut .= "\n";
		}
		if (preg_match(",^\n+,", $suite, $regs)) {
			$debut .= $regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut . $suite;

	$texte = echappe_retour($texte);

	return $texte . $fin;
}


/**
 * Expression régulière pour obtenir le contenu des extraits idiomes `<:module:cle:>`
 *
 * @var string
 */
define('_EXTRAIRE_IDIOME', '@<:(?:([a-z0-9_]+):)?([a-z0-9_]+):>@isS');

/**
 * Extrait une langue des extraits idiomes (`<:module:cle_de_langue:>`)
 *
 * Retrouve les balises `<:cle_de_langue:>` d'un texte et remplace son contenu
 * par l'extrait correspondant à la langue demandée (si possible), sinon dans la
 * langue par défaut du site.
 *
 * Ne pas mettre de span@lang=fr si on est déjà en fr.
 *
 * @filtre
 * @uses inc_traduire_dist()
 * @uses code_echappement()
 * @uses echappe_retour()
 *
 * @param string $letexte
 * @param string $lang
 *     Langue à retrouver (si vide, utilise la langue en cours).
 * @param array $options Options {
 * @type bool $echappe_span
 *         True pour échapper les balises span (false par défaut)
 * @type string $lang_defaut
 *         Code de langue : permet de définir la langue utilisée par défaut,
 *         en cas d'absence de traduction dans la langue demandée.
 *         Par défaut la langue du site.
 *         Indiquer 'aucune' pour ne pas retourner de texte si la langue
 *         exacte n'a pas été trouvée.
 * }
 * @return string
 **/
function extraire_idiome($letexte, $lang = null, $options = array()) {
	static $traduire = false;
	if ($letexte
		and preg_match_all(_EXTRAIRE_IDIOME, $letexte, $regs, PREG_SET_ORDER)
	) {
		if (!$traduire) {
			$traduire = charger_fonction('traduire', 'inc');
			include_spip('inc/lang');
		}
		if (!$lang) {
			$lang = $GLOBALS['spip_lang'];
		}
		// Compatibilité avec le prototype de fonction précédente qui utilisait un boolean
		if (is_bool($options)) {
			$options = array('echappe_span' => $options);
		}
		if (!isset($options['echappe_span'])) {
			$options = array_merge($options, array('echappe_span' => false));
		}

		foreach ($regs as $reg) {
			$cle = ($reg[1] ? $reg[1] . ':' : '') . $reg[2];
			$desc = $traduire($cle, $lang, true);
			$l = $desc->langue;
			// si pas de traduction, on laissera l'écriture de l'idiome entier dans le texte.
			if (strlen($desc->texte)) {
				$trad = code_echappement($desc->texte, 'idiome', false);
				if ($l !== $lang) {
					$trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
				}
				if (lang_dir($l) !== lang_dir($lang)) {
					$trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
				}
				if (!$options['echappe_span']) {
					$trad = echappe_retour($trad, 'idiome');
				}
				$letexte = str_replace($reg[0], $trad, $letexte);
			}
		}
	}
	return $letexte;
}

/**
 * Expression régulière pour obtenir le contenu des extraits polyglottes `<multi>`
 *
 * @var string
 */
define('_EXTRAIRE_MULTI', "@<multi>(.*?)</multi>@sS");


/**
 * Extrait une langue des extraits polyglottes (`<multi>`)
 *
 * Retrouve les balises `<multi>` d'un texte et remplace son contenu
 * par l'extrait correspondant à la langue demandée.
 *
 * Si la langue demandée n'est pas trouvée dans le multi, ni une langue
 * approchante (exemple `fr` si on demande `fr_TU`), on retourne l'extrait
 * correspondant à la langue par défaut (option 'lang_defaut'), qui est
 * par défaut la langue du site. Et si l'extrait n'existe toujours pas
 * dans cette langue, ça utilisera la première langue utilisée
 * dans la balise `<multi>`.
 *
 * Ne pas mettre de span@lang=fr si on est déjà en fr.
 *
 * @filtre
 * @link https://www.spip.net/5332
 *
 * @uses extraire_trads()
 * @uses approcher_langue()
 * @uses lang_typo()
 * @uses code_echappement()
 * @uses echappe_retour()
 *
 * @param string $letexte
 * @param string $lang
 *     Langue à retrouver (si vide, utilise la langue en cours).
 * @param array $options Options {
 * @type bool $echappe_span
 *         True pour échapper les balises span (false par défaut)
 * @type string $lang_defaut
 *         Code de langue : permet de définir la langue utilisée par défaut,
 *         en cas d'absence de traduction dans la langue demandée.
 *         Par défaut la langue du site.
 *         Indiquer 'aucune' pour ne pas retourner de texte si la langue
 *         exacte n'a pas été trouvée.
 * }
 * @return string
 **/
function extraire_multi($letexte, $lang = null, $options = array()) {

	if ($letexte
		and preg_match_all(_EXTRAIRE_MULTI, $letexte, $regs, PREG_SET_ORDER)
	) {
		if (!$lang) {
			$lang = $GLOBALS['spip_lang'];
		}

		// Compatibilité avec le prototype de fonction précédente qui utilisait un boolean
		if (is_bool($options)) {
			$options = array('echappe_span' => $options, 'lang_defaut' => _LANGUE_PAR_DEFAUT);
		}
		if (!isset($options['echappe_span'])) {
			$options = array_merge($options, array('echappe_span' => false));
		}
		if (!isset($options['lang_defaut'])) {
			$options = array_merge($options, array('lang_defaut' => _LANGUE_PAR_DEFAUT));
		}

		include_spip('inc/lang');
		foreach ($regs as $reg) {
			// chercher la version de la langue courante
			$trads = extraire_trads($reg[1]);
			if ($l = approcher_langue($trads, $lang)) {
				$trad = $trads[$l];
			} else {
				if ($options['lang_defaut'] == 'aucune') {
					$trad = '';
				} else {
					// langue absente, prendre le fr ou une langue précisée (meme comportement que inc/traduire.php)
					// ou la premiere dispo
					// mais typographier le texte selon les regles de celle-ci
					// Attention aux blocs multi sur plusieurs lignes
					if (!$l = approcher_langue($trads, $options['lang_defaut'])) {
						$l = key($trads);
					}
					$trad = $trads[$l];
					$typographie = charger_fonction(lang_typo($l), 'typographie');
					$trad = $typographie($trad);
					// Tester si on echappe en span ou en div
					// il ne faut pas echapper en div si propre produit un seul paragraphe
					include_spip('inc/texte');
					$trad_propre = preg_replace(",(^<p[^>]*>|</p>$),Uims", "", propre($trad));
					$mode = preg_match(',</?(' . _BALISES_BLOCS . ')[>[:space:]],iS', $trad_propre) ? 'div' : 'span';
					if ($mode === 'div') {
						$trad = rtrim($trad) . "\n\n";
					}
					$trad = code_echappement($trad, 'multi', false, $mode);
					$trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
					if (lang_dir($l) !== lang_dir($lang)) {
						$trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
					}
					if (!$options['echappe_span']) {
						$trad = echappe_retour($trad, 'multi');
					}
				}
			}
			$letexte = str_replace($reg[0], $trad, $letexte);
		}
	}

	return $letexte;
}

/**
 * Convertit le contenu d'une balise `<multi>` en un tableau
 *
 * Exemple de blocs.
 * - `texte par défaut [fr] en français [en] en anglais`
 * - `[fr] en français [en] en anglais`
 *
 * @param string $bloc
 *     Le contenu intérieur d'un bloc multi
 * @return array [code de langue => texte]
 *     Peut retourner un code de langue vide, lorsqu'un texte par défaut est indiqué.
 **/
function extraire_trads($bloc) {
	$lang = '';
// ce reg fait planter l'analyse multi s'il y a de l'{italique} dans le champ
//	while (preg_match("/^(.*?)[{\[]([a-z_]+)[}\]]/siS", $bloc, $regs)) {
	while (preg_match("/^(.*?)[\[]([a-z_]+)[\]]/siS", $bloc, $regs)) {
		$texte = trim($regs[1]);
		if ($texte or $lang) {
			$trads[$lang] = $texte;
		}
		$bloc = substr($bloc, strlen($regs[0]));
		$lang = $regs[2];
	}
	$trads[$lang] = $bloc;

	return $trads;
}


/**
 * Calculer l'initiale d'un nom
 *
 * @param string $nom
 * @return string L'initiale en majuscule
 */
function filtre_initiale($nom) {
	return spip_substr(trim(strtoupper(extraire_multi($nom))), 0, 1);
}


/**
 * Retourne la donnée si c'est la première fois qu'il la voit
 *
 * Il est possible de gérer différentes "familles" de données avec
 * le second paramètre.
 *
 * @filtre
 * @link https://www.spip.net/4320
 * @example
 *     ```
 *     [(#ID_SECTEUR|unique)]
 *     [(#ID_SECTEUR|unique{tete})] n'a pas d'incidence sur
 *     [(#ID_SECTEUR|unique{pied})]
 *     [(#ID_SECTEUR|unique{pied,1})] affiche le nombre d'éléments.
 *     Préférer totefois #TOTAL_UNIQUE{pied}
 *     ```
 *
 * @todo
 *    Ameliorations possibles :
 *
 *    1) si la donnée est grosse, mettre son md5 comme clé
 *    2) purger $mem quand on change de squelette (sinon bug inclusions)
 *
 * @param string $donnee
 *      Donnée que l'on souhaite unique
 * @param string $famille
 *      Famille de stockage (1 unique donnée par famille)
 *
 *      - _spip_raz_ : (interne) Vide la pile de mémoire et la retourne
 *      - _spip_set_ : (interne) Affecte la pile de mémoire avec la donnée
 * @param bool $cpt
 *      True pour obtenir le nombre d'éléments différents stockés
 * @return string|int|array|null|void
 *
 *      - string : Donnée si c'est la première fois qu'elle est vue
 *      - void : si la donnée a déjà été vue
 *      - int : si l'on demande le nombre d'éléments
 *      - array (interne) : si on dépile
 *      - null (interne) : si on empile
 **/
function unique($donnee, $famille = '', $cpt = false) {
	static $mem = array();
	// permettre de vider la pile et de la restaurer
	// pour le calcul de introduction...
	if ($famille == '_spip_raz_') {
		$tmp = $mem;
		$mem = array();

		return $tmp;
	} elseif ($famille == '_spip_set_') {
		$mem = $donnee;

		return;
	}
	// eviter une notice
	if (!isset($mem[$famille])) {
		$mem[$famille] = array();
	}
	if ($cpt) {
		return count($mem[$famille]);
	}
	// eviter une notice
	if (!isset($mem[$famille][$donnee])) {
		$mem[$famille][$donnee] = 0;
	}
	if (!($mem[$famille][$donnee]++)) {
		return $donnee;
	}
}


/**
 * Filtre qui alterne des valeurs en fonction d'un compteur
 *
 * Affiche à tour de rôle et dans l'ordre, un des arguments transmis
 * à chaque incrément du compteur.
 *
 * S'il n'y a qu'un seul argument, et que c'est un tableau,
 * l'alternance se fait sur les valeurs du tableau.
 *
 * Souvent appliqué à l'intérieur d'une boucle, avec le compteur `#COMPTEUR_BOUCLE`
 *
 * @example
 *     - `[(#COMPTEUR_BOUCLE|alterner{bleu,vert,rouge})]`
 *     - `[(#COMPTEUR_BOUCLE|alterner{#LISTE{bleu,vert,rouge}})]`
 *
 * @filtre
 * @link https://www.spip.net/4145
 *
 * @param int $i
 *     Le compteur
 * @return mixed
 *     Une des valeurs en fonction du compteur.
 **/
function alterner($i) {
	// recuperer les arguments (attention fonctions un peu space)
	$num = func_num_args();
	$args = func_get_args();

	if ($num == 2 && is_array($args[1])) {
		$args = $args[1];
		array_unshift($args, '');
		$num = count($args);
	}

	// renvoyer le i-ieme argument, modulo le nombre d'arguments
	return $args[(intval($i) - 1) % ($num - 1) + 1];
}


/**
 * Récupérer un attribut d'une balise HTML
 *
 * la regexp est mortelle : cf. `tests/unit/filtres/extraire_attribut.php`
 * Si on a passé un tableau de balises, renvoyer un tableau de résultats
 * (dans ce cas l'option `$complet` n'est pas disponible)
 *
 * @param string|array $balise
 *     Texte ou liste de textes dont on veut extraire des balises
 * @param string $attribut
 *     Nom de l'attribut désiré
 * @param bool $complet
 *     True pour retourner un tableau avec
 *     - le texte de la balise
 *     - l'ensemble des résultats de la regexp ($r)
 * @return string|array
 *     - Texte de l'attribut retourné, ou tableau des texte d'attributs
 *       (si 1er argument tableau)
 *     - Tableau complet (si 2e argument)
 **/
function extraire_attribut($balise, $attribut, $complet = false) {
	if (is_array($balise)) {
		array_walk(
			$balise,
			function(&$a, $key, $t){
				$a = extraire_attribut($a, $t);
			},
			$attribut
		);

		return $balise;
	}
	if (preg_match(
		',(^.*?<(?:(?>\s*)(?>[\w:.-]+)(?>(?:=(?:"[^"]*"|\'[^\']*\'|[^\'"]\S*))?))*?)(\s+'
		. $attribut
		. '(?:=\s*("[^"]*"|\'[^\']*\'|[^\'"]\S*))?)()((?:[\s/][^>]*)?>.*),isS',

		$balise, $r)) {
		if (isset($r[3][0]) and ($r[3][0] == '"' || $r[3][0] == "'")) {
			$r[4] = substr($r[3], 1, -1);
			$r[3] = $r[3][0];
		} elseif ($r[3] !== '') {
			$r[4] = $r[3];
			$r[3] = '';
		} else {
			$r[4] = trim($r[2]);
		}
		$att = $r[4];
		if (strpos($att, "&#") !== false) {
			$att = str_replace(array("&#039;", "&#39;", "&#034;", "&#34;"), array("'", "'", '"', '"'), $att);
		}
		$att = filtrer_entites($att);
	} else {
		$att = null;
	}

	if ($complet) {
		return array($att, $r);
	} else {
		return $att;
	}
}

/**
 * Insérer (ou modifier) un attribut html dans une balise
 *
 * @example
 *     - `[(#LOGO_ARTICLE|inserer_attribut{class, logo article})]`
 *     - `[(#LOGO_ARTICLE|inserer_attribut{alt, #TTTRE|attribut_html|couper{60}})]`
 *     - `[(#FICHIER|image_reduire{40}|inserer_attribut{data-description, #DESCRIPTIF})]`
 *       Laissera les balises HTML de la valeur (ici `#DESCRIPTIF`) si on n'applique pas le
 *       filtre `attribut_html` dessus.
 *
 * @filtre
 * @link https://www.spip.net/4294
 * @uses attribut_html()
 * @uses extraire_attribut()
 *
 * @param string $balise
 *     Code html de la balise (ou contenant une balise)
 * @param string $attribut
 *     Nom de l'attribut html à modifier
 * @param string $val
 *     Valeur de l'attribut à appliquer
 * @param bool $proteger
 *     Prépare la valeur en tant qu'attribut de balise (mais conserve les balises html).
 * @param bool $vider
 *     True pour vider l'attribut. Une chaîne vide pour `$val` fera pareil.
 * @return string
 *     Code html modifié
 **/
function inserer_attribut($balise, $attribut, $val, $proteger = true, $vider = false) {
	// preparer l'attribut
	// supprimer les &nbsp; etc mais pas les balises html
	// qui ont un sens dans un attribut value d'un input
	if ($proteger) {
		$val = attribut_html($val, false);
	}

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#039;", $val);
	if ($vider and strlen($val) == 0) {
		$insert = '';
	} else {
		$insert = " $attribut='$val'";
	}

	list($old, $r) = extraire_attribut($balise, $attribut, true);

	if ($old !== null) {
		// Remplacer l'ancien attribut du meme nom
		$balise = $r[1] . $insert . $r[5];
	} else {
		// preferer une balise " />" (comme <img />)
		if (preg_match(',/>,', $balise)) {
			$balise = preg_replace(",\s?/>,S", $insert . " />", $balise, 1);
		} // sinon une balise <a ...> ... </a>
		else {
			$balise = preg_replace(",\s?>,S", $insert . ">", $balise, 1);
		}
	}

	return $balise;
}

/**
 * Supprime un attribut HTML
 *
 * @example `[(#LOGO_ARTICLE|vider_attribut{class})]`
 *
 * @filtre
 * @link https://www.spip.net/4142
 * @uses inserer_attribut()
 * @see  extraire_attribut()
 *
 * @param string $balise Code HTML de l'élément
 * @param string $attribut Nom de l'attribut à enlever
 * @return string Code HTML sans l'attribut
 **/
function vider_attribut($balise, $attribut) {
	return inserer_attribut($balise, $attribut, '', false, true);
}

/**
 * Fonction support pour les filtres |ajouter_class |supprimer_class |commuter_class
 *
 * @param string $balise
 * @param string|array $class
 * @param string $operation
 * @return string
 */
function modifier_class($balise, $class, $operation='ajouter') {
	if (is_string($class)) {
		$class = explode(' ', trim($class));
	}
	$class = array_filter($class);

	// si la ou les classes ont des caracteres invalides on ne fait rien
	if (preg_match(",[^\w-],", implode('', $class))) {
		return $balise;
	}

	if ($class) {
		$class = array_unique($class);
		$class_courante = extraire_attribut($balise, 'class');

		$class_new = $class_courante;
		foreach ($class as $c) {
			if ($c) {
				$is_class_presente = false;
				if (strpos($class_courante, $c) !== false
					and preg_match("/(^|\s)".preg_quote($c)."($|\s)/", $class_courante)) {
					$is_class_presente = true;
				}
				if (in_array($operation, ['ajouter', 'commuter'])
					and !$is_class_presente) {
					$class_new = rtrim($class_new) . " " . $c;
				}
				elseif (in_array($operation, ['supprimer', 'commuter'])
					and $is_class_presente) {
					$class_new = trim(preg_replace("/(^|\s)".preg_quote($c)."($|\s)/", "\\1", $class_new));
				}
			}
		}

		if ($class_new !== $class_courante) {
			if (strlen($class_new)) {
				$balise = inserer_attribut($balise, 'class', $class_new);
			}
			elseif ($class_courante) {
				$balise = vider_attribut($balise, 'class');
			}
		}
	}

	return $balise;
}

/**
 * Ajoute une ou plusieurs classes sur une balise (si pas deja presentes)
 * @param string $balise
 * @param string|array $class
 * @return string
 */
function ajouter_class($balise, $class){
	return modifier_class($balise, $class, 'ajouter');
}

/**
 * Supprime une ou plusieurs classes sur une balise (si presentes)
 * @param string $balise
 * @param string|array $class
 * @return string
 */
function supprimer_class($balise, $class){
	return modifier_class($balise, $class, 'supprimer');
}

/**
 * Bascule une ou plusieurs classes sur une balise : ajoutees si absentes, supprimees si presentes
 *
 * @param string $balise
 * @param string|array $class
 * @return string
 */
function commuter_class($balise, $class){
	return modifier_class($balise, $class, 'commuter');
}

/**
 * Un filtre pour déterminer le nom du statut des inscrits
 *
 * @param void|int $id
 * @param string $mode
 * @return string
 */
function tester_config($id, $mode = '') {
	include_spip('action/inscrire_auteur');

	return tester_statut_inscription($mode, $id);
}

//
// Quelques fonctions de calcul arithmetique
//
function floatstr($a) { return str_replace(',','.',(string)floatval($a)); }
function strize($f, $a, $b) { return floatstr($f(floatstr($a),floatstr($b))); }

/**
 * Additionne 2 nombres
 *
 * @filtre
 * @link https://www.spip.net/4307
 * @see moins()
 * @example
 *     ```
 *     [(#VAL{28}|plus{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 * @return int $a+$b
 **/
function plus($a, $b) {
	return $a + $b;
}
function strplus($a, $b) {return strize('plus', $a, $b);}
/**
 * Soustrait 2 nombres
 *
 * @filtre
 * @link https://www.spip.net/4302
 * @see plus()
 * @example
 *     ```
 *     [(#VAL{28}|moins{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 * @return int $a-$b
 **/
function moins($a, $b) {
	return $a - $b;
}
function strmoins($a, $b) {return strize('moins', $a, $b);}

/**
 * Multiplie 2 nombres
 *
 * @filtre
 * @link https://www.spip.net/4304
 * @see div()
 * @see modulo()
 * @example
 *     ```
 *     [(#VAL{28}|mult{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 * @return int $a*$b
 **/
function mult($a, $b) {
	return $a * $b;
}
function strmult($a, $b) {return strize('mult', $a, $b);}

/**
 * Divise 2 nombres
 *
 * @filtre
 * @link https://www.spip.net/4279
 * @see mult()
 * @see modulo()
 * @example
 *     ```
 *     [(#VAL{28}|div{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 * @return int $a/$b (ou 0 si $b est nul)
 **/
function div($a, $b) {
	return $b ? $a / $b : 0;
}
function strdiv($a, $b) {return strize('div', $a, $b);}

/**
 * Retourne le modulo 2 nombres
 *
 * @filtre
 * @link https://www.spip.net/4301
 * @see mult()
 * @see div()
 * @example
 *     ```
 *     [(#VAL{28}|modulo{14})]
 *     ```
 *
 * @param int $nb
 * @param int $mod
 * @param int $add
 * @return int ($nb % $mod) + $add
 **/
function modulo($nb, $mod, $add = 0) {
	return ($mod ? $nb % $mod : 0) + $add;
}


/**
 * Vérifie qu'un nom (d'auteur) ne comporte pas d'autres tags que <multi>
 * et ceux volontairement spécifiés dans la constante
 *
 * @param string $nom
 *      Nom (signature) proposé
 * @return bool
 *      - false si pas conforme,
 *      - true sinon
 **/
function nom_acceptable($nom) {
	if (!is_string($nom)) {
		return false;
	}
	if (!defined('_TAGS_NOM_AUTEUR')) {
		define('_TAGS_NOM_AUTEUR', '');
	}
	$tags_acceptes = array_unique(explode(',', 'multi,' . _TAGS_NOM_AUTEUR));
	foreach ($tags_acceptes as $tag) {
		if (strlen($tag)) {
			$remp1[] = '<' . trim($tag) . '>';
			$remp1[] = '</' . trim($tag) . '>';
			$remp2[] = '\x60' . trim($tag) . '\x61';
			$remp2[] = '\x60/' . trim($tag) . '\x61';
		}
	}
	$v_nom = str_replace($remp2, $remp1, supprimer_tags(str_replace($remp1, $remp2, $nom)));

	return str_replace('&lt;', '<', $v_nom) == $nom;
}


/**
 * Vérifier la conformité d'une ou plusieurs adresses email (suivant RFC 822)
 *
 * @param string|array $adresses
 *      Adresse ou liste d'adresse
 *      si on fournit un tableau, il est filtre et la fonction renvoie avec uniquement les adresses email valides (donc possiblement vide)
 * @return bool|string|array
 *      - false si une des adresses n'est pas conforme,
 *      - la normalisation de la dernière adresse donnée sinon
 *      - renvoie un tableau si l'entree est un tableau
 **/
function email_valide($adresses) {
	if (is_array($adresses)) {
		$adresses = array_map('email_valide', $adresses);
		$adresses = array_filter($adresses);
		return $adresses;
	}

	$email_valide = charger_fonction('email_valide', 'inc');
	return $email_valide($adresses);
}

/**
 * Permet d'afficher un symbole à côté des liens pointant vers les
 * documents attachés d'un article (liens ayant `rel=enclosure`).
 *
 * @filtre
 * @link https://www.spip.net/4134
 *
 * @param string $tags Texte
 * @return string Texte
 **/
function afficher_enclosures($tags) {
	$s = array();
	foreach (extraire_balises($tags, 'a') as $tag) {
		if (extraire_attribut($tag, 'rel') == 'enclosure'
			and $t = extraire_attribut($tag, 'href')
		) {
			$s[] = preg_replace(',>[^<]+</a>,S',
				'>'
				. http_img_pack('attachment-16.png', $t,
					'title="' . attribut_html($t) . '"')
				. '</a>', $tag);
		}
	}

	return join('&nbsp;', $s);
}

/**
 * Filtre des liens HTML `<a>` selon la valeur de leur attribut `rel`
 * et ne retourne que ceux là.
 *
 * @filtre
 * @link https://www.spip.net/4187
 *
 * @param string $tags Texte
 * @param string $rels Attribut `rel` à capturer (ou plusieurs séparés par des virgules)
 * @return string Liens trouvés
 **/
function afficher_tags($tags, $rels = 'tag,directory') {
	$s = array();
	foreach (extraire_balises($tags, 'a') as $tag) {
		$rel = extraire_attribut($tag, 'rel');
		if (strstr(",$rels,", ",$rel,")) {
			$s[] = $tag;
		}
	}

	return join(', ', $s);
}


/**
 * Convertir les médias fournis par un flux RSS (podcasts)
 * en liens conformes aux microformats
 *
 * Passe un `<enclosure url="fichier" length="5588242" type="audio/mpeg"/>`
 * au format microformat `<a rel="enclosure" href="fichier" ...>fichier</a>`.
 *
 * Peut recevoir un `<link` ou un `<media:content` parfois.
 *
 * Attention : `length="zz"` devient `title="zz"`, pour rester conforme.
 *
 * @filtre
 * @see microformat2enclosure() Pour l'inverse
 *
 * @param string $e Tag RSS `<enclosure>`
 * @return string Tag HTML `<a>` avec microformat.
 **/
function enclosure2microformat($e) {
	if (!$url = filtrer_entites(extraire_attribut($e, 'url'))) {
		$url = filtrer_entites(extraire_attribut($e, 'href'));
	}
	$type = extraire_attribut($e, 'type');
	if (!$length = extraire_attribut($e, 'length')) {
		# <media:content : longeur dans fileSize. On tente.
		$length = extraire_attribut($e, 'fileSize');
	}
	$fichier = basename($url);

	return '<a rel="enclosure"'
	. ($url ? ' href="' . spip_htmlspecialchars($url) . '"' : '')
	. ($type ? ' type="' . spip_htmlspecialchars($type) . '"' : '')
	. ($length ? ' title="' . spip_htmlspecialchars($length) . '"' : '')
	. '>' . $fichier . '</a>';
}

/**
 * Convertir les liens conformes aux microformats en médias pour flux RSS,
 * par exemple pour les podcasts
 *
 * Passe un texte ayant des liens avec microformat
 * `<a rel="enclosure" href="fichier" ...>fichier</a>`
 * au format RSS `<enclosure url="fichier" ... />`.
 *
 * @filtre
 * @see enclosure2microformat() Pour l'inverse
 *
 * @param string $tags Texte HTML ayant des tag `<a>` avec microformat
 * @return string Tags RSS `<enclosure>`.
 **/
function microformat2enclosure($tags) {
	$enclosures = array();
	foreach (extraire_balises($tags, 'a') as $e) {
		if (extraire_attribut($e, 'rel') == 'enclosure') {
			$url = filtrer_entites(extraire_attribut($e, 'href'));
			$type = extraire_attribut($e, 'type');
			if (!$length = intval(extraire_attribut($e, 'title'))) {
				$length = intval(extraire_attribut($e, 'length'));
			} # vieux data
			$fichier = basename($url);
			$enclosures[] = '<enclosure'
				. ($url ? ' url="' . spip_htmlspecialchars($url) . '"' : '')
				. ($type ? ' type="' . spip_htmlspecialchars($type) . '"' : '')
				. ($length ? ' length="' . $length . '"' : '')
				. ' />';
		}
	}

	return join("\n", $enclosures);
}


/**
 * Créer les éléments ATOM `<dc:subject>` à partir des tags
 *
 * Convertit les liens avec attribut `rel="tag"`
 * en balise `<dc:subject></dc:subject>` pour les flux RSS au format Atom.
 *
 * @filtre
 *
 * @param string $tags Texte
 * @return string Tags RSS Atom `<dc:subject>`.
 **/
function tags2dcsubject($tags) {
	$subjects = '';
	foreach (extraire_balises($tags, 'a') as $e) {
		if (extraire_attribut($e, 'rel') == 'tag') {
			$subjects .= '<dc:subject>'
				. texte_backend(textebrut($e))
				. '</dc:subject>' . "\n";
		}
	}

	return $subjects;
}

/**
 * Retourne la premiere balise html du type demandé
 *
 * Retourne le contenu d'une balise jusqu'à la première fermeture rencontrée
 * du même type.
 * Si on a passe un tableau de textes, retourne un tableau de resultats.
 *
 * @example `[(#DESCRIPTIF|extraire_balise{img})]`
 *
 * @filtre
 * @link https://www.spip.net/4289
 * @see extraire_balises()
 * @note
 *     Attention : les résultats peuvent être incohérents sur des balises imbricables,
 *     tel que demander à extraire `div` dans le texte `<div> un <div> mot </div> absent </div>`,
 *     ce qui retournerait `<div> un <div> mot </div>` donc.
 *
 * @param string|array $texte
 *     Texte(s) dont on souhaite extraire une balise html
 * @param string $tag
 *     Nom de la balise html à extraire
 * @return void|string|array
 *     - Code html de la balise, sinon rien
 *     - Tableau de résultats, si tableau en entrée.
 **/
function extraire_balise($texte, $tag = 'a') {
	if (is_array($texte)) {
		array_walk(
			$texte,
			function(&$a, $key, $t){
				$a = extraire_balise($a, $t);
			},
			$tag
		);

		return $texte;
	}

	if (preg_match(
		",<$tag\b[^>]*(/>|>.*</$tag\b[^>]*>|>),UimsS",
		$texte, $regs)) {
		return $regs[0];
	}
}

/**
 * Extrait toutes les balises html du type demandé
 *
 * Retourne dans un tableau le contenu de chaque balise jusqu'à la première
 * fermeture rencontrée du même type.
 * Si on a passe un tableau de textes, retourne un tableau de resultats.
 *
 * @example `[(#TEXTE|extraire_balises{img}|implode{" - "})]`
 *
 * @filtre
 * @link https://www.spip.net/5618
 * @see extraire_balise()
 * @note
 *     Attention : les résultats peuvent être incohérents sur des balises imbricables,
 *     tel que demander à extraire `div` dans un texte.
 *
 * @param string|array $texte
 *     Texte(s) dont on souhaite extraire une balise html
 * @param string $tag
 *     Nom de la balise html à extraire
 * @return array
 *     - Liste des codes html des occurrences de la balise, sinon tableau vide
 *     - Tableau de résultats, si tableau en entrée.
 **/
function extraire_balises($texte, $tag = 'a') {
	if (is_array($texte)) {
		array_walk(
			$texte,
			function(&$a, $key, $t){
				$a = extraire_balises($a, $t);
			},
			$tag
		);

		return $texte;
	}

	if (preg_match_all(
		",<${tag}\b[^>]*(/>|>.*</${tag}\b[^>]*>|>),UimsS",
		$texte, $regs, PREG_PATTERN_ORDER)) {
		return $regs[0];
	} else {
		return array();
	}
}

/**
 * Indique si le premier argument est contenu dans le second
 *
 * Cette fonction est proche de `in_array()` en PHP avec comme principale
 * différence qu'elle ne crée pas d'erreur si le second argument n'est pas
 * un tableau (dans ce cas elle tentera de le désérialiser, et sinon retournera
 * la valeur par défaut transmise).
 *
 * @example `[(#VAL{deux}|in_any{#LISTE{un,deux,trois}}|oui) ... ]`
 *
 * @filtre
 * @see filtre_find() Assez proche, avec les arguments valeur et tableau inversés.
 *
 * @param string $val
 *     Valeur à chercher dans le tableau
 * @param array|string $vals
 *     Tableau des valeurs. S'il ce n'est pas un tableau qui est transmis,
 *     la fonction tente de la désérialiser.
 * @param string $def
 *     Valeur par défaut retournée si `$vals` n'est pas un tableau.
 * @return string
 *     - ' ' si la valeur cherchée est dans le tableau
 *     - '' si la valeur n'est pas dans le tableau
 *     - `$def` si on n'a pas transmis de tableau
 **/
function in_any($val, $vals, $def = '') {
	if (!is_array($vals) and $v = unserialize($vals)) {
		$vals = $v;
	}

	return (!is_array($vals) ? $def : (in_array($val, $vals) ? ' ' : ''));
}


/**
 * Retourne le résultat d'une expression mathématique simple
 *
 * N'accepte que les *, + et - (à ameliorer si on l'utilise vraiment).
 *
 * @filtre
 * @example
 *      ```
 *      valeur_numerique("3*2") retourne 6
 *      ```
 *
 * @param string $expr
 *     Expression mathématique `nombre operateur nombre` comme `3*2`
 * @return int
 *     Résultat du calcul
 **/
function valeur_numerique($expr) {
	$a = 0;
	if (preg_match(',^[0-9]+(\s*[+*-]\s*[0-9]+)*$,S', trim($expr))) {
		eval("\$a = $expr;");
	}

	return intval($a);
}

/**
 * Retourne un calcul de règle de trois
 *
 * @filtre
 * @example
 *     ```
 *     [(#VAL{6}|regledetrois{4,3})] retourne 8
 *     ```
 *
 * @param int $a
 * @param int $b
 * @param int $c
 * @return int
 *      Retourne `$a*$b/$c`
 **/
function regledetrois($a, $b, $c) {
	return round($a * $b / $c);
}


/**
 * Crée des tags HTML input hidden pour chaque paramètre et valeur d'une URL
 *
 * Fournit la suite de Input-Hidden correspondant aux paramètres de
 * l'URL donnée en argument, compatible avec les types_urls
 *
 * @filtre
 * @link https://www.spip.net/4286
 * @see balise_ACTION_FORMULAIRE()
 *     Également pour transmettre les actions à un formulaire
 * @example
 *     ```
 *     [(#ENV{action}|form_hidden)] dans un formulaire
 *     ```
 *
 * @param string $action URL
 * @return string Suite de champs input hidden
 **/
function form_hidden($action) {

	$contexte = array();
	include_spip('inc/urls');
	if ($p = urls_decoder_url($action, '')
		and reset($p)
	) {
		$fond = array_shift($p);
		if ($fond != '404') {
			$contexte = array_shift($p);
			$contexte['page'] = $fond;
			$action = preg_replace('/([?]' . preg_quote($fond) . '[^&=]*[0-9]+)(&|$)/', '?&', $action);
		}
	}
	// defaire ce qu'a injecte urls_decoder_url : a revoir en modifiant la signature de urls_decoder_url
	if (defined('_DEFINIR_CONTEXTE_TYPE') and _DEFINIR_CONTEXTE_TYPE) {
		unset($contexte['type']);
	}
	if (defined('_DEFINIR_CONTEXTE_TYPE_PAGE') and _DEFINIR_CONTEXTE_TYPE_PAGE) {
		unset($contexte['type-page']);
	}

	// on va remplir un tableau de valeurs en prenant bien soin de ne pas
	// ecraser les elements de la forme mots[]=1&mots[]=2
	$values = array();

	// d'abord avec celles de l'url
	if (false !== ($p = strpos($action, '?'))) {
		foreach (preg_split('/&(amp;)?/S', substr($action, $p + 1)) as $c) {
			$c = explode('=', $c, 2);
			$var = array_shift($c);
			$val = array_shift($c);
			if ($var) {
				$val = rawurldecode($val);
				$var = rawurldecode($var); // decoder les [] eventuels
				if (preg_match(',\[\]$,S', $var)) {
					$values[] = array($var, $val);
				} else {
					if (!isset($values[$var])) {
						$values[$var] = array($var, $val);
					}
				}
			}
		}
	}

	// ensuite avec celles du contexte, sans doublonner !
	foreach ($contexte as $var => $val) {
		if (preg_match(',\[\]$,S', $var)) {
			$values[] = array($var, $val);
		} else {
			if (!isset($values[$var])) {
				$values[$var] = array($var, $val);
			}
		}
	}

	// puis on rassemble le tout
	$hidden = array();
	foreach ($values as $value) {
		list($var, $val) = $value;
		$hidden[] = '<input name="'
			. entites_html($var)
			. '"'
			. (is_null($val)
				? ''
				: ' value="' . entites_html($val) . '"'
			)
			. ' type="hidden"' . "\n/>";
	}

	return join("", $hidden);
}


/**
 * Retourne la première valeur d'un tableau
 *
 * Plus précisément déplace le pointeur du tableau sur la première valeur et la retourne.
 *
 * @example `[(#LISTE{un,deux,trois}|reset)]` retourne 'un'
 *
 * @filtre
 * @link http://php.net/manual/fr/function.reset.php
 * @see filtre_end()
 *
 * @param array $array
 * @return mixed|null|false
 *    - null si $array n'est pas un tableau,
 *    - false si le tableau est vide
 *    - la première valeur du tableau sinon.
 **/
function filtre_reset($array) {
	return !is_array($array) ? null : reset($array);
}

/**
 * Retourne la dernière valeur d'un tableau
 *
 * Plus précisément déplace le pointeur du tableau sur la dernière valeur et la retourne.
 *
 * @example `[(#LISTE{un,deux,trois}|end)]` retourne 'trois'
 *
 * @filtre
 * @link http://php.net/manual/fr/function.end.php
 * @see filtre_reset()
 *
 * @param array $array
 * @return mixed|null|false
 *    - null si $array n'est pas un tableau,
 *    - false si le tableau est vide
 *    - la dernière valeur du tableau sinon.
 **/
function filtre_end($array) {
	return !is_array($array) ? null : end($array);
}

/**
 * Empile une valeur à la fin d'un tableau
 *
 * @example `[(#LISTE{un,deux,trois}|push{quatre}|print)]`
 *
 * @filtre
 * @link https://www.spip.net/4571
 * @link http://php.net/manual/fr/function.array-push.php
 *
 * @param array $array
 * @param mixed $val
 * @return array|string
 *     - '' si $array n'est pas un tableau ou si echec.
 *     - le tableau complété de la valeur sinon.
 *
 **/
function filtre_push($array, $val) {
	if (!is_array($array) or !array_push($array, $val)) {
		return '';
	}

	return $array;
}

/**
 * Indique si une valeur est contenue dans un tableau
 *
 * @example `[(#LISTE{un,deux,trois}|find{quatre}|oui) ... ]`
 *
 * @filtre
 * @link https://www.spip.net/4575
 * @see in_any() Assez proche, avec les paramètres tableau et valeur inversés.
 *
 * @param array $array
 * @param mixed $val
 * @return bool
 *     - `false` si `$array` n'est pas un tableau
 *     - `true` si la valeur existe dans le tableau, `false` sinon.
 **/
function filtre_find($array, $val) {
	return (is_array($array) and in_array($val, $array));
}


/**
 * Passer les url relatives à la css d'origine en url absolues
 *
 * @uses suivre_lien()
 *
 * @param string $contenu
 *     Contenu du fichier CSS
 * @param string $source
 *     Chemin du fichier CSS
 * @return string
 *     Contenu avec urls en absolus
 **/
function urls_absolues_css($contenu, $source) {
	$path = suivre_lien(url_absolue($source), './');

	return preg_replace_callback(
		",url\s*\(\s*['\"]?([^'\"/#\s][^:]*)['\"]?\s*\),Uims",
		function($x) use ($path) {
			return "url('" . suivre_lien($path, $x[1]) . "')";
		},
		$contenu
	);
}


/**
 * Inverse le code CSS (left <--> right) d'une feuille de style CSS
 *
 * Récupère le chemin d'une CSS existante et :
 *
 * 1. regarde si une CSS inversée droite-gauche existe dans le meme répertoire
 * 2. sinon la crée (ou la recrée) dans `_DIR_VAR/cache_css/`
 *
 * Si on lui donne à manger une feuille nommée `*_rtl.css` il va faire l'inverse.
 *
 * @filtre
 * @example
 *     ```
 *     [<link rel="stylesheet" href="(#CHEMIN{css/perso.css}|direction_css)" type="text/css" />]
 *     ```
 * @param string $css
 *     Chemin vers le fichier CSS
 * @param string $voulue
 *     Permet de forcer le sens voulu (en indiquant `ltr`, `rtl` ou un
 *     code de langue). En absence, prend le sens de la langue en cours.
 *
 * @return string
 *     Chemin du fichier CSS inversé
 **/
function direction_css($css, $voulue = '') {
	if (!preg_match(',(_rtl)?\.css$,i', $css, $r)) {
		return $css;
	}
	include_spip("inc/lang");
	// si on a precise le sens voulu en argument, le prendre en compte
	if ($voulue = strtolower($voulue)) {
		if ($voulue != 'rtl' and $voulue != 'ltr') {
			$voulue = lang_dir($voulue);
		}
	} else {
		$voulue = lang_dir();
	}

	$r = count($r) > 1;
	$right = $r ? 'left' : 'right'; // 'right' de la css lue en entree
	$dir = $r ? 'rtl' : 'ltr';
	$ndir = $r ? 'ltr' : 'rtl';

	if ($voulue == $dir) {
		return $css;
	}

	if (
		// url absolue
		preg_match(",^https?:,i", $css)
		// ou qui contient un ?
		or (($p = strpos($css, '?')) !== false)
	) {
		$distant = true;
		$cssf = parse_url($css);
		$cssf = $cssf['path'] . ($cssf['query'] ? "?" . $cssf['query'] : "");
		$cssf = preg_replace(',[?:&=],', "_", $cssf);
	} else {
		$distant = false;
		$cssf = $css;
		// 1. regarder d'abord si un fichier avec la bonne direction n'est pas aussi
		//propose (rien a faire dans ce cas)
		$f = preg_replace(',(_rtl)?\.css$,i', '_' . $ndir . '.css', $css);
		if (@file_exists($f)) {
			return $f;
		}
	}

	// 2.
	$dir_var = sous_repertoire(_DIR_VAR, 'cache-css');
	$f = $dir_var
		. preg_replace(',.*/(.*?)(_rtl)?\.css,', '\1', $cssf)
		. '.' . substr(md5($cssf), 0, 4) . '_' . $ndir . '.css';

	// la css peut etre distante (url absolue !)
	if ($distant) {
		include_spip('inc/distant');
		$res = recuperer_url($css);
		if (!$res or !$contenu = $res['page']) {
			return $css;
		}
	} else {
		if ((@filemtime($f) > @filemtime($css))
			and (_VAR_MODE != 'recalcul')
		) {
			return $f;
		}
		if (!lire_fichier($css, $contenu)) {
			return $css;
		}
	}


	// Inverser la direction gauche-droite en utilisant CSSTidy qui gere aussi les shorthands
	include_spip("lib/csstidy/class.csstidy");
	$parser = new csstidy();
	$parser->set_cfg('optimise_shorthands', 0);
	$parser->set_cfg('reverse_left_and_right', true);
	$parser->parse($contenu);

	$contenu = $parser->print->plain();


	// reperer les @import auxquels il faut propager le direction_css
	preg_match_all(",\@import\s*url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims", $contenu, $regs);
	$src = array();
	$src_direction_css = array();
	$src_faux_abs = array();
	$d = dirname($css);
	foreach ($regs[1] as $k => $import_css) {
		$css_direction = direction_css("$d/$import_css", $voulue);
		// si la css_direction est dans le meme path que la css d'origine, on tronque le path, elle sera passee en absolue
		if (substr($css_direction, 0, strlen($d) + 1) == "$d/") {
			$css_direction = substr($css_direction, strlen($d) + 1);
		} // si la css_direction commence par $dir_var on la fait passer pour une absolue
		elseif (substr($css_direction, 0, strlen($dir_var)) == $dir_var) {
			$css_direction = substr($css_direction, strlen($dir_var));
			$src_faux_abs["/@@@@@@/" . $css_direction] = $css_direction;
			$css_direction = "/@@@@@@/" . $css_direction;
		}
		$src[] = $regs[0][$k];
		$src_direction_css[] = str_replace($import_css, $css_direction, $regs[0][$k]);
	}
	$contenu = str_replace($src, $src_direction_css, $contenu);

	$contenu = urls_absolues_css($contenu, $css);

	// virer les fausses url absolues que l'on a mis dans les import
	if (count($src_faux_abs)) {
		$contenu = str_replace(array_keys($src_faux_abs), $src_faux_abs, $contenu);
	}

	if (!ecrire_fichier($f, $contenu)) {
		return $css;
	}

	return $f;
}


/**
 * Transforme les urls relatives d'un fichier CSS en absolues
 *
 * Récupère le chemin d'une css existante et crée (ou recrée) dans `_DIR_VAR/cache_css/`
 * une css dont les url relatives sont passées en url absolues
 *
 * Le calcul n'est pas refait si le fichier cache existe déjà et que
 * la source n'a pas été modifiée depuis.
 *
 * @uses recuperer_page() si l'URL source n'est pas sur le même site
 * @uses urls_absolues_css()
 *
 * @param string $css
 *     Chemin ou URL du fichier CSS source
 * @return string
 *     - Chemin du fichier CSS transformé (si source lisible et mise en cache réussie)
 *     - Chemin ou URL du fichier CSS source sinon.
 **/
function url_absolue_css($css) {
	if (!preg_match(',\.css$,i', $css, $r)) {
		return $css;
	}

	$url_absolue_css = url_absolue($css);

	$f = basename($css, '.css');
	$f = sous_repertoire(_DIR_VAR, 'cache-css')
		. preg_replace(",(.*?)(_rtl|_ltr)?$,", "\\1-urlabs-" . substr(md5("$css-urlabs"), 0, 4) . "\\2", $f)
		. '.css';

	if ((@filemtime($f) > @filemtime($css)) and (_VAR_MODE != 'recalcul')) {
		return $f;
	}

	if ($url_absolue_css == $css) {
		if (strncmp($GLOBALS['meta']['adresse_site'], $css, $l = strlen($GLOBALS['meta']['adresse_site'])) != 0
			or !lire_fichier(_DIR_RACINE . substr($css, $l), $contenu)
		) {
			include_spip('inc/distant');
			if (!$contenu = recuperer_page($css)) {
				return $css;
			}
		}
	} elseif (!lire_fichier($css, $contenu)) {
		return $css;
	}

	// passer les url relatives a la css d'origine en url absolues
	$contenu = urls_absolues_css($contenu, $css);

	// ecrire la css
	if (!ecrire_fichier($f, $contenu)) {
		return $css;
	}

	return $f;
}


/**
 * Récupère la valeur d'une clé donnée
 * dans un tableau (ou un objet).
 *
 * @filtre
 * @link https://www.spip.net/4572
 * @example
 *     ```
 *     [(#VALEUR|table_valeur{cle/sous/element})]
 *     ```
 *
 * @param mixed $table
 *     Tableau ou objet PHP
 *     (ou chaîne serialisée de tableau, ce qui permet d'enchaîner le filtre)
 * @param string $cle
 *     Clé du tableau (ou paramètre public de l'objet)
 *     Cette clé peut contenir des caractères / pour sélectionner
 *     des sous éléments dans le tableau, tel que `sous/element/ici`
 *     pour obtenir la valeur de `$tableau['sous']['element']['ici']`
 * @param mixed $defaut
 *     Valeur par defaut retournée si la clé demandée n'existe pas
 * @param bool  $conserver_null
 *     Permet de forcer la fonction à renvoyer la valeur null d'un index
 *     et non pas $defaut comme cela est fait naturellement par la fonction
 *     isset. On utilise alors array_key_exists() à la place de isset().
 * 
 * @return mixed
 *     Valeur trouvée ou valeur par défaut.
 **/
function table_valeur($table, $cle, $defaut = '', $conserver_null = false) {
	foreach (explode('/', $cle) as $k) {

		$table = is_string($table) ? @unserialize($table) : $table;

		if (is_object($table)) {
			$table = (($k !== "") and isset($table->$k)) ? $table->$k : $defaut;
		} elseif (is_array($table)) {
			if ($conserver_null) {
				$table = array_key_exists($k, $table) ? $table[$k] : $defaut;
			} else {
				$table = isset($table[$k]) ? $table[$k] : $defaut;
			}
		} else {
			$table = $defaut;
		}
	}

	return $table;
}

/**
 * Retrouve un motif dans un texte à partir d'une expression régulière
 *
 * S'appuie sur la fonction `preg_match()` en PHP
 *
 * @example
 *    - `[(#TITRE|match{toto})]`
 *    - `[(#TEXTE|match{^ceci$,Uims})]`
 *    - `[(#TEXTE|match{truc(...)$, UimsS, 1})]` Capture de la parenthèse indiquée
 *    - `[(#TEXTE|match{truc(...)$, 1})]` Équivalent, sans indiquer les modificateurs
 *
 * @filtre
 * @link https://www.spip.net/4299
 * @link http://php.net/manual/fr/function.preg-match.php Pour des infos sur `preg_match()`
 *
 * @param string $texte
 *     Texte dans lequel chercher
 * @param string|int $expression
 *     Expression régulière de recherche, sans le délimiteur
 * @param string $modif
 *     - string : Modificateurs de l'expression régulière
 *     - int : Numéro de parenthèse capturante
 * @param int $capte
 *     Numéro de parenthèse capturante
 * @return bool|string
 *     - false : l'expression n'a pas été trouvée
 *     - true : expression trouvée, mais pas la parenthèse capturante
 *     - string : expression trouvée.
 **/
function filtre_match_dist($texte, $expression, $modif = "UimsS", $capte = 0) {
	if (intval($modif) and $capte == 0) {
		$capte = $modif;
		$modif = "UimsS";
	}
	$expression = str_replace("\/", "/", $expression);
	$expression = str_replace("/", "\/", $expression);

	if (preg_match('/' . $expression . '/' . $modif, $texte, $r)) {
		if (isset($r[$capte])) {
			return $r[$capte];
		} else {
			return true;
		}
	}

	return false;
}


/**
 * Remplacement de texte à base d'expression régulière
 *
 * @filtre
 * @link https://www.spip.net/4309
 * @see match()
 * @example
 *     ```
 *     [(#TEXTE|replace{^ceci$,cela,UimsS})]
 *     ```
 *
 * @param string $texte
 *     Texte
 * @param string $expression
 *     Expression régulière
 * @param string $replace
 *     Texte de substitution des éléments trouvés
 * @param string $modif
 *     Modificateurs pour l'expression régulière.
 * @return string
 *     Texte
 **/
function replace($texte, $expression, $replace = '', $modif = "UimsS") {
	$expression = str_replace("\/", "/", $expression);
	$expression = str_replace("/", "\/", $expression);

	return preg_replace('/' . $expression . '/' . $modif, $replace, $texte);
}


/**
 * Cherche les documents numerotés dans un texte traite par `propre()`
 *
 * Affecte la liste des doublons['documents']
 *
 * @param array $doublons
 *     Liste des doublons
 * @param string $letexte
 *     Le texte
 * @return string
 *     Le texte
 **/
function traiter_doublons_documents(&$doublons, $letexte) {

	// Verifier dans le texte & les notes (pas beau, helas)
	$t = $letexte . $GLOBALS['les_notes'];

	if (strstr($t, 'spip_document_') // evite le preg_match_all si inutile
		and preg_match_all(
			',<[^>]+\sclass=["\']spip_document_([0-9]+)[\s"\'],imsS',
			$t, $matches, PREG_PATTERN_ORDER)
	) {
		if (!isset($doublons['documents'])) {
			$doublons['documents'] = "";
		}
		$doublons['documents'] .= "," . join(',', $matches[1]);
	}

	return $letexte;
}

/**
 * Filtre vide qui ne renvoie rien
 *
 * @example
 *     `[(#CALCUL|vide)]` n'affichera pas le résultat du calcul
 * @filtre
 *
 * @param mixed $texte
 * @return string Chaîne vide
 **/
function vide($texte) {
	return "";
}

//
// Filtres pour le modele/emb (embed document)
//

/**
 * Écrit des balises HTML `<param...>` à partir d'un tableau de données tel que `#ENV`
 *
 * Permet d'écrire les balises `<param>` à indiquer dans un `<object>`
 * en prenant toutes les valeurs du tableau transmis.
 *
 * Certaines clés spécifiques à SPIP et aux modèles embed sont omises :
 * id, lang, id_document, date, date_redac, align, fond, recurs, emb, dir_racine
 *
 * @example `[(#ENV*|env_to_params)]`
 *
 * @filtre
 * @link https://www.spip.net/4005
 *
 * @param array|string $env
 *      Tableau cle => valeur des paramètres à écrire, ou chaine sérialisée de ce tableau
 * @param array $ignore_params
 *      Permet de compléter les clés ignorées du tableau.
 * @return string
 *      Code HTML résultant
 **/
function env_to_params($env, $ignore_params = array()) {
	$ignore_params = array_merge(
		array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
		$ignore_params
	);
	if (!is_array($env)) {
		$env = unserialize($env);
	}
	$texte = "";
	if ($env) {
		foreach ($env as $i => $j) {
			if (is_string($j) and !in_array($i, $ignore_params)) {
				$texte .= "<param name='" . attribut_html($i) . "'\n\tvalue='" . attribut_html($j) . "' />";
			}
		}
	}

	return $texte;
}

/**
 * Écrit des attributs HTML à partir d'un tableau de données tel que `#ENV`
 *
 * Permet d'écrire des attributs d'une balise HTML en utilisant les données du tableau transmis.
 * Chaque clé deviendra le nom de l'attribut (et la valeur, sa valeur)
 *
 * Certaines clés spécifiques à SPIP et aux modèles embed sont omises :
 * id, lang, id_document, date, date_redac, align, fond, recurs, emb, dir_racine
 *
 * @example `<embed src='#URL_DOCUMENT' [(#ENV*|env_to_attributs)] width='#GET{largeur}' height='#GET{hauteur}'></embed>`
 * @filtre
 *
 * @param array|string $env
 *      Tableau cle => valeur des attributs à écrire, ou chaine sérialisée de ce tableau
 * @param array $ignore_params
 *      Permet de compléter les clés ignorées du tableau.
 * @return string
 *      Code HTML résultant
 **/
function env_to_attributs($env, $ignore_params = array()) {
	$ignore_params = array_merge(
		array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
		$ignore_params
	);
	if (!is_array($env)) {
		$env = unserialize($env);
	}
	$texte = "";
	if ($env) {
		foreach ($env as $i => $j) {
			if (is_string($j) and !in_array($i, $ignore_params)) {
				$texte .= attribut_html($i) . "='" . attribut_html($j) . "' ";
			}
		}
	}

	return $texte;
}


/**
 * Concatène des chaînes
 *
 * @filtre
 * @link https://www.spip.net/4150
 * @example
 *     ```
 *     #TEXTE|concat{texte1,texte2,...}
 *     ```
 *
 * @return string Chaînes concaténés
 **/
function concat() {
	$args = func_get_args();

	return join('', $args);
}


/**
 * Retourne le contenu d'un ou plusieurs fichiers
 *
 * Les chemins sont cherchés dans le path de SPIP
 *
 * @see balise_INCLURE_dist() La balise `#INCLURE` peut appeler cette fonction
 *
 * @param array|string $files
 *     - array : Liste de fichiers
 *     - string : fichier ou fichiers séparés par `|`
 * @param bool $script
 *     - si true, considère que c'est un fichier js à chercher `javascript/`
 * @return string
 *     Contenu du ou des fichiers, concaténé
 **/
function charge_scripts($files, $script = true) {
	$flux = "";
	foreach (is_array($files) ? $files : explode("|", $files) as $file) {
		if (!is_string($file)) {
			continue;
		}
		if ($script) {
			$file = preg_match(",^\w+$,", $file) ? "javascript/$file.js" : '';
		}
		if ($file) {
			$path = find_in_path($file);
			if ($path) {
				$flux .= spip_file_get_contents($path);
			}
		}
	}

	return $flux;
}


/**
 * Produit une balise img avec un champ alt d'office si vide
 *
 * Attention le htmlentities et la traduction doivent être appliqués avant.
 *
 * @param string $img
 * @param string $alt
 * @param string $atts
 * @param string $title
 * @param array $options
 *   chemin_image : utiliser chemin_image sur $img fourni, ou non (oui par dafaut)
 *   utiliser_suffixe_size : utiliser ou non le suffixe de taille dans le nom de fichier de l'image
 *   sous forme -xx.png (pour les icones essentiellement) (oui par defaut)
 *   variante_svg_si_possible: utiliser l'image -xx.svg au lieu de -32.png par exemple (si la variante svg est disponible)
 * @return string
 */
function http_img_pack($img, $alt, $atts = '', $title = '', $options = array()) {

	$img_file = $img;
	if ($p = strpos($img_file, '?')) {
		$img_file = substr($img_file,0, $p);
	}
	if (!isset($options['chemin_image']) or $options['chemin_image'] == true) {
		$img_file = chemin_image($img);
	}
	else {
		if (!isset($options['variante_svg_si_possible']) or $options['variante_svg_si_possible'] == true){
			// on peut fournir une icone generique -xx.svg qui fera le job dans toutes les tailles, et qui est prioritaire sur le png
			// si il y a un .svg a la bonne taille (-16.svg) a cote, on l'utilise en remplacement du -16.png
			if (preg_match(',-(\d+)[.](png|gif|svg)$,', $img_file, $m)
			  and $variante_svg_generique = substr($img_file, 0, -strlen($m[0])) . "-xx.svg"
			  and file_exists($variante_svg_generique)) {
				if ($variante_svg_size = substr($variante_svg_generique,0,-6) . $m[1] . ".svg" and file_exists($variante_svg_size)) {
					$img_file = $variante_svg_size;
				}
				else {
					$img_file = $variante_svg_generique;
				}
			}
		}
	}
	if (stripos($atts, 'width') === false) {
		// utiliser directement l'info de taille presente dans le nom
		if ((!isset($options['utiliser_suffixe_size'])
				or $options['utiliser_suffixe_size'] == true
			  or strpos($img_file, '-xx.svg') !== false)
			and (preg_match(',-([0-9]+)[.](png|gif|svg)$,', $img, $regs)
					 or preg_match(',\?([0-9]+)px$,', $img, $regs))
		) {
			$largeur = $hauteur = intval($regs[1]);
		} else {
			$taille = taille_image($img_file);
			list($hauteur, $largeur) = $taille;
			if (!$hauteur or !$largeur) {
				return "";
			}
		}
		$atts .= " width='" . $largeur . "' height='" . $hauteur . "'";
	}

	if (file_exists($img_file)) {
		$img_file = timestamp($img_file);
	}
	if ($alt === false) {
		$alt = '';
	}
	elseif($alt or $alt==='') {
		$alt = " alt='".attribut_html($alt)."'";
	}
	else {
		$alt = " alt='".attribut_html($title)."'";
	}
	return "<img src='$img_file'$alt"
	. ($title ? ' title="' . attribut_html($title) . '"' : '')
	. " " . ltrim($atts)
	. " />";
}

/**
 * Générer une directive `style='background:url()'` à partir d'un fichier image
 *
 * @param string $img
 * @param string $att
 * @param string $size
 * @return string
 */
function http_style_background($img, $att = '', $size=null) {
	if ($size and is_numeric($size)){
		$size = trim($size) . "px";
	}
	return " style='background" .
		($att ? "" : "-image") . ": url(\"" . chemin_image($img) . "\")" . ($att ? (' ' . $att) : '') . ";"
		. ($size ? "background-size:{$size};" : '')
		. "'";
}


function helper_filtre_balise_img_svg_arguments($alt_or_size, $class_or_size, $size) {
	$args = [$alt_or_size, $class_or_size, $size];
	while (is_null(end($args)) and count($args)) {
		array_pop($args);
	}
	if (!count($args)) {
		return [null, null, null];
	}
	if (count($args) < 3) {
		$maybe_size = array_pop($args);
		// @2x
		// @1.5x
		// 512
		// 512x*
		// 512x300
		if (!strlen($maybe_size)
			or !preg_match(',^(@\d+(\.\d+)?x|\d+(x\*)?|\d+x\d+)$,', trim($maybe_size))) {
			$args[] = $maybe_size;
			$maybe_size = null;
		}
		while (count($args)<2) {
			$args[] = null; // default alt or class
		}
		$args[] = $maybe_size;
	}
	return $args;
}

function helper_filtre_balise_img_svg_size($img, $size) {
	// si size est de la forme '@2x' c'est un coeff multiplicateur sur la densite
	if (strpos($size, '@') === 0 and substr($size,-1) === 'x') {
		$coef = floatval(substr($size, 1, -1));
		list($h, $w) = taille_image($img);
		$height = intval(round($h / $coef));
		$width = intval(round($w / $coef));
	}
	// sinon c'est une valeur seule si image caree ou largeurxhauteur
	else {
		$size = explode('x', $size, 2);
		$size = array_map('trim', $size);
		$height = $width = intval(array_shift($size));

		if (count($size) and reset($size)) {
			$height = array_shift($size);
			if ($height === '*') {
				list($h, $w) = taille_image($img);
				$height = intval(round($h * $width / $w));
			}
		}
	}

	return [$width, $height];
}

/**
 * Générer une balise HTML `img` à partir d'un nom de fichier et/ou renseigne son alt/class/width/height
 * selon les arguments passés
 *
 * Le class et le alt peuvent etre omis et dans ce cas size peut-être renseigné comme dernier argument :
 * [(#FICHIER|balise_img{@2x})]
 * [(#FICHIER|balise_img{1024})]
 * [(#FICHIER|balise_img{1024x*})]
 * [(#FICHIER|balise_img{1024x640})]
 * [(#FICHIER|balise_img{'un nuage',1024x640})]
 * [(#FICHIER|balise_img{'un nuage','spip_logo',1024x640})]
 * Si le alt ou la class sont ambigu et peuvent etre interpretes comme une taille, il suffit d'indiquer une taille vide pour lever l'ambiguite
 * [(#FICHIER|balise_img{'@2x','',''})]
 *
 * @uses http_img_pack()
 *
 * @param string $img
 *   chemin vers un fichier ou balise `<img src='...' />` (generee par un filtre image par exemple)
 * @param string $alt
 *   texte alternatif ; une valeur nulle pour explicitement ne pas avoir de balise alt sur l'image (au lieu d'un alt vide)
 * @param string $class
 *   attribut class ; null par defaut (ie si img est une balise, son attribut class sera inchange. pas de class inseree
 * @param string|int $size
 *   taille imposee
 *     @2x : pour imposer une densite x2 (widht et height seront divisees par 2)
 *     largeur : pour une image carree
 *     largeurx* : pour imposer uniquement la largeur (un attribut height sera aussi ajoute, mais calcule automatiquement pour respecter le ratio initial de l'image)
 *     largeurxhauteur pour fixer les 2 dimensions
 * @return string
 *     Code HTML de la balise IMG
 */
function filtre_balise_img_dist($img, $alt = '', $class = null, $size=null) {

	list($alt, $class, $size) = helper_filtre_balise_img_svg_arguments($alt, $class, $size);

	$img = trim($img);
	if (strpos($img, '<img') === 0) {
		if (!is_null($alt)) {
			$img = inserer_attribut($img, 'alt', $alt);
		}
		if (!is_null($class)) {
			if (strlen($class)) {
				$img = inserer_attribut($img, 'class', $class);
			}
			else {
				$img = vider_attribut($img, 'class');
			}
		}
	}
	else {
		$img = http_img_pack($img, $alt, $class ? " class='" . attribut_html($class) . "'" : '', '',
				array('chemin_image' => false, 'utiliser_suffixe_size' => false));
		if (is_null($alt)) {
			$img = vider_attribut($img, 'alt');
		}
	}

	if ($img and !is_null($size) and strlen($size = trim($size))) {
		list($width, $height) = helper_filtre_balise_img_svg_size($img, $size);

		$img = inserer_attribut($img, 'width', $width);
		$img = inserer_attribut($img, 'height', $height);
	}

	return $img;
}


/**
 * Inserer un svg inline
 * http://www.accede-web.com/notices/html-css-javascript/6-images-icones/6-2-svg-images-vectorielles/
 *
 * pour l'inserer avec une balise <img>, utiliser le filtre |balise_img
 *
 * Le class et le alt peuvent etre omis et dans ce cas size peut-être renseigné comme dernier argument :
 * [(#FICHIER|balise_svg{1024x640})]
 * [(#FICHIER|balise_svg{'un nuage',1024x640})]
 * [(#FICHIER|balise_svg{'un nuage','spip_logo',1024x640})]
 * Si le alt ou la class sont ambigu et peuvent etre interpretes comme une taille, il suffit d'indiquer une taille vide pour lever l'ambiguite
 * [(#FICHIER|balise_svg{'un nuage','@2x',''})]
 *
 * @param string $img
 *   chemin vers un fichier ou balise `<svg ... >... </svg>` deja preparee
 * @param string $alt
 *   texte alternatif ; une valeur nulle pour explicitement ne pas avoir de balise alt sur l'image (au lieu d'un alt vide)
 * @param string $class
 *   attribut class ; null par defaut (ie si img est une balise, son attribut class sera inchange. pas de class inseree
 * @param string|int $size
 *   taille imposee
 *     @2x : pour imposer une densite x2 (widht et height seront divisees par 2)
 *     largeur : pour une image carree
 *     largeurx* : pour imposer uniquement la largeur (un attribut height sera aussi ajoute, mais calcule automatiquement pour respecter le ratio initial de l'image)
 *     largeurxhauteur pour fixer les 2 dimensions
 * @return string
 *     Code HTML de la balise SVG
 */
function filtre_balise_svg_dist($img, $alt = '', $class = null, $size=null) {

	$img = trim($img);
	$img_file = $img;
	if (strpos($img, '<svg') === false){
		if ($p = strpos($img_file, '?')){
			$img_file = substr($img_file, 0, $p);
		}

		if (!$img_file or !$svg = file_get_contents($img_file)){
			return '';
		}
	}

	if (!preg_match(",<svg\b[^>]*>,UimsS", $svg, $match)) {
		return '';
	}

	list($alt, $class, $size) = helper_filtre_balise_img_svg_arguments($alt, $class, $size);

	$balise_svg = $match[0];
	$balise_svg_source = $balise_svg;

	// entete XML à supprimer
	$svg = preg_replace(',^\s*<\?xml[^>]*\?' . '>,', '', $svg);

	// IE est toujours mon ami
	$balise_svg = inserer_attribut($balise_svg, 'focusable', 'false');

	// regler la classe
	if (!is_null($class)) {
		if (strlen($class)) {
			$balise_svg = inserer_attribut($balise_svg, 'class', $class);
		}
		else {
			$balise_svg = vider_attribut($balise_svg, 'class');
		}
	}

	// regler le alt
	if ($alt){
		$balise_svg = inserer_attribut($balise_svg, 'role', 'img');
		$id = "img-svg-title-" . substr(md5("$img_file:$svg:$alt"),0,4);
		$balise_svg = inserer_attribut($balise_svg, 'aria-labelledby', $id);
		$title = "<title id=\"$id\">" . entites_html($alt)."</title>\n";
		$balise_svg .= $title;
	}
	else {
		$balise_svg = inserer_attribut($balise_svg, 'aria-hidden', 'true');
	}

	$svg = str_replace($balise_svg_source, $balise_svg, $svg);

	if (!is_null($size) and strlen($size = trim($size))) {
		list($width, $height) = helper_filtre_balise_img_svg_size($svg, $size);

		if (!function_exists('svg_redimensionner')) {
			include_spip('inc/svg');
		}
		$svg = svg_redimensionner($svg, $width, $height);
	}

	return $svg;
}



/**
 * Affiche chaque valeur d'un tableau associatif en utilisant un modèle
 *
 * @example
 *     - `[(#ENV*|unserialize|foreach)]`
 *     - `[(#ARRAY{a,un,b,deux}|foreach)]`
 *
 * @filtre
 * @link https://www.spip.net/4248
 *
 * @param array $tableau
 *     Tableau de données à afficher
 * @param string $modele
 *     Nom du modèle à utiliser
 * @return string
 *     Code HTML résultant
 **/
function filtre_foreach_dist($tableau, $modele = 'foreach') {
	$texte = '';
	if (is_array($tableau)) {
		foreach ($tableau as $k => $v) {
			$res = recuperer_fond('modeles/' . $modele,
				array_merge(array('cle' => $k), (is_array($v) ? $v : array('valeur' => $v)))
			);
			$texte .= $res;
		}
	}

	return $texte;
}


/**
 * Obtient des informations sur les plugins actifs
 *
 * @filtre
 * @uses liste_plugin_actifs() pour connaître les informations affichables
 *
 * @param string $plugin
 *     Préfixe du plugin ou chaîne vide
 * @param string $type_info
 *     Type d'info demandée
 * @param bool $reload
 *     true (à éviter) pour forcer le recalcul du cache des informations des plugins.
 * @return array|string|bool
 *
 *     - Liste sérialisée des préfixes de plugins actifs (si $plugin = '')
 *     - Suivant $type_info, avec $plugin un préfixe
 *         - est_actif : renvoie true s'il est actif, false sinon
 *         - x : retourne l'information x du plugin si présente (et plugin actif)
 *         - tout : retourne toutes les informations du plugin actif
 **/
function filtre_info_plugin_dist($plugin, $type_info, $reload = false) {
	include_spip('inc/plugin');
	$plugin = strtoupper($plugin);
	$plugins_actifs = liste_plugin_actifs();

	if (!$plugin) {
		return serialize(array_keys($plugins_actifs));
	} elseif (empty($plugins_actifs[$plugin]) and !$reload) {
		return '';
	} elseif (($type_info == 'est_actif') and !$reload) {
		return $plugins_actifs[$plugin] ? 1 : 0;
	} elseif (isset($plugins_actifs[$plugin][$type_info]) and !$reload) {
		return $plugins_actifs[$plugin][$type_info];
	} else {
		$get_infos = charger_fonction('get_infos', 'plugins');
		// On prend en compte les extensions
		if (!is_dir($plugins_actifs[$plugin]['dir_type'])) {
			$dir_plugins = constant($plugins_actifs[$plugin]['dir_type']);
		} else {
			$dir_plugins = $plugins_actifs[$plugin]['dir_type'];
		}
		if (!$infos = $get_infos($plugins_actifs[$plugin]['dir'], $reload, $dir_plugins)) {
			return '';
		}
		if ($type_info == 'tout') {
			return $infos;
		} elseif ($type_info == 'est_actif') {
			return $infos ? 1 : 0;
		} else {
			return strval($infos[$type_info]);
		}
	}
}


/**
 * Affiche la puce statut d'un objet, avec un menu rapide pour changer
 * de statut si possibilité de l'avoir
 *
 * @see inc_puce_statut_dist()
 *
 * @filtre
 *
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $statut
 *     Statut actuel de l'objet
 * @param int $id_rubrique
 *     Identifiant du parent
 * @param string $type
 *     Type d'objet
 * @param bool $ajax
 *     Indique s'il ne faut renvoyer que le coeur du menu car on est
 *     dans une requete ajax suite à un post de changement rapide
 * @return string
 *     Code HTML de l'image de puce de statut à insérer (et du menu de changement si présent)
 */
function puce_changement_statut($id_objet, $statut, $id_rubrique, $type, $ajax = false) {
	$puce_statut = charger_fonction('puce_statut', 'inc');

	return $puce_statut($id_objet, $statut, $id_rubrique, $type, $ajax);
}


/**
 * Affiche la puce statut d'un objet, avec un menu rapide pour changer
 * de statut si possibilité de l'avoir
 *
 * Utilisable sur tout objet qui a declaré ses statuts
 *
 * @example
 *     [(#STATUT|puce_statut{article})] affiche une puce passive
 *     [(#STATUT|puce_statut{article,#ID_ARTICLE,#ID_RUBRIQUE})] affiche une puce avec changement rapide
 *
 * @see inc_puce_statut_dist()
 *
 * @filtre
 *
 * @param string $statut
 *     Statut actuel de l'objet
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param int $id_parent
 *     Identifiant du parent
 * @return string
 *     Code HTML de l'image de puce de statut à insérer (et du menu de changement si présent)
 */
function filtre_puce_statut_dist($statut, $objet, $id_objet = 0, $id_parent = 0) {
	static $puce_statut = null;
	if (!$puce_statut) {
		$puce_statut = charger_fonction('puce_statut', 'inc');
	}

	return $puce_statut($id_objet, $statut, $id_parent, $objet, false,
		objet_info($objet, 'editable') ? _ACTIVER_PUCE_RAPIDE : false);
}


/**
 * Encoder un contexte pour l'ajax
 *
 * Encoder le contexte, le signer avec une clé, le crypter
 * avec le secret du site, le gziper si possible.
 *
 * L'entrée peut-être sérialisée (le `#ENV**` des fonds ajax et ajax_stat)
 *
 * @see  decoder_contexte_ajax()
 * @uses calculer_cle_action()
 *
 * @param string|array $c
 *   contexte, peut etre un tableau serialize
 * @param string $form
 *   nom du formulaire eventuel
 * @param string $emboite
 *   contenu a emboiter dans le conteneur ajax
 * @param string $ajaxid
 *   ajaxid pour cibler le bloc et forcer sa mise a jour
 * @return string
 *   hash du contexte
 */
function encoder_contexte_ajax($c, $form = '', $emboite = null, $ajaxid = '') {
	if (is_string($c)
		and @unserialize($c) !== false
	) {
		$c = unserialize($c);
	}

	// supprimer les parametres debut_x
	// pour que la pagination ajax ne soit pas plantee
	// si on charge la page &debut_x=1 : car alors en cliquant sur l'item 0,
	// le debut_x=0 n'existe pas, et on resterait sur 1
	if (is_array($c)) {
		foreach ($c as $k => $v) {
			if (strpos($k, 'debut_') === 0) {
				unset($c[$k]);
			}
		}
	}

	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}

	$c = serialize($c);
	$cle = calculer_cle_action($form . $c);
	$c = "$cle:$c";

	// on ne stocke pas les contextes dans des fichiers en cache
	// par defaut, sauf si cette configuration a été forcée
	// OU que la longueur de l’argument géneré est plus long
	// que ce qui est toléré.
	$cache_contextes_ajax = (defined('_CACHE_CONTEXTES_AJAX') and _CACHE_CONTEXTES_AJAX);
	if (!$cache_contextes_ajax) {
		$env = $c;
		if (function_exists('gzdeflate') && function_exists('gzinflate')) {
			$env = gzdeflate($env);
		}
		$env = _xor($env);
		$env = base64_encode($env);
		$len = strlen($env);
		// Si l’url est trop longue pour le navigateur
		$max_len = _CACHE_CONTEXTES_AJAX_SUR_LONGUEUR;
		if ($len > $max_len) {
			$cache_contextes_ajax = true;
			spip_log("Contextes AJAX forces en fichiers !"
				. " Cela arrive lorsque la valeur du contexte" 
				. " depasse la longueur maximale autorisee ($max_len). Ici : $len."
				, _LOG_AVERTISSEMENT);
		}
		// Sinon si Suhosin est actif et a une la valeur maximale des variables en GET...
		elseif (
			$max_len = @ini_get('suhosin.get.max_value_length')
			and $max_len < $len
		) {
			$cache_contextes_ajax = true;
			spip_log("Contextes AJAX forces en fichiers !"
				. " Cela arrive lorsque la valeur du contexte"
				. " depasse la longueur maximale autorisee par Suhosin"
				. " ($max_len) dans 'suhosin.get.max_value_length'. Ici : $len."
				. " Vous devriez modifier les parametres de Suhosin"
				. " pour accepter au moins 1024 caracteres.", _LOG_AVERTISSEMENT);
		} 

	}

	if ($cache_contextes_ajax) {
		$dir = sous_repertoire(_DIR_CACHE, 'contextes');
		// stocker les contextes sur disque et ne passer qu'un hash dans l'url
		$md5 = md5($c);
		ecrire_fichier("$dir/c$md5", $c);
		$env = $md5;
	}

	if ($emboite === null) {
		return $env;
	}
	if (!trim($emboite)) {
		return "";
	}
	// toujours encoder l'url source dans le bloc ajax
	$r = self();
	$r = ' data-origin="' . $r . '"';
	$class = 'ajaxbloc';
	if ($ajaxid and is_string($ajaxid)) {
		// ajaxid est normalement conforme a un nom de classe css
		// on ne verifie pas la conformite, mais on passe entites_html par dessus par precaution
		$class .= ' ajax-id-' . entites_html($ajaxid);
	}

	return "<div class='$class' " . "data-ajax-env='$env'$r>\n$emboite</div><!--ajaxbloc-->\n";
}

/**
 * Décoder un hash de contexte pour l'ajax
 *
 * Précude inverse de `encoder_contexte_ajax()`
 *
 * @see  encoder_contexte_ajax()
 * @uses calculer_cle_action()
 *
 * @param string $c
 *   hash du contexte
 * @param string $form
 *   nom du formulaire eventuel
 * @return array|string|bool
 *   - array|string : contexte d'environnement, possiblement sérialisé
 *   - false : erreur de décodage
 */
function decoder_contexte_ajax($c, $form = '') {
	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	if (((defined('_CACHE_CONTEXTES_AJAX') and _CACHE_CONTEXTES_AJAX) or strlen($c) == 32)
		and $dir = sous_repertoire(_DIR_CACHE, 'contextes')
		and lire_fichier("$dir/c$c", $contexte)
	) {
		$c = $contexte;
	} else {
		$c = @base64_decode($c);
		$c = _xor($c);
		if (function_exists('gzdeflate') && function_exists('gzinflate')) {
			$c = @gzinflate($c);
		}
	}

	// extraire la signature en debut de contexte
	// et la verifier avant de deserializer
	// format : signature:donneesserializees
	if ($p = strpos($c,":")){
		$cle = substr($c,0,$p);
		$c = substr($c,$p+1);

		if ($cle == calculer_cle_action($form . $c)) {
			$env = @unserialize($c);
			return $env;
		}
	}

	return false;
}


/**
 * Encrypte ou décrypte un message
 *
 * @link http://www.php.net/manual/fr/language.operators.bitwise.php#81358
 *
 * @param string $message
 *    Message à encrypter ou décrypter
 * @param null|string $key
 *    Clé de cryptage / décryptage.
 *    Une clé sera calculée si non transmise
 * @return string
 *    Message décrypté ou encrypté
 **/
function _xor($message, $key = null) {
	if (is_null($key)) {
		if (!function_exists('calculer_cle_action')) {
			include_spip("inc/securiser_action");
		}
		$key = pack("H*", calculer_cle_action('_xor'));
	}

	$keylen = strlen($key);
	$messagelen = strlen($message);
	for ($i = 0; $i < $messagelen; $i++) {
		$message[$i] = ~($message[$i] ^ $key[$i % $keylen]);
	}

	return $message;
}

/**
 * Retourne une URL de réponse de forum (aucune action ici)
 *
 * @see filtre_url_reponse_forum() du plugin forum (prioritaire)
 * @note
 *   La vraie fonction est dans le plugin forum,
 *   mais on évite ici une erreur du compilateur en absence du plugin
 * @param string $texte
 * @return string
 */
function url_reponse_forum($texte) { return $texte; }

/**
 * retourne une URL de suivi rss d'un forum (aucune action ici)
 *
 * @see filtre_url_rss_forum() du plugin forum (prioritaire)
 * @note
 *   La vraie fonction est dans le plugin forum,
 *   mais on évite ici une erreur du compilateur en absence du plugin
 * @param string $texte
 * @return string
 */
function url_rss_forum($texte) { return $texte; }


/**
 * Génère des menus avec liens ou `<strong class='on'>` non clicable lorsque
 * l'item est sélectionné
 * Le parametre $on peut recevoir un selecteur simplifié de type 'a.active' 'strong.active.expose'
 * pour préciser la balise (a, span ou strong uniquement) et la/les classes à utiliser quand on est expose
 *
 * @filtre
 * @link https://www.spip.net/4004
 * @example
 *   ```
 *   [(#URL_RUBRIQUE|lien_ou_expose{#TITRE, #ENV{test}|=={en_cours}})]
 *   [(#URL_RUBRIQUE|lien_ou_expose{#TITRE, #ENV{test}|=={en_cours}|?{a.monlien.active}, 'monlien'})]
 *   ```
 *
 *
 * @param string $url
 *   URL du lien
 * @param string $libelle
 *   Texte du lien
 * @param bool $on
 *   État exposé ou non (génère un lien). Si $on vaut true ou 1 ou ' ', l'etat expose est rendu par un <strong class='on'>...</strong>
 *   Si $on est de la forme span.active.truc par exemple, l'etat expose est rendu par <span class='active truc'>...</span> Seules les balises a, span et strong sont acceptees dans ce cas
 * @param string $class
 *   Classes CSS ajoutées au lien
 * @param string $title
 *   Title ajouté au lien
 * @param string $rel
 *   Attribut `rel` ajouté au lien
 * @param string $evt
 *   Complement à la balise `a` pour gérer un événement javascript,
 *   de la forme ` onclick='...'`
 * @return string
 *   Code HTML
 */
function lien_ou_expose($url, $libelle = null, $on = false, $class = "", $title = "", $rel = "", $evt = '') {
	if ($on) {
		$bal = 'strong';
		$class = "";
		$att = "";
		// si $on passe la balise et optionnelement une ou ++classe
		// a.active span.selected.active etc....
		if (is_string($on) and (strncmp($on, 'a', 1)==0 or strncmp($on, 'span', 4)==0 or strncmp($on, 'strong', 6)==0)){
			$on = explode(".", $on);
			// on verifie que c'est exactement une des 3 balises a, span ou strong
			if (in_array(reset($on), array('a', 'span', 'strong'))){
				$bal = array_shift($on);
				$class = implode(" ", $on);
				if ($bal=="a"){
					$att = 'href="#" ';
				}
			}
		}
		$att .= 'class="' . ($class ? attribut_html($class) . ' ' : '') . (defined('_LIEN_OU_EXPOSE_CLASS_ON') ? _LIEN_OU_EXPOSE_CLASS_ON : 'on') . '"';
	} else {
		$bal = 'a';
		$att = "href='$url'"
			. ($title ? " title='" . attribut_html($title) . "'" : '')
			. ($class ? " class='" . attribut_html($class) . "'" : '')
			. ($rel ? " rel='" . attribut_html($rel) . "'" : '')
			. $evt;
	}
	if ($libelle === null) {
		$libelle = $url;
	}

	return "<$bal $att>$libelle</$bal>";
}


/**
 * Afficher un message "un truc"/"N trucs"
 * Les items sont à indiquer comme pour la fonction _T() sous la forme :
 * "module:chaine"
 *
 * @param int $nb : le nombre
 * @param string $chaine_un : l'item de langue si $nb vaut un
 * @param string $chaine_plusieurs : l'item de lanque si $nb >= 2
 * @param string $var : La variable à remplacer par $nb dans l'item de langue (facultatif, défaut "nb")
 * @param array $vars : Les autres variables nécessaires aux chaines de langues (facultatif)
 * @return string : la chaine de langue finale en utilisant la fonction _T()
 */
function singulier_ou_pluriel($nb, $chaine_un, $chaine_plusieurs, $var = 'nb', $vars = array()) {
	static $local_singulier_ou_pluriel = array();

	// si nb=0 ou pas de $vars valide on retourne une chaine vide, a traiter par un |sinon
	if (!is_numeric($nb) or $nb == 0) {
		return "";
	}
	if (!is_array($vars)) {
		return "";
	}

	$langue = $GLOBALS['spip_lang'];
	if (!isset($local_singulier_ou_pluriel[$langue])) {
		$local_singulier_ou_pluriel[$langue] = false;
		if ($f = charger_fonction("singulier_ou_pluriel_${langue}", 'inc', true)
		  or $f = charger_fonction("singulier_ou_pluriel", 'inc', true)) {
			$local_singulier_ou_pluriel[$langue] = $f;
		}
	}

	// si on a une surcharge on l'utilise
	if ($local_singulier_ou_pluriel[$langue]) {
		return ($local_singulier_ou_pluriel[$langue])($nb, $chaine_un, $chaine_plusieurs, $var, $vars);
	}

	// sinon traitement par defaut
	$vars[$var] = $nb;
	if ($nb >= 2) {
		return _T($chaine_plusieurs, $vars);
	} else {
		return _T($chaine_un, $vars);
	}
}


/**
 * Fonction de base pour une icone dans un squelette.
 * Il peut s'agir soit d'un simple lien, structure html : `<span><a><img><b>texte</b></span>`,
 * soit d'un bouton d'action, structure identique au retour de `#BOUTON_ACTION`.
 *
 * @param string $type
 *  'lien' ou 'bouton'
 * @param string $lien
 *  url
 * @param string $texte
 *  texte du lien / alt de l'image
 * @param string $fond
 *  objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *  new/del/edit
 * @param string $class
 *  Classes supplémentaires (horizontale, verticale, ajax…)
 * @param string $javascript
 *  code javascript tel que "onclick='...'" par exemple
 *  ou texte du message de confirmation s'il s'agit d'un bouton
 * @return string
 */
function prepare_icone_base($type, $lien, $texte, $fond, $fonction = '', $class = '', $javascript = '') {

	$class_lien = $class_bouton = $class;

	// Normaliser la fonction et compléter la classe en fonction
	if (in_array($fonction, ['del', 'supprimer.gif'])) {
		$class_lien .= ' danger';
		$class_bouton .= ' btn_danger';
	} elseif ($fonction == 'rien.gif') {
		$fonction = '';
	} elseif ($fonction == 'delsafe') {
		$fonction = 'del';
	}

	$fond_origine = $fond;
	// Remappage des icone : article-24.png+new => article-new-24.png
	if ($icone_renommer = charger_fonction('icone_renommer', 'inc', true)) {
		list($fond, $fonction) = $icone_renommer($fond, $fonction);
	}

	// Ajouter le type d'objet dans la classe
	$objet_type = substr(basename($fond), 0, -4);
	$class_lien .= " $objet_type";
	$class_bouton .= " $objet_type";

	// Texte
	$alt = attribut_html($texte);
	$title = " title=\"$alt\""; // est-ce pertinent de doubler le alt par un title ?

	// Liens : préparer les classes ajax
	$ajax = '';
	if ($type === 'lien') {
		if (strpos($class_lien, 'ajax') !== false) {
			$ajax = 'ajax';
			if (strpos($class_lien, 'preload') !== false) {
				$ajax .= ' preload';
			}
			if (strpos($class_lien, 'nocache') !== false) {
				$ajax .= ' nocache';
			}
			$ajax = " class='$ajax'";
		}
	}

	// Repérer la taille et l'ajouter dans la classe
	$size = 24;
	if (preg_match("/-([0-9]{1,3})[.](gif|png|svg)$/i", $fond, $match)
	  or preg_match("/-([0-9]{1,3})([.](gif|png|svg))?$/i", $fond_origine, $match)) {
		$size = $match[1];
	}
	$class_lien .= " s$size";
	$class_bouton .= " s$size";

	// Icône
	$icone = http_img_pack($fond, $alt, "width='$size' height='$size'");
	$icone = "<span class=\"icone-image".($fonction ? " icone-fonction icone-fonction-$fonction" : "") . "\">$icone</span>";

	// Markup final
	if ($type == 'lien') {
		return "<span class='icone $class_lien'>"
		. "<a href='$lien'$title$ajax$javascript>"
		. $icone
		. "<b>$texte</b>"
		. "</a></span>\n";
	} else {
		return bouton_action("$icone $texte", $lien, $class_bouton, $javascript, $alt);
	}
}

/**
 * Crée un lien ayant une icone
 *
 * @uses prepare_icone_base()
 *
 * @param string $lien
 *     URL du lien
 * @param string $texte
 *     Texte du lien
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *     Classe CSS, tel que `left`, `right` pour définir un alignement
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 **/
function icone_base($lien, $texte, $fond, $fonction = "", $class = "", $javascript = "") {
	return prepare_icone_base('lien', $lien, $texte, $fond, $fonction, $class, $javascript);
}

/**
 * Crée un lien précédé d'une icone au dessus du texte
 *
 * @uses icone_base()
 * @see  icone_verticale() Pour un usage dans un code PHP.
 *
 * @filtre
 * @example
 *     ```
 *     [(#AUTORISER{voir,groupemots,#ID_GROUPE})
 *         [(#URL_ECRIRE{groupe_mots,id_groupe=#ID_GROUPE}
 *            |icone_verticale{<:mots:icone_voir_groupe_mots:>,groupe_mots-24.png,'',left})]
 *    ]
 *     ```
 *
 * @param string $lien
 *     URL du lien
 * @param string $texte
 *     Texte du lien
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *     Classe CSS à ajouter, tel que `left`, `right`, `center` pour définir un alignement.
 *     Il peut y en avoir plusieurs : `left ajax`
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 **/
function filtre_icone_verticale_dist($lien, $texte, $fond, $fonction = "", $class = "", $javascript = "") {
	return icone_base($lien, $texte, $fond, $fonction, "verticale $class", $javascript);
}

/**
 * Crée un lien précédé d'une icone horizontale
 *
 * @uses icone_base()
 * @see  icone_horizontale() Pour un usage dans un code PHP.
 *
 * @filtre
 * @example
 *     En tant que filtre dans un squelettes :
 *     ```
 *     [(#URL_ECRIRE{sites}|icone_horizontale{<:sites:icone_voir_sites_references:>,site-24.png})]
 *
 *     [(#AUTORISER{supprimer,groupemots,#ID_GROUPE}|oui)
 *         [(#URL_ACTION_AUTEUR{supprimer_groupe_mots,#ID_GROUPE,#URL_ECRIRE{mots}}
 *             |icone_horizontale{<:mots:icone_supprimer_groupe_mots:>,groupe_mots,del})]
 *     ]
 *     ```
 *
 *     En tant que filtre dans un code php :
 *     ```
 *     $icone_horizontale=chercher_filtre('icone_horizontale');
 *     $icone = $icone_horizontale(generer_url_ecrire("stats_visites","id_article=$id_article"),
 *         _T('statistiques:icone_evolution_visites', array('visites' => $visites)),
 *         "statistique-24.png");
 *     ```
 *
 * @param string $lien
 *     URL du lien
 * @param string $texte
 *     Texte du lien
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *     Classe CSS à ajouter
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 **/
function filtre_icone_horizontale_dist($lien, $texte, $fond, $fonction = "", $class = "", $javascript = "") {
	return icone_base($lien, $texte, $fond, $fonction, "horizontale $class", $javascript);
}

/**
 * Crée un bouton d'action intégrant une icone horizontale
 *
 * @uses prepare_icone_base()
 *
 * @filtre
 * @example
 *     ```
 *     [(#URL_ACTION_AUTEUR{supprimer_mot, #ID_MOT, #URL_ECRIRE{groupe_mots,id_groupe=#ID_GROUPE}}
 *         |bouton_action_horizontal{<:mots:info_supprimer_mot:>,mot-24.png,del,#ARRAY{bouton,btn_large}})]
 *     ```
 *
 * @param string $lien
 *     URL de l'action
 * @param string $texte
 *     Texte du bouton
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du bouton (`edit`, `new`, `del`)
 * @param string $class
 *     Classes à ajouter au bouton, à l'exception de `ajax` qui est placé sur le formulaire.
 * @param string $confirm
 *     Message de confirmation à ajouter en javascript sur le bouton
 * @return string
 *     Code HTML du lien
 **/
function filtre_bouton_action_horizontal_dist($lien, $texte, $fond, $fonction = "", $class = "", $confirm = "") {
	return prepare_icone_base('bouton', $lien, $texte, $fond, $fonction, $class, $confirm);
}

/**
 * Filtre `icone` pour compatibilité mappé sur `icone_base`
 *
 * @uses icone_base()
 * @see  filtre_icone_verticale_dist()
 *
 * @filtre
 * @deprecated Utiliser le filtre `icone_verticale`
 *
 * @param string $lien
 *     URL du lien
 * @param string $texte
 *     Texte du lien
 * @param string $fond
 *     Nom de l'image utilisée
 * @param string $align
 *     Classe CSS d'alignement (`left`, `right`, `center`)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *     Classe CSS à ajouter
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 */
function filtre_icone_dist($lien, $texte, $fond, $align = "", $fonction = "", $class = "", $javascript = "") {
	return icone_base($lien, $texte, $fond, $fonction, "verticale $align $class", $javascript);
}


/**
 * Explose un texte en tableau suivant un séparateur
 *
 * @note
 *     Inverse l'écriture de la fonction PHP de même nom
 *     pour que le filtre soit plus pratique dans les squelettes
 *
 * @filtre
 * @example
 *     ```
 *     [(#GET{truc}|explode{-})]
 *     ```
 *
 * @param string $a Texte
 * @param string $b Séparateur
 * @return array Liste des éléments
 */
function filtre_explode_dist($a, $b) { return explode($b, $a); }

/**
 * Implose un tableau en chaine en liant avec un séparateur
 *
 * @note
 *     Inverse l'écriture de la fonction PHP de même nom
 *     pour que le filtre soit plus pratique dans les squelettes
 *
 * @filtre
 * @example
 *     ```
 *     [(#GET{truc}|implode{-})]
 *     ```
 *
 * @param array $a Tableau
 * @param string $b Séparateur
 * @return string Texte
 */
function filtre_implode_dist($a, $b) { return is_array($a) ? implode($b, $a) : $a; }

/**
 * Produire les styles privés qui associent item de menu avec icone en background
 *
 * @return string Code CSS
 */
function bando_images_background() {
	include_spip('inc/bandeau');
	// recuperer tous les boutons et leurs images
	$boutons = definir_barre_boutons(definir_barre_contexte(), true, false);

	$res = "";
	foreach ($boutons as $page => $detail) {
		$selecteur = (in_array($page, array('outils_rapides', 'outils_collaboratifs')) ? "" : ".navigation_avec_icones ");
		if (is_array($detail->sousmenu)) {
			foreach ($detail->sousmenu as $souspage => $sousdetail) {
				if ($sousdetail->icone and strlen(trim($sousdetail->icone))) {
					$res .= "\n$selecteur.bando2_$souspage {background-image:url(" . $sousdetail->icone . ");}";
				}
			}
		}
	}

	return $res;
}

/**
 * Générer un bouton_action
 * utilisé par #BOUTON_ACTION
 *
 * @param string $libelle
 *   Libellé du bouton
 * @param string $url
 *   URL d'action sécurisée, généralement obtenue avec generer_action_auteur()
 * @param string $class
 *   Classes à ajouter au bouton, à l'exception de `ajax` qui est placé sur le formulaire.
 * @param string $confirm
 *   Message de confirmation oui/non avant l'action
 * @param string $title
 *   Attribut title à ajouter au bouton
 * @param string $callback
 *   Callback js a appeler lors de l'évènement action et avant execution de l'action
 *   (ou après confirmation éventuelle si $confirm est non vide).
 *   Si la callback renvoie false, elle annule le déclenchement de l'action.
 * @return string
 */
function bouton_action($libelle, $url, $class = '', $confirm = '', $title = '', $callback = '') {

	// Classes : dispatcher `ajax` sur le formulaire
	$class_form = '';
	if (strpos($class, 'ajax') !== false) {
		$class_form = 'ajax';
		$class = str_replace('ajax', '', $class);
	}
	$class_btn = 'submit ' . trim($class);

	if ($confirm) {
		$confirm = "confirm(\"" . attribut_html($confirm) . "\")";
		if ($callback) {
			$callback = "$confirm?($callback):false";
		} else {
			$callback = $confirm;
		}
	}
	$onclick = $callback ? " onclick='return " . addcslashes($callback, "'") . "'" : "";
	$title = $title ? " title='$title'" : '';

	return "<form class='bouton_action_post $class_form' method='post' action='$url'><div>" . form_hidden($url)
	. "<button type='submit' class='$class_btn'$title$onclick>$libelle</button></div></form>";
}

/**
 * Donner n'importe quelle information sur un objet de maniere generique.
 *
 * La fonction va gerer en interne deux cas particuliers les plus utilises :
 * l'URL et le titre (qui n'est pas forcemment le champ SQL "titre").
 *
 * On peut ensuite personnaliser les autres infos en creant une fonction
 * generer_<nom_info>_entite($id_objet, $type_objet, $ligne).
 * $ligne correspond a la ligne SQL de tous les champs de l'objet, les fonctions
 * de personnalisation n'ont donc pas a refaire de requete.
 *
 * @param int $id_objet
 * @param string $type_objet
 * @param string $info
 * @param string $etoile
 * @return string
 */
function generer_info_entite($id_objet, $type_objet, $info, $etoile = "") {
	static $trouver_table = null;
	static $objets;

	// On verifie qu'on a tout ce qu'il faut
	$id_objet = intval($id_objet);
	if (!($id_objet and $type_objet and $info)) {
		return '';
	}

	// si on a deja note que l'objet n'existe pas, ne pas aller plus loin
	if (isset($objets[$type_objet]) and $objets[$type_objet] === false) {
		return '';
	}

	// Si on demande l'url, on retourne direct la fonction
	if ($info == 'url') {
		return generer_url_entite($id_objet, $type_objet);
	}

	// Sinon on va tout chercher dans la table et on garde en memoire
	$demande_titre = ($info == 'titre');

	// On ne fait la requete que si on a pas deja l'objet ou si on demande le titre mais qu'on ne l'a pas encore
	if (!isset($objets[$type_objet][$id_objet])
		or
		($demande_titre and !isset($objets[$type_objet][$id_objet]['titre']))
	) {
		if (!$trouver_table) {
			$trouver_table = charger_fonction('trouver_table', 'base');
		}
		$desc = $trouver_table(table_objet_sql($type_objet));
		if (!$desc) {
			return $objets[$type_objet] = false;
		}

		// Si on demande le titre, on le gere en interne
		$champ_titre = "";
		if ($demande_titre) {
			// si pas de titre declare mais champ titre, il sera peuple par le select *
			$champ_titre = (!empty($desc['titre'])) ? ', ' . $desc['titre'] : '';
		}
		include_spip('base/abstract_sql');
		include_spip('base/connect_sql');
		$objets[$type_objet][$id_objet] = sql_fetsel(
			'*' . $champ_titre,
			$desc['table_sql'],
			id_table_objet($type_objet) . ' = ' . intval($id_objet)
		);
	}

	// Si la fonction generer_TRUC_TYPE existe, on l'utilise pour formater $info_generee
	if ($generer = charger_fonction("generer_${info}_${type_objet}", '', true)) {
		$info_generee = $generer($id_objet, $objets[$type_objet][$id_objet]);
	} // Si la fonction generer_TRUC_entite existe, on l'utilise pour formater $info_generee
	else {
		if ($generer = charger_fonction("generer_${info}_entite", '', true)) {
			$info_generee = $generer($id_objet, $type_objet, $objets[$type_objet][$id_objet]);
		} // Sinon on prend directement le champ SQL tel quel
		else {
			$info_generee = (isset($objets[$type_objet][$id_objet][$info]) ? $objets[$type_objet][$id_objet][$info] : '');
		}
	}

	// On va ensuite appliquer les traitements automatiques si besoin
	if (!$etoile) {
		// FIXME: on fournit un ENV minimum avec id et type et connect=''
		// mais ce fonctionnement est a ameliorer !
		$info_generee = appliquer_traitement_champ($info_generee, $info, table_objet($type_objet),
			array('id_objet' => $id_objet, 'objet' => $type_objet, ''));
	}

	return $info_generee;
}

/**
 * Appliquer a un champ SQL le traitement qui est configure pour la balise homonyme dans les squelettes
 *
 * @param string $texte
 * @param string $champ
 * @param string $table_objet
 * @param array $env
 * @param string $connect
 * @return string
 */
function appliquer_traitement_champ($texte, $champ, $table_objet = '', $env = array(), $connect = '') {
	if (!$champ) {
		return $texte;
	}
	
	// On charge toujours les filtres de texte car la majorité des traitements les utilisent
	// et il ne faut pas partir du principe que c'est déjà chargé (form ajax, etc)
	include_spip('inc/texte');
	
	$champ = strtoupper($champ);
	$traitements = isset($GLOBALS['table_des_traitements'][$champ]) ? $GLOBALS['table_des_traitements'][$champ] : false;
	if (!$traitements or !is_array($traitements)) {
		return $texte;
	}

	$traitement = '';
	if ($table_objet and (!isset($traitements[0]) or count($traitements) > 1)) {
		// necessaire pour prendre en charge les vieux appels avec un table_objet_sql en 3e arg
		$table_objet = table_objet($table_objet);
		if (isset($traitements[$table_objet])) {
			$traitement = $traitements[$table_objet];
		}
	}
	if (!$traitement and isset($traitements[0])) {
		$traitement = $traitements[0];
	}
	// (sinon prendre le premier de la liste par defaut ?)

	if (!$traitement) {
		return $texte;
	}

	$traitement = str_replace('%s', "'" . texte_script($texte) . "'", $traitement);

	// Fournir $connect et $Pile[0] au traitement si besoin
	$Pile = array(0 => $env);
	eval("\$texte = $traitement;");

	return $texte;
}


/**
 * Generer un lien (titre clicable vers url) vers un objet
 *
 * @param int $id_objet
 * @param $objet
 * @param int $longueur
 * @param null|string $connect
 * @return string
 */
function generer_lien_entite($id_objet, $objet, $longueur = 80, $connect = null) {
	include_spip('inc/liens');
	$titre = traiter_raccourci_titre($id_objet, $objet, $connect);
	// lorsque l'objet n'est plus declare (plugin desactive par exemple)
	// le raccourcis n'est plus valide
	$titre = isset($titre['titre']) ? typo($titre['titre']) : '';
	// on essaye avec generer_info_entite ?
	if (!strlen($titre) and !$connect) {
		$titre = generer_info_entite($id_objet, $objet, 'titre');
	}
	if (!strlen($titre)) {
		$titre = _T('info_sans_titre');
	}
	$url = generer_url_entite($id_objet, $objet, '', '', $connect);

	return "<a href='$url' class='$objet'>" . couper($titre, $longueur) . "</a>";
}


/**
 * Englobe (Wrap) un texte avec des balises
 *
 * @example `wrap('mot','<b>')` donne `<b>mot</b>'`
 *
 * @filtre
 * @uses extraire_balises()
 *
 * @param string $texte
 * @param string $wrap
 * @return string
 */
function wrap($texte, $wrap) {
	$balises = extraire_balises($wrap);
	if (preg_match_all(",<([a-z]\w*)\b[^>]*>,UimsS", $wrap, $regs, PREG_PATTERN_ORDER)) {
		$texte = $wrap . $texte;
		$regs = array_reverse($regs[1]);
		$wrap = "</" . implode("></", $regs) . ">";
		$texte = $texte . $wrap;
	}

	return $texte;
}


/**
 * afficher proprement n'importe quoi
 * On affiche in fine un pseudo-yaml qui premet de lire humainement les tableaux et de s'y reperer
 *
 * Les textes sont retournes avec simplement mise en forme typo
 *
 * le $join sert a separer les items d'un tableau, c'est en general un \n ou <br /> selon si on fait du html ou du texte
 * les tableaux-listes (qui n'ont que des cles numeriques), sont affiches sous forme de liste separee par des virgules :
 * c'est VOULU !
 *
 * @param $u
 * @param string $join
 * @param int $indent
 * @return array|mixed|string
 */
function filtre_print_dist($u, $join = "<br />", $indent = 0) {
	if (is_string($u)) {
		$u = typo($u);

		return $u;
	}

	// caster $u en array si besoin
	if (is_object($u)) {
		$u = (array)$u;
	}

	if (is_array($u)) {
		$out = "";
		// toutes les cles sont numeriques ?
		// et aucun enfant n'est un tableau
		// liste simple separee par des virgules
		$numeric_keys = array_map('is_numeric', array_keys($u));
		$array_values = array_map('is_array', $u);
		$object_values = array_map('is_object', $u);
		if (array_sum($numeric_keys) == count($numeric_keys)
			and !array_sum($array_values)
			and !array_sum($object_values)
		) {
			return join(", ", array_map('filtre_print_dist', $u));
		}

		// sinon on passe a la ligne et on indente
		$i_str = str_pad("", $indent, " ");
		foreach ($u as $k => $v) {
			$out .= $join . $i_str . "$k: " . filtre_print_dist($v, $join, $indent + 2);
		}

		return $out;
	}

	// on sait pas quoi faire...
	return $u;
}


/**
 * Renvoyer l'info d'un objet
 * telles que definies dans declarer_tables_objets_sql
 *
 * @param string $objet
 * @param string $info
 * @return string|array
 */
function objet_info($objet, $info) {
	$table = table_objet_sql($objet);
	$infos = lister_tables_objets_sql($table);

	return (isset($infos[$info]) ? $infos[$info] : '');
}

/**
 * Filtre pour afficher 'Aucun truc' ou '1 truc' ou 'N trucs'
 * avec la bonne chaîne de langue en fonction de l'objet utilisé
 *
 * @param int $nb
 *     Nombre d'éléments
 * @param string $objet
 *     Objet
 * @return mixed|string
 *     Texte traduit du comptage, tel que '3 articles'
 */
function objet_afficher_nb($nb, $objet) {
	if (!$nb) {
		return _T(objet_info($objet, 'info_aucun_objet'));
	} else {
		return _T(objet_info($objet, $nb == 1 ? 'info_1_objet' : 'info_nb_objets'), array('nb' => $nb));
	}
}

/**
 * Filtre pour afficher l'img icone d'un objet
 *
 * @param string $objet
 * @param int $taille
 * @param string $class
 * @return string
 */
function objet_icone($objet, $taille = 24, $class='') {
	$icone = objet_info($objet, 'icone_objet') . "-" . $taille . ".png";
	$icone = chemin_image($icone);
	$balise_img = charger_filtre('balise_img');

	return $icone ? $balise_img($icone, _T(objet_info($objet, 'texte_objet')), $class, $taille) : '';
}

/**
 * Renvoyer une traduction d'une chaine de langue contextuelle à un objet si elle existe,
 * la traduction de la chaine generique
 *
 * Ex : [(#ENV{objet}|objet_label{trad_reference})]
 * va chercher si une chaine objet:trad_reference existe et renvoyer sa trad le cas echeant
 * sinon renvoie la trad de la chaine trad_reference
 * Si la chaine fournie contient un prefixe il est remplacé par celui de l'objet pour chercher la chaine contextuelle
 *
 * Les arguments $args et $options sont ceux de la fonction _T
 *
 * @param string $objet
 * @param string $chaine
 * @param array $args
 * @param array $options
 * @return string
 */
function objet_T($objet, $chaine, $args = array(), $options = array()){
	$chaine = explode(':',$chaine);
	if ($t = _T($objet . ':' . end($chaine), $args, array_merge($options, array('force'=>false)))) {
		return $t;
	}
	$chaine = implode(':',$chaine);
	return _T($chaine, $args, $options);
}

/**
 * Fonction de secours pour inserer le head_css de facon conditionnelle
 *
 * Appelée en filtre sur le squelette qui contient #INSERT_HEAD,
 * elle vérifie l'absence éventuelle de #INSERT_HEAD_CSS et y suplée si besoin
 * pour assurer la compat avec les squelettes qui n'utilisent pas.
 *
 * @param string $flux Code HTML
 * @return string      Code HTML
 */
function insert_head_css_conditionnel($flux) {
	if (strpos($flux, '<!-- insert_head_css -->') === false
		and $p = strpos($flux, '<!-- insert_head -->')
	) {
		// plutot avant le premier js externe (jquery) pour etre non bloquant
		if ($p1 = stripos($flux, '<script src=') and $p1 < $p) {
			$p = $p1;
		}
		$flux = substr_replace($flux, pipeline('insert_head_css', '<!-- insert_head_css -->'), $p, 0);
	}

	return $flux;
}

/**
 * Produire un fichier statique à partir d'un squelette dynamique
 *
 * Permet ensuite à Apache de le servir en statique sans repasser
 * par spip.php à chaque hit sur le fichier.
 *
 * Si le format (css ou js) est passe dans `contexte['format']`, on l'utilise
 * sinon on regarde si le fond finit par .css ou .js, sinon on utilie "html"
 *
 * @uses urls_absolues_css()
 *
 * @param string $fond
 * @param array $contexte
 * @param array $options
 * @param string $connect
 * @return string
 */
function produire_fond_statique($fond, $contexte = array(), $options = array(), $connect = '') {
	if (isset($contexte['format'])) {
		$extension = $contexte['format'];
		unset($contexte['format']);
	} else {
		$extension = "html";
		if (preg_match(',[.](css|js|json)$,', $fond, $m)) {
			$extension = $m[1];
		}
	}
	// recuperer le contenu produit par le squelette
	$options['raw'] = true;
	$cache = recuperer_fond($fond, $contexte, $options, $connect);

	// calculer le nom de la css
	$dir_var = sous_repertoire(_DIR_VAR, 'cache-' . $extension);
	$nom_safe = preg_replace(",\W,", '_', str_replace('.', '_', $fond));
	$contexte_implicite = calculer_contexte_implicite();

	// par defaut on hash selon les contextes qui sont a priori moins variables
	// mais on peut hasher selon le contenu a la demande, si plusieurs contextes produisent un meme contenu
	// reduit la variabilite du nom et donc le nombre de css concatenees possibles in fine
	if (isset($options['hash_on_content']) and $options['hash_on_content']) {
		$hash = md5($contexte_implicite['host'] . '::'. $cache);
	}
	else {
		unset($contexte_implicite['notes']); // pas pertinent pour signaler un changeemnt de contenu pour des css/js
		ksort($contexte);
		$hash = md5($fond . json_encode($contexte_implicite) . json_encode($contexte) . $connect);
	}
	$filename = $dir_var . $extension . "dyn-$nom_safe-" . substr($hash, 0, 8) . ".$extension";

	// mettre a jour le fichier si il n'existe pas
	// ou trop ancien
	// le dernier fichier produit est toujours suffixe par .last
	// et recopie sur le fichier cible uniquement si il change
	if (!file_exists($filename)
		or !file_exists($filename . ".last")
		or (isset($cache['lastmodified']) and $cache['lastmodified'] and filemtime($filename . ".last") < $cache['lastmodified'])
		or (defined('_VAR_MODE') and _VAR_MODE == 'recalcul')
	) {
		$contenu = $cache['texte'];
		// passer les urls en absolu si c'est une css
		if ($extension == "css") {
			$contenu = urls_absolues_css($contenu,
				test_espace_prive() ? generer_url_ecrire('accueil') : generer_url_public($fond));
		}

		$comment = '';
		// ne pas insérer de commentaire si c'est du json
		if ($extension != "json") {
			$comment = "/* #PRODUIRE{fond=$fond";
			foreach ($contexte as $k => $v) {
				$comment .= ",$k=$v";
			}
			// pas de date dans le commentaire car sinon ca invalide le md5 et force la maj
			// mais on peut mettre un md5 du contenu, ce qui donne un aperu rapide si la feuille a change ou non
			$comment .= "}\n   md5:" . md5($contenu) . " */\n";
		}
		// et ecrire le fichier si il change
		ecrire_fichier_calcule_si_modifie($filename, $comment . $contenu, false, true);
	}

	return timestamp($filename);
}

/**
 * Ajouter un timestamp a une url de fichier
 * [(#CHEMIN{monfichier}|timestamp)]
 *
 * @param string $fichier
 *    Le chemin du fichier sur lequel on souhaite ajouter le timestamp
 * @return string
 *    $fichier auquel on a ajouté le timestamp
 */
function timestamp($fichier) {
	if (!$fichier
		or !file_exists($fichier)
		or !$m = filemtime($fichier)
	) {
		return $fichier;
	}

	return "$fichier?$m";
}

/**
 * Supprimer le timestamp d'une url
 *
 * @param string $url
 * @return string
 */
function supprimer_timestamp($url) {
	if (strpos($url, "?") === false) {
		return $url;
	}

	return preg_replace(",\?[[:digit:]]+$,", "", $url);
}

/**
 * Nettoyer le titre d'un email
 *
 * Éviter une erreur lorsqu'on utilise `|nettoyer_titre_email` dans un squelette de mail
 *
 * @filtre
 * @uses nettoyer_titre_email()
 *
 * @param string $titre
 * @return string
 */
function filtre_nettoyer_titre_email_dist($titre) {
	include_spip('inc/envoyer_mail');

	return nettoyer_titre_email($titre);
}

/**
 * Afficher le sélecteur de rubrique
 *
 * Il permet de placer un objet dans la hiérarchie des rubriques de SPIP
 *
 * @uses chercher_rubrique()
 *
 * @param string $titre
 * @param int $id_objet
 * @param int $id_parent
 * @param string $objet
 * @param int $id_secteur
 * @param bool $restreint
 * @param bool $actionable
 *   true : fournit le selecteur dans un form directement postable
 * @param bool $retour_sans_cadre
 * @return string
 */
function filtre_chercher_rubrique_dist(
	$titre,
	$id_objet,
	$id_parent,
	$objet,
	$id_secteur,
	$restreint,
	$actionable = false,
	$retour_sans_cadre = false
) {
	include_spip('inc/filtres_ecrire');

	return chercher_rubrique($titre, $id_objet, $id_parent, $objet, $id_secteur, $restreint, $actionable,
		$retour_sans_cadre);
}

/**
 * Rediriger une page suivant une autorisation,
 * et ce, n'importe où dans un squelette, même dans les inclusions.
 *
 * En l'absence de redirection indiquée, la fonction redirige par défaut
 * sur une 403 dans l'espace privé et 404 dans l'espace public.
 *
 * @example
 *     ```
 *     [(#AUTORISER{non}|sinon_interdire_acces)]
 *     [(#AUTORISER{non}|sinon_interdire_acces{#URL_PAGE{login}, 401})]
 *     ```
 *
 * @filtre
 * @param bool $ok
 *     Indique si l'on doit rediriger ou pas
 * @param string $url
 *     Adresse eventuelle vers laquelle rediriger
 * @param int $statut
 *     Statut HTML avec lequel on redirigera
 * @param string $message
 *     message d'erreur
 * @return string|void
 *     Chaîne vide si l'accès est autorisé
 */
function sinon_interdire_acces($ok = false, $url = '', $statut = 0, $message = null) {
	if ($ok) {
		return '';
	}

	// Vider tous les tampons
	$level = @ob_get_level();
	while ($level--) {
		@ob_end_clean();
	}

	include_spip('inc/headers');

	// S'il y a une URL, on redirige (si pas de statut, la fonction mettra 302 par défaut)
	if ($url) {
		redirige_par_entete($url, '', $statut);
	}

	// ecriture simplifiee avec message en 3eme argument (= statut 403)
	if (!is_numeric($statut) and is_null($message)) {
		$message = $statut;
		$statut = 0;
	}
	if (!$message) {
		$message = '';
	}
	$statut = intval($statut);

	// Si on est dans l'espace privé, on génère du 403 Forbidden par defaut ou du 404
	if (test_espace_prive()) {
		if (!$statut or !in_array($statut, array(404, 403))) {
			$statut = 403;
		}
		http_status(403);
		$echec = charger_fonction('403', 'exec');
		$echec($message);
	} else {
		// Sinon dans l'espace public on redirige vers une 404 par défaut, car elle toujours présente normalement
		if (!$statut) {
			$statut = 404;
		}
		// Dans tous les cas on modifie l'entité avec ce qui est demandé
		http_status($statut);
		// Si le statut est une erreur et qu'il n'y a pas de redirection on va chercher le squelette du même nom
		if ($statut >= 400) {
			echo recuperer_fond("$statut", array('erreur' => $message));
		}
	}


	exit;
}

/**
 * Assurer le fonctionnement de |compacte meme sans l'extension compresseur
 *
 * @param string $source
 * @param null|string $format
 * @return string
 */
function filtre_compacte_dist($source, $format = null) {
	if (function_exists('compacte')) {
		return compacte($source, $format);
	}

	return $source;
}


/**
 * Afficher partiellement un mot de passe que l'on ne veut pas rendre lisible par un champ hidden
 * @param string $passe
 * @param bool $afficher_partiellement
 * @param int|null $portion_pourcent
 * @return string
 */
function spip_affiche_mot_de_passe_masque($passe, $afficher_partiellement = false, $portion_pourcent = null) {
	$l = strlen($passe);

	if ($l<=8 or !$afficher_partiellement){
		if (!$l) {
			return ''; // montrer qu'il y a pas de mot de passe si il y en a pas
		}
		return str_pad('',$afficher_partiellement ? $l : 16,'*');
	}

	if (is_null($portion_pourcent)) {
		if (!defined('_SPIP_AFFICHE_MOT_DE_PASSE_MASQUE_PERCENT')) {
			define('_SPIP_AFFICHE_MOT_DE_PASSE_MASQUE_PERCENT', 20); // 20%
		}
		$portion_pourcent = _SPIP_AFFICHE_MOT_DE_PASSE_MASQUE_PERCENT;
	}
	if ($portion_pourcent >= 100) {
		return $passe;
	}
	$e = intval(ceil($l * $portion_pourcent / 100 / 2));
	$e = max($e, 0);
	$mid = str_pad('',$l-2*$e,'*');
	if ($e>0 and strlen($mid)>8){
		$mid = '***...***';
	}
	return substr($passe,0,$e) . $mid . ($e > 0 ? substr($passe,-$e) : '');
}


/**
 * Cette fonction permet de transformer un texte clair en nom court pouvant servir d'identifiant, class, id, url...
 * en ne conservant que des caracteres alphanumeriques et un separateur
 *
 * @param string $texte
 *   Texte à transformer en nom machine
 * @param string $type
 *
 * @param array $options
 *   string separateur : par défaut, un underscore `_`.
 *   int longueur_maxi : par defaut 60
 *   int longueur_mini : par defaut 0
 *
 * @return string
 */
function identifiant_slug($texte, $type = '', $options = array()) {

	$original = $texte;
	$separateur = (isset($options['separateur'])?$options['separateur']:'_');
	$longueur_maxi = (isset($options['longueur_maxi'])?$options['longueur_maxi']:60);
	$longueur_mini = (isset($options['longueur_mini'])?$options['longueur_mini']:0);

	if (!function_exists('translitteration')) {
		include_spip('inc/charsets');
	}

	// pas de balise html
	if (strpos($texte, '<') !== false) {
		$texte = strip_tags($texte);
	}
	if (strpos($texte, '&') !== false) {
		$texte = unicode2charset($texte);
	}
	// On enlève les espaces indésirables
	$texte = trim($texte);

	// On enlève les accents et cie
	$texte = translitteration($texte);

	// On remplace tout ce qui n'est pas un mot par un séparateur
	$texte = preg_replace(',[\W_]+,ms', $separateur, $texte);

	// nettoyer les doubles occurences du separateur si besoin
	while (strpos($texte, "$separateur$separateur") !== false) {
		$texte = str_replace("$separateur$separateur", $separateur, $texte);
	}

	// pas de separateur au debut ni a la fin
	$texte = trim($texte, $separateur);

	// en minuscules
	$texte = strtolower($texte);

	switch ($type) {
		case 'class':
		case 'id':
		case 'anchor':
			if (preg_match(',^\d,', $texte)) {
				$texte = substr($type, 0, 1).$texte;
			}
	}

	if (strlen($texte)>$longueur_maxi) {
		$texte = substr($texte, 0, $longueur_maxi);
	}

	if (strlen($texte) < $longueur_mini and $longueur_mini < $longueur_maxi) {
		if (preg_match(',^\d,', $texte)) {
			$texte = ($type ? substr($type,0,1) : "s") . $texte;
		}
		$texte .= $separateur . md5($original);
		$texte = substr($texte, 0, $longueur_mini);
	}

	return $texte;
}


/**
 * Prépare un texte (issu d'une chaine de langue historique) pour un affichage en label ou titre
 * 
 * Enlève un ':' à la fin d'une chaine de caractère, ainsi que les espaces qui pourraient l'accompagner,
 * Met la première lettre en majuscule (par défaut)
 *
 * Utile afficher dans un contexte de titre des chaines de langues qui contiennent des ':'
 * 
 * @exemple `<:info_maximum|uniformiser_label:>`
 */
function uniformiser_label(string $text, bool $ucfirst = true) : string {
	$label = rtrim($text, " : \t\n\r\0\x0B\xc2\xa0");
	if ($label and $label[-1] === ';') {
		$label = preg_replace("#(\&nbsp;)+$#", "", $label);
	}
	if ($ucfirst) {
		$label = spip_ucfirst($label);
	}
	return $label;
}


/**
 * Helper pour les filtres associés aux fonctions objet_lister_(parents|enfants)(_par_type)?
 *
 * @param string $objet
 * @param int|string $id_objet
 * @return array
 */
function helper_filtre_objet_lister_enfants_ou_parents($objet, $id_objet, $fonction) {
	if (!in_array($fonction, ['objet_lister_parents', 'objet_lister_enfants', 'objet_lister_parents_par_type', 'objet_lister_enfants_par_type'])) {
		return [];
	}

	// compatibilite signature inversee
	if (is_numeric($objet) and !is_numeric($id_objet)) {
		list($objet, $id_objet) = [$id_objet, $objet];
	}

	if (!function_exists($fonction)) {
		include_spip('base/objets');
	}
	return $fonction($objet, $id_objet);
}


/**
 * Cherche les contenus parents d'un objet
 * Supporte l'ecriture inverseee sous la forme
 * [(#ID_OBJET|objet_lister_parents{#OBJET})]
 *
 * @see objet_lister_parents()
 * @param string $objet
 * @param int|string $id_objet
 * @return array
 */
function filtre_objet_lister_parents_dist($objet, $id_objet) {
	return helper_filtre_objet_lister_enfants_ou_parents($objet, $id_objet, 'objet_lister_parents');
}

/**
 * Cherche les contenus parents d'un objet
 * Supporte l'ecriture inverseee sous la forme
 * [(#ID_OBJET|objet_lister_parents_par_type{#OBJET})]
 *
 * @see objet_lister_parents_par_type()
 * @param string $objet
 * @param int|string $id_objet
 * @return array
 */
function filtre_objet_lister_parents_par_type_dist($objet, $id_objet) {
	return helper_filtre_objet_lister_enfants_ou_parents($objet, $id_objet, 'objet_lister_parents_par_type');
}

/**
 * Cherche les contenus enfants d'un objet
 * Supporte l'ecriture inverseee sous la forme
 * [(#ID_OBJET|objet_lister_enfants{#OBJET})]
 *
 * @see objet_lister_enfants()
 * @param string $objet
 * @param int|string $id_objet
 * @return array
 */
function filtre_objet_lister_enfants_dist($objet, $id_objet) {
	return helper_filtre_objet_lister_enfants_ou_parents($objet, $id_objet, 'objet_lister_enfants');
}

/**
 * Cherche les contenus enfants d'un objet
 * Supporte l'ecriture inverseee sous la forme
 * [(#ID_OBJET|objet_lister_enfants_par_type{#OBJET})]
 *
 * @see objet_lister_enfants_par_type()
 * @param string $objet
 * @param int|string $id_objet
 * @return array
 */
function filtre_objet_lister_enfants_par_type_dist($objet, $id_objet) {
	return helper_filtre_objet_lister_enfants_ou_parents($objet, $id_objet, 'objet_lister_enfants_par_type');
}

