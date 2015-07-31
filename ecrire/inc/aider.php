<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/minipres');

// http://doc.spip.org/@inc_aider_dist
function inc_aider_dist($aide='') {
	global $spip_lang, $spip_lang_rtl, $spip_display;

	if (!$aide OR $spip_display == 4) return;

	$t = _T('titre_image_aide');
	return "\n&nbsp;&nbsp;<a target='spip_aide' class='aide'\nhref='"
	. generer_url_ecrire("aide_index", "aide=$aide&var_lang=$spip_lang")
	. "'\nonclick=\"javascript:window.open(this.href,"
	. "'spip_aide', "
	. "'scrollbars=yes, resizable=yes, width=740, height=580'); "
	. "return false;\">"
	. http_img_pack("aide".aide_lang_dir($spip_lang,$spip_lang_rtl).".gif",
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