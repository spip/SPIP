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
 * Ce fichier contient les fonctions simples
 * de traitement d'image.
 *
 * @package SPIP\Core\Filtres\Images
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/filtres_images_lib_mini'); // par precaution

/**
 * Transforme un code couleur textuel (black, white, green...) et code hexadécimal
 *
 * @param string $couleur
 *    Le code couleur textuel
 * @return string
 *    Le code hexadécimal de la couleur (sans le #) ou le code couleur textuel si non trouvé
 */
function couleur_html_to_hex($couleur) {
	$couleurs_html = array(
		'aliceblue' => 'F0F8FF',
		'antiquewhite' => 'FAEBD7',
		'aqua' => '00FFFF',
		'aquamarine' => '7FFFD4',
		'azure' => 'F0FFFF',
		'beige' => 'F5F5DC',
		'bisque' => 'FFE4C4',
		'black' => '000000',
		'blanchedalmond' => 'FFEBCD',
		'blue' => '0000FF',
		'blueviolet' => '8A2BE2',
		'brown' => 'A52A2A',
		'burlywood' => 'DEB887',
		'cadetblue' => '5F9EA0',
		'chartreuse' => '7FFF00',
		'chocolate' => 'D2691E',
		'coral' => 'FF7F50',
		'cornflowerblue' => '6495ED',
		'cornsilk' => 'FFF8DC',
		'crimson' => 'DC143C',
		'cyan' => '00FFFF',
		'darkblue' => '00008B',
		'darkcyan' => '008B8B',
		'darkgoldenrod' => 'B8860B',
		'darkgray' => 'A9A9A9',
		'darkgreen' => '006400',
		'darkgrey' => 'A9A9A9',
		'darkkhaki' => 'BDB76B',
		'darkmagenta' => '8B008B',
		'darkolivegreen' => '556B2F',
		'darkorange' => 'FF8C00',
		'darkorchid' => '9932CC',
		'darkred' => '8B0000',
		'darksalmon' => 'E9967A',
		'darkseagreen' => '8FBC8F',
		'darkslateblue' => '483D8B',
		'darkslategray' => '2F4F4F',
		'darkslategrey' => '2F4F4F',
		'darkturquoise' => '00CED1',
		'darkviolet' => '9400D3',
		'deeppink' => 'FF1493',
		'deepskyblue' => '00BFFF',
		'dimgray' => '696969',
		'dimgrey' => '696969',
		'dodgerblue' => '1E90FF',
		'firebrick' => 'B22222',
		'floralwhite' => 'FFFAF0',
		'forestgreen' => '228B22',
		'fuchsia' => 'FF00FF',
		'gainsboro' => 'DCDCDC',
		'ghostwhite' => 'F8F8FF',
		'gold' => 'FFD700',
		'goldenrod' => 'DAA520',
		'gray' => '808080',
		'green' => '008000',
		'greenyellow' => 'ADFF2F',
		'grey' => '808080',
		'honeydew' => 'F0FFF0',
		'hotpink' => 'FF69B4',
		'indianred' => 'CD5C5C',
		'indigo' => '4B0082',
		'ivory' => 'FFFFF0',
		'khaki' => 'F0E68C',
		'lavender' => 'E6E6FA',
		'lavenderblush' => 'FFF0F5',
		'lawngreen' => '7CFC00',
		'lemonchiffon' => 'FFFACD',
		'lightblue' => 'ADD8E6',
		'lightcoral' => 'F08080',
		'lightcyan' => 'E0FFFF',
		'lightgoldenrodyellow' => 'FAFAD2',
		'lightgray' => 'D3D3D3',
		'lightgreen' => '90EE90',
		'lightgrey' => 'D3D3D3',
		'lightpink' => 'FFB6C1',
		'lightsalmon' => 'FFA07A',
		'lightseagreen' => '20B2AA',
		'lightskyblue' => '87CEFA',
		'lightslategray' => '778899',
		'lightslategrey' => '778899',
		'lightsteelblue' => 'B0C4DE',
		'lightyellow' => 'FFFFE0',
		'lime' => '00FF00',
		'limegreen' => '32CD32',
		'linen' => 'FAF0E6',
		'magenta' => 'FF00FF',
		'maroon' => '800000',
		'mediumaquamarine' => '66CDAA',
		'mediumblue' => '0000CD',
		'mediumorchid' => 'BA55D3',
		'mediumpurple' => '9370DB',
		'mediumseagreen' => '3CB371',
		'mediumslateblue' => '7B68EE',
		'mediumspringgreen' => '00FA9A',
		'mediumturquoise' => '48D1CC',
		'mediumvioletred' => 'C71585',
		'midnightblue' => '191970',
		'mintcream' => 'F5FFFA',
		'mistyrose' => 'FFE4E1',
		'moccasin' => 'FFE4B5',
		'navajowhite' => 'FFDEAD',
		'navy' => '000080',
		'oldlace' => 'FDF5E6',
		'olive' => '808000',
		'olivedrab' => '6B8E23',
		'orange' => 'FFA500',
		'orangered' => 'FF4500',
		'orchid' => 'DA70D6',
		'palegoldenrod' => 'EEE8AA',
		'palegreen' => '98FB98',
		'paleturquoise' => 'AFEEEE',
		'palevioletred' => 'DB7093',
		'papayawhip' => 'FFEFD5',
		'peachpuff' => 'FFDAB9',
		'peru' => 'CD853F',
		'pink' => 'FFC0CB',
		'plum' => 'DDA0DD',
		'powderblue' => 'B0E0E6',
		'purple' => '800080',
		'rebeccapurple' => '663399',
		'red' => 'FF0000',
		'rosybrown' => 'BC8F8F',
		'royalblue' => '4169E1',
		'saddlebrown' => '8B4513',
		'salmon' => 'FA8072',
		'sandybrown' => 'F4A460',
		'seagreen' => '2E8B57',
		'seashell' => 'FFF5EE',
		'sienna' => 'A0522D',
		'silver' => 'C0C0C0',
		'skyblue' => '87CEEB',
		'slateblue' => '6A5ACD',
		'slategray' => '708090',
		'slategrey' => '708090',
		'snow' => 'FFFAFA',
		'springgreen' => '00FF7F',
		'steelblue' => '4682B4',
		'tan' => 'D2B48C',
		'teal' => '008080',
		'thistle' => 'D8BFD8',
		'tomato' => 'FF6347',
		'turquoise' => '40E0D0',
		'violet' => 'EE82EE',
		'wheat' => 'F5DEB3',
		'white' => 'FFFFFF',
		'whitesmoke' => 'F5F5F5',
		'yellow' => 'FFFF00',
		'yellowgreen' => '9ACD32',
	);
	if (isset($couleurs_html[$lc = strtolower($couleur)])) {
		return $couleurs_html[$lc];
	}

	return $couleur;
}

