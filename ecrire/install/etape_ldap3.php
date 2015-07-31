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
function install_etape_ldap3_dist()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $spip_lang_right;

	install_debut_html();

	echo info_etape(_T('info_chemin_acces_1'),_T('info_chemin_acces_2'));

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	echo generer_url_post_ecrire('install');
	echo "<input type='hidden' name='etape' value='ldap4' />";
	echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\" />";
	echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\" />";
	echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\" />";
	echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\" />";
	echo "<input type='hidden' name='protocole_ldap' value=\"$protocole_ldap\" />";
	echo "<input type='hidden' name='tls_ldap' value=\"$tls_ldap\" />";

	echo "<fieldset>";

	$checked = false;

	if (is_array($info) AND $info["count"] > 0) {
		echo "<p>"._T('info_selection_chemin_acces')."</p>";
		echo "<ul>";
		$n = 0;
		for ($i = 0; $i < $info["count"]; $i++) {
			$names = $info[$i]["namingcontexts"];
			if (is_array($names)) {
				for ($j = 0; $j < $names["count"]; $j++) {
					$n++;
					echo "<li><input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='radio' id='tab$n'";
					if (!$checked) {
						echo " checked=\"checked\"";
						$checked = true;
					}
					echo " />";
					echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label></li>\n";
				}
			}
		}
		echo "</ul>";
		echo _T('info_ou')." ";
	}
	echo "<input name=\"base_ldap\" value=\"\" type='radio' id='manuel'";
	if (!$checked) {
		echo " checked=\"checked\"";
		$checked = true;
	}
	echo " />";
	echo "<label for='manuel'>"._T('entree_chemin_acces')."</label> ";
	echo "<input type='text' name='base_ldap_text' class='formo' value=\"ou=users, dc=mon-domaine, dc=com\" size='40' />";
	echo "</fieldset>";

	echo bouton_suivant();
	echo "</form>";

	install_fin_html();
}


?>
