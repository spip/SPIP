<?php

include ("inc_version.php3");
//include_ecrire ("inc_db_mysql.php3");
include_ecrire ("inc_presentation.php3");

if (file_exists("inc_connect.php3")) {
	install_debut_html();
	echo "<P><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4><B>Espace interdit</B><p>SPIP est d&eacute;j&agrave; install&eacute;.</FONT>";
	install_fin_html();
	exit;
}

include_ecrire ("inc_acces.php3");
include_ecrire ("inc_base.php3");


//
// Etapes de l'installation standard
//

if ($etape == 6) {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Derni&egrave;re &eacute;tape : <B>C'est termin&eacute; !</B></FONT>";
	echo "<P>";
	echo "<B>N'oubliez pas vos propres codes d'acc&egrave;s&nbsp;!</B>";
	echo "<P>Vous pouvez maintenant commencer &agrave; utiliser le syst&egrave;me de publication assist&eacute;...";

	include_ecrire ("inc_connect_install.php3");

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
		spip_query($query);

		// inserer email comme email webmaster principal
		include_ecrire('inc_meta.php3');
		ecrire_meta('email_webmaster', $email);
		ecrire_metas();
	}

	ecrire_acces();

	$protec = "deny from all\n";
	$myFile = fopen("data/.htaccess", "w");
	fputs($myFile, $protec);
	fclose($myFile);

	@unlink("inc_meta_cache.php3");
	if (!@rename("inc_connect_install.php3", "inc_connect.php3")) {
		copy("inc_connect_install.php3", "inc_connect.php3");
		@unlink("inc_connect_install.php3");
	}

	echo "<FORM ACTION='index.php3' METHOD='post'>";
	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
	echo "</FORM>";

	install_fin_html();

}

else if ($etape == 5) {
	install_debut_html();

	include_ecrire ("inc_connect_install.php3");

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Cinqui&egrave;me &eacute;tape : <B>Informations personnelles</B></FONT>";
	echo "<P>";

	echo "<b>Le syst&egrave;me va maintenant vous cr&eacute;er un acc&egrave;s personnalis&eacute; au site.</b>";
	echo aide ("install5");
	echo "<p>(Note : s'il s'agit d'une r&eacute;installation, et que votre acc&egrave;s marche toujours, vous pouvez ";
	echo "laisser ces champs vides)";

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='6'>";

	echo "<fieldset><label><B>Votre identit&eacute; publique...</B><BR></label>";
	echo "<B>Signature</B><BR>";
	echo "(Votre nom ou votre pseudo)<BR>";
	echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40'><P>";

	echo "<B>Votre adresse email</B><BR>";
	echo "<INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"$email\" SIZE='40'></fieldset><P>\n";

	echo "<fieldset><label><B>Vos identifiants de connexion...</B><BR></label>";
	echo "<B>Votre login</B><BR>";
	echo "(Plus de 3 caract&egrave;res)<BR>";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40'><P>\n";

	echo "<B>Votre mot de passe</B> <BR>";
	echo "(Plus de 5 caract&egrave;res)<BR>";
	echo "<INPUT TYPE='text' NAME='pass' CLASS='formo' VALUE=\"$pass\" SIZE='40'></fieldset><P>\n";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
	echo "</FORM>";
	echo "<p>";

	if ($flag_ldap AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>Authentification externe</b>";
		echo "<p>Si vous avez acc&egrave;s &agrave; un annuaire (LDAP), vous pouvez l'utiliser pour ";
		echo "importer automatiquement des utilisateurs sous SPIP.";
		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap1'>";
		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE=\"Ajouter l'acc&egrave;s &agrave; LDAP >>\">";
		echo "</FORM>";
	}

	install_fin_html();

}

