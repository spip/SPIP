<?php

include ("inc_version.php3");

include_ecrire ("inc_presentation.php3");

gerer_menu_langues();

if (file_exists("inc_connect.php3")) {
	install_debut_html();
	echo "<P><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4>"._T('avis_espace_interdit')."</FONT>";

	install_fin_html();
	exit;
}

include_ecrire ("inc_base.php3");


//
// Etapes de l'installation standard
//

if ($etape == 6) {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_derniere_etape')."</B></FONT>";
	echo "<P>";
	echo "<B>"._T('info_code_acces')."</B>";
	echo "<P>"._T('info_utilisation_spip');

	include_ecrire ("inc_connect_install.php3");

	include_ecrire ("inc_meta.php3");

	if ($login) {
		$nom = addslashes($nom);
		$query = "SELECT id_auteur FROM spip_auteurs WHERE login=\"$login\"";
		$result = spip_query_db($query);
		unset($id_auteur);
		while ($row = spip_fetch_array($result)) $id_auteur = $row['id_auteur'];

		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);

		if ($id_auteur) {
			$query = "UPDATE spip_auteurs SET nom=\"$nom\", email=\"$email\", login=\"$login\", pass=\"$mdpass\", alea_actuel='', alea_futur=FLOOR(32000*RAND()), htpass=\"$htpass\", statut=\"0minirezo\" WHERE id_auteur=$id_auteur";
		}
		else {
			$query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"$nom\",\"$email\",\"$login\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"0minirezo\")";
		}
		spip_query_db($query);

		// inserer email comme email webmaster principal
		ecrire_meta('email_webmaster', $email);
	}

	include_ecrire("inc_config.php3");
	init_config();
	init_langues();

	if ($hebergeur == 'nexenservices') {
		echo "<p><B>"._T('info_nexen_1')."</B><br />";
		echo "<p>"._T('info_nexen_2');
		echo " <a href=\"http://www.nexenservices.com/webmestres/htlocal.php\" target=\"_blank\">"._T('info_nexen_3')."</a>.";
		echo "<p>"._T('info_nexen_4');
	}
	else {
		include_ecrire ("inc_acces.php3");
		ecrire_acces();
		$protec = "deny from all\n";
		$myFile = fopen("data/.htaccess", "w");
		fputs($myFile, $protec);
		fclose($myFile);
	}

	@unlink("data/inc_meta_cache.php3");
	if (!@rename("inc_connect_install.php3", "inc_connect.php3")) {
		copy("inc_connect_install.php3", "inc_connect.php3");
		@unlink("inc_connect_install.php3");
	}

	echo "<FORM ACTION='index.php3' METHOD='post'>";
	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	ecrire_metas();

	install_fin_html();
}

else if ($etape == 5) {
	install_debut_html();

	include_ecrire ("inc_connect_install.php3");

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_informations_personnelles')."</FONT>";
	echo "<P>";

	echo "<b>"._T('texte_informations_personnelles_1')."</b>";
	echo aide ("install5");
	echo "<p>"._T('texte_informations_personnelles_2')." ";
	echo _T('info_laisser_champs_vides');

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='6'>";

	echo "<fieldset><label><B>"._T('info_identification_publique')."</B><BR></label>";
	echo "<B>"._T('entree_signature')."</B><BR>";
	echo _T('entree_nom_pseudo_1')."<BR>";
	echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40'><P>";

	echo "<B>"._T('entree_adresse_email')."</B><BR>";
	echo "<INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"$email\" SIZE='40'></fieldset><P>\n";

	echo "<fieldset><label><B>"._T('entree_identifiants_connexion')."</B><BR></label>";
	echo "<B>"._T('entree_login')."</B><BR>";
	echo _T('info_plus_trois_car')."<BR>";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40'><P>\n";

	echo "<B>"._T('entree_mot_passe')."</B> <BR>";
	echo _T('info_plus_cinq_car_2')."<BR>";
	echo "<INPUT TYPE='password' NAME='pass' CLASS='formo' VALUE=\"$pass\" SIZE='40'></fieldset><P>\n";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";
	echo "<p>";

	if ($flag_ldap AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>"._T('info_authentification_externe')."</b>";
		echo "<p>"._T('texte_annuaire_ldap_1');
		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap1'>";
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE=\""._T('bouton_acces_ldap')."\">";
		echo "</FORM>";
	}

	install_fin_html();

}

