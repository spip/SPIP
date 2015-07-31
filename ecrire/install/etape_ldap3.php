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


// http://doc.spip.org/@inc_install_ldap3
function install_etape_ldap3_dist()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $spip_lang_right;

	echo install_debut_html();

	echo info_etape(_T('info_chemin_acces_1'),_T('info_chemin_acces_2'));

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	$checked = false;
	$res = '';
	if (is_array($info) AND $info["count"] > 0) {
		$res .= "<p>"._T('info_selection_chemin_acces')."</p>";
		$res .= "<ul>";
		$n = 0;
		for ($i = 0; $i < $info["count"]; $i++) {
			$names = $info[$i]["namingcontexts"];
			if (is_array($names)) {
				for ($j = 0; $j < $names["count"]; $j++) {
					$n++;
					$res .= "<li><input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='radio' id='tab$n'";
					if (!$checked) {
						$res .= " checked=\"checked\"";
						$checked = true;
					}
					$res .= " />";
					$res .= "<label for='tab$n'>".htmlspecialchars($names[$j])."</label></li>\n";
				}
			}
		}
		$res .= "</ul>";
		$res .= _T('info_ou')." ";
	}
	$res .= "<br />\n<input name=\"base_ldap\" value=\"\" type='radio' id='manuel'";
	if (!$checked) {
		$res .= " checked=\"checked\"";
		$checked = true;
	}
	$res .= " />"
	. "\n<label for='manuel'>"._T('entree_chemin_acces')."</label> ";

	echo generer_post_ecrire('install', ($res
	. "\n<input type='hidden' name='etape' value='ldap4' />"
	. "\n<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\" />"
	. "\n<input type='hidden' name='port_ldap' value=\"$port_ldap\" />"
	. "\n<input type='hidden' name='login_ldap' value=\"$login_ldap\" />"
	. "\n<input type='hidden' name='pass_ldap' value=\"$pass_ldap\" />"
	. "\n<input type='hidden' name='protocole_ldap' value=\"$protocole_ldap\" />"
	. "\n<input type='hidden' name='tls_ldap' value=\"$tls_ldap\" />"

	. "\n<fieldset>"
	. "<input type='text' name='base_ldap_text' class='formo' value=\"ou=users, dc=mon-domaine, dc=com\" size='40' />"
	. "\n</fieldset>"
	. bouton_suivant()));

	echo info_progression_etape(3,'etape_ldap','install/');
	echo install_fin_html();
}


?>