else if ($etape == 4) {

	install_debut_html();

	// Necessaire pour appeler les fonctions SQL wrappees
	include_ecrire("inc_db_mysql.php3");

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Quatri&egrave;me &eacute;tape : <B>Cr&eacute;ation des tables de la base</B></FONT>";
	echo "<P>";

	$link = mysql_connect("$adresse_db", "$login_db", "$pass_db");

	echo "<"."!--";

	if ($choix_db == "new_spip") {
		$sel_db = $table_new;
		mysql_create_db($sel_db);
	}
	else {
		$sel_db = $choix_db;
	}
	mysql_select_db("$sel_db");

	creer_base();
	maj_base();

	$query = "SELECT COUNT(*) FROM spip_articles";
	$result = spip_query_db($query);
	$result_ok = (spip_num_rows($result) > 0);

	echo "-->";


	if ($result_ok) {
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

		echo "<B>La structure de votre base de donn&eacute;es est install&eacute;e.</B><P>Vous pouvez passer &agrave; l'&eacute;tape suivante.";

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";

		echo "</FORM>";
	}
	else {
		echo "<B>L'op&eacute;ration a &eacute;chou&eacute;.</B> Retournez &agrave; la page pr&eacute;c&eacute;dente, s&eacute;lectionnez une autre base ou cr&eacute;ez-en une nouvelle. V&eacute;rifiez les informations fournies par votre h&eacute;bergeur.";
	}

	install_fin_html();

}

else if ($etape == 3) {

	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Troisi&egrave;me &eacute;tape : <B>Choix de votre base</B></FONT>";

	echo aide ("install2");
	echo "<P>";

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='4'>";
	echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
	echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
	echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><label><B>Choisissez votre base :</B><BR></label>";

	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<B>Le serveur MySQL contient plusieurs bases de donn&eacute;es.</B><P> <B>S&eacute;lectionnez</B> ci-apr&egrave;s celle qui vous a &eacute;t&eacute; attribu&eacute;e par votre h&eacute;bergeur:";
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
		echo "ou... ";
	}
	else {
		echo "<B>Le programme d'installation n'a pas pu lire les noms des bases de donn&eacute;es install&eacute;es.</B>
		Soit aucune base n'est disponible, soit la fonction permettant de lister les bases a &eacute;t&eacute; d&eacute;sactiv&eacute;e
		pour des raisons de s&eacute;curit&eacute; (ce qui est le cas chez de nombreux h&eacute;bergeurs).<P>";
		if ($login_db) {
			echo "Dans la seconde alternative, il est probable qu'une base portant votre nom de login soit utilisable&nbsp;:";
			echo "<UL>";
			echo "<INPUT NAME=\"choix_db\" VALUE=\"".$login_db."\" TYPE=Radio id='stand' CHECKED>";
			echo "<label for='stand'>".$login_db."</label><BR>\n";
			echo "</UL>";
			echo "ou... ";
			$checked = true;
		}
	}
	echo "<INPUT NAME=\"choix_db\" VALUE=\"new_spip\" TYPE=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo "> <label for='nou'><B>Cr&eacute;er</B> une nouvelle base de donn&eacute;es&nbsp;:</label> ";
	echo "<INPUT TYPE='text' NAME='table_new' CLASS='fondo' VALUE=\"spip\" SIZE='20'></fieldset><P>";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";


	echo "</FORM>";

	install_fin_html();

}

