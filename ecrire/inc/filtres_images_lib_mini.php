<?php

/* *************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier contient les fonctions utilisées
 * par les fonctions-filtres de traitement d'image.
 *
 * @package SPIP\Core\Filtres\Images
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/filtres'); // par precaution
include_spip('inc/filtres_images_mini'); // par precaution

define('_SVG_SUPPORTED', true); // ne sert qu'a la lisibilite du code

/**
 * Transforme une couleur vectorielle R,G,B en hexa (par exemple pour usage css)
 *
 * @param int $red
 *     Valeur du rouge de 0 à 255.
 * @param int $green
 *     Valeur du vert de 0 à 255.
 * @param int $blue
 *     Valeur du bleu de 0 à 255.
 * @return string
 *     Le code de la couleur en hexadécimal.
 */
function _couleur_dec_to_hex($red, $green, $blue) {
	$red = dechex($red);
	$green = dechex($green);
	$blue = dechex($blue);

	if (strlen($red) == 1) {
		$red = '0' . $red;
	}
	if (strlen($green) == 1) {
		$green = '0' . $green;
	}
	if (strlen($blue) == 1) {
		$blue = '0' . $blue;
	}

	return "$red$green$blue";
}

/**
 * Transforme une couleur hexa en vectorielle R,G,B
 *
 * @param string $couleur
 *     Code couleur en hexa (#000000 à #FFFFFF).
 * @return array
 *     Un tableau des 3 éléments : rouge, vert, bleu.
 */
function _couleur_hex_to_dec($couleur) {
	$couleur = couleur_html_to_hex($couleur);
	$couleur = ltrim($couleur, '#');
	if (strlen($couleur) === 3) {
		$couleur = $couleur[0] . $couleur[0] . $couleur[1] . $couleur[1] . $couleur[2] . $couleur[2];
	}
	$retour = [];
	$retour['red'] = hexdec(substr($couleur, 0, 2));
	$retour['green'] = hexdec(substr($couleur, 2, 2));
	$retour['blue'] = hexdec(substr($couleur, 4, 2));

	return $retour;
}


/**
 * Transforme une couleur vectorielle H,S,L en hexa (par exemple pour usage css)
 *
 * @param int $hue
 *     Valeur de teinte de 0 à 1.
 * @param int $saturation
 *     Valeur de saturation de 0 à 1.
 * @param int $lightness
 *     Valeur de luminosité de 0 à 1.
 * @return string
 *     Le code de la couleur en hexadécimal.
 */
function _couleur_hsl_to_hex($hue, $saturation, $lightness) {
	$rgb = _couleur_hsl_to_rgb($hue, $saturation, $lightness);
	return _couleur_dec_to_hex($rgb['r'], $rgb['g'], $rgb['b']);
}

/**
 * Transforme une couleur vectorielle H,S,L en hexa (par exemple pour usage css)
 *
 * @param string $couleur
 *     Code couleur en hexa (#000000 à #FFFFFF).
 * @return array
 *     Un tableau des 3 éléments : teinte, saturation, luminosité.
 */
function _couleur_hex_to_hsl($couleur) {
	$rgb = _couleur_hex_to_dec($couleur);
	return _couleur_rgb_to_hsl($rgb['red'], $rgb['green'], $rgb['blue']);
}

/**
 * Transformation d'une couleur RGB en HSL
 *
 * HSL float entre 0 et 1
 * RGB entiers entre 0 et 255
 *
 * @param int $R
 * @param int $G
 * @param int $B
 * @return array
 */
function _couleur_rgb_to_hsl($R, $G, $B) {
	$var_R = ($R / 255); // Where RGB values = 0 Ã· 255
	$var_G = ($G / 255);
	$var_B = ($B / 255);

	$var_Min = min($var_R, $var_G, $var_B);   //Min. value of RGB
	$var_Max = max($var_R, $var_G, $var_B);   //Max. value of RGB
	$del_Max = $var_Max - $var_Min;           //Delta RGB value

	$L = ($var_Max + $var_Min) / 2;

	if ($del_Max == 0) {
		//This is a gray, no chroma...
		$H = 0; //HSL results = 0 Ã· 1
		$S = 0;
	} else {
		// Chromatic data...
		if ($L < 0.5) {
			$S = $del_Max / ($var_Max + $var_Min);
		} else {
			$S = $del_Max / (2 - $var_Max - $var_Min);
		}

		$del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
		$del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
		$del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

		if ($var_R == $var_Max) {
			$H = $del_B - $del_G;
		} else {
			if ($var_G == $var_Max) {
				$H = (1 / 3) + $del_R - $del_B;
			} else {
				if ($var_B == $var_Max) {
					$H = (2 / 3) + $del_G - $del_R;
				}
			}
		}

		if ($H < 0) {
			$H += 1;
		}
		if ($H > 1) {
			$H -= 1;
		}
	}

	$ret = [];
	$ret['h'] = $H;
	$ret['s'] = $S;
	$ret['l'] = $L;

	return $ret;
}


/**
 * Transformation d'une couleur HSL en RGB
 *
 * HSL float entre 0 et 1
 * RGB entiers entre 0 et 255
 *
 * @param float $H
 * @param float $S
 * @param float $L
 * @return array
 */
function _couleur_hsl_to_rgb($H, $S, $L) {
	// helper
	$hue_2_rgb = function ($v1, $v2, $vH) {
		if ($vH < 0) {
			$vH += 1;
		}
		if ($vH > 1) {
			$vH -= 1;
		}
		if ((6 * $vH) < 1) {
			return ($v1 + ($v2 - $v1) * 6 * $vH);
		}
		if ((2 * $vH) < 1) {
			return ($v2);
		}
		if ((3 * $vH) < 2) {
			return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
		}

		return ($v1);
	};

	if ($S == 0) {
		// HSV values = 0 -> 1
		$R = $L * 255;
		$G = $L * 255;
		$B = $L * 255;
	} else {
		if ($L < 0.5) {
			$var_2 = $L * (1 + $S);
		} else {
			$var_2 = ($L + $S) - ($S * $L);
		}

		$var_1 = 2 * $L - $var_2;

		$R = 255 * $hue_2_rgb($var_1, $var_2, $H + (1 / 3));
		$G = 255 * $hue_2_rgb($var_1, $var_2, $H);
		$B = 255 * $hue_2_rgb($var_1, $var_2, $H - (1 / 3));
	}

	$ret = [];
	$ret['r'] = floor($R);
	$ret['g'] = floor($G);
	$ret['b'] = floor($B);

	return $ret;
}

/**
 * Donne un statut au fichier-image intermédiaire servant au traitement d'image
 * selon qu'il doit être gravé (fichier .src) ou pas.
 *
 * Un appel PHP direct aux fonctions de filtre d'image produira ainsi une image
 * permanente (gravée) ; un appel généré par le compilateur via
 * `filtrer('image_xx, ...)` effacera automatiquement le fichier-image temporaire.
 *
 * @param bool|string $stat
 *     true, false ou le statut déjà défini si traitements enchaînés.
 * @return bool
 *     true si il faut supprimer le fichier temporaire ; false sinon.
 */
function statut_effacer_images_temporaires($stat) {
	static $statut = false; // par defaut on grave toute les images
	if ($stat === 'get') {
		return $statut;
	}
	$statut = $stat ? true : false;
}


