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
include_spip('inc/headers');
include_spip('inc/meta');

// http://doc.spip.org/@inc_install_ldap5
function install_etape_ldap5_dist()
{
	//  _FILE_CONNECT n'existe pas encore

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	ecrire_meta('ldap_statut_import', _request('statut_ldap'));
	spip_unlink(_FILE_META); // virer le vieux ca suffit.

	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	echo info_etape(_T('info_ldap_ok'), _T('info_terminer_installation'));

	echo generer_form_ecrire('install', (
		"<input type='hidden' name='etape' value='3' />" .
		"<input type='hidden' name='ldap_present' value='true' />" 
		. bouton_suivant()));

	echo info_progression_etape(5,'etape_ldap','install/');
	echo install_fin_html();
}

?>
