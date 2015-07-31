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

function inc_install_ldap1()
{
	global $spip_lang_right;

	$protocole_ldap = 3; // on essaie 2 en cas d'echec
	$adresse_ldap = 'localhost';
	$port_ldap = 389;

	// Recuperer les anciennes donnees (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
		if (ereg('ldap_connect\("(.*)","(.*)"\)', $s, $regs)) {
			$adresse_ldap = $regs[1];
			$port_ldap = $regs[2];
		}
	}
	install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>";
	echo _T('titre_connexion_ldap');
	echo "</FONT><br />";

	echo _T('entree_informations_connexion_ldap');
	echo generer_url_post_ecrire('install');
	echo "<p><INPUT TYPE='hidden' NAME='etape' VALUE='ldap2'>";

	echo "<fieldset><label><B>";
	echo _T('entree_adresse_annuaire');
	echo "</B><BR />\n</label>";
	echo _T('texte_adresse_annuaire_1');
	echo "<BR />\n<INPUT TYPE='text' NAME='adresse_ldap' CLASS='formo' VALUE=\"$adresse_ldap\" SIZE='20'></p>";

	echo "<p><label><B>";
	echo _T('entree_port_annuaire');
	echo "</B><BR />\n</label>";
	echo _T('texte_port_annuaire');
	echo "<BR />\n<INPUT TYPE='text' NAME='port_ldap' CLASS='formo' VALUE=\"$port_ldap\" SIZE='20' /></p>";

	echo "<p><label><B>";
	echo _L('Transport Layer Security' );
	echo "</B></label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<INPUT TYPE='radio' NAME='tls_ldap' value='non' checked='checked' />";
	echo _T('item_non');
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<INPUT TYPE='radio' NAME='tls_ldap' value='oui'/>";
	echo _T('item_oui');
	echo '</p>';

	echo "<p><label><B>"._T('version')."</B></label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<INPUT TYPE='text' NAME='protocole_ldap' CLASS='formo' VALUE=\"$protocole_ldap\" SIZE='5' />";

	echo "</fieldset>";

	echo "<p><fieldset>";
	echo _T('texte_acces_ldap_anonyme_1')." ";
	echo "<label><B>"._T('entree_login_ldap')."</B><BR />\n</label>";
	echo _T('texte_login_ldap_1')."<br />\n";
	echo "<INPUT TYPE='text' NAME='login_ldap' CLASS='formo' VALUE=\"\" SIZE='40'><P>";

	echo "<label><B>"._T('entree_passe_ldap')."</B><BR />\n</label>";
	echo "<INPUT TYPE='password' NAME='pass_ldap' CLASS='formo' VALUE=\"\" SIZE='40'></fieldset>";

	echo "<p><DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";

	install_fin_html();
}

?>