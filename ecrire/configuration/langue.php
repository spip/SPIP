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

//
// Configuration i18n
//

include_spip('inc/lang');
include_spip('inc/presentation');

function configuration_langue_dist()
{
 $l_site = $GLOBALS['meta']['langue_site'];
 $langue_site = traduire_nom_langue($l_site);

 $res = "<option value='$l_site' selected='selected'>$langue_site</option>\n";
 
 foreach (split(",",$GLOBALS['all_langs']) as $l) {
	if ($l <> $l_site)
		$res .= "<option value='$l'>".traduire_nom_langue($l)."</option>\n";
 }

 $res = ajax_action_post('configurer',
			 'langue',
			 'config_lang',
			 '',
			 _T('info_langue_principale') .
			 " : <select name='changer_langue_site' class='fondl'>\n$res</select>\n",
			 '',
			 " class='fondo'");

 $res =  debut_cadre_couleur("langues-24.gif", true, "", _T('info_langue_principale') . "&nbsp;:&nbsp;" . $langue_site) .
	   _T('texte_selection_langue_principale') .
	  $res .
	   fin_cadre_couleur(true);

 return ajax_action_greffe("configurer-langue", $res);
}
?>