/**
 * Fonctions de preparation aux filtres de traitement d'image
 * les fonctions de lecture et d'ecriture renseignees traitent uniquement le cas GD2
 * qui est le cas general des filtres images
 *
 * mais image_reduire utilise aussi cette fonction en adaptant le traitement
 * en fonction de la librairie graphique choisie dans la configuration de SPIP
 *
 * @pipeline_appel image_preparer_filtre
 * @uses extraire_attribut()
 * @uses inserer_attribut()
 * @uses tester_url_absolue()
 * @uses copie_locale() Si l'image est distante
 * @uses taille_image()
 * @uses _image_ratio()
 * @uses reconstruire_image_intermediaire()
 *
 * @param string $img
 *     Chemin de l'image ou balise html `<img src=... />`.
 * @param string $effet
 *     Les nom et paramètres de l'effet à apporter sur l'image
 *     (par exemple : reduire-300-200).
 * @param bool|string $forcer_format
 *     Un nom d'extension spécifique demandé (par exemple : jpg, png, txt...).
 *     Par défaut false : GD se débrouille seule).
 * @param array $fonction_creation
 *     Un tableau à 2 éléments :
 *     1) string : indique le nom du filtre de traitement demandé (par exemple : `image_reduire`) ;
 *     2) array : tableau reprenant la valeur de `$img` et chacun des arguments passés au filtre utilisé.
 * @param bool $find_in_path
 *     false (par défaut) indique que l'on travaille sur un fichier
 *     temporaire (.src) ; true, sur un fichier définitif déjà existant.
 * @param bool $support_svg
 *     false (par defaut) indique que le filtre ne sait pas traiter le cas particulier du SVG
 *     on lui substitue un filtre generique qui ne fait rien pour ne pas briser la chaine des filtres images
 *     true si le filtre appelant sait traiter les SVG
 * @return bool|string|array
 *
 *     - false si pas de tag `<img`,
 *       -   si l'extension n'existe pas,
 *       -   si le fichier source n'existe pas,
 *       -   si les dimensions de la source ne sont pas accessibles,
 *       -   si le fichier temporaire n'existe pas,
 *       -   si la fonction `_imagecreatefrom{extension}` n'existe pas ;
 *     - "" (chaîne vide) si le fichier source est distant et n'a pas
 *       réussi à être copié sur le serveur ;
 *     - array : tableau décrivant de l'image
 */
function _image_valeurs_trans($img, $effet, $forcer_format = false, $fonction_creation = null, $find_in_path = false, $support_svg = false) {
	static $images_recalcul = [];
	if (strlen($img) == 0) {
		return false;
	}

	$source = trim(extraire_attribut($img, 'src') ?? '');
	if (strlen($source) < 1) {
		$source = $img;
		$img = "<img src='$source' />";
	} elseif (
		preg_match('@^data:image/([^;]*);base64,(.*)$@isS', $source, $regs)
		and $extension = _image_trouver_extension_depuis_mime('image/' . $regs[1])
		and in_array($extension, _image_extensions_acceptees_en_entree())
	) {
		# gerer img src="data:....base64"
		$local = sous_repertoire(_DIR_VAR, 'image-data') . md5($regs[2]) . '.' . _image_extension_normalisee($extension);
		if (!file_exists($local)) {
			ecrire_fichier($local, base64_decode($regs[2]));
		}
		if ($sanitizer = charger_fonction($extension, 'sanitizer', true)) {
			$sanitizer($local);
		}
		$source = $local;
		$img = inserer_attribut($img, 'src', $source);
		# eviter les mauvaises surprises lors de conversions de format
		$img = inserer_attribut($img, 'width', '');
		$img = inserer_attribut($img, 'height', '');
	}

	// les protocoles web prennent au moins 3 lettres
	if (tester_url_absolue($source)) {
		include_spip('inc/distant');
		$fichier = _DIR_RACINE . copie_locale($source);
		if (!$fichier) {
			return '';
		}
		if (
			$extension = _image_trouver_extension($fichier)
			and $sanitizer = charger_fonction($extension, 'sanitizer', true)
		) {
			$sanitizer($fichier);
		}
	} else {
		// enlever le timestamp eventuel
		if (strpos($source, '?') !== false) {
			$source = preg_replace(',[?][0-9]+$,', '', $source);
		}
		if (
			strpos($source, '?') !== false
			and strncmp($source, _DIR_IMG, strlen(_DIR_IMG)) == 0
			and file_exists($f = preg_replace(',[?].*$,', '', $source))
		) {
			$source = $f;
		}
		$fichier = $source;
	}

	$terminaison_dest = '';
	if ($terminaison = _image_trouver_extension($fichier)) {
		$terminaison_dest = ($terminaison == 'gif') ? 'png' : $terminaison;
	}

	if (
		$forcer_format !== false
		// ignorer forcer_format si on a une image svg, que le filtre appelant ne supporte pas SVG, et que le forcage est un autre format image
		and ($terminaison_dest !== 'svg' or $support_svg or !in_array($forcer_format, _image_extensions_acceptees_en_sortie()))
	) {
		$terminaison_dest = $forcer_format;
	}

	if (!$terminaison_dest) {
		return false;
	}

	$nom_fichier = substr($fichier, 0, strlen($fichier) - (strlen($terminaison) + 1));
	$fichier_dest = $nom_fichier;
	if (
		($find_in_path and $f = find_in_path($fichier) and $fichier = $f)
		or @file_exists($f = $fichier)
	) {
		// on passe la balise img a taille image qui exraira les attributs si possible
		// au lieu de faire un acces disque sur le fichier
		list($ret['hauteur'], $ret['largeur']) = taille_image($find_in_path ? $f : $img);
		$date_src = @filemtime($f);
	} elseif (
		@file_exists($f = "$fichier.src")
		and lire_fichier($f, $valeurs)
		and $valeurs = unserialize($valeurs)
		and isset($valeurs['hauteur_dest'])
		and isset($valeurs['largeur_dest'])
	) {
		$ret['hauteur'] = $valeurs['hauteur_dest'];
		$ret['largeur'] = $valeurs['largeur_dest'];
		$date_src = $valeurs['date'];
	} // pas de fichier source par la
	else {
		return false;
	}

	// pas de taille mesurable
	if (!($ret['hauteur'] or $ret['largeur'])) {
		return false;
	}

	// les images calculees dependent du chemin du fichier source
	// pour une meme image source et un meme filtre on aboutira a 2 fichiers selon si l'appel est dans le public ou dans le prive
	// ce n'est pas totalement optimal en terme de stockage, mais chaque image est associee a un fichier .src
	// qui contient la methode de reconstrucion (le filtre + les arguments d'appel) et les arguments different entre prive et public
	// la mise en commun du fichier image cree donc un bug et des problemes qui necessiteraient beaucoup de complexite de code
	// alors que ca concerne peu de site au final
	// la release de r23632+r23633+r23634 a provoque peu de remontee de bug attestant du peu de sites impactes
	$identifiant = $fichier;

	// cas general :
	// on a un dossier cache commun et un nom de fichier qui varie avec l'effet
	// cas particulier de reduire :
	// un cache par dimension, et le nom de fichier est conserve, suffixe par la dimension aussi
	$cache = 'cache-gd2';
	if (substr($effet, 0, 7) == 'reduire') {
		list(, $maxWidth, $maxHeight) = explode('-', $effet);
		list($destWidth, $destHeight) = _image_ratio($ret['largeur'], $ret['hauteur'], $maxWidth, $maxHeight);
		$ret['largeur_dest'] = $destWidth;
		$ret['hauteur_dest'] = $destHeight;
		$effet = "L{$destWidth}xH$destHeight";
		$cache = 'cache-vignettes';
		$fichier_dest = basename($fichier_dest);
		if (($ret['largeur'] <= $maxWidth) && ($ret['hauteur'] <= $maxHeight)) {
			// on garde la terminaison initiale car image simplement copiee
			// et on postfixe son nom avec un md5 du path
			$terminaison_dest = $terminaison;
			$fichier_dest .= '-' . substr(md5("$identifiant"), 0, 5);
		} else {
			$fichier_dest .= '-' . substr(md5("$identifiant-$effet"), 0, 5);
		}
		$cache = sous_repertoire(_DIR_VAR, $cache);
		$cache = sous_repertoire($cache, $effet);
	} else {
		$fichier_dest = md5("$identifiant-$effet");
		$cache = sous_repertoire(_DIR_VAR, $cache);
		$cache = sous_repertoire($cache, substr($fichier_dest, 0, 2));
		$fichier_dest = substr($fichier_dest, 2);
	}

	$fichier_dest = $cache . $fichier_dest . '.' . $terminaison_dest;

	$GLOBALS['images_calculees'][] = $fichier_dest;

	$creer = true;
	// si recalcul des images demande, recalculer chaque image une fois
	if (defined('_VAR_IMAGES') and _VAR_IMAGES and !isset($images_recalcul[$fichier_dest])) {
		$images_recalcul[$fichier_dest] = true;
	} else {
		if (@file_exists($f = $fichier_dest)) {
			if (filemtime($f) >= $date_src) {
				$creer = false;
			}
		} else {
			if (
				@file_exists($f = "$fichier_dest.src")
				and lire_fichier($f, $valeurs)
				and $valeurs = unserialize($valeurs)
				and $valeurs['date'] >= $date_src
			) {
				$creer = false;
			}
		}
	}
	if ($creer) {
		if (!@file_exists($fichier)) {
			if (!@file_exists("$fichier.src")) {
				spip_log("Image absente : $fichier");

				return false;
			}
			# on reconstruit l'image source absente a partir de la chaine des .src
			reconstruire_image_intermediaire($fichier);
		}
	}

	if ($creer) {
		spip_log(
			'filtre image ' . ($fonction_creation ? reset($fonction_creation) : '') . "[$effet] sur $fichier",
			'images' . _LOG_DEBUG
		);
	}

	$term_fonction = _image_trouver_extension_pertinente($fichier);
	$ret['fonction_imagecreatefrom'] = '_imagecreatefrom' . $term_fonction;
	$ret['fichier'] = $fichier;
	$ret['fonction_image'] = '_image_image' . $terminaison_dest;
	$ret['fichier_dest'] = $fichier_dest;
	$ret['format_source'] = _image_extension_normalisee($terminaison);
	$ret['format_dest'] = $terminaison_dest;
	$ret['date_src'] = $date_src;
	$ret['creer'] = $creer;
	$ret['class'] = extraire_attribut($img, 'class');
	$ret['alt'] = extraire_attribut($img, 'alt');
	$ret['style'] = extraire_attribut($img, 'style');
	$ret['tag'] = $img;
	if ($fonction_creation) {
		$ret['reconstruction'] = $fonction_creation;
		# ecrire ici comment creer le fichier, car il est pas sur qu'on l'ecrira reelement
		# cas de image_reduire qui finalement ne reduit pas l'image source
		# ca evite d'essayer de le creer au prochain hit si il n'est pas la
		#ecrire_fichier($ret['fichier_dest'].'.src',serialize($ret),true);
	}

	$ret = pipeline('image_preparer_filtre', [
			'args' => [
				'img' => $img,
				'effet' => $effet,
				'forcer_format' => $forcer_format,
				'fonction_creation' => $fonction_creation,
				'find_in_path' => $find_in_path,
			],
			'data' => $ret
		]);

	// une globale pour le debug en cas de crash memoire
	$GLOBALS['derniere_image_calculee'] = $ret;

	// traiter le cas particulier des SVG : si le filtre n'a pas annonce explicitement qu'il savait faire, on delegue
	if ($term_fonction === 'svg') {
		if ($creer and !$support_svg) {
			process_image_svg_identite($ret);
			$ret['creer'] = false;
		}
	}
	else {
		if (!function_exists($ret['fonction_imagecreatefrom'])) {
			return false;
		}
	}

	return $ret;
}