/**
 * Retourne une couleur hsl a partir d'une couleur hex
 *
 * @example couleur_hex_to_hsl("#ffccaa");
 * @example couleur_hex_to_hsl("#ffccaa", "h");
 * @example couleur_hex_to_hsl("#ffccaa", "h, s, l");
 * 
 * @filtre
 * @param string $couleur
 * @param string|null $format Si précisé, remplace h, s ou l par sa valeur dans la chaine passée.
 * @return string Couleur tel que "hsl(200, 40%, 34%)" ou valeur formattée
 */
function couleur_hex_to_hsl($couleur, $format = null) {
	$hsl = _couleur_hex_to_hsl($couleur);
	$hsl = [
		'h' => round($hsl['h'] * 360), 
		's' => round($hsl['s'] * 100) . '%', 
		'l' => round($hsl['l'] * 100) . '%'
	];
	if ($format === null) {
		return "hsl({$hsl['h']}, {$hsl['s']}, {$hsl['l']})";
	}
	return str_replace(array_keys($hsl), $hsl, $format);
}


/**
 * Retourne une couleur rgb a partir d'une couleur hex
 *
 * @example couleur_hex_to_rgb("#ffccaa");
 * @example couleur_hex_to_rgb("#ffccaa", "r");
 * @example couleur_hex_to_rgb("#ffccaa", "r, g, b");
 * 
 * @filtre
 * @param string $couleur
 * @param string|null $format Si précisé, remplace r, g ou b par sa valeur dans la chaine passée.
 * @return string Couleur tel que "rgb(200, 40, 84)" ou valeur formattée
 */