else if ($etape == 4) {

	install_debut_html();

	// Necessaire pour appeler les fonctions SQL wrappees
	include_ecrire("inc_db_mysql.php3");

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_creation_tables')."</FONT>";
	echo "<P>";

	$link = mysql_connect("$adresse_db", "$login_db", "$pass_db");

	echo "<"."!--";

	if ($choix_db == "new_spip") {
		$sel_db = $table_new;
		mysql_query("CREATE DATABASE $sel_db");
	}
	else {
		$sel_db = $choix_db;
	}
	mysql_select_db("$sel_db");

	// Test si SPIP deja installe
	@spip_query_db("SELECT COUNT(*) FROM spip_meta");
	$deja_installe = !spip_sql_errno();

	creer_base();
	$maj_ok = maj_base();

	$query = "SELECT COUNT(*) FROM spip_articles";
	$result = spip_query_db($query);
	$result_ok = (spip_num_rows($result) > 0);
	if (!$deja_installe) {
		$query = "INSERT spip_meta (nom, valeur) VALUES ('nouvelle_install', 'oui')";
		spip_query_db($query);
		$result_ok = !spip_sql_errno();
	}

	echo "-->";


	if ($result_ok && $maj_ok) {
		$conn = "<"."?php\n";
		$conn .= "if (defined(\"_ECRIRE_INC_CONNECT\")) return;\n";
		$conn .= "define(\"_ECRIRE_INC_CONNECT\", \"1\");\n";
		$conn .= "\$GLOBALS['spip_connect_version'] = 0.1;\n";
		$conn .= "include_ecrire('inc_db_mysql.php3');\n";
		$conn .= "@spip_connect_db('$adresse_db','','$login_db','$pass_db','$sel_db');\n";
		$conn .= "\$GLOBALS['db_ok'] = !!@spip_num_rows(@spip_query_db('SELECT COUNT(*) FROM spip_meta'));\n";
		$conn .= "?".">";
		$myFile = fopen("inc_connect_install.php3", "wb");
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<B>"._T('info_base_installee')."</B><P>"._T('info_etape_suivante_1');

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";

		echo "</FORM>";
	}
	else if ($result_ok) {
		echo _T('alerte_maj_impossible', array('version' => $spip_version));
	}
	else {
		echo "<B>"._T('avis_operation_echec')."</B> "._T('texte_operation_echec');
	}

	install_fin_html();

}

else if ($etape == 3) {

	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_choix_base')." <B>"._T('menu_aide_installation_choix_base')."</B></FONT>";

	echo aide ("install2");
	echo "<P>";

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='4'>";
	echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
	echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
	echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><label><B>"._T('texte_choix_base_1')."</B><BR></label>";

	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<B>"._T('texte_choix_base_2')."</B><P> "._T('texte_choix_base_3');
		echo "<UL>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<INPUT NAME=\"choix_db\" VALUE=\"".$table_nom."\" TYPE=Radio id='tab$i'";
			$base_fin = "><label for='tab$i'>".$table_nom."</label><BR>\n";
			if ($table_nom == $login_db) {
				$bases = "$base CHECKED$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</UL>";
		echo _T('info_ou')." ";
	}
	else {
		echo "<B>"._T('avis_lecture_noms_bases_1')."</B>
		"._T('avis_lecture_noms_bases_2')."<P>";
		if ($login_db) {
			echo _T('avis_lecture_noms_bases_3');
			echo "<UL>";
			echo "<INPUT NAME=\"choix_db\" VALUE=\"".$login_db."\" TYPE=Radio id='stand' CHECKED>";
			echo "<label for='stand'>".$login_db."</label><BR>\n";
			echo "</UL>";
			echo _T('info_ou')." ";
			$checked = true;
		}
	}
	echo "<INPUT NAME=\"choix_db\" VALUE=\"new_spip\" TYPE=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo "> <label for='nou'>"._T('info_creer_base')."</label> ";
	echo "<INPUT TYPE='text' NAME='table_new' CLASS='fondo' VALUE=\"spip\" SIZE='20'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";


	echo "</FORM>";

	install_fin_html();

}