/**
 * @param string $quoi
 * @return array
 */
function _image_extensions_acceptees_en_entree() {
	static $extensions = null;
	if (empty($extensions)) {
		$extensions = ['png', 'gif', 'jpg', 'jpeg'];
		if (!empty($GLOBALS['meta']['gd_formats'])) {
			// action=tester renseigne gd_formats et detecte le support de webp
			$extensions = array_merge(explode(',', $GLOBALS['meta']['gd_formats']));
			$extensions = array_map('trim', $extensions);
			$extensions = array_filter($extensions);
			$extensions = array_unique($extensions);
			if (in_array('jpg', $extensions)) { $extensions[] = 'jpeg';
			}
		}
		$extensions[] = 'svg'; // on le supporte toujours avec des fonctions specifiques
	}

	return $extensions;
}

/**
 * @return array|string[]|null
 */
function _image_extensions_acceptees_en_sortie() {
	static $extensions = null;
	if (empty($extensions)) {
		$extensions = _image_extensions_acceptees_en_entree();
		$extensions = array_diff($extensions, ['jpeg']);
		if (in_array('gif', $extensions) and !function_exists('imagegif')) {
			$extensions = array_diff($extensions, ['gif']);
		}
		if (in_array('webp', $extensions) and !function_exists('imagewebp')) {
			$extensions = array_diff($extensions, ['webp']);
		}
	}

	return $extensions;
}

function _image_extension_normalisee($extension) {
	$extension = strtolower($extension);
	if ($extension === 'jpeg') {
		$extension = 'jpg';
	}
	return $extension;
}

function _image_extensions_conservent_transparence() {
	return ['png', 'webp'];
}


/**
 * Retourne la terminaison d’un fichier image
 * @param string $path
 * @return string
 */
function _image_trouver_extension($path) {
	$preg_extensions = implode('|', _image_extensions_acceptees_en_entree());
	if (preg_match(",\.($preg_extensions)($|[?]),i", $path, $regs)) {
		$terminaison = strtolower($regs[1]);
		return $terminaison;
	}
	return '';
}

/**
 * Tente de trouver le véritable type d’une image,
 * même si une image est d’extension .jpg alors que son contenu est autre chose (gif ou png)
 *
 * @param string $path
 * @return string Extension, dans le format attendu par les fonctions 'gd' ('jpeg' pour les .jpg par exemple)
 */
function _image_trouver_extension_pertinente($path) {
	$path = supprimer_timestamp($path);
	$terminaison = _image_trouver_extension($path);
	if ($terminaison == 'jpg') {
		$terminaison = 'jpeg';
	}

	if (!file_exists($path)) {
		return $terminaison;
	}

	if (!$info = @spip_getimagesize($path)) {
		return $terminaison;
	}

	if (isset($info['mime'])) {
		$mime = $info['mime'];
	}
	else {
		$mime = image_type_to_mime_type($info[2]);
	}

	$_terminaison = _image_trouver_extension_depuis_mime($mime);
	if ($_terminaison and $_terminaison !== $terminaison) {
		spip_log("Mauvaise extension du fichier : $path . Son type mime est : $mime", 'images.' . _LOG_INFO_IMPORTANTE);
		$terminaison = $_terminaison;
	}
	return $terminaison;
}

/**
 * @param string $mime
 * @return string
 */
function _image_trouver_extension_depuis_mime($mime) {
	switch (strtolower($mime)) {
		case 'image/png':
		case 'image/x-png':
			$terminaison = 'png';
			break;

		case 'image/jpg':
		case 'image/jpeg':
		case 'image/pjpeg':
			$terminaison = 'jpeg';
			break;

		case 'image/gif':
			$terminaison = 'gif';
			break;

		case 'image/webp':
		case 'image/x-webp':
			$terminaison = 'webp';
			break;

		case 'image/svg+xml':
			$terminaison = 'svg';
			break;

		default:
			$terminaison = '';
	}

	return $terminaison;
}


/**
 * Crée une image depuis un fichier ou une URL (en indiquant la fonction GD à utiliser)
 *
 * Utilise les fonctions spécifiques GD.
 * - Si la fonction GD n'existe pas (GD non actif?), génère une erreur, mais ne retourne rien
 * - Si l'image est impossible à créer avec la fonction indiquée, génère une erreur, et une image vide
 *
 * @param string $func
 *     Fonction GD à utiliser tel que 'imagecreatefromjpeg'
 * @param string $filename
 *     Le path vers l'image à traiter (par exemple : IMG/distant/jpg/image.jpg
 *     ou local/cache-vignettes/L180xH51/image.jpg).
 * @return resource|null
 *     Une ressource de type Image GD.
 */
function _imagecreatefrom_func(string $func, string $filename) {
	if (!function_exists($func)) {
		spip_log("GD indisponible : $func inexistante. Traitement $filename impossible.", _LOG_CRITIQUE);
		erreur_squelette("GD indisponible : $func inexistante. Traitement $filename impossible.");
		return null;
	}
	$img = @$func($filename);
	if (!$img) {
		spip_log("Erreur lecture imagecreatefromjpeg $filename", _LOG_CRITIQUE);
		erreur_squelette("Erreur lecture imagecreatefromjpeg $filename");
		$img = imagecreate(10, 10);
	}
	return $img;
}

/**
 * Crée une image depuis un fichier ou une URL (au format jpeg)
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @uses _imagecreatefrom_func()
 * @param string $filename
 *     Le path vers l'image à traiter (par exemple : IMG/distant/jpg/image.jpg
 *     ou local/cache-vignettes/L180xH51/image.jpg).
 * @return resource|null
 *     Une ressource de type Image GD.
 */
function _imagecreatefromjpeg($filename) {
	return _imagecreatefrom_func('imagecreatefromjpeg', $filename);
}

/**
 * Crée une image depuis un fichier ou une URL (au format png)
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @uses _imagecreatefrom_func()
 * @param string $filename
 *     Le path vers l'image à traiter (par exemple : IMG/distant/png/image.png
 *     ou local/cache-vignettes/L180xH51/image.png).
 * @return resource|null
 *     Une ressource de type Image GD.
 */
