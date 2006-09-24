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

// http://doc.spip.org/@inc_install_ldap5
function inc_install_ldap5()
{
	global $spip_lang_right, $statut_ldap;

	install_debut_html();

	// simuler ecrire_meta/s pour pouvoir sauver le statut
	// car _FILE_CONNECT est defa a False a ce moment.

	include_spip('inc/meta');
	spip_query("REPLACE spip_meta (nom, valeur) VALUES ('ldap_statut_import', " . spip_abstract_quote($statut_ldap) . " )");
	@unlink(_FILE_META);

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";
}

?>