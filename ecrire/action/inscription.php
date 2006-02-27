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

function action_inscription_dist() {

	include_local(find_in_path("inc-formulaire_inscription" . _EXTENSION_PHP));
	include_spip('public/global'); 
	include_ecrire("inc_lang");
	include_ecrire('inc_headers');

	utiliser_langue_site();
	utiliser_langue_visiteur();
	http_no_cache();
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="', 
  $GLOBALS['spip_lang'],
  '" dir="',
  ($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr'),
  '">
<head><title>',
  _T('pass_vousinscrire'), 
  '</title>
<link rel="stylesheet" type="text/css" href="spip_style.css" />
</head><body>';

	inclure_balise_dynamique(balise_formulaire_inscription_dyn(_request('mode'), _request('focus'), _request('id_rubrique'))
	);
	echo "</body></html>";
}

?>