function _imagecreatefrompng($filename) {
	return _imagecreatefrom_func('imagecreatefrompng', $filename);
}

/**
 * Crée une image depuis un fichier ou une URL (au format gif)
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @uses _imagecreatefrom_func()
 * @param string $filename
 *     Le path vers l'image à traiter (par exemple : IMG/distant/gif/image.gif
 *     ou local/cache-vignettes/L180xH51/image.gif).
 * @return resource|null
 *     Une ressource de type Image GD.
 */
function _imagecreatefromgif($filename) {
	return _imagecreatefrom_func('imagecreatefromgif', $filename);
}


/**
 * Crée une image depuis un fichier ou une URL (au format webp)
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @uses _imagecreatefrom_func()
 * @param string $filename
 *     Le path vers l'image à traiter (par exemple : IMG/distant/png/image.png
 *     ou local/cache-vignettes/L180xH51/image.png).
 * @return resource|null
 *     Une ressource de type Image GD.
 */
function _imagecreatefromwebp($filename) {
	return _imagecreatefrom_func('imagecreatefromwebp', $filename);
}

/**
 * Affiche ou sauvegarde une image au format PNG
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @param ressource $img
 *     Une ressource de type Image GD.
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.png).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagepng($img, $fichier) {
	if (!function_exists('imagepng')) {
		return false;
	}
	$tmp = $fichier . '.tmp';
	$ret = imagepng($img, $tmp);
	if (file_exists($tmp)) {
		$taille_test = getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return $ret;
	}

	return false;
}

/**
 * Affiche ou sauvegarde une image au format GIF
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @param resource $img
 *     Une ressource de type Image GD.
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.gif).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagegif($img, $fichier) {
	if (!function_exists('imagegif')) {
		return false;
	}
	$tmp = $fichier . '.tmp';
	$ret = imagegif($img, $tmp);
	if (file_exists($tmp)) {
		$taille_test = getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return $ret;
	}

	return false;
}

/**
 * Affiche ou sauvegarde une image au format JPG
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @param resource $img
 *     Une ressource de type Image GD.
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.jpg).
 * @param int $qualite
 *     Le niveau de qualité du fichier résultant : de 0 (pire qualité, petit
 *     fichier) à 100 (meilleure qualité, gros fichier). Par défaut, prend la
 *     valeur (85) de la constante _IMG_GD_QUALITE (modifiable depuis
 *     mes_options.php).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagejpg($img, $fichier, $qualite = _IMG_GD_QUALITE) {
	if (!function_exists('imagejpeg')) {
		return false;
	}
	$tmp = $fichier . '.tmp';

	// Enable interlancing
	imageinterlace($img, true);

	$ret = imagejpeg($img, $tmp, $qualite);

	if (file_exists($tmp)) {
		$taille_test = getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return $ret;
	}

	return false;
}

/**
 * Crée un fichier-image au format ICO
 *
 * Utilise les fonctions de la classe phpthumb_functions.
 *
 * @uses phpthumb_functions::GD2ICOstring()
 *
 * @param resource $img
 *     Une ressource de type Image GD.
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.jpg).
 * @return bool
 *     true si le fichier a bien été créé ; false sinon.
 */
function _image_imageico($img, $fichier) {
	$gd_image_array = [$img];

	return ecrire_fichier($fichier, phpthumb_functions::GD2ICOstring($gd_image_array));
}


/**
 * Affiche ou sauvegarde une image au format WEBP
 *
 * Utilise les fonctions spécifiques GD.
 *
 * @param resource $img
 *     Une ressource de type Image GD.
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.png).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagewebp($img, $fichier, $qualite = _IMG_GD_QUALITE) {
	if (!function_exists('imagewebp')) {
		return false;
	}
	$tmp = $fichier . '.tmp';
	$ret = imagewebp($img, $tmp, $qualite);
	if (file_exists($tmp)) {
		$taille_test = getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return $ret;
	}

	return false;
}

/**
 * Sauvegarde une image au format SVG
 *
 * - N'UTILISE PAS GD -
 * C'est une fonction derogatoire pour faciliter le traitement des SVG
 *
 * @param string $img
 *     contenu du SVG ou chemin vers le SVG source (et c'est alors une copie)
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.png).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagesvg($img, $fichier) {

	$tmp = $fichier . '.tmp';
	if (strpos($img, '<') === false) {
		$img = supprimer_timestamp($img);
		if (!file_exists($img)) {
			return false;
		}
		@copy($img, $tmp);
		if (filesize($tmp) == filesize($img)) {
			spip_unlink($fichier); // le fichier peut deja exister
			@rename($tmp, $fichier);
			return true;
		}
		return false;
	}

	file_put_contents($tmp, $img);
	if (file_exists($tmp)) {
		$taille_test = spip_getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return true;
	}

	return false;
}


/**
 * Finalise le traitement GD
 *
 * Crée un fichier_image temporaire .src ou vérifie que le fichier_image
 * définitif a bien été créé.
 *
 * @uses statut_effacer_images_temporaires()
 *
 * @param resource|string $img
 *     Une ressource de type Image GD (ou une string pour un SVG)
 * @param array $valeurs
 *     Un tableau des informations (tailles, traitement, path...) accompagnant
 *     l'image.
 * @param int $qualite
 *     N'est utilisé que pour les images jpg.
 *     Le niveau de qualité du fichier résultant : de 0 (pire qualité, petit
 *     fichier) à 100 (meilleure qualité, gros fichier). Par défaut, prend la
 *     valeur (85) de la constante _IMG_GD_QUALITE (modifiable depuis
 *     mes_options.php).
 * @return bool
 *     - true si le traitement GD s'est bien finalisé ;
 *     - false sinon.
 */
function _image_gd_output($img, $valeurs, $qualite = _IMG_GD_QUALITE, $fonction = null) {
	if (is_null($fonction)) {
		$fonction = '_image_image' . $valeurs['format_dest'];
	}
	$ret = false;
	#un flag pour reperer les images gravees
	$lock =
		!statut_effacer_images_temporaires('get') // si la fonction n'a pas ete activee, on grave tout
	or (@file_exists($valeurs['fichier_dest']) and !@file_exists($valeurs['fichier_dest'] . '.src'));
	if (
		function_exists($fonction)
		&& ($ret = $fonction($img, $valeurs['fichier_dest'], $qualite)) # on a reussi a creer l'image
		&& isset($valeurs['reconstruction']) # et on sait comment la resonctruire le cas echeant
		&& !$lock
	) {
		if (@file_exists($valeurs['fichier_dest'])) {
			// dans tous les cas mettre a jour la taille de l'image finale
			list($valeurs['hauteur_dest'], $valeurs['largeur_dest']) = taille_image($valeurs['fichier_dest']);
			$valeurs['date'] = @filemtime($valeurs['fichier_dest']); // pour la retrouver apres disparition
			ecrire_fichier($valeurs['fichier_dest'] . '.src', serialize($valeurs), true);
		}
	}

	return $ret;
}

/**
 * Reconstruit une image à partir des sources de contrôle de son ancienne
 * construction
 *
 * @uses ramasse_miettes()
 *
 * @param string $fichier_manquant
 *     Chemin vers le fichier manquant
 **/
function reconstruire_image_intermediaire($fichier_manquant) {
	$reconstruire = [];
	$fichier = $fichier_manquant;
	while (
		strpos($fichier, '://') === false
		and !@file_exists($fichier)
		and lire_fichier($src = "$fichier.src", $source)
		and $valeurs = unserialize($source)
		and ($fichier = $valeurs['fichier']) # l'origine est connue (on ne verifie pas son existence, qu'importe ...)
	) {
		spip_unlink($src); // si jamais on a un timeout pendant la reconstruction, elle se fera naturellement au hit suivant
		$reconstruire[] = $valeurs['reconstruction'];
	}
	while (count($reconstruire)) {
		$r = array_pop($reconstruire);
		$fonction = $r[0];
		$args = $r[1];
		call_user_func_array($fonction, $args);
	}
	// cette image intermediaire est commune a plusieurs series de filtre, il faut la conserver
	// mais l'on peut nettoyer les miettes de sa creation
	ramasse_miettes($fichier_manquant);
}

