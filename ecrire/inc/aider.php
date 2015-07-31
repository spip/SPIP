<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/filtres');

// L'aide en ligne de SPIP est disponible sous forme d'articles de www.spip.net
// qui ont des reperes nommes arrtitre, artdesc etc.
// La fonction inc_aider(_dist) recoit soit ces reperes, 
// soit le nom du champ de saisie, le nom du squelette le contenant et enfin
// l'environnement d'execution du squelette (inutilise pour le moment).
// Le tableau ci-dessous donne le repere correspondant a ces informations.

$GLOBALS['aider_index'] = array(
	'editer_article.html' => array (
		'surtitre' => 'arttitre',
		'titre' => 'arttitre',
		'soustitre' => 'arttitre',
		'id_parent' => 'artrub',
		'descriptif' => 'artdesc',
		'virtuel' => 'artvirt',
		'chapo' => 'arttitre',
		'text_area' => 'arttexte'),

	'editer_breve.html' => array(
		'id_parent' => 'brevesrub',
		'lien_titre' => 'breveslien',
		'statut' => 'brevesstatut'),

	'editer_groupe_mot.html' => array(
		'titre' => 'motsgroupes'),

	'editer_mot.html' => array(
		'titre' => 'mots',
		'id_groupe' => 'motsgroupes'),

	'editer_rubrique.html' => array(
		'titre' => 'arttitre',
		'id_parent' => 'rubrub',
		'text_area' => 'raccourcis')
				);

// http://doc.spip.org/@inc_aider_dist
function inc_aider_dist($aide='', $skel='', $env=array()) {
	global $spip_lang, $spip_display, $aider_index;

	if (!$aide OR $spip_display == 4) return '';

	if (($skel = basename($skel))
	AND isset($aider_index[$skel])
	AND isset($aider_index[$skel][$aide]))
		$aide = $aider_index[$skel][$aide];

	$args = "aide=$aide&var_lang=$spip_lang";
	
	return aider_icone(generer_url_ecrire("aide_index", $args));
}

function aider_icone($url)
{
	global $spip_lang, $spip_lang_rtl;

	$t = _T('titre_image_aide');

	return "\n&nbsp;<a class='aide'\nhref='"
	.  $url
	. "'\nonclick=\"javascript:window.open(this.href,"
	. "'spip_aide', "
	. "'scrollbars=yes, resizable=yes, width=740, height=580'); "
	. "return false;\">"
	. http_img_pack("aide-12".aide_lang_dir($spip_lang,$spip_lang_rtl).".png",
			_T('info_image_aide'),
			" title=\"$t\" class='aide'")
	. "</a>";
}


// en hebreu le ? ne doit pas etre inverse
// http://doc.spip.org/@aide_lang_dir
function aide_lang_dir($spip_lang,$spip_lang_rtl) {
	return ($spip_lang<>'he') ? $spip_lang_rtl : '';
}
?>