else if ($etape == 2) {

	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_connexion_base')."</FONT>";

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	echo "<P>";

	if (($db_connect=="0") && $link){
		echo "<B>"._T('info_connexion_ok')."</B><P> "._T('info_etape_suivante_2');

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_echec_2');
		echo "<P><FONT SIZE=2>"._T('avis_connexion_echec_3')."</FONT>";
	}

	install_fin_html();

}
else if ($etape == 1) {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_connexion_mysql')."</FONT>";

	echo "<P>"._T('texte_connexion_mysql');

	echo aide ("install1");

	$adresse_db = 'localhost';
	$login_db = $login_hebergeur;
	$pass_db = '';

	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (file_exists("inc_connect_install.php3")) {
		$s = @join('', @file("inc_connect_install.php3"));
		if (ereg("mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)", $s, $regs)) {
			$adresse_db = $regs[1];
			$login_db = $regs[2];
		}
		else if (ereg("spip_connect_db\('(.*)','(.*)','(.*)','(.*)','(.*)'\)", $s, $regs)) {
			$adresse_db = $regs[1];
			if ($port_db = $regs[2]) $adresse_db .= ':'.$port_db;
			$login_db = $regs[3];
		}
	}

	echo "<p><FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='2'>";
	echo "<fieldset><label><B>"._T('entree_base_donnee_1')."</B><BR></label>";
	echo _T('entree_base_donnee_2')."<BR>";
	echo "<INPUT TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"$adresse_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_login_connexion_1')."</B><BR></label>";
	echo _T('entree_login_connexion_2')."<BR>";
	echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_mot_passe_1')."</B><BR></label>";
	echo _T('entree_mot_passe_2')."<BR>";
	echo "<INPUT TYPE='password' NAME='pass_db' CLASS='formo' VALUE=\"$pass_db\" SIZE='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";


	echo "</FORM>";

	install_fin_html();

}
else if ($etape == 'dirs') {
	header("Location: ../spip_test_dirs.php3");
}
else if (!$etape) {
	$menu_langues = menu_langues();
	if (!$menu_langues) header("Location: ../spip_test_dirs.php3");
	else {
		install_debut_html();

		echo "<p>&nbsp;</p><p align='center'><img src='img_pack/logo-spip.gif'></p>";

		echo "<p>&nbsp;</p><p>" . _T('install_select_langue');

		echo "<p><div align='center'>".$menu_langues."</div>";

		echo "<p><FORM ACTION='install.php3' METHOD='get'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='dirs'>";
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";

		install_fin_html();
	}
}


//
// Etapes de l'installation LDAP
//

else if ($etape == 'ldap5') {
	install_debut_html();

	include_ecrire('inc_connect_install.php3');
	include_ecrire('inc_meta.php3');
	ecrire_meta("ldap_statut_import", $statut_ldap);
	ecrire_metas();

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";
}

