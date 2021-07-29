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
 * Déclaration de filtres permettent l'incrustation d'un document selon son type Mime
 *
 * Ces filtres peuvent être appelés par le modèle `<embXX>` dans certains cas,
 * en utilisant `|appliquer_filtre{#MIME_TYPE}` sur un contenu
 *
 * @see \appliquer_filtre()
 *
 * @package SPIP\Core\Filtres\Mime
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/filtres');


// Les 7 familles de base ne font rien sauf celle des textes

/**
 * Filtre d'incrustation d'un document image
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_image_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document audio
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_audio_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document video
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_video_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document application
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_application_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document message
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_message_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document multipart
 *
 * Ne fait rien.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Rien.
 **/
function filtre_multipart_dist($t) {
	return '';
}

/**
 * Filtre d'incrustation d'un document test
 *
 * Échappe les chevrons et l'esperluette.
 *
 * @filtre
 * @param string $t Contenu
 * @return string Contenu échappé.
 **/
function filtre_text_dist($t) {
	static $t1 = array('&', '<', '>');
	static $t2 = array('&amp;', '&lt;', '&gt;');

	return '<pre>' . str_replace($t1, $t2, $t) . '</pre>';
}

/**
 * Filtre d'incrustation d'un document CSV
 *
 * Produit un joli tableau à partir du texte CSV
 *
 * @filtre
 * @param string $t
 *     Texte CSV
 * @return string
 *     Tableau (formaté en SPIP)
 **/
function filtre_text_csv_dist($t) {
	include_spip('inc/csv');
	list($entete, $lignes, $caption) = analyse_csv($t);
	foreach ($lignes as &$l) {
		$l = join('|', $l);
	}
	$corps = join("\n", $lignes) . "\n";
	$corps = $caption .
		"\n|{{" .
		join('}}|{{', $entete) .
		'}}|' .
		"\n|" .
		str_replace("\n", "|\n|", $corps);
	include_spip('inc/texte');

	return propre($corps);
}

/**
 * Filtre d'incrustation d'un document text/html
 *
 * Incrustation de HTML, si on est capable de le sécuriser,
 * sinon, afficher la source
 *
 * @filtre
 * @param string $t Code html
 * @return string Code html sécurisé ou texte échappé
 **/
function filtre_text_html_dist($t) {
	if (!preg_match(',^(.*?)<body[^>]*>(.*)</body>,is', $t, $r)) {
		return appliquer_filtre($t, 'text/plain');
	}

	list(, $h, $t) = $r;

	$style = '';
	// recuperer les styles internes
	if (preg_match_all(',<style>(.*?)</style>,is', $h, $r, PREG_PATTERN_ORDER)) {
		$style = join("\n", $r[1]);
	}
	// ... et externes

	include_spip('inc/distant');
	if (preg_match_all(',<link[^>]+type=.text/css[^>]*>,is', $h, $r, PREG_PATTERN_ORDER)) {
		foreach ($r[0] as $l) {
			preg_match("/href='([^']*)'/", str_replace('"', "'", $l), $m);
			$page = recuperer_url($m[1]);
			$page = $page['page'] ?? '';
			$style .= "\n/* $l */\n"
				. str_replace('<', '', $page);
		}
	}
	// Pourquoi SafeHtml transforme-t-il en texte les scripts dans Body ?
	$t = safehtml(preg_replace(',<script' . '.*?</script>,is', '', $t));

	return (!$style ? '' : "\n<style>" . $style . '</style>') . $t;
}

/**
 * Filtre d'incrustation d'un document RealAudio
 *
 * Retourne les paramètres `<param>` nécessaires à la balise `<object>`
 *
 * @filtre
 * @param string $id
 * @return string Code HTML des balises `<param>`
 **/
function filtre_audio_x_pn_realaudio($id) {
	return "
	<param name='controls' value='PositionSlider' />
	<param name='controls' value='ImageWindow' />
	<param name='controls' value='PlayButton' />
	<param name='console' value='Console$id' />
	<param name='nojava' value='true' />";
}
