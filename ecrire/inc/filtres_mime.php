<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/filtres');

// Fichier des filtres d'incrustation d'un document selon son type MIME
// Les 7 familles de base ne font rien sauf celle des textes

function filtre_image_dist($t) {return '';}
function filtre_audio_dist($t) {return '';}
function filtre_video_dist($t) {return '';}
function filtre_application_dist($t) {return '';}
function filtre_message_dist($t) {return '';}
function filtre_multipart_dist($t) {return '';}

// http://doc.spip.org/@filtre_text_txt_dist
function filtre_text_dist($t) {
	static $t1 = array('&', '<', '>');
	static $t2 = array('&amp;', '&lt;', '&gt;');
	return '<pre>' . str_replace($t1, $t2, $t) . '</pre>';
}

// http://doc.spip.org/@filtre_text_csv_dist
function filtre_text_csv_dist($t) {
	include_spip('inc/csv');
	list($entete, $lignes) = analyse_csv($t);
	foreach ($lignes as &$l)
		$l = join('|', $l);
	$corps = join("\n", $lignes) . "\n";
	$corps = $caption .
		"\n|{{" .
		join('}}|{{',$entete) .
		"}}|" .
		"\n|" .
		str_replace("\n", "|\n|",$corps);
	$corps = str_replace('&#34#','&#34;',$corps);
	include_spip('inc/texte');
	return propre($corps);
}

// Incrustation de HTML, si on est capable de le securiser
// sinon, afficher le source
// http://doc.spip.org/@filtre_text_html_dist
function filtre_text_html_dist($t)
{
	if (!preg_match(',^(.*?)<body[^>]*>(.*)</body>,is', $t, $r))
		return appliquer_filtre($t,'text/plain');

	list(,$h,$t) = $r;

	$style = '';
	// recuperer les styles internes
	if (preg_match_all(',<style>(.*?)</style>,is', $h, $r, PREG_PATTERN_ORDER))
		$style =  join("\n",$r[1]);
	// ... et externes

	include_spip('inc/distant');
	if (preg_match_all(',<link[^>]+type=.text/css[^>]*>,is', $h, $r, PREG_PATTERN_ORDER))
		foreach($r[0] as $l) {
			preg_match("/href='([^']*)'/", str_replace('"',"'",$l), $m);
			$style .= "\n/* $l */\n"
			. str_replace('<','',recuperer_page($m[1]));
		}
	// Pourquoi SafeHtml transforme-t-il en texte les scripts dans Body ?
	$t = safehtml(preg_replace(',<script'.'.*?</script>,is','',$t));
	return (!$style ? '' : "\n<style>".$style."</style>") . $t;
}

// http://doc.spip.org/@filtre_audio_x_pn_realaudio
function filtre_audio_x_pn_realaudio($id)
{
  return "
	<param name='controls' value='PositionSlider' />
	<param name='controls' value='ImageWindow' />
	<param name='controls' value='PlayButton' />
	<param name='console' value='Console$id' />
	<param name='nojava' value='true' />";
}
?>
