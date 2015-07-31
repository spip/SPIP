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


// http://doc.spip.org/@inc_install_ldap2
function inc_install_ldap2()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $spip_lang_right;

	 install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('titre_connexion_ldap')."</FONT>";

	echo "<P>";

	$port_ldap = intval($port_ldap);
	$ldap_link = ldap_connect($adresse_ldap, $port_ldap);
	$erreur = "ldap_connect($adresse_ldap, $port_ldap)";

        if ($ldap_link) {

		if ( !ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap) ) {
			$protocole_ldap = 2 ;
			ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap);
		}
		if ($tls_ldap == 'oui') {
			if (!ldap_start_tls($ldap_link)) {
				$erreur = "ldap_start_tls($ldap_link) $adresse_ldap, $port_ldap";
				$ldap_link = false;
			}
		}
	        if ($ldap_link) {
			$ldap_link = ldap_bind($ldap_link, $login_ldap, $pass_ldap);
			$erreur = "ldap_bind('$ldap_link', '$login_ldap', '$pass_ldap'): $adresse_ldap, $port_ldap";
		}
	}

	if ($ldap_link) {
		echo "<B>"._T('info_connexion_ldap_ok');

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='protocole_ldap' VALUE=\"$protocole_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='tls_ldap' VALUE=\"$tls_ldap\">";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_ldap_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_ldap_echec_2');
		echo "<br />\n"._T('avis_connexion_ldap_echec_3');
		echo '<br /><br />', $erreur, '<b> ?</b>';
	}

	install_fin_html();
}

?>
