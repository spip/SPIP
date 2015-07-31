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

function inc_install_ldap5()
{
	global $spip_lang_right, $statut_ldap;

	install_debut_html();

	include_once(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	include_spip('inc/meta');
	ecrire_meta("ldap_statut_import", $statut_ldap);
	ecrire_metas();

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";
}

?>