function couleur_hex_to_rgb($couleur, $format = null) {
	$rgb = _couleur_hex_to_dec($couleur);
	$rgb = [
		'r' => $rgb['red'], 
		'g' => $rgb['green'], 
		'b' => $rgb['blue'],
	];
	if ($format === null) {
		return "rgb({$rgb['r']}, {$rgb['g']}, {$rgb['b']})";
	}
	return str_replace(array_keys($rgb), $rgb, $format);
}

/**
 * Rend une couleur (code hexadécimal) plus foncée
 *
 * @uses _couleur_hex_to_dec() Pour transformer le code hexadécimal en décimal
 *
 * @param string $couleur
 *    Code hexadécimal d'une couleur
 * @param float $coeff
 *    Coefficient (de 0 à 1)
 * @return string
 *    Code hexadécimal de la couleur plus foncée
 */
function couleur_foncer($couleur, $coeff = 0.5) {
	$couleurs = _couleur_hex_to_dec($couleur);

	$red = $couleurs['red'] - round(($couleurs['red']) * $coeff);
	$green = $couleurs['green'] - round(($couleurs['green']) * $coeff);
	$blue = $couleurs['blue'] - round(($couleurs['blue']) * $coeff);

	$couleur = _couleur_dec_to_hex($red, $green, $blue);

	return $couleur;
}

/**
 * Eclaircit une couleur (code hexadécimal)
 *
 * @uses _couleur_hex_to_dec() Pour transformer le code hexadécimal en décimal
 *
 * @param string $couleur
 *    Code hexadécimal d'une couleur
 * @param float $coeff
 *    Coefficient (de 0 à 1)
 * @return string
 *    Code hexadécimal de la couleur éclaircie
 */
function couleur_eclaircir($couleur, $coeff = 0.5) {
	$couleurs = _couleur_hex_to_dec($couleur);

	$red = $couleurs['red'] + round((255 - $couleurs['red']) * $coeff);
	$green = $couleurs['green'] + round((255 - $couleurs['green']) * $coeff);
	$blue = $couleurs['blue'] + round((255 - $couleurs['blue']) * $coeff);

	$couleur = _couleur_dec_to_hex($red, $green, $blue);

	return $couleur;
}

/**
 * Selectionne les images qui vont subir une transformation sur un critere de taille
 *
 * Les images exclues sont marquees d'une class filtre_inactif qui bloque les filtres suivants
 * dans la fonction image_filtrer
 *
 * @param string $img
 *    Un tag html `<img src=... />`.
 * @param int $width_min
 *    Largeur minimale de l'image à traiter (0 par défaut)
 * @param int $height_min
 *    Hauteur minimale de l'image à traiter (0 par défaut)
 * @param int $width_max
 *    Largeur minimale de l'image à traiter (10000 par défaut)
 * @param int $height_max
 *    Hauteur minimale de l'image à traiter (10000 par défaut)
 * @return
 *    Le tag html `<img src=... />` avec une class `filtre_inactif` ou pas
 */
function image_select($img, $width_min = 0, $height_min = 0, $width_max = 10000, $height_max = 1000) {
	if (!$img) {
		return $img;
	}
	list($h, $l) = taille_image($img);
	$select = true;
	if ($l < $width_min or $l > $width_max or $h < $height_min or $h > $height_max) {
		$select = false;
	}

	$class = extraire_attribut($img, 'class');
	$p = strpos($class, 'filtre_inactif');
	if (($select == false) and ($p === false)) {
		$class .= ' filtre_inactif';
		$img = inserer_attribut($img, 'class', $class);
	}
	if (($select == true) and ($p !== false)) {
		// no_image_filtrer : historique, a virer
		$class = preg_replace(',\s*(filtre_inactif|no_image_filtrer),', '', $class);
		$img = inserer_attribut($img, 'class', $class);
	}

	return $img;
}

