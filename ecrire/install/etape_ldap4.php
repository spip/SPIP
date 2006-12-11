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

// http://doc.spip.org/@inc_install_ldap4
function install_etape_ldap4_dist()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $tls_ldap, $protocole_ldap, $base_ldap, $base_ldap_text, $spip_lang_right;

	echo install_debut_html();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		info_etape(_T('info_chemin_acces_annuaire'),
			"<B>"._T('avis_operation_echec')."</B> "._T('avis_chemin_invalide_1')." (<tt>".htmlspecialchars($base_ldap)."</tt>) "._T('avis_chemin_invalide_2')
		);
	}
	else {
		info_etape(_T('info_reglage_ldap'));

		lire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);
		if ($p = strpos($conn, '?'.'>')) 
			$conn = substr($conn, 0, $p);
		if (!strpos($conn, 'spip_connect_ldap')) {
			$conn .= "function spip_connect_ldap() {\n";
			$conn .= "\t\$GLOBALS['ldap_link'] = @ldap_connect(\"$adresse_ldap\",\"$port_ldap\");\n";
			$conn .= "\t@ldap_set_option(\$GLOBALS['ldap_link'],LDAP_OPT_PROTOCOL_VERSION,\"$protocole_ldap\");\n";
 			if ($tls_ldap == 'oui')
				$conn .= "\t@ldap_start_tls(\$GLOBALS['ldap_link']);\n";


			$conn .= "\t@ldap_bind(\$GLOBALS['ldap_link'],\"$login_ldap\",\"$pass_ldap\");\n";
			$conn .= "\treturn \$GLOBALS['ldap_link'];\n";
			$conn .= "}\n";
			$conn .= "\$GLOBALS['ldap_base'] = \"$base_ldap\";\n";
			$conn .= "\$GLOBALS['ldap_present'] = true;\n";
		}
		$conn .= "?".">";
		ecrire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);

		echo generer_url_post_ecrire('install');
		echo "<input type='hidden' name='etape' value='ldap5' />";
		
		echo fieldset(_T('info_statut_utilisateurs_1'),
			array(
				'$statut_ldap' => array(
					'label' => _T('info_statut_utilisateurs_2').'<br />',
					'valeur' => '1comite',
					'alternatives' => array(
						'6forum' => "<b>"._T('info_visiteur_1')."</b> "._T('info_visiteur_2')."<br />",
						'1comite' => "<b>"._T('info_redacteur_1')."</b> "._T('info_redacteur_2')."<br />",
						'0minirezo' => "<b>"._T('info_administrateur_1')."</b> "._T('info_administrateur_2')."<br />"
					)
				)
			)
		);

		echo bouton_suivant();
		echo "</form>";
	}

	echo install_fin_html();
}
?>
