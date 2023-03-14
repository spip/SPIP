<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Outils pour lecture/manipulation simple de SVG
 *
 * @package SPIP\Core\SVG
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('IMG_SVG')) {
	// complete 	IMG_BMP | IMG_GIF | IMG_JPG | IMG_PNG | IMG_WBMP | IMG_XPM | IMG_WEBP
	define('IMG_SVG', 128);
	define('IMAGETYPE_SVG', 19);
}

/**
 * Charger une image SVG a partir d'une source qui peut etre
 * - l'image svg deja chargee
 * - une data-url
 * - un nom de fichier
 *
 * @param string $fichier
 * @param null|int $maxlen
 *   pour limiter la taille chargee en memoire si on lit depuis le disque et qu'on a besoin que du debut du fichier
 * @return bool|string
 *   false si on a pas pu charger l'image
 */
function svg_charger($fichier, $maxlen = null) {
	if (str_starts_with($fichier, 'data:image/svg+xml')) {
		$image = explode(';', $fichier, 2);
		$image = end($image);
		if (str_starts_with($image, 'base64,')) {
			$image = base64_decode(substr($image, 7));
		}
		if (str_contains($image, '<svg')) {
			return $image;
		}
		// encodage inconnu ou autre format d'image ?
		return false;
	}
	// c'est peut etre deja une image svg ?
	if (str_contains($fichier, '<svg')) {
		return $fichier;
	}
	if (!file_exists($fichier)) {
		$fichier  = supprimer_timestamp($fichier);
		if (!file_exists($fichier)) {
			return false;
		}
	}
	$image = is_null($maxlen) ? file_get_contents($fichier) : file_get_contents($fichier, false, null, 0, $maxlen);
	// est-ce bien une image svg ?
	if (str_contains($image, '<svg')) {
		return $image;
	}
	return false;
}

/**
 * Lire la balise <svg...> qui demarre le fichier et la parser pour renvoyer un tableau de ses attributs
 * @param string $fichier
 * @return array|bool
 */
function svg_lire_balise_svg($fichier) {
	if (!$debut_fichier = svg_charger($fichier, 4096)) {
		return false;
	}

	if (($ps = stripos($debut_fichier, '<svg')) !== false) {
		$pe = stripos($debut_fichier, '>', $ps);
		$balise_svg = substr($debut_fichier, $ps, $pe - $ps + 1);

		if (preg_match_all(',([\w:\-]+)=,Uims', $balise_svg, $matches)) {
			if (!function_exists('extraire_attribut')) {
				include_spip('inc/filtres');
			}
			$attributs = [];
			foreach ($matches[1] as $att) {
				$attributs[$att] = extraire_attribut($balise_svg, $att);
			}

			return [$balise_svg, $attributs];
		}
	}

	return false;
}

/**
 * Attributs de la balise SVG
 * @param string $img
 * @return array|bool
 */
function svg_lire_attributs($img) {

	if ($svg_infos = svg_lire_balise_svg($img)) {
		[$balise_svg, $attributs] = $svg_infos;
		return $attributs;
	}

	return false;
}

/**
 * Convertir l'attribut widht/height d'un SVG en pixels
 * (approximatif eventuellement, du moment qu'on respecte le ratio)
 * @param $dimension
 * @return bool|float|int
 */
function svg_dimension_to_pixels($dimension, $precision = 2) {
	if (preg_match(',^(-?\d+(\.\d+)?)([^\d]*),i', trim($dimension), $m)) {
		return match (strtolower($m[2])) {
			'%' => false,
			'em' => round($m[1] * 16, $precision),
			'ex' => round($m[1] * 16, $precision),
			'pc' => round($m[1] * 16, $precision),
			'cm' => round($m[1] * 96 / 2.54, $precision),
			'mm' => round($m[1] * 96 / 25.4, $precision),
			'in' => round($m[1] * 96, $precision),
			default => $m[1],
  		};
	}
	return false;
}

/**
 * Modifier la balise SVG en entete du source
 * @param string $svg
 * @param string $old_balise_svg
 * @param array $attributs
 * @return string
 */