/**
 * Réduit les images à une taille maximale (chevauchant un rectangle)
 *
 * L'image possède un côté réduit dans les dimensions indiquées et
 * l'autre côté (hauteur ou largeur) de l'image peut être plus grand
 * que les dimensions du rectangle.
 *
 * Alors que image_reduire produit la plus petite image tenant dans un
 * rectangle, image_passe_partout produit la plus grande image qui
 * remplit ce rectangle.
 *
 * @example
 *     ```
 *     [(#FICHIER
 *       |image_passe_partout{70,70}
 *       |image_recadre{70,70,center})]
 *     ```
 *
 * @filtre
 * @link https://www.spip.net/4562
 * @see  image_reduire()
 * @uses taille_image()
 * @uses ratio_passe_partout()
 * @uses process_image_reduire()
 *
 * @param string $img
 *     Chemin de l'image ou code html d'une balise img
 * @param int $taille_x
 *     - Largeur maximale en pixels désirée
 *     - -1 prend la taille de réduction des vignettes par défaut
 *     - 0 la taille s'adapte à la largeur
 * @param int $taille_y
 *     - Hauteur maximale en pixels désirée
 *     - -1 pour prendre pareil que la largeur
 *     - 0 la taille s'adapte à la hauteur
 * @param bool $force
 * @param bool $cherche_image
 *     Inutilisé
 * @param string $process
 *     Librairie graphique à utiliser (gd1, gd2, netpbm, convert, imagick).
 *     AUTO utilise la librairie sélectionnée dans la configuration.
 * @return string
 *     Code HTML de l'image ou du texte.
 **/
function image_passe_partout(
	$img,
	$taille_x = -1,
	$taille_y = -1,
	$force = false,
	$cherche_image = false,
	$process = 'AUTO'
) {
	// PHP 7+ type hint
	$img = (string)$img;
	$taille_x = (int)$taille_x;
	$taille_y = (int)$taille_y;
	$force = (bool)$force;
	$process = (string)$process;

	if (!$img) {
		return '';
	}
	list($hauteur, $largeur) = taille_image($img);
	if ($taille_x === -1) {
		$taille_x = isset($GLOBALS['meta']['taille_preview']) ? $GLOBALS['meta']['taille_preview'] : 150;
	}
	if ($taille_y === -1) {
		$taille_y = $taille_x;
	}

	if ($taille_x === 0 and $taille_y > 0) {
		$taille_x = 1;
	} # {0,300} -> c'est 300 qui compte
	elseif ($taille_x > 0 and $taille_y === 0) {
		$taille_y = 1;
	} # {300,0} -> c'est 300 qui compte
	elseif ($taille_x == 0 and $taille_y === 0) {
		return '';
	}

	list($destWidth, $destHeight, $ratio) = ratio_passe_partout($largeur, $hauteur, $taille_x, $taille_y);
	$fonction = array('image_passe_partout', func_get_args());

	return process_image_reduire($fonction, $img, $destWidth, $destHeight, $force, $process);
}

/**
 * Réduit les images à une taille maximale (inscrite dans un rectangle)
 *
 * L'image possède un côté dans les dimensions indiquées et
 * l'autre côté (hauteur ou largeur) de l'image peut être plus petit
 * que les dimensions du rectangle.
 *
 * Peut être utilisé pour réduire toutes les images d'un texte.
 *
 * @example
 *     ```
 *     [(#LOGO_ARTICLE|image_reduire{130})]
 *     [(#TEXTE|image_reduire{600,0})]
 *     ```
 *
 * @filtre
 * @see  image_reduire_par()
 * @see  image_passe_partout()
 * @uses process_image_reduire()
 *
 * @param string $img
 *     Chemin de l'image ou code html d'une balise img
 * @param int $taille
 *     - Largeur maximale en pixels désirée
 *     - -1 prend la taille de réduction des vignettes par défaut
 *     - 0 la taille s'adapte à la largeur
 * @param int $taille_y
 *     - Hauteur maximale en pixels désirée
 *     - -1 pour prendre pareil que la largeur
 *     - 0 la taille s'adapte à la hauteur
 * @param bool $force
 * @param bool $cherche_image
 *     Inutilisé
 * @param string $process
 *     Librairie graphique à utiliser (gd1, gd2, netpbm, convert, imagick).
 *     AUTO utilise la librairie sélectionnée dans la configuration.
 * @return string
 *     Code HTML de l'image ou du texte.
 **/
