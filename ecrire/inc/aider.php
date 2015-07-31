<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function inc_aider_dist($aide='') {
	global $spip_lang, $spip_lang_rtl, $spip_display;

	if (!$aide OR $spip_display == 4) return;

	return "&nbsp;&nbsp;<a class='aide' href='" . generer_url_ecrire("aide_index", "aide=$aide&var_lang=$spip_lang")
		. "' target=\"spip_aide\" "
		. "onclick=\"javascript:window.open(this.href,"
		. "'spip_aide', 'scrollbars=yes, resizable=yes, width=740, "
		. "height=580'); return false;\">"
		. http_img_pack("aide".aide_lang_dir($spip_lang,$spip_lang_rtl).".gif",
			_T('info_image_aide'), "title=\""._T('titre_image_aide')
			. "\" width=\"12\" height=\"12\" align=\"middle\"")
		. "</a>";
}


// en hebreu le ? ne doit pas etre inverse
// http://doc.spip.org/@aide_lang_dir
function aide_lang_dir($spip_lang,$spip_lang_rtl) {
	return ($spip_lang<>'he') ? $spip_lang_rtl : '';
}
?>