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

// http://doc.spip.org/@inc_install_ldap5
function install_etape_ldap5_dist()
{
	global $spip_lang_right, $statut_ldap;

	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	// simuler ecrire_meta/s pour pouvoir sauver le statut
	// car _FILE_CONNECT n'existe pas encore

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	spip_query("REPLACE spip_meta (nom, valeur) VALUES ('ldap_statut_import', " . _q($statut_ldap) . " )");
	@unlink(_FILE_META);

	echo info_etape(_T('info_ldap_ok'), _T('info_terminer_installation'));

	echo generer_post_ecrire('install', (
		"<input type='hidden' name='etape' value='5' />"
		. bouton_suivant()));

	echo info_progression_etape(5,'etape_ldap','install/');
	echo install_fin_html();
}

?>