function image_reduire(
	$img,
	$taille = -1,
	$taille_y = -1,
	$force = false,
	$cherche_image = false,
	$process = 'AUTO'
) {
	// PHP 7+ type hint
	$img = (string)$img;
	$taille = (int)$taille;
	$taille_y = (int)$taille_y;
	$force = (bool)$force;
	$process = (string)$process;

	// Determiner la taille x,y maxi
	// prendre le reglage de previsu par defaut
	if ($taille === -1) {
		$taille = (isset($GLOBALS['meta']['taille_preview']) and intval($GLOBALS['meta']['taille_preview']))
			? intval($GLOBALS['meta']['taille_preview'])
			: 150;
	}
	if ($taille_y === -1) {
		$taille_y = $taille;
	}

	if ($taille === 0 and $taille_y > 0) {
		$taille = 10000;
	} # {0,300} -> c'est 300 qui compte
	elseif ($taille > 0 and $taille_y === 0) {
		$taille_y = 10000;
	} # {300,0} -> c'est 300 qui compte
	elseif ($taille == 0 and $taille_y === 0) {
		return '';
	}

	$fonction = array('image_reduire', func_get_args());

	return process_image_reduire($fonction, $img, $taille, $taille_y, $force, $process);
}

/**
 * 
 * Usage espace privé:
 * recadre une image uniquement si GD2 et image_recadre (plugin activé) existe
 * sinon retourne le image_passe_partout usuel
 * 
 **/

function image_recadre_avec_fallback (
	$im, 
	$width, 
	$height='-', 
	$position = 'focus', 
	$background_color = 'white') {

	if (function_exists("image_recadre") && lire_meta("image_process")=="gd2")
		return image_reduire(image_recadre($im, $width.":".$height, "-", $position, $background_color), $width, $height);
	else 
		return image_passe_partout($im, $width, $height);

}


/**
 * Réduit les images d'un certain facteur
 *
 * @filtre
 * @uses image_reduire()
 *
 * @param string $img
 *     Chemin de l'image ou code html d'une balise img
 * @param int $val
 *     Facteur de réduction
 * @param bool $force
 * @return string
 *     Code HTML de l'image ou du texte.
 **/
function image_reduire_par($img, $val = 1, $force = false) {
	// PHP 7+ type hint
	$img = (string)$img;
	$val = (int)$val;
	$force = (bool)$force;

	list($hauteur, $largeur) = taille_image($img);

	$l = round($largeur / $val);
	$h = round($hauteur / $val);

	if ($l > $h) {
		$h = 0;
	} else {
		$l = 0;
	}

	$img = image_reduire($img, $l, $h, $force);

	return $img;
}

/**
 * Modifie la saturation de la couleur transmise
 *
 * @note
 *     Nécessite le plugin `filtres_images` pour fonctionner.
 *     La couleur d’entrée est retournée tel quelle en cas d'absence.
 * 
 * @see couleur_saturation() du plugin `filtres_images`
 * @uses couleur_saturation()
 * 
 * @param string $couleur
 *      Couleur en écriture hexadécimale, tel que `ff3300`
 * @param float $val
 *      Pourcentage désiré (entre 0 et 1)
 * @param bool|string $strict
 *      Si true, ne change vraiment que la saturation, sans toucher à la luminosité
 * @return string
 *      Couleur en écriture hexadécimale.
**/
function filtre_couleur_saturation_dist($couleur, $val, $strict = false) {
	if (function_exists('couleur_saturation')) {
		return couleur_saturation($couleur, $val, $strict);
	}
	return $couleur;
}

/**
 * Modifie la luminance de la couleur transmise
 *
 * @note
 *     Nécessite le plugin `filtres_images` pour fonctionner.
 *     La couleur d’entrée est retournée tel quelle en cas d'absence.
 * 
 * @see couleur_luminance() du plugin `filtres_images`
 * @uses couleur_luminance()
 * 
 * @param string $couleur
 *      Couleur en écriture hexadécimale, tel que `ff3300`
 * @param float $val
 *      Pourcentage désiré (entre 0 et 1)
 * @return string
 *      Couleur en écriture hexadécimale.
**/
function filtre_couleur_luminance_dist($couleur, $val) {
	if (function_exists('couleur_luminance')) {
		return couleur_luminance($couleur, $val);
	}
	return $couleur;
}