else if ($etape == 2) {

	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Deuxi&egrave;me &eacute;tape : <B>Essai de connexion &agrave; la base</B></FONT>";

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	echo "<P>";

	if (($db_connect=="0") && $link){
		echo "<B>La connexion a r&eacute;ussi.</B><P> Vous pouvez passer &agrave; l'&eacute;tape suivante.";

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>La connexion au serveur MySQL a &eacute;chou&eacute;.</B>";
		echo "<P>Revenez &agrave; la page pr&eacute;c&eacute;dente, et v&eacute;rifiez les informations que vous avez fournies.";
		echo "<P><FONT SIZE=2><B>N.B.</B> Sur de nombreux serveurs, vous devez <B>demander</B> l'activation de votre acc&egrave;s &agrave; la base MySQL avant de pouvoir l'utiliser. Si vous ne pouvez vous connecter, v&eacute;rifiez que vous avez effectu&eacute; cette d&eacute;marche.</FONT>";
	}

	install_fin_html();

}
else if ($etape == 1) {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Premi&egrave;re &eacute;tape : <B>Votre connexion MySQL</B></FONT>";

	echo "<P>Consultez les informations fournies par votre h&eacute;bergeur&nbsp;: vous devez y trouver, si votre h&eacute;bergeur supporte MySQL, les codes de connexion au serveur MySQL.";

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
	echo "<fieldset><label><B>Adresse de la base de donn&eacute;es</B><BR></label>";
	echo "(Souvent cette adresse correspond &agrave; celle de votre site, parfois elle correspond &agrave; la mention &laquo;localhost&raquo;, parfois elle est laiss&eacute;e totalement vide.)<BR>";
	echo "<INPUT TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"$adresse_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>Le login de connexion</B><BR></label>";
	echo "(Correspond parfois &agrave; votre login d'acc&egrave;s au FTP; parfois laiss&eacute; vide)<BR>";
	echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>Le mot de passe de connexion</B><BR></label>";
	echo "(Correspond parfois &agrave; votre mot de passe pour le FTP; parfois laiss&eacute; vide)<BR>";
	echo "<INPUT TYPE='password' NAME='pass_db' CLASS='formo' VALUE=\"$pass_db\" SIZE='40'></fieldset><P>";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";


	echo "</FORM>";

	install_fin_html();

}
else if (!$etape) {
	header("Location: ../spip_test_dirs.php3");
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

	echo "<B>L'authentification LDAP est install&eacute;e.</B>";
	echo "<P>Vous pouvez maintenant terminer la proc&eacute;dure d'installation standard.";

	echo "<FORM ACTION='install.php3' METHOD='post'>";
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";

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
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Options : <B>Chemin d'acc&egrave;s dans l'annuaire</B></FONT>";
		echo "<P>";

		echo "<B>L'op&eacute;ration a &eacute;chou&eacute;.</B> Le chemin que vous avez choisi (<tt>".htmlspecialchars($base_ldap);
		echo "</tt>) ne semble pas valide. Veuillez retourner &agrave; la page pr&eacute;c&eacute;dente ";
		echo "et v&eacute;rifier les informations fournies.";
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Options : <B>R&eacute;glage de l'importation LDAP</B></FONT>";
		echo "<P>";

		$conn = join('', file("inc_connect_install.php3"));
		$conn = split('\?'.'\>', $conn, 2);
		$conn = $conn[0];
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
		echo "<fieldset><label><B>Statut par d&eacute;faut des utilisateurs import&eacute;s</B></label><BR>";
		echo "Choisissez le statut qui est attribu&eacute; aux personnes pr&eacute;sentes dans ";
		echo "l'annuaire LDAP lorsqu'elles se connectent pour la première fois. ";
		echo "Vous pourrez par la suite modifier cette valeur pour chaque auteur au cas par cas. ";
		echo "<p>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"6forum\" id='visit'>";
		echo "<label for='visit'><b>Visiteur</b></label> du site public<br>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"1comite\" id='redac' CHECKED>";
		echo "<label for='redac'><b>R&eacute;dacteur</b></label> ayant acc&egrave;s &agrave; l'espace priv&eacute; (<i>recommand&eacute;</i>)<br>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"0minirezo\" id='admin'>";
		echo "<label for='admin'><b>Administrateur</b></label> du site (<i>utilisez avec pr&eacute;caution</i>)<br>";
	
		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";

		echo "</FORM>";
	}

	install_fin_html();
}

