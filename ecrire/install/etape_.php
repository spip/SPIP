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

// http://doc.spip.org/@install_etape__dist
function install_etape__dist()
{
	utiliser_langue_visiteur();
	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues) {
		redirige_url_ecrire('install', "etape=chmod");
	} else {
		include_spip('inc/presentation'); // pour info_copyright

		$res = "<div class='petit-centre'><img alt='SPIP' src='" .  chemin_image('logo-spip2.gif') . "' />\n" .
			"<p class='small'>" .info_copyright() ."</p></div>\n" .
			"<p>" ._T('install_select_langue') ."</p>" .
			"<div>" .$menu_langues ."</div>\n" .
			generer_form_ecrire('install', "<input type='hidden' name='etape' value='chmod' />" . bouton_suivant());
		echo minipres('AUTO', $res);
	}
}
?>