function svg_change_balise_svg($svg, $old_balise_svg, $attributs) {
	$new_balise_svg = '<svg';
	foreach ($attributs as $k => $v) {
		$new_balise_svg .= " $k=\"" . entites_html($v) . '"';
	}
	$new_balise_svg .= '>';

	$p = strpos($svg, $old_balise_svg);
	return substr_replace($svg, $new_balise_svg, $p, strlen($old_balise_svg));
}

/**
 * @param string $svg
 * @param string $shapes
 * @param bool|string $start
 *   inserer au debut (true) ou a la fin (false)
 * @return string
 */
function svg_insert_shapes($svg, $shapes, $start = true) {

	if ($start === false || $start === 'end') {
		$svg = str_replace('</svg>', $shapes . '</svg>', $svg);
	}
	else {
		$p = stripos($svg, '<svg');
		$p = strpos($svg, '>', $p);
		$svg = substr_replace($svg, $shapes, $p + 1, 0);
	}
	return $svg;
}

/**
 * Clipper le SVG dans une box
 * @param string $svg
 * @param int $x
 * @param int $y
 * @param int $width
 * @param int $height
 * @return string
 */
function svg_clip_in_box($svg, $x, $y, $width, $height) {
	$rect = "<rect x=\"$x\" y=\"$y\" width=\"$width\" height=\"$height\" />";
	$id = 'clip-' . substr(md5($rect . strlen($svg)), 0, 8);
	$clippath = "<clipPath id=\"$id\">$rect</clipPath>";
	$g = "<g clip-path=\"url(#$id)\">";
	$svg = svg_insert_shapes($svg, $clippath . $g);
	return svg_insert_shapes($svg, '</g>', false);
}

/**
 * Redimensionner le SVG via le width/height de la balise
 * @param string $img
 * @param $new_width
 * @param $new_height
 * @return bool|string
 */
function svg_redimensionner($img, $new_width, $new_height) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		[$balise_svg, $attributs] = $svg_infos;
		if (!isset($attributs['viewBox'])) {
			$attributs['viewBox'] = '0 0 ' . $attributs['width'] . ' ' . $attributs['height'];
		}
		$attributs['width'] = (string) $new_width;
		$attributs['height'] = (string) $new_height;
		return svg_change_balise_svg($svg, $balise_svg, $attributs);
	}

	return $img;
}

/**
 * Transformer une couleur extraite du SVG en hexa
 * @param string $couleur
 * @return string
 */
function svg_couleur_to_hexa($couleur) {
	if (str_starts_with($couleur, 'rgb(')) {
		$c = explode(',', substr($couleur, 4));
		$couleur = _couleur_dec_to_hex((int) $c[0], (int) $c[1], (int) $c[2]);
	}
	else {
		$couleur = couleur_html_to_hex($couleur);
	}
	return '#' . ltrim($couleur, '#');
}

/**
 * Transformer une couleur extraite du SVG en rgb
 * @param string $couleur
 * @return array
 */
function svg_couleur_to_rgb($couleur) {
	if (str_starts_with($couleur, 'rgb(')) {
		$c = explode(',', substr($couleur, 4));
		return ['red' => (int) $c[0],'green' => (int) $c[1],'blue' => (int) $c[2]];
	}
	return _couleur_hex_to_dec($couleur);
}


/**
 * Calculer les dimensions width/heigt/viewBox du SVG d'apres les attributs de la balise <svg>
 * @param array $attributs
 * @return array
 */
