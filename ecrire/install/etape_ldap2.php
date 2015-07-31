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


// http://doc.spip.org/@inc_install_ldap2
function install_etape_ldap2_dist()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $spip_lang_right;

	echo install_debut_html('AUTO', ' onLoad="document.getElementById(\'suivant\').focus();return false;"');

	$port_ldap = intval($port_ldap);
	$ldap_link = @ldap_connect($adresse_ldap, $port_ldap);
	$erreur = "ldap_connect($adresse_ldap, $port_ldap)";

	if ($ldap_link) {
		if ( !@ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap) ) {
			$protocole_ldap = 2 ;
			@ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap);
		}
		if ($tls_ldap == 'oui') {
			if (!@ldap_start_tls($ldap_link)) {
				$erreur = "ldap_start_tls($ldap_link) $adresse_ldap, $port_ldap";
				$ldap_link = false;
			}
		}
		if ($ldap_link) {
			$ldap_link = @ldap_bind($ldap_link, $login_ldap, $pass_ldap);
			$erreur = "ldap_bind('$ldap_link', '$login_ldap', '$pass_ldap'): $adresse_ldap, $port_ldap";
		}
	}

	if ($ldap_link) {
		echo info_etape(_T('titre_connexion_ldap'),_T('info_connexion_ldap_ok'));

		echo generer_url_post_ecrire('install');
		echo "<input type='hidden' name='etape' value='ldap3' />";
		echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\" />";
		echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\" />";
		echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\" />";
		echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\" />";
		echo "<input type='hidden' name='protocole_ldap' value=\"$protocole_ldap\" />";
		echo "<input type='hidden' name='tls_ldap' value=\"$tls_ldap\" />";

		echo bouton_suivant();
		echo "</form>";
	}
	else {
		echo info_etape(_T('titre_connexion_ldap'),
			_T('avis_connexion_ldap_echec_1').
			"<p>"._T('avis_connexion_ldap_echec_2').
			"<br />\n"._T('avis_connexion_ldap_echec_3') .
			'<br /><br />'. $erreur. '<b> ?</b></p>'
		);
	}

	echo install_fin_html();
}

?>
