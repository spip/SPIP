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

include_spip('balise/formulaire_inscription');
include_spip('public/assembler'); 
include_spip('inc/lang');
include_spip('inc/headers');

// http://doc.spip.org/@action_inscription_dist
function action_inscription_dist() {

	utiliser_langue_site();
	utiliser_langue_visiteur();
	http_no_cache();

	echo _DOCTYPE_ECRIRE,
		html_lang_attributes(),
		'<head><title>',
		_T('pass_vousinscrire'), 
		'</title>',
		'<link rel="stylesheet" type="text/css" href="',
		find_in_path('spip_style.css'),
		'"></head><body>';

	inclure_balise_dynamique(balise_FORMULAIRE_INSCRIPTION_dyn(_request('mode'), _request('focus'), _request('id_rubrique')));
	echo "</body></html>";
}

?>