function svg_getimagesize_from_attr($attributs) {
	$width = 350; // default width
	$height = 150; // default height

	$viewBox = "0 0 $width $height";
	if (isset($attributs['viewBox'])) {
		$viewBox = $attributs['viewBox'];
		$viewBox = preg_replace(',\s+,', ' ', $viewBox);
	}
	// et on la convertit en px
	$viewBox = explode(' ', $viewBox);
	$viewBox = array_map('svg_dimension_to_pixels', $viewBox);
	if (!$viewBox[2]) {
		$viewBox[2] = $width;
	}
	if (!$viewBox[3]) {
		$viewBox[3] = $height;
	}

	$coeff = 1;
	if (
		isset($attributs['width'])
		&& ($w = svg_dimension_to_pixels($attributs['width']))
	) {
		$width = $w;
		// si on avait pas de viewBox, la construire a partir de ce width
		if (empty($attributs['viewBox'])) {
			$viewBox[2] = $width;
			// si pas de height valide, on suppose l'image carree
			$viewBox[3] = $width;
		}
	}
	else {
		// si on recupere la taille de la viewbox mais si la viewbox est petite on met un multiplicateur pour la taille finale
		$width = $viewBox[2];
		if ($width < 1) {
			$coeff = max($coeff, 1000);
		}
		elseif ($width < 10) {
			$coeff = max($coeff, 100);
		}
		elseif ($width < 100) {
			$coeff = max($coeff, 10);
		}
	}
	if (
		isset($attributs['height'])
		&& ($h = svg_dimension_to_pixels($attributs['height']))
	) {
		$height = $h;
		// si on avait pas de viewBox, la construire a partir de ce height
		if (empty($attributs['viewBox'])) {
			$viewBox[3] = $height;
		}
	}
	else {
		$height = $viewBox[3];
		if ($height < 1) {
			$coeff = max($coeff, 1000);
		}
		elseif ($height < 10) {
			$coeff = max($coeff, 100);
		}
		elseif ($height < 100) {
			$coeff = max($coeff, 10);
		}
	}

	// arrondir le width et height en pixel in fine
	$width = round($coeff * $width);
	$height = round($coeff * $height);

	$viewBox = implode(' ', $viewBox);

	return [$width, $height, $viewBox];
}

/**
 * Forcer la viewBox du SVG, en px
 * cree l'attribut viewBox si il n'y en a pas
 * convertit les unites en px si besoin
 *
 * Les manipulations d'image par les filtres images se font en px, on a donc besoin d'une viewBox en px
 * Certains svg produits avec des unites exotiques subiront donc peut etre des deformations...
 *
 * @param string $img
 * @param bool $force_width_and_height
 * @return string
 */
function svg_force_viewBox_px($img, $force_width_and_height = false) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		[$balise_svg, $attributs] = $svg_infos;

		[$width, $height, $viewBox] = svg_getimagesize_from_attr($attributs);

		if ($force_width_and_height) {
			$attributs['width'] = $width;
			$attributs['height'] = $height;
		}

		$attributs['viewBox'] = $viewBox;
		return svg_change_balise_svg($svg, $balise_svg, $attributs);
	}
	return $img;
}

/**
 * Extract all colors in SVG
 * @param $img
 * @return array|mixed
 */
function svg_extract_couleurs($img) {
	if (
		($svg = svg_charger($img))
		&& preg_match_all('/(#[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f])|(rgb\([\s\d]+,[\s\d]+,[\s\d]+\))|(#[0-9a-f][0-9a-f][0-9a-f])/imS', $svg, $matches)
	) {
		return $matches[0];
	}
	return [];
}

/**
 * Redimensionner le SVG via le width/height de la balise
 * @param string $img
 * @param $new_width
 * @param $new_height
 * @return bool|string
 */