/**
 * Indique qu'un fichier d'image calculé est à conserver
 *
 * Permet de rendre une image définitive et de supprimer les images
 * intermédiaires à son calcul.
 *
 * Supprime le fichier de contrôle de l’image cible (le $fichier.src)
 * ce qui indique que l'image est définitive.
 *
 * Remonte ensuite la chaîne des fichiers de contrôle pour supprimer
 * les images temporaires (mais laisse les fichiers de contrôle permettant
 * de les reconstruire).
 *
 * @param string $fichier
 *     Chemin du fichier d'image calculé
 **/
function ramasse_miettes($fichier) {
	if (
		strpos($fichier, '://') !== false
		or !lire_fichier($src = "$fichier.src", $source)
		or !$valeurs = unserialize($source)
	) {
		return;
	}
	spip_unlink($src); # on supprime la reference a sa source pour marquer cette image comme non intermediaire
	while (
		($fichier = $valeurs['fichier']) # l'origine est connue (on ne verifie pas son existence, qu'importe ...)
		and (substr($fichier, 0, strlen(_DIR_VAR)) == _DIR_VAR) # et est dans local
		and (lire_fichier(
			$src = "$fichier.src",
			$source
		)) # le fichier a une source connue (c'est donc une image calculee intermediaire)
		and ($valeurs = unserialize($source))  # et valide
	) {
		# on efface le fichier
		spip_unlink($fichier);
		# mais laisse le .src qui permet de savoir comment reconstruire l'image si besoin
		#spip_unlink($src);
	}
}


/**
 * Clôture une série de filtres d'images
 *
 * Ce filtre est automatiquement appelé à la fin d'une série de filtres
 * d'images dans un squelette.
 *
 * @filtre
 * @uses reconstruire_image_intermediaire()
 *     Si l'image finale a déjà été supprimée car considérée comme temporaire
 *     par une autre série de filtres images débutant pareil
 * @uses ramasse_miettes()
 *     Pour déclarer l'image définitive et nettoyer les images intermédiaires.
 *
 * @pipeline_appel post_image_filtrer
 *
 * @param string $img
 *     Code HTML de l'image
 * @return string
 *     Code HTML de l'image
 **/
function image_graver($img) {
	// appeler le filtre post_image_filtrer qui permet de faire
	// des traitements auto a la fin d'une serie de filtres
	$img = pipeline('post_image_filtrer', $img);

	$fichier_ori = $fichier = extraire_attribut($img, 'src');
	if (($p = strpos($fichier, '?')) !== false) {
		$fichier = substr($fichier, 0, $p);
	}
	if (strlen($fichier) < 1) {
		$fichier = $img;
	}
	# si jamais le fichier final n'a pas ete calcule car suppose temporaire
	# et qu'il ne s'agit pas d'une URL
	if (strpos($fichier, '://') === false and !@file_exists($fichier)) {
		reconstruire_image_intermediaire($fichier);
	}
	ramasse_miettes($fichier);

	// ajouter le timestamp si besoin
	if (strpos($fichier_ori, '?') === false) {
		// on utilise str_replace pour attraper le onmouseover des logo si besoin
		$img = str_replace($fichier_ori, timestamp($fichier_ori), $img);
	}

	return $img;
}


if (!function_exists('imagepalettetotruecolor')) {
	/**
	 * Transforme une image à palette indexée (256 couleurs max) en "vraies" couleurs RGB
	 *
	 * @note Pour compatibilité avec PHP < 5.5
	 *
	 * @link http://php.net/manual/fr/function.imagepalettetotruecolor.php
	 *
	 * @param ressource $img
	 * @return bool
	 *     - true si l'image est déjà en vrai RGB ou peut être transformée
	 *     - false si la transformation ne peut être faite.
	 **/
	function imagepalettetotruecolor(&$img) {
		if (!$img or !function_exists('imagecreatetruecolor')) {
			return false;
		} elseif (!imageistruecolor($img)) {
			$w = imagesx($img);
			$h = imagesy($img);
			$img1 = imagecreatetruecolor($w, $h);
			//Conserver la transparence si possible
			if (function_exists('ImageCopyResampled')) {
				if (function_exists('imageAntiAlias')) {
					imageAntiAlias($img1, true);
				}
				@imagealphablending($img1, false);
				@imagesavealpha($img1, true);
				@ImageCopyResampled($img1, $img, 0, 0, 0, 0, $w, $h, $w, $h);
			} else {
				imagecopy($img1, $img, 0, 0, 0, 0, $w, $h);
			}

			$img = $img1;
		}

		return true;
	}
}

/**
 * Applique des attributs de taille (width, height) à une balise HTML
 *
 * Utilisé avec des balises `<img>` tout particulièrement.
 *
 * Modifie l'attribut style s'il était renseigné, en enlevant les
 * informations éventuelles width / height dedans.
 *
 * @uses extraire_attribut()
 * @uses inserer_attribut()
 *
 * @param string $tag
 *     Code html de la balise
 * @param int $width
 *     Hauteur
 * @param int $height
 *     Largeur
 * @param bool|string $style
 *     Attribut html style à appliquer.
 *     False extrait celui présent dans la balise
 * @return string
 *     Code html modifié de la balise.
 **/
function _image_tag_changer_taille($tag, $width, $height, $style = false) {
	if ($style === false) {
		$style = extraire_attribut($tag, 'style');
	}

	// enlever le width et height du style
	if ($style) {
		$style = preg_replace(',(^|;)\s*(width|height)\s*:\s*[^;]+,ims', '', $style);
	}
	if ($style and $style[0] === ';') {
		$style = substr($style, 1);
	}

	// mettre des attributs de width et height sur les images,
	// ca accelere le rendu du navigateur
	// ca permet aux navigateurs de reserver la bonne taille
	// quand on a desactive l'affichage des images.
	$tag = inserer_attribut($tag, 'width', round($width));
	$tag = inserer_attribut($tag, 'height', round($height));

	// attributs deprecies. Transformer en CSS
	if ($espace = extraire_attribut($tag, 'hspace')) {
		$style = "margin:${espace}px;" . $style;
		$tag = inserer_attribut($tag, 'hspace', '');
	}

	$tag = inserer_attribut($tag, 'style', (string) $style, true, $style ? false : true);

	return $tag;
}


/**
 * Écriture de la balise img en sortie de filtre image
 *
 * Reprend le tag initial et surcharge les attributs modifiés
 *
 * @pipeline_appel image_ecrire_tag_preparer
 * @pipeline_appel image_ecrire_tag_finir
 *
 * @uses _image_tag_changer_taille()
 * @uses extraire_attribut()
 * @uses inserer_attribut()
 * @see  _image_valeurs_trans()
 *
 * @param array $valeurs
 *     Description de l'image tel que retourné par `_image_valeurs_trans()`
 * @param array $surcharge
 *     Permet de surcharger certaines descriptions présentes dans `$valeurs`
 *     tel que 'style', 'width', 'height'
 * @return string
 *     Retourne le code HTML de l'image
 **/
function _image_ecrire_tag($valeurs, $surcharge = []) {
	$valeurs = pipeline('image_ecrire_tag_preparer', $valeurs);

	// fermer les tags img pas bien fermes;
	$tag = str_replace('>', '/>', str_replace('/>', '>', $valeurs['tag']));

	// le style
	$style = $valeurs['style'];
	if (isset($surcharge['style'])) {
		$style = $surcharge['style'];
		unset($surcharge['style']);
	}

	// traiter specifiquement la largeur et la hauteur
	$width = $valeurs['largeur'];
	if (isset($surcharge['width'])) {
		$width = $surcharge['width'];
		unset($surcharge['width']);
	}
	$height = $valeurs['hauteur'];
	if (isset($surcharge['height'])) {
		$height = $surcharge['height'];
		unset($surcharge['height']);
	}

	$tag = _image_tag_changer_taille($tag, $width, $height, $style);
	// traiter specifiquement le src qui peut etre repris dans un onmouseout
	// on remplace toute les ref a src dans le tag
	$src = extraire_attribut($tag, 'src');
	if (isset($surcharge['src'])) {
		$tag = str_replace($src, $surcharge['src'], $tag);
		// si il y a des & dans src, alors ils peuvent provenir d'un &amp
		// pas garanti comme methode, mais mieux que rien
		if (strpos($src, '&') !== false) {
			$tag = str_replace(str_replace('&', '&amp;', $src), $surcharge['src'], $tag);
		}
		$src = $surcharge['src'];
		unset($surcharge['src']);
	}

	$class = $valeurs['class'];
	if (isset($surcharge['class'])) {
		$class = $surcharge['class'];
		unset($surcharge['class']);
	}
	if (is_scalar($class) && strlen($class)) {
		$tag = inserer_attribut($tag, 'class', $class);
	}

	if (count($surcharge)) {
		foreach ($surcharge as $attribut => $valeur) {
			$tag = inserer_attribut($tag, $attribut, $valeur);
		}
	}

	$tag = pipeline(
		'image_ecrire_tag_finir',
		[
			'args' => [
				'valeurs' => $valeurs,
				'surcharge' => $surcharge,
			],
			'data' => $tag
		]
	);

	return $tag;
}