else if ($etape == 'ldap4') {
	install_debut_html();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_chemin_acces_annuaire')."</B></FONT>";
		echo "<P>";

		echo "<B>"._T('avis_operation_echec')."</B> "._T('avis_chemin_invalide_1')." (<tt>".htmlspecialchars($base_ldap);
		echo "</tt>) "._T('avis_chemin_invalide_2');
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_reglage_ldap')."</FONT>";
		echo "<P>";

		$conn = join('', file("inc_connect_install.php3"));
		if ($p = strpos($conn, '?'.'>')) 
			$conn = substr($conn, 0, $p);
		if (!strpos($conn, 'spip_connect_ldap')) {
			$conn .= "function spip_connect_ldap() {\n";
			$conn .= "\t\$GLOBALS['ldap_link'] = @ldap_connect(\"$adresse_ldap\",\"$port_ldap\");\n";
			$conn .= "\t@ldap_bind(\$GLOBALS['ldap_link'],\"$login_ldap\",\"$pass_ldap\");\n";
			$conn .= "\treturn \$GLOBALS['ldap_link'];\n";
			$conn .= "}\n";
			$conn .= "\$GLOBALS['ldap_base'] = \"$base_ldap\";\n";
			$conn .= "\$GLOBALS['ldap_present'] = true;\n";
		}
		$conn .= "?".">";
		$myFile = fopen("inc_connect_install.php3", "wb");
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<p><FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap5'>";
		echo "<fieldset><label><B>"._T('info_statut_utilisateurs_1')."</B></label><BR>";
		echo _T('info_statut_utilisateurs_2')." ";
		echo "<p>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"6forum\" id='visit'>";
		echo "<label for='visit'><b>"._T('info_visiteur_1')."</b></label> "._T('info_visiteur_2')."<br>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"1comite\" id='redac' CHECKED>";
		echo "<label for='redac'><b>"._T('info_redacteur_1')."</b></label> "._T('info_redacteur_2')."<br>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"0minirezo\" id='admin'>";
		echo "<label for='admin'><b>"._T('info_administrateur_1')."</b></label> "._T('info_administrateur_2')."<br>";
	
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";

		echo "</FORM>";
	}

	install_fin_html();
}

else if ($etape == 'ldap3') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_chemin_acces_1')."</FONT>";

	echo "<P>"._T('info_chemin_acces_2');

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap4'>";
	echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
	echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";

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
					echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label><BR>\n";
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

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	install_fin_html();

}

else if ($etape == 'ldap2') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('titre_connexion_ldap')."</FONT>";

	echo "<P>";

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	$r = @ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	if ($ldap_link && ($r || !$login_ldap)) {
		echo "<B>"._T('info_connexion_ldap_ok');

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_ldap_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_ldap_echec_2');
		echo "<br>"._T('avis_connexion_ldap_echec_3');
	}

	install_fin_html();

}

else if ($etape == 'ldap1') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('titre_connexion_ldap')."</FONT>";

	echo "<P>"._T('entree_informations_connexion_ldap');

	$adresse_ldap = 'localhost';
	$port_ldap = 389;

	// Recuperer les anciennes donnees (si presentes)
	if (file_exists("inc_connect_install.php3")) {
		$s = @join('', @file("inc_connect_install.php3"));
		if (ereg('ldap_connect\("(.*)","(.*)"\)', $s, $regs)) {
			$adresse_ldap = $regs[1];
			$port_ldap = $regs[2];
		}
	}

	echo "<p><FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap2'>";
	echo "<fieldset><label><B>"._T('entree_adresse_annuaire')."</B><BR></label>";
	echo _T('texte_adresse_annuaire_1')."<BR>";
	echo "<INPUT TYPE='text' NAME='adresse_ldap' CLASS='formo' VALUE=\"$adresse_ldap\" SIZE='20'><P>";

	echo "<label><B>"._T('entree_port_annuaire')."</B><BR></label>";
	echo _T('texte_port_annuaire')."<BR>";
	echo "<INPUT TYPE='text' NAME='port_ldap' CLASS='formo' VALUE=\"$port_ldap\" SIZE='20'><P></fieldset>";

	echo "<p><fieldset>";
	echo _T('texte_acces_ldap_anonyme_1')." ";
	echo "<label><B>"._T('entree_login_ldap')."</B><BR></label>";
	echo _T('texte_login_ldap_1')."<br>";
	echo "<INPUT TYPE='text' NAME='login_ldap' CLASS='formo' VALUE=\"\" SIZE='40'><P>";

	echo "<label><B>"._T('entree_passe_ldap')."</B><BR></label>";
	echo "<INPUT TYPE='password' NAME='pass_ldap' CLASS='formo' VALUE=\"\" SIZE='40'></fieldset>";

	echo "<p><DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";

	install_fin_html();

}


?>
