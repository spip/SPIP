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


// http://doc.spip.org/@inc_install_ldap3
function inc_install_ldap3()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $spip_lang_right;

	install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_chemin_acces_1')."</FONT>";

	echo "<P>"._T('info_chemin_acces_2');

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap4'>";
	echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='protocole_ldap' VALUE=\"$protocole_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='tls_ldap' VALUE=\"$tls_ldap\">";

	echo "<fieldset>";

	$checked = false;

	if (is_array($info) AND $info["count"] > 0) {
		echo "<P>"._T('info_selection_chemin_acces');
		echo "<UL>";
		$n = 0;
		for ($i = 0; $i < $info["count"]; $i++) {
			$names = $info[$i]["namingcontexts"];
			if (is_array($names)) {
				for ($j = 0; $j < $names["count"]; $j++) {
					$n++;
					echo "<INPUT NAME=\"base_ldap\" VALUE=\"".htmlspecialchars($names[$j])."\" TYPE='Radio' id='tab$n'";
					if (!$checked) {
						echo " CHECKED";
						$checked = true;
					}
					echo ">";
					echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label><BR />\n\n";
				}
			}
		}
		echo "</UL>";
		echo _T('info_ou')." ";
	}
	echo "<INPUT NAME=\"base_ldap\" VALUE=\"\" TYPE='Radio' id='manuel'";
	if (!$checked) {
		echo " CHECKED";
		$checked = true;
	}
	echo ">";
	echo "<label for='manuel'>"._T('entree_chemin_acces')."</label> ";
	echo "<INPUT TYPE='text' NAME='base_ldap_text' CLASS='formo' VALUE=\"ou=users, dc=mon-domaine, dc=com\" SIZE='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	install_fin_html();
}


?>