/**
 * Crée si possible une miniature d'une image
 *
 * @see  _image_valeurs_trans()
 * @uses _image_ratio()
 *
 * @param array $valeurs
 *     Description de l'image, telle que retournée par `_image_valeurs_trans()`
 * @param int $maxWidth
 *     Largeur maximum en px de la miniature à réaliser
 * @param int $maxHeight
 *     Hauteur maximum en px de la miniateure à réaliser
 * @param string $process
 *     Librairie graphique à utiliser (gd1, gd2, netpbm, convert, imagick).
 *     AUTO utilise la librairie sélectionnée dans la configuration.
 * @param bool $force
 * @return array|null
 *     Description de l'image, sinon null.
 **/
function _image_creer_vignette($valeurs, $maxWidth, $maxHeight, $process = 'AUTO', $force = false) {
	// ordre de preference des formats graphiques pour creer les vignettes
	// le premier format disponible, selon la methode demandee, est utilise
	$image = $valeurs['fichier'];
	$format = $valeurs['format_source'];
	$destdir = dirname($valeurs['fichier_dest']);
	$destfile = basename($valeurs['fichier_dest'], '.' . $valeurs['format_dest']);

	$format_sortie = $valeurs['format_dest'];

	if (($process == 'AUTO') and isset($GLOBALS['meta']['image_process'])) {
		$process = $GLOBALS['meta']['image_process'];
	}

	// si le doc n'est pas une image dans un format accetpable, refuser
	if (!$force and !in_array($format, formats_image_acceptables(in_array($process, ['gd1', 'gd2'])))) {
		return;
	}
	$destination = "$destdir/$destfile";

	// calculer la taille
	if (($srcWidth = $valeurs['largeur']) && ($srcHeight = $valeurs['hauteur'])) {
		if (!($destWidth = $valeurs['largeur_dest']) || !($destHeight = $valeurs['hauteur_dest'])) {
			list($destWidth, $destHeight) = _image_ratio($srcWidth, $srcHeight, $maxWidth, $maxHeight);
		}
	} elseif ($process == 'convert' or $process == 'imagick') {
		$destWidth = $maxWidth;
		$destHeight = $maxHeight;
	} else {
		spip_log("echec $process sur $image");

		return;
	}

	$vignette = '';

	// Si l'image est de la taille demandee (ou plus petite), simplement la retourner
	if ($srcWidth and $srcWidth <= $maxWidth and $srcHeight <= $maxHeight) {
		$vignette = $destination . '.' . $format;
		@copy($image, $vignette);
	}

	elseif ($valeurs['format_source'] === 'svg') {
		if ($svg = svg_redimensionner($valeurs['fichier'], $destWidth, $destHeight)) {
			$format_sortie = 'svg';
			$vignette = $destination . '.' . $format_sortie;
			$valeurs['fichier_dest'] = $vignette;
			_image_gd_output($svg, $valeurs);
		}
	}

	// imagemagick en ligne de commande
	elseif ($process == 'convert') {
		if (!defined('_CONVERT_COMMAND')) {
			define('_CONVERT_COMMAND', 'convert');
		} // Securite : mes_options.php peut preciser le chemin absolu
		if (!defined('_RESIZE_COMMAND')) {
			define('_RESIZE_COMMAND', _CONVERT_COMMAND . ' -quality ' . _IMG_CONVERT_QUALITE . ' -resize %xx%y! %src %dest');
		}
		$vignette = $destination . '.' . $format_sortie;
		$commande = str_replace(
			['%x', '%y', '%src', '%dest'],
			[
				$destWidth,
				$destHeight,
				escapeshellcmd($image),
				escapeshellcmd($vignette)
			],
			_RESIZE_COMMAND
		);
		spip_log($commande);
		exec($commande);
		if (!@file_exists($vignette)) {
			spip_log("echec convert sur $vignette");

			return;  // echec commande
		}
	}

	// php5 imagemagick
	elseif ($process == 'imagick') {
		$vignette = "$destination." . $format_sortie;

		if (!class_exists('Imagick')) {
			spip_log('Classe Imagick absente !', _LOG_ERREUR);

			return;
		}
		$imagick = new Imagick();
		$imagick->readImage($image);
		$imagick->resizeImage(
			$destWidth,
			$destHeight,
			Imagick::FILTER_LANCZOS,
			1
		);//, IMAGICK_FILTER_LANCZOS, _IMG_IMAGICK_QUALITE / 100);
		$imagick->writeImage($vignette);

		if (!@file_exists($vignette)) {
			spip_log("echec imagick sur $vignette");

			return;
		}
	}

	// netpbm
	elseif ($process == 'netpbm') {
		if (!defined('_PNMSCALE_COMMAND')) {
			define('_PNMSCALE_COMMAND', 'pnmscale');
		} // Securite : mes_options.php peut preciser le chemin absolu
		if (_PNMSCALE_COMMAND == '') {
			return;
		}
		$vignette = $destination . '.' . $format_sortie;
		$pnmtojpeg_command = str_replace('pnmscale', 'pnmtojpeg', _PNMSCALE_COMMAND);
		if ($format == 'jpg') {
			$jpegtopnm_command = str_replace('pnmscale', 'jpegtopnm', _PNMSCALE_COMMAND);
			exec("$jpegtopnm_command $image | " . _PNMSCALE_COMMAND . " -width $destWidth | $pnmtojpeg_command > $vignette");
			if (!($s = @filesize($vignette))) {
				spip_unlink($vignette);
			}
			if (!@file_exists($vignette)) {
				spip_log("echec netpbm-jpg sur $vignette");

				return;
			}
		} else {
			if ($format == 'gif') {
				$giftopnm_command = str_replace('pnmscale', 'giftopnm', _PNMSCALE_COMMAND);
				exec("$giftopnm_command $image | " . _PNMSCALE_COMMAND . " -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!($s = @filesize($vignette))) {
					spip_unlink($vignette);
				}
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-gif sur $vignette");

					return;
				}
			} else {
				if ($format == 'png') {
					$pngtopnm_command = str_replace('pnmscale', 'pngtopnm', _PNMSCALE_COMMAND);
					exec("$pngtopnm_command $image | " . _PNMSCALE_COMMAND . " -width $destWidth | $pnmtojpeg_command > $vignette");
					if (!($s = @filesize($vignette))) {
						spip_unlink($vignette);
					}
					if (!@file_exists($vignette)) {
						spip_log("echec netpbm-png sur $vignette");

						return;
					}
				}
			}
		}
	}

	// gd ou gd2
	elseif ($process == 'gd1' or $process == 'gd2') {
		if (!function_exists('gd_info')) {
			spip_log('Librairie GD absente !', _LOG_ERREUR);

			return;
		}
		if (_IMG_GD_MAX_PIXELS && $srcWidth * $srcHeight > _IMG_GD_MAX_PIXELS) {
			spip_log('vignette gd1/gd2 impossible : ' . $srcWidth * $srcHeight . 'pixels');

			return;
		}
		$destFormat = $format_sortie;
		if (!$destFormat) {
			spip_log("pas de format pour $image");

			return;
		}

		$fonction_imagecreatefrom = $valeurs['fonction_imagecreatefrom'];
		if (!function_exists($fonction_imagecreatefrom)) {
			return '';
		}
		$srcImage = @$fonction_imagecreatefrom($image);
		if (!$srcImage) {
			spip_log('echec gd1/gd2');

			return;
		}

		// Initialisation de l'image destination
		$destImage = null;
		if ($process == 'gd2' and $destFormat != 'gif') {
			$destImage = ImageCreateTrueColor($destWidth, $destHeight);
		}
		if (!$destImage) {
			$destImage = ImageCreate($destWidth, $destHeight);
		}

		// Recopie de l'image d'origine avec adaptation de la taille
		$ok = false;
		if (($process == 'gd2') and function_exists('ImageCopyResampled')) {
			if ($format == 'gif') {
				// Si un GIF est transparent,
				// fabriquer un PNG transparent
				$transp = imagecolortransparent($srcImage);
				if ($transp > 0) {
					$destFormat = 'png';
				}
			}
			if (in_array($destFormat, _image_extensions_conservent_transparence())) {
				// Conserver la transparence
				if (function_exists('imageAntiAlias')) {
					imageAntiAlias($destImage, true);
				}
				@imagealphablending($destImage, false);
				@imagesavealpha($destImage, true);
			}
			$ok = @ImageCopyResampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
		}
		if (!$ok) {
			$ok = ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
		}

		// Sauvegarde de l'image destination
		$valeurs['fichier_dest'] = $vignette = "$destination.$destFormat";
		$valeurs['format_dest'] = $format = $destFormat;
		_image_gd_output($destImage, $valeurs);

		if ($srcImage) {
			ImageDestroy($srcImage);
		}
		ImageDestroy($destImage);
	}

	if (!$vignette or !$size = @spip_getimagesize($vignette)) {
		$size = [$destWidth, $destHeight];
	}

	// Gaffe: en safe mode, pas d'acces a la vignette,
	// donc risque de balancer "width='0'", ce qui masque l'image sous MSIE
	if ($size[0] < 1) {
		$size[0] = $destWidth;
	}
	if ($size[1] < 1) {
		$size[1] = $destHeight;
	}

	$retour['width'] = $largeur = $size[0];
	$retour['height'] = $hauteur = $size[1];

	$retour['fichier'] = $vignette;
	$retour['format'] = $format;
	$retour['date'] = @filemtime($vignette);

	// renvoyer l'image
	return $retour;
}