function svg_recadrer($img, $new_width, $new_height, $offset_width, $offset_height, $background_color = '') {
	if (
		($svg = svg_force_viewBox_px($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		[$balise_svg, $attributs] = $svg_infos;
		$viewBox = explode(' ', $attributs['viewBox']);

		$viewport_w = $new_width;
		$viewport_h = $new_height;
		$viewport_ox = $offset_width;
		$viewport_oy = $offset_height;

		// si on a un width/height qui rescale, il faut rescaler
		if (
			isset($attributs['width'])
			&& ($w = svg_dimension_to_pixels($attributs['width']))
			&& isset($attributs['height'])
			&& ($h = svg_dimension_to_pixels($attributs['height']))
		) {
			$xscale = $viewBox[2] / $w;
			$viewport_w = round($viewport_w * $xscale, 2);
			$viewport_ox = round($viewport_ox * $xscale, 2);
			$yscale = $viewBox[3] / $h;
			$viewport_h = round($viewport_h * $yscale, 2);
			$viewport_oy = round($viewport_oy * $yscale, 2);
		}

		if ($viewport_w > $viewBox[2] || $viewport_h > $viewBox[3]) {
			$svg = svg_clip_in_box($svg, $viewBox[0], $viewBox[1], $viewBox[2], $viewBox[3]);
		}

		// maintenant on redefinit la viewBox
		$viewBox[0] += $viewport_ox;
		$viewBox[1] += $viewport_oy;
		$viewBox[2] = $viewport_w;
		$viewBox[3] = $viewport_h;

		$attributs['viewBox'] = implode(' ', $viewBox);
		$attributs['width'] = (string) $new_width;
		$attributs['height'] = (string) $new_height;

		$svg = svg_change_balise_svg($svg, $balise_svg, $attributs);

		// ajouter un background
		if ($background_color && $background_color !== 'transparent') {
			$svg = svg_ajouter_background($svg, $background_color);
		}

		return $svg;
	}

	return $img;
}

/**
 * Ajouter un background au SVG : un rect pleine taille avec la bonne couleur
 * @param $img
 * @param $background_color
 * @return bool|string
 */
function svg_ajouter_background($img, $background_color) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		if ($background_color && $background_color !== 'transparent') {
			[$balise_svg, $attributs] = $svg_infos;

			$background_color = svg_couleur_to_hexa($background_color);
			if (isset($attributs['viewBox'])) {
				$viewBox = explode(' ', $attributs['viewBox']);
				$rect = '<rect x="' . $viewBox[0] . '" y="' . $viewBox[1] . '" width="' . $viewBox[2] . '" height="' . $viewBox[3] . "\" fill=\"$background_color\"/>";
			}
			else {
				$rect = "<rect width=\"100%\" height=\"100%\" fill=\"$background_color\"/>";
			}
			$svg = svg_insert_shapes($svg, $rect);
		}
		return $svg;
	}
	return $img;
}


/**
 * Ajouter un voile au SVG : un rect pleine taille avec la bonne couleur/opacite, en premier plan
 * @param $img
 * @param $background_color
 * @return bool|string
 */
function svg_ajouter_voile($img, $background_color, $opacity) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		if ($background_color && $background_color !== 'transparent') {
			[$balise_svg, $attributs] = $svg_infos;

			$background_color = svg_couleur_to_hexa($background_color);
			if (isset($attributs['viewBox'])) {
				$viewBox = explode(' ', $attributs['viewBox']);
				$rect = '<rect x="' . $viewBox[0] . '" y="' . $viewBox[1] . '" width="' . $viewBox[2] . '" height="' . $viewBox[3] . "\" fill=\"$background_color\" opacity=\"$opacity\"/>";
			}
			else {
				$rect = "<rect width=\"100%\" height=\"100%\" fill=\"$background_color\"/>";
			}
			$svg = svg_insert_shapes($svg, $rect, false);
		}
		return $svg;
	}
	return $img;
}


/**
 * Ajouter un background au SVG : un rect pleine taille avec la bonne couleur
 * @param $img
 * @param array $attributs
 * @return bool|string
 */
function svg_transformer($img, $attributs) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		if ($attributs) {
			[$balise_svg, ] = $svg_infos;
			$g = '<g';
			foreach ($attributs as $k => $v) {
				if (strlen($v)) {
					$g .= " $k=\"" . attribut_html($v) . '"';
				}
			}
			if (strlen($g) > 2) {
				$g .= '>';
				$svg = svg_insert_shapes($svg, $g);
				$svg = svg_insert_shapes($svg, '</g>', false);
			}
		}
		return $svg;
	}
	return $img;
}

/**
 * Ajouter + appliquer un filtre a un svg
 * @param string $img
 * @param string $filter_def
 *   definition du filtre (contenu de <filter>...</filter>
 * @return bool|string
 */