else if ($etape == 'ldap3') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Options : <B>Chemin d'acc&egrave;s dans l'annuaire</B></FONT>";

	echo "<P>Vous devez d&eacute;sormais configurer le chemin d'acc&egrave;s aux informations dans l'annuaire. ";
	echo "Cette information est indispensable pour lire les profils utilisateurs stock&eacute;s dans l'annuaire. ";

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
		echo "<P><b>S&eacute;lectionnez</b> ci-apr&egrave;s le chemin d'acc&egrave;s dans l'annuaire&nbsp;:";
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
		echo "ou... ";
	}
	echo "<INPUT NAME=\"base_ldap\" VALUE=\"\" TYPE='Radio' id='manuel'";
	if (!$checked) {
		echo " CHECKED";
		$checked = true;
	}
	echo ">";
	echo "<label for='manuel'><B>Entrer</B> le chemin d'acc&egrave;s&nbsp;:</label> ";
	echo "<INPUT TYPE='text' NAME='base_ldap_text' CLASS='formo' VALUE=\"ou=users, dc=mon-domaine, dc=com\" SIZE='40'></fieldset><P>";

	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
	echo "</FORM>";

	install_fin_html();

}

else if ($etape == 'ldap2') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Options : <B>Votre connexion LDAP</B></FONT>";

	echo "<P>";

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	$r = @ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	if ($ldap_link && ($r || !$login_ldap)) {
		echo "<B>La connexion LDAP a r&eacute;ussi.</B><P> Vous pouvez passer &agrave; l'&eacute;tape suivante.";

		echo "<FORM ACTION='install.php3' METHOD='post'>";
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>La connexion au serveur LDAP a &eacute;chou&eacute;.</B>";
		echo "<P>Revenez &agrave; la page pr&eacute;c&eacute;dente, et v&eacute;rifiez les informations que vous avez fournies. ";
		echo "<br>Alternativement, n'utilisez pas le support LDAP pour importer des utilisateurs.";
	}

	install_fin_html();

}

else if ($etape == 'ldap1') {
	install_debut_html();

	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Options : <B>Votre connexion LDAP</B></FONT>";

	echo "<P>Veuillez entrer dans ce formulaire les informations de connexion &agrave; votre annuaire LDAP. ";
	echo "Ces informations doivent pouvoir vous &ecirc;tre fournies par l'administrateur du syst&egrave;me ";
	echo "ou du r&eacute;seau.";

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
	echo "<fieldset><label><B>Adresse de l'annuaire</B><BR></label>";
	echo "(Si votre annuaire est install&eacute; sur la m&ecirc;me machine que ce site Web, il s'agit ";
	echo "probablement de &laquo;localhost&raquo;.)<BR>";
	echo "<INPUT TYPE='text' NAME='adresse_ldap' CLASS='formo' VALUE=\"$adresse_ldap\" SIZE='20'><P>";

	echo "<label><B>Le num&eacute;ro de port de l'annuaire</B><BR></label>";
	echo "(La valeur indiqu&eacute;e par d&eacute;faut convient g&eacute;n&eacute;ralement.)<BR>";
	echo "<INPUT TYPE='text' NAME='port_ldap' CLASS='formo' VALUE=\"$port_ldap\" SIZE='20'><P></fieldset>";

	echo "<p><fieldset>";
	echo "Certains serveurs LDAP n'acceptent aucun acc&egrave;s anonyme. Dans ce cas ";
	echo "il faut sp&eacute;cifier un identifiant d'acc&egrave;s initial afin de pouvoir ";
	echo "ensuite rechercher des informations dans l'annuaire. Dans la plupart des cas ";
	echo "n&eacute;anmoins, les champs suivants pourront &ecirc;tre laiss&eacute;s vides.<p>";
	echo "<label><B>Login LDAP initial</B><BR></label>";
	echo "(Laisser vide pour un acc&egrave;s anonyme, ou entrer le chemin complet, ";
	echo "par exemple &laquo;&nbsp;<tt>uid=dupont, ou=users, dc=mon-domaine, dc=com</tt>&nbsp;&raquo;.)<br>";
	echo "<INPUT TYPE='text' NAME='login_ldap' CLASS='formo' VALUE=\"\" SIZE='40'><P>";

	echo "<label><B>Mot de passe</B><BR></label>";
	echo "<INPUT TYPE='password' NAME='pass_ldap' CLASS='formo' VALUE=\"\" SIZE='40'></fieldset>";

	echo "<p><DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";

	echo "</FORM>";

	install_fin_html();

}


?>