/**
 * Réduire des dimensions en respectant un ratio
 *
 * Réduit des dimensions (hauteur, largeur) pour qu'elles
 * soient incluses dans une hauteur et largeur maximum fournies
 * en respectant la proportion d'origine
 *
 * @example `image_ratio(1000, 1000, 100, 10)` donne `array(10, 10, 100)`
 * @see ratio_passe_partout() Assez proche.
 *
 * @param int $srcWidth Largeur de l'image source
 * @param int $srcHeight Hauteur de l'image source
 * @param int $maxWidth Largeur maximum souhaitée
 * @param int $maxHeight Hauteur maximum souhaitée
 * @return array Liste [ largeur, hauteur, ratio de réduction ]
 **/
function _image_ratio(int $srcWidth, int $srcHeight, int $maxWidth, int $maxHeight): array {
	$ratioWidth = $srcWidth / $maxWidth;
	$ratioHeight = $srcHeight / $maxHeight;

	if ($srcWidth <= $maxWidth and $srcHeight <= $maxHeight) {
		$destWidth = $srcWidth;
		$destHeight = $srcHeight;
	} elseif ($ratioWidth < $ratioHeight) {
		$destWidth = $srcWidth / $ratioHeight;
		$destHeight = $maxHeight;
	} else {
		$destWidth = $maxWidth;
		$destHeight = $srcHeight / $ratioWidth;
	}

	return [
		intval(round($destWidth)),
		intval(round($destHeight)),
		max($ratioWidth, $ratioHeight)
	];
}

/**
 * Réduire des dimensions en respectant un ratio sur la plus petite dimension
 *
 * Réduit des dimensions (hauteur, largeur) pour qu'elles
 * soient incluses dans la plus grande hauteur ou largeur maximum fournie
 * en respectant la proportion d'origine
 *
 * @example `ratio_passe_partout(1000, 1000, 100, 10)` donne `array(100, 100, 10)`
 * @see _image_ratio() Assez proche.
 *
 * @param int $srcWidth Largeur de l'image source
 * @param int $srcHeight Hauteur de l'image source
 * @param int $maxWidth Largeur maximum souhaitée
 * @param int $maxHeight Hauteur maximum souhaitée
 * @return array Liste [ largeur, hauteur, ratio de réduction ]
 **/
function ratio_passe_partout(int $srcWidth, int $srcHeight, int $maxWidth, int $maxHeight): array {
	$ratioWidth = $srcWidth / $maxWidth;
	$ratioHeight = $srcHeight / $maxHeight;

	if ($srcWidth <= $maxWidth and $srcHeight <= $maxHeight) {
		$destWidth = $srcWidth;
		$destHeight = $srcHeight;
	} elseif ($ratioWidth > $ratioHeight) {
		$destWidth = $srcWidth / $ratioHeight;
		$destHeight = $maxHeight;
	} else {
		$destWidth = $maxWidth;
		$destHeight = $srcHeight / $ratioWidth;
	}

	return [
		intval(round($destWidth)),
		intval(round($destHeight)),
		min($ratioWidth, $ratioHeight)
	];
}


/**
 * Fonction identite de traitement par defaut des images SVG
 * (quand un filtre n'annonce pas qu'il sait traiter un SVG on applique cette fonction a la place)
 *
 * @param array $image
 *   tableau des valeurs crees par _image_valeurs_trans
 * @return string
 */
function process_image_svg_identite($image) {
	if ($image['creer']) {
		$source = $image['fichier'];
		_image_gd_output($source, $image);
	}

	return _image_ecrire_tag($image, ['src' => $image['fichier_dest']]);
}


/**
 * Réduit une image
 *
 * @uses extraire_attribut()
 * @uses inserer_attribut()
 * @uses _image_valeurs_trans()
 * @uses _image_ratio()
 * @uses _image_tag_changer_taille()
 * @uses _image_ecrire_tag()
 * @uses _image_creer_vignette()
 *
 * @param array $fonction
 *     Un tableau à 2 éléments :
 *     1) string : indique le nom du filtre de traitement demandé (par exemple : `image_reduire`) ;
 *     2) array : tableau reprenant la valeur de `$img` et chacun des arguments passés au filtre utilisé.
 * @param string $img
 *     Chemin de l'image ou texte contenant une balise img
 * @param int $taille
 *     Largeur désirée
 * @param int $taille_y
 *     Hauteur désirée
 * @param bool $force
 * @param string $process
 *     Librairie graphique à utiliser (gd1, gd2, netpbm, convert, imagick).
 *     AUTO utilise la librairie sélectionnée dans la configuration.
 * @return string
 *     Code HTML de la balise img produite
 **/