function svg_apply_filter($img, $filter_def) {
	if (
		($svg = svg_charger($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		if ($filter_def) {
			[$balise_svg, ] = $svg_infos;
			$filter_id = 'filter-' . substr(md5($filter_def . strlen($svg)), 0, 8);
			$filter = "<defs><filter id=\"$filter_id\">$filter_def</filter></defs>";
			$g = "<g filter=\"url(#$filter_id)\">";
			$svg = svg_insert_shapes($svg, $filter . $g);
			$svg = svg_insert_shapes($svg, '</g>', false);
		}
		return $svg;
	}
	return $img;
}

/**
 * Filtre blur en utilisant <filter>
 * @param string $img
 * @param int $blur_width
 * @return string
 */
function svg_filter_blur($img, $blur_width) {
	$blur_width = (int) $blur_width;
	return svg_apply_filter($img, "<feGaussianBlur stdDeviation=\"$blur_width\"/>");
}

/**
 * Filtre grayscale en utilisant <filter>
 * @param string $img
 * @param float $intensity
 * @return bool|string
 */
function svg_filter_grayscale($img, $intensity) {
	$value = round(1.0 - $intensity, 2);
	//$filter = "<feColorMatrix type=\"matrix\" values=\"0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0 1 0\"/>";
	$filter = "<feColorMatrix type=\"saturate\" values=\"$value\"/>";
	return svg_apply_filter($img, $filter);
}

/**
 * Filtre sepia en utilisant <filter>
 * @param $img
 * @param $intensity
 * @return bool|string
 */
function svg_filter_sepia($img, $intensity) {
	$filter = '<feColorMatrix type="matrix" values="0.30 0.30 0.30 0.0 0 0.25 0.25 0.25 0.0 0 0.20 0.20 0.20 0.0 0 0.00 0.00 0.00 1 0"/>';
	return svg_apply_filter($img, $filter);
}

/**
 * Ajouter un background au SVG : un rect pleine taille avec la bonne couleur
 * @param $img
 * @param array string $HorV
 * @return bool|string
 */
function svg_flip($img, $HorV) {
	if (
		($svg = svg_force_viewBox_px($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		[$balise_svg, $atts] = $svg_infos;
		$viewBox = explode(' ', $atts['viewBox']);

		if (!in_array($HorV, ['h', 'H'])) {
			$transform = 'scale(-1,1)';

			$x = (int) $viewBox[0] + (int) ($viewBox[2] / 2);
			$mx = -$x;
			$transform = "translate($x, 0) $transform translate($mx, 0)";
		}
		else {
			$transform = 'scale(1,-1)';

			$y = (int) $viewBox[1] + (int) ($viewBox[3] / 2);
			$my = -$y;
			$transform = "translate(0, $y) $transform translate(0, $my)";
		}
		return svg_transformer($svg, ['transform' => $transform]);
	}
	return $img;
}

/**
 * @param string $img
 * @param int/float $angle
 *   angle en degres
 * @param $center_x
 *   centre X de la rotation entre 0 et 1, relatif a la pleine largeur (0=bord gauche, 1=bord droit)
 * @param $center_y
 *   centre Y de la rotation entre 0 et 1, relatif a la pleine hauteur (0=bord top, 1=bord bottom)
 * @return bool|string
 */
function svg_rotate($img, $angle, $center_x, $center_y) {
	if (
		($svg = svg_force_viewBox_px($img))
		&& ($svg_infos = svg_lire_balise_svg($svg))
	) {
		[$balise_svg, $atts] = $svg_infos;
		$viewBox = explode(' ', $atts['viewBox']);

		$center_x = round($viewBox[0] + $center_x * $viewBox[2]);
		$center_y = round($viewBox[1] + $center_y * $viewBox[3]);

		return svg_transformer($svg, ['transform' => "rotate($angle $center_x $center_y)"]);
	}
	return $img;
}

/**
 * Filtrer les couleurs d'un SVG avec une callback
 * (peut etre lent si beaucoup de couleurs)
 *
 * @param $img
 * @param $callback_filter
 * @return bool|mixed|string
 */
function svg_filtrer_couleurs($img, $callback_filter) {
	if (
		($svg = svg_force_viewBox_px($img))
		&& ($colors = svg_extract_couleurs($svg))
	) {
		$colors = array_unique($colors);

		$short = [];
		$long = [];
		while (count($colors)) {
			$c = array_shift($colors);
			if (strlen($c) == 4) {
				$short[] = $c;
			}
			else {
				$long[] = $c;
			}
		}

		$colors = [...$long, ...$short];
		$new_colors = [];
		$colors = array_flip($colors);
		foreach ($colors as $c => $k) {
			$colors[$c] = "@@@COLOR$$k$@@@";
		}


		foreach ($colors as $original => $replace) {
			$new = svg_couleur_to_hexa($original);
			$new_colors[$replace] = $callback_filter($new);
		}

		$svg = str_replace(array_keys($colors), array_values($colors), $svg);

		return str_replace(array_keys($new_colors), array_values($new_colors), $svg);
	}
	return $img;
}
