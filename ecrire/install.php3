<?php

if (!file_exists("inc_connect.php3")) {
	include ("inc_version.php3");
	include_local ("inc_presentation.php3");
	include_local ("inc_acces.php3");
	include_local ("inc_base.php3");

	if ($connect){

	}elseif($etape6){
		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Derni&egrave;re &eacute;tape : <B>C'est termin&eacute; !</B></FONT>";
		echo "<P>";
		echo "<B>N'oubliez pas vos propres codes d'acc&egrave;s&nbsp;!</B>";
		echo "<P>Vous pouvez maintenant commencer &agrave; utiliser le syst&egrave;me de publication assist&eacute;...";

		$link = mysql_connect($adresse_db, $login_db, $pass_db);
		mysql_select_db($sel_db);

		if ($login) {
			$nom = addslashes($nom);
			$query = "SELECT id_auteur FROM spip_auteurs WHERE login=\"$login\"";
			$result = mysql_query($query);
			unset($id_auteur);
			while ($row = mysql_fetch_array($result)) $id_auteur = $row[0];
			
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);

			if ($id_auteur) {
				$query = "UPDATE spip_auteurs SET nom=\"$nom\", email=\"$email\", login=\"$login\", pass=\"$mdpass\", htpass=\"$htpass\", statut=\"0minirezo\" WHERE id_auteur=$id_auteur";
			}
			else {
				$query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, statut) VALUES(\"$nom\",\"$email\",\"$login\",\"$mdpass\",\"$htpass\",\"0minirezo\")";
			}
			mysql_query($query);
		}

		// Si PHP est installe en module Apache, pas besoin de .htaccess
		$ecrire_htaccess = !$php_module;

		if ($ecrire_htaccess) {
			$protec = "AuthUserFile $rootf$htpasswd\nAuthGroupFile /dev/null\nAuthName administrateur\nAuthType Basic\n\n<Limit GET POST PUT>\nrequire valid-user\n</Limit>\n";
			$myFile = fopen($htaccess, "w");
			fputs($myFile, $protec);
			fclose($myFile);
		}

		ecrire_acces();

		$protec = "deny from all\n";
		$myFile = fopen("data/.htaccess", "w");
		fputs($myFile, $protec);
		fclose($myFile);

		$conn = "<?\n";
		$conn .= "if (defined(\"_ECRIRE_INC_CONNECT\")) return;\n";
		$conn .= "define(\"_ECRIRE_INC_CONNECT\", \"1\");\n";
		$conn .= "\$GLOBALS['db_ok'] = true;\n";
		$conn .= "\$GLOBALS['db_ok'] &= !!@mysql_connect(\"$adresse_db\",\"$login_db\",\"$pass_db\");\n";
		$conn .= "\$GLOBALS['db_ok'] &= !!@mysql_select_db(\"$sel_db\");\n";
		$conn .= "?".">";
		$myFile = fopen("inc_connect.php3", "w");
		fputs($myFile, $conn);
		fclose($myFile);

		@unlink("inc_meta_cache.php3");

		echo "<FORM ACTION='index.php3' METHOD='get'>";
		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
		echo "</FORM>";

		install_fin_html();

	}elseif($etape5){
		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Cinqui&egrave;me &eacute;tape : <B>Informations personnelles</B></FONT>";
		echo "<P>";
		
		echo "<b>Le syst&egrave;me va maintenant vous cr&eacute;er un acc&egrave;s personnalis&eacute; au site.</b>";
		echo aide ("install5");
		echo "<p>(Note : s'il s'agit d'une r&eacute;installation, et que votre acc&egrave;s marche toujours, vous pouvez ";
		echo "laisser ces champs vides)";

		echo "<FORM ACTION='install.php3' METHOD='get'>";
		echo "<INPUT TYPE='hidden' NAME='etape6' VALUE='oui'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";
		echo "<INPUT TYPE='hidden' NAME='sel_db' VALUE=\"$sel_db\"><P>";

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
	
		$rootf = $PATH_TRANSLATED;
		$rootf = ereg_replace("\\\\", "/", $rootf);
		$rootf = substr($rootf, 0, strrpos($rootf, "/"))."/";

		// Cas particuliers

		// Multimania
		if ($hebergeur == 'multimania') {
			$compte = substr($login_db, 0, strlen($login_db) - 3);
			$rootf = "/data/perso/".substr($compte,0,1)."/".substr($compte,1,1)."/".substr($compte,2,1)."/$compte/";
			$chemin = $REQUEST_URI;
			if (ereg('^www\.', $HTTP_X_HOST))
				ereg('^/[^/]*/([^?]*/)', $chemin, $regs);
			else
				ereg('^/([^?]*/)', $chemin, $regs);
			$chemin = $regs[1];
			$rootf .= $chemin;
		}

//		echo "<fieldset><legend><B>Le chemin d'acc&egrave;s &agrave; ce dossier sur le serveur</B> <BR></legend>";
//		echo "(Important pour la protection du dossier. Si vous ne savez pas, laissez tel quel.)<BR>";
		echo "<INPUT TYPE='hidden' NAME='rootf' CLASS='forml' VALUE=\"$rootf\" SIZE='40'>";
//		echo "</fieldset><P>\n";

	
		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";
		echo "</FORM>";


		install_fin_html();

	}elseif($etape4){

		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Quatri&egrave;me &eacute;tape : <B>Cr&eacute;ation des tables de la base</B></FONT>";
		echo "<P>";

		$link = mysql_connect("$adresse_db", "$login_db", "$pass_db");


		echo "<"."!--";

		if ($choix_db == "new_spip"){
			$sel_db = $table_new;
			mysql_create_db($sel_db);		
		}
		else{
			$sel_db = $choix_db;
		}
		mysql_select_db("$sel_db");

		creer_base();
		maj_base();

		$query = "SELECT COUNT(*) FROM spip_articles";
		$result = mysql_query($query);
		$result_ok = (mysql_num_rows($result) > 0);

		echo "-->";

		
		if ($result_ok) {
			echo "<B>La structure de votre base de donn&eacute;es est install&eacute;e.</B><P>Vous pouvez passer &agrave; l'&eacute;tape suivante.";

			echo "<FORM ACTION='install.php3' METHOD='get'>";
			echo "<INPUT TYPE='hidden' NAME='etape5' VALUE='oui'>";
			echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
			echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
			echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";
			echo "<INPUT TYPE='hidden' NAME='sel_db' VALUE=\"$sel_db\"><P>";

			echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";

			echo "</FORM>";
		}
		else{
			echo "<B>L'op&eacute;ration a &eacute;chou&eacute;.</B> Retournez &agrave; la page pr&eacute;c&eacute;dente, s&eacute;lectionnez une autre base ou cr&eacute;ez-en une nouvelle. V&eacute;rifiez les informations fournies par votre h&eacute;bergeur.";
		}

		install_fin_html();

	}elseif($etape3){

		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Troisi&egrave;me &eacute;tape : <B>Choix de votre base</B></FONT>";

		echo aide ("install2");
		echo "<P>";

		echo "<FORM ACTION='install.php3' METHOD='get'>";
		echo "<INPUT TYPE='hidden' NAME='etape4' VALUE='oui'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

		$link=mysql_connect("$adresse_db","$login_db","$pass_db");
		$result=@mysql_list_dbs();

		echo "<fieldset><label><B>Choisissez votre base :</B><BR></label>";

		if ($result AND (@mysql_num_rows($result) > 0)) {
			echo "<B>Le serveur mySQL contient plusieurs bases de donn&eacute;es.</B><P> <B>S&eacute;lectionnez</B> ci-apr&egrave;s celle qui vous a &eacute;t&eacute; attribu&eacute;e par votre h&eacute;bergeur:";
			echo "<UL>";	
			$i=0;
			$bases = "";
			while ($i < mysql_num_rows($result)) {
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
				$i++;
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

	}elseif($etape2){

		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Deuxi&egrave;me &eacute;tape : <B>Essai de connexion &agrave; la base</B></FONT>";
	
		echo "<!--";
		$link=mysql_connect("$adresse_db","$login_db","$pass_db");
		$db_connect=mysql_errno();
		echo "-->";
		
		echo "<P>";
		
		if (($db_connect=="0") && $link){
			echo "<B>La connexion a r&eacute;ussi.</B><P> Vous pouvez passer &agrave; l'&eacute;tape suivante.";

			echo "<FORM ACTION='install.php3' METHOD='get'>";
			echo "<INPUT TYPE='hidden' NAME='etape3' VALUE='oui'>";
			echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
			echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
			echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

			echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";


			echo "</FORM>";



		}else{
			echo "<B>La connexion au serveur mySQL a &eacute;chou&eacute;.</B>";
			echo "<P>Revenez &agrave; la page pr&eacute;c&eacute;dente, et v&eacute;rifiez les informations que vous avez fournies.";
			echo "<P><FONT SIZE=2><B>N.B.</B> Sur de nombreux serveurs, vous devez <B>demander</B> l'activation de votre acc&egrave;s &agrave; la base mySQL avant de pouvoir l'utiliser. Si vous ne pouvez vous connecter, v&eacute;rifiez que vous avez effectu&eacute; cette d&eacute;marche.</FONT>";
		}

		install_fin_html();
			
	}elseif($etape1){

		install_debut_html();

		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Premi&egrave;re &eacute;tape : <B>Votre connexion mySQL</B></FONT>";

		echo "<P>Consultez les informations fournies par votre h&eacute;bergeur&nbsp;: vous devez y trouver, si votre h&eacute;bergeur supporte mySQL, les codes de connexion au serveur mySQL.";

		echo aide ("install1");


		echo "<FORM ACTION='install.php3' METHOD='get'>";
		echo "<INPUT TYPE='hidden' NAME='etape2' VALUE='oui'>";
		echo "<fieldset><label><B>Adresse de la base de donn&eacute;es</B><BR></label>";
		echo "(Souvent cette adresse correspond &agrave; celle de votre site, parfois elle correspond &agrave; la mention &laquo;localhost&raquo;, parfois elle est laiss&eacute;e totalement vide.)<BR>";
		echo "<INPUT TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"localhost\" SIZE='40'></fieldset><P>";

		echo "<fieldset><label><B>Le login de connexion</B><BR></label>";
		echo "(Correspond parfois &agrave; votre login d'acc&egrave;s au FTP; parfois laiss&eacute; vide)<BR>";
		echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_hebergeur\" SIZE='40'></fieldset><P>";

		echo "<fieldset><label><B>Le mot de passe de connexion</B><BR></label>";
		echo "(Correspond parfois &agrave; votre mot de passe pour le FTP; parfois laiss&eacute; vide)<BR>";
		echo "<INPUT TYPE='text' NAME='pass_db' CLASS='formo' VALUE=\"\" SIZE='40'></fieldset><P>";

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='Suivant >>'>";


		echo "</FORM>";

		install_fin_html();

	}
	else {
		header("Location: ../spip_test_dirs.php3");
	}
}else{
	install_debut_html();
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4><B>Espace interdit</B></FONT>";
	install_fin_html();
}

?>
</FONT>
</TD></TR></TABLE>
</CENTER>
</BODY>

</HTML>