function process_image_reduire($fonction, $img, $taille, $taille_y, $force, $process = 'AUTO') {
	$image = false;
	if (($process == 'AUTO') and isset($GLOBALS['meta']['image_process'])) {
		$process = $GLOBALS['meta']['image_process'];
	}
	# determiner le format de sortie
	$format_sortie = false; // le choix par defaut sera bon
	if ($process == 'netpbm') {
		$format_sortie = 'jpg';
	} elseif ($process == 'gd1' or $process == 'gd2') {
		$image = _image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}", $format_sortie, $fonction, false, _SVG_SUPPORTED);
		// on verifie que l'extension choisie est bonne (en principe oui)
		$gd_formats = formats_image_acceptables(true);
		if (
			is_array($image)
			and (!in_array($image['format_dest'], $gd_formats)
				or (!in_array($image['format_dest'], _image_extensions_acceptees_en_sortie()))
			)
		) {
			if ($image['format_source'] == 'jpg') {
				$formats_sortie = ['jpg', 'png', 'gif'];
			} else // les gif sont passes en png preferentiellement pour etre homogene aux autres filtres images
			{
				$formats_sortie = ['png', 'jpg', 'gif'];
			}
			// Choisir le format destination
			// - on sauve de preference en JPEG (meilleure compression)
			// - pour le GIF : les GD recentes peuvent le lire mais pas l'ecrire
			# bug : gd_formats contient la liste des fichiers qu'on sait *lire*,
			# pas *ecrire*
			$format_sortie = '';
			foreach ($formats_sortie as $fmt) {
				if (in_array($fmt, $gd_formats) and in_array($fmt, _image_extensions_acceptees_en_sortie())) {
					$format_sortie = $fmt;
					break;
				}
			}
			$image = false;
		}
	}

	if (!is_array($image)) {
		$image = _image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}", $format_sortie, $fonction, false, _SVG_SUPPORTED);
	}

	if (!is_array($image) or !$image['largeur'] or !$image['hauteur']) {
		spip_log("image_reduire_src:pas de version locale de $img");
		// on peut resizer en mode html si on dispose des elements
		if (
			$srcw = extraire_attribut($img, 'width')
			and $srch = extraire_attribut($img, 'height')
		) {
			list($w, $h) = _image_ratio($srcw, $srch, $taille, $taille_y);

			return _image_tag_changer_taille($img, $w, $h);
		}
		// la on n'a pas d'infos sur l'image source... on refile le truc a css
		// sous la forme style='max-width: NNpx;'
		return inserer_attribut(
			$img,
			'style',
			"max-width: ${taille}px; max-height: ${taille_y}px"
		);
	}

	// si l'image est plus petite que la cible retourner une copie cachee de l'image
	if (($image['largeur'] <= $taille) && ($image['hauteur'] <= $taille_y)) {
		if ($image['creer']) {
			@copy($image['fichier'], $image['fichier_dest']);
		}

		return _image_ecrire_tag($image, ['src' => $image['fichier_dest']]);
	}

	if ($image['creer'] == false && !$force) {
		return _image_ecrire_tag(
			$image,
			['src' => $image['fichier_dest'], 'width' => $image['largeur_dest'], 'height' => $image['hauteur_dest']]
		);
	}

	if (in_array($image['format_source'], _image_extensions_acceptees_en_entree())) {
		$destWidth = $image['largeur_dest'];
		$destHeight = $image['hauteur_dest'];
		$logo = $image['fichier'];
		$date = $image['date_src'];
		$preview = _image_creer_vignette($image, $taille, $taille_y, $process, $force);

		if ($preview && $preview['fichier']) {
			$logo = $preview['fichier'];
			$destWidth = $preview['width'];
			$destHeight = $preview['height'];
			$date = $preview['date'];
		}
		// dans l'espace prive mettre un timestamp sur l'adresse
		// de l'image, de facon a tromper le cache du navigateur
		// quand on fait supprimer/reuploader un logo
		// (pas de filemtime si SAFE MODE)
		$date = test_espace_prive() ? ('?' . $date) : '';

		return _image_ecrire_tag($image, ['src' => "$logo$date", 'width' => $destWidth, 'height' => $destHeight]);
	}
	else {
		# BMP, tiff ... les redacteurs osent tout!
		return $img;
	}
}

/**
 * Produire des fichiers au format .ico
 *
 * Avec du code récupéré de phpThumb()
 *
 * @author James Heinrich <info@silisoftware.com>
 * @link http://phpthumb.sourceforge.net
 *
 * Class phpthumb_functions
 */
class phpthumb_functions {

	/**
	 * Retourne la couleur d'un pixel dans une image
	 *
	 * @param ressource $img
	 * @param int $x
	 * @param int $y
	 * @return array|bool
	 */
	public static function GetPixelColor(&$img, $x, $y) {
		if (is_resource($img) || (is_object($img) && $img instanceof \GdImage)) {
			return @ImageColorsForIndex($img, @ImageColorAt($img, $x, $y));
		}
		return false;
	}

	/**
	 * Retourne un nombre dans une représentation en Little Endian
	 *
	 * @param int $number
	 * @param int $minbytes
	 * @return string
	 */
	public static function LittleEndian2String($number, $minbytes = 1) {
		$intstring = '';
		while ($number > 0) {
			$intstring = $intstring . chr($number & 255);
			$number >>= 8;
		}

		return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
	}

	/**
	 * Transforme une ressource GD en image au format ICO
	 *
	 * @param array $gd_image_array
	 *     Tableau de ressources d'images GD
	 * @return string
	 *     Image au format ICO
	 */
	public static function GD2ICOstring(&$gd_image_array) {
		foreach ($gd_image_array as $key => $gd_image) {
			$ImageWidths[$key] = ImageSX($gd_image);
			$ImageHeights[$key] = ImageSY($gd_image);
			$bpp[$key] = ImageIsTrueColor($gd_image) ? 32 : 24;
			$totalcolors[$key] = ImageColorsTotal($gd_image);

			$icXOR[$key] = '';
			for ($y = $ImageHeights[$key] - 1; $y >= 0; $y--) {
				for ($x = 0; $x < $ImageWidths[$key]; $x++) {
					$argb = phpthumb_functions::GetPixelColor($gd_image, $x, $y);
					$a = round(255 * ((127 - $argb['alpha']) / 127));
					$r = $argb['red'];
					$g = $argb['green'];
					$b = $argb['blue'];

					if ($bpp[$key] == 32) {
						$icXOR[$key] .= chr($b) . chr($g) . chr($r) . chr($a);
					} elseif ($bpp[$key] == 24) {
						$icXOR[$key] .= chr($b) . chr($g) . chr($r);
					}

					if ($a < 128) {
						@$icANDmask[$key][$y] .= '1';
					} else {
						@$icANDmask[$key][$y] .= '0';
					}
				}
				// mask bits are 32-bit aligned per scanline
				while (strlen($icANDmask[$key][$y]) % 32) {
					$icANDmask[$key][$y] .= '0';
				}
			}
			$icAND[$key] = '';
			foreach ($icANDmask[$key] as $y => $scanlinemaskbits) {
				for ($i = 0; $i < strlen($scanlinemaskbits); $i += 8) {
					$icAND[$key] .= chr(bindec(str_pad(substr($scanlinemaskbits, $i, 8), 8, '0', STR_PAD_LEFT)));
				}
			}
		}

		foreach ($gd_image_array as $key => $gd_image) {
			$biSizeImage = $ImageWidths[$key] * $ImageHeights[$key] * ($bpp[$key] / 8);

			// BITMAPINFOHEADER - 40 bytes
			$BitmapInfoHeader[$key] = '';
			$BitmapInfoHeader[$key] .= "\x28\x00\x00\x00";                // DWORD  biSize;
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($ImageWidths[$key], 4);    // LONG   biWidth;
			// The biHeight member specifies the combined
			// height of the XOR and AND masks.
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($ImageHeights[$key] * 2, 4); // LONG   biHeight;
			$BitmapInfoHeader[$key] .= "\x01\x00";                    // WORD   biPlanes;
			$BitmapInfoHeader[$key] .= chr($bpp[$key]) . "\x00";              // wBitCount;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                // DWORD  biCompression;
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($biSizeImage, 4);      // DWORD  biSizeImage;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                // LONG   biXPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                // LONG   biYPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                // DWORD  biClrUsed;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                // DWORD  biClrImportant;
		}


		$icondata = "\x00\x00";                    // idReserved;   // Reserved (must be 0)
		$icondata .= "\x01\x00";                    // idType;	   // Resource Type (1 for icons)
		$icondata .= phpthumb_functions::LittleEndian2String(count($gd_image_array), 2);  // idCount;	  // How many images?

		$dwImageOffset = 6 + (count($gd_image_array) * 16);
		foreach ($gd_image_array as $key => $gd_image) {
			// ICONDIRENTRY   idEntries[1]; // An entry for each image (idCount of 'em)

			$icondata .= chr($ImageWidths[$key]);           // bWidth;		  // Width, in pixels, of the image
			$icondata .= chr($ImageHeights[$key]);          // bHeight;		 // Height, in pixels, of the image
			$icondata .= chr($totalcolors[$key]);           // bColorCount;	 // Number of colors in image (0 if >=8bpp)
			$icondata .= "\x00";                    // bReserved;	   // Reserved ( must be 0)

			$icondata .= "\x01\x00";                  // wPlanes;		 // Color Planes
			$icondata .= chr($bpp[$key]) . "\x00";            // wBitCount;	   // Bits per pixel

			$dwBytesInRes = 40 + strlen($icXOR[$key]) + strlen($icAND[$key]);
			$icondata .= phpthumb_functions::LittleEndian2String(
				$dwBytesInRes,
				4
			);     // dwBytesInRes;	// How many bytes in this resource?

			$icondata .= phpthumb_functions::LittleEndian2String(
				$dwImageOffset,
				4
			);    // dwImageOffset;   // Where in the file is this image?
			$dwImageOffset += strlen($BitmapInfoHeader[$key]);
			$dwImageOffset += strlen($icXOR[$key]);
			$dwImageOffset += strlen($icAND[$key]);
		}

		foreach ($gd_image_array as $key => $gd_image) {
			$icondata .= $BitmapInfoHeader[$key];
			$icondata .= $icXOR[$key];
			$icondata .= $icAND[$key];
		}

		return $icondata;
	}
}
