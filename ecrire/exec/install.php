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

if (!defined("_ECRIRE_INC_VERSION")) return;

define("_ECRIRE_INSTALL", "1");
define('_FILE_TMP', '_install');
include_spip('inc/minipres');
include_spip('base/create');
include_spip('base/db_mysql');

function exec_install_dist()
{
	global $etape;
	if (_FILE_CONNECT && $etape != 'unpack') {
		install_debut_html();
		echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=4>"._T('avis_espace_interdit')."</FONT>";

		install_fin_html();
		exit;
	}
	@unlink(_FILE_META);
	unset($GLOBALS['meta']);
	$fonc = 'install_' . $etape;
	if (function_exists($fonc))
		$fonc();
	else {
		spip_log("install: etape '$etape' inconnue");
		phpinfo();
	}
}


//
// Verifier que l'hebergement est compatible SPIP ... ou l'inverse :-)
// (sert a l'etape 1 de l'installation)
function tester_compatibilite_hebergement() {
	$err = array();

	$p = phpversion();
	if (ereg('^([0-9]+)\.([0-9]+)\.([0-9]+)', $p, $regs)) {
		$php = array($regs[1], $regs[2], $regs[3]);
		$m = '4.0.8';
		$min = explode('.', $m);
		if ($php[0]<$min[0]
		OR ($php[0]==$min[0] AND $php[1]<$min[1])
		OR ($php[0]==$min[0] AND $php[1]==$min[1] AND $php[2]<$min[2]))
			$err[] = _L("PHP version $p insuffisant (minimum = $m)");
	}

	if (!function_exists('mysql_query'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/mysql'>MYSQL</a>";

	if (!function_exists('preg_match_all'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/pcre'>PCRE</a>";

	if ($a = @ini_get('mbstring.func_overload'))
		$err[] = _T('install_extension_mbstring')
		. "mbstring.func_overload=$a - <a href='http://se.php.net/mb_string'>mb_string</a>.<br /><small>";

	if ($err) {
			echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=4><b>"._T('avis_attention').'</b> <p>'._T('install_echec_annonce')."</p></FONT>";
		while (list(,$e) = each ($err))
			echo "<li>$e</li>\n";

		# a priori ici on pourrait die(), mais il faut laisser la possibilite
		# de forcer malgre tout (pour tester, ou si bug de detection)
		echo "<p /><hr />\n";
	}
}


// Une fonction pour faciliter la recherche du login (superflu ?)
function login_hebergeur() {
	global $HTTP_X_HOST, $REQUEST_URI, $SERVER_NAME, $HTTP_HOST;

	$base_hebergeur = 'localhost'; # par defaut

	// Lycos (ex-Multimachin)
	if ($HTTP_X_HOST == 'membres.lycos.fr') {
		ereg('^/([^/]*)', $REQUEST_URI, $regs);
		$login_hebergeur = $regs[1];
	}
	// Altern
	else if (ereg('altern\.com$', $SERVER_NAME)) {
		ereg('([^.]*\.[^.]*)$', $HTTP_HOST, $regs);
		$login_hebergeur = ereg_replace('[^a-zA-Z0-9]', '_', $regs[1]);
	}
	// Free
	else if (ereg('(.*)\.free\.fr$', $SERVER_NAME, $regs)) {
		$base_hebergeur = 'sql.free.fr';
		$login_hebergeur = $regs[1];
	}

	return array($base_hebergeur, $login_hebergeur);
}


function install_6()
{
	global $email,$login,$nom,$pass,$spip_lang_right;

	install_debut_html();


	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_derniere_etape')."</B></FONT>";
	echo "<P>";
	echo "<B>"._T('info_code_acces')."</B>";
	echo "<P>"._T('info_utilisation_spip');

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	# maintenant on connait le vrai charset du site s'il est deja configure
	# sinon par defaut inc/meta reglera _DEFAULT_CHARSET
	# (les donnees arrivent de toute facon postees en _DEFAULT_CHARSET)
	include_spip('inc/meta');
	lire_metas();

	if ($login) {
		include_spip('inc/charsets');

		$nom = addslashes(importer_charset($nom, _DEFAULT_CHARSET));
		$login = addslashes(importer_charset($login, _DEFAULT_CHARSET));
		$email = addslashes(importer_charset($email, _DEFAULT_CHARSET));
		# pour le passwd, bizarrement il faut le convertir comme s'il avait
		# ete tape en iso-8859-1 ; car c'est en fait ce que voit md5.js
		$pass = unicode2charset(utf_8_to_unicode($pass), 'iso-8859-1');
		$result = spip_query("SELECT id_auteur FROM spip_auteurs WHERE login='$login'");

		unset($id_auteur);
		while ($row = spip_fetch_array($result)) $id_auteur = $row['id_auteur'];

		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);

		if ($id_auteur) {
			spip_query("UPDATE spip_auteurs SET nom='$nom', email='$email', login='$login', pass='$mdpass', alea_actuel='', alea_futur=FLOOR(32000*RAND()), htpass='$htpass', statut='0minirezo' WHERE id_auteur=$id_auteur");
		}
		else {
			spip_query("INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES('$nom','$email','$login','$mdpass','$htpass',FLOOR(32000*RAND()),'0minirezo')");
		}

		// inserer email comme email webmaster principal
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('email_webmaster', '".addslashes($email)."')");
	}

	include_spip('inc/config');
	init_config();

	include_spip('inc/acces');
	$htpasswd = _DIR_SESSIONS . _AUTH_USER_FILE;
	@unlink($htpasswd);
	@unlink($htpasswd."-admin");
	ecrire_acces();

	if (!@rename(_FILE_CONNECT_INS . _FILE_TMP . '.php',
		    _FILE_CONNECT_INS . '.php')) {
		copy(_FILE_CONNECT_INS . _FILE_TMP . '.php', 
		     _FILE_CONNECT_INS . '.php');
		@unlink(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	}

	echo "<form action='./' method='post'>";
	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	ecrire_metas();

	install_fin_html();
}

function install_5()
{
	global $email, $ldap_present, $login, $nom, $pass, $spip_lang_right;

	install_debut_html();

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_informations_personnelles')."</FONT>";
	echo "<P>\n";

	echo "<b>"._T('texte_informations_personnelles_1')."</b>";
	echo aide ("install5");
	echo "<p>\n"._T('texte_informations_personnelles_2')." ";
	echo _T('info_laisser_champs_vides');

	echo generer_url_post_ecrire('install');

	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='6'>";

	echo "<fieldset><label><B>"._T('info_identification_publique')."</B><BR />\n</label>";
	echo "<B>"._T('entree_signature')."</B><BR />\n";
	echo _T('entree_nom_pseudo_1')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40'><P>\n";

	echo "<B>"._T('entree_adresse_email')."</B><BR />\n";
	echo "<INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"$email\" SIZE='40'></fieldset><P>\n";

	echo "<fieldset><label><B>"._T('entree_identifiants_connexion')."</B><BR />\n</label>";
	echo "<B>"._T('entree_login')."</B><BR />\n";
	echo _T('info_plus_trois_car')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40'><P>\n";

	echo "<B>"._T('entree_mot_passe')."</B> <BR />\n";
	echo _T('info_plus_cinq_car_2')."<BR />\n";
	echo "<INPUT TYPE='password' NAME='pass' CLASS='formo' VALUE=\"$pass\" SIZE='40'></fieldset><P>\n";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";
	echo "<p>\n";

	if (function_exists('ldap_connect') AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>"._T('info_authentification_externe')."</b>";
		echo "<p>\n"._T('texte_annuaire_ldap_1');
		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap1'>";
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE=\""._T('bouton_acces_ldap')."\">";
		echo "</FORM>";
	}

	install_fin_html();
}

function install_4()
{
	global $adresse_db, $choix_db, $login_db, $pass_db, $spip_lang_right, $spip_version, $table_new;

	install_debut_html();

	// Necessaire pour appeler les fonctions SQL wrappees
	include_spip('base/db_mysql');

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_creation_tables')."</FONT>";
	echo "<P>\n";

	$link = mysql_connect("$adresse_db", "$login_db", "$pass_db");

	echo "<"."!-- $link ";

	if ($choix_db == "new_spip") {
		$sel_db = $table_new;
		mysql_query("CREATE DATABASE `$sel_db`");
	}
	else {
		$sel_db = $choix_db;
	}
	echo "$sel_db ";
	mysql_select_db($sel_db);

	// Message pour spip_query : tout va bien !
	$GLOBALS['db_ok'] = true;
	$GLOBALS['spip_connect_version'] = 0.3;

	// Test si SPIP deja installe
	spip_query("SELECT COUNT(*) FROM spip_meta");
	$nouvelle = spip_sql_errno();
	creer_base();

	$maj_ok = maj_base();

	// Tester $mysql_rappel_nom_base
	$GLOBALS['mysql_rappel_nom_base'] = true;
	$GLOBALS['spip_mysql_db'] = $sel_db;
	$ok_rappel_nom = spip_query("INSERT INTO spip_meta (nom,valeur)
		VALUES ('mysql_rappel_nom_base', 'test')");
	if ($ok_rappel_nom) {
		echo " (ok rappel nom base `$sel_db`.spip_meta) ";
		$ligne_rappel = '';
		spip_query("DELETE FROM spip_meta WHERE nom='mysql_rappel_nom_base'");
	} else {
		echo " (erreur rappel nom base `$sel_db`.spip_meta) ";
		$GLOBALS['mysql_rappel_nom_base'] = false;
		$ligne_rappel = "\$GLOBALS['mysql_rappel_nom_base'] = false; ".
		"/* echec du test sur `$sel_db`.spip_meta lors de l'installation. */\n";
	}

	if ($nouvelle) {
		spip_query("INSERT INTO spip_meta (nom, valeur) VALUES ('nouvelle_install', 'oui')");
		$result_ok = !spip_sql_errno();
	} else {
	  // en cas de reinstall sur mise a jour mal passee
	  	spip_query("DELETE FROM spip_meta WHERE nom='debut_restauration'");
		$result = spip_query("SELECT COUNT(*) FROM spip_articles");
		$result_ok = (spip_num_rows($result) > 0);
	}
	echo "($result_ok && $maj_ok) -->";


	if ($result_ok && $maj_ok) {
		if (preg_match(',(.*):(.*),', $adresse_db, $r))
			list(,$adresse_db, $port) = $r;
		else
			$port = '';
		$conn = "<"."?php\n";
		$conn .= "if (!defined(\"_ECRIRE_INC_VERSION\")) return;\n";
		$conn .= "\$GLOBALS['spip_connect_version'] = 0.3;\n";
		$conn .= $ligne_rappel;
		$conn .= "spip_connect_db("
			. "'$adresse_db','$port','$login_db','$pass_db','$sel_db'"
			. ");\n";
		$conn .= "?".">";

		if (!ecrire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php',
		$conn))
			redirige_par_entete(generer_url_ecrire('install'));

		echo "<B>"._T('info_base_installee')."</B><P>\n"._T('info_etape_suivante_1');

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

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

function install_3()
{
	global $adresse_db, $login_db, $pass_db, $spip_lang_right;

	install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_choix_base')." <B>"._T('menu_aide_installation_choix_base')."</B></FONT>";

	echo aide ("install2");
	echo "<P>\n";

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='4'>";
	echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
	echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
	echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>\n";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><label><B>"._T('texte_choix_base_1')."</B><BR />\n</label>";

	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<B>"._T('texte_choix_base_2')."</B><P> "._T('texte_choix_base_3');
		echo "<UL>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<INPUT NAME=\"choix_db\" VALUE=\"".$table_nom."\" TYPE=Radio id='tab$i'";
			$base_fin = "><label for='tab$i'>".$table_nom."</label><BR />\n\n";
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
			// Si un login comporte un point, le nom de la base est plus
			// probablement le login sans le point -- testons pour savoir
			$test_base = $login_db;
			$ok = @mysql_select_db($test_base);
			$test_base2 = str_replace('.', '_', $test_base);
			if (@mysql_select_db($test_base2)) {
				$test_base = $test_base2;
				$ok = true;
			}
			
			if ($ok) {
				echo _T('avis_lecture_noms_bases_3');
				echo "<UL>";
				echo "<INPUT NAME=\"choix_db\" VALUE=\"".$test_base."\" TYPE=Radio id='stand' CHECKED>";
				echo "<label for='stand'>".$test_base."</label><BR />\n";
				echo "</UL>";
				echo _T('info_ou')." ";
				$checked = true;
			}
		}
	}
	echo "<INPUT NAME=\"choix_db\" VALUE=\"new_spip\" TYPE=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo "> <label for='nou'>"._T('info_creer_base')."</label> ";
	echo "<INPUT TYPE='text' NAME='table_new' CLASS='fondl' VALUE=\"spip\" SIZE='20'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";


	echo "</FORM>";

	install_fin_html();
}

function install_2()
{
	global $adresse_db, $login_db, $pass_db, $spip_lang_right;

	install_debut_html();



	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_connexion_base')."</FONT>";

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	echo "<P>";

	if (($db_connect=="0") && $link){
		echo "<B>"._T('info_connexion_ok')."</B><P> "._T('info_etape_suivante_2');

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_echec_2');
		echo "<P><FONT SIZE=2>"._T('avis_connexion_echec_3')."</FONT>";
	}

	install_fin_html();
}

function install_1()
{
	global $spip_lang_right;

	install_debut_html();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_connexion_mysql')."</FONT>";

	echo "<P>"._T('texte_connexion_mysql');

	echo aide ("install1");

	list($adresse_db, $login_db) = login_hebergeur();
	$pass_db = '';

	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
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

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='2'>";
	echo "<fieldset><label><B>"._T('entree_base_donnee_1')."</B><BR />\n</label>";
	echo _T('entree_base_donnee_2')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"$adresse_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_login_connexion_1')."</B><BR />\n</label>";
	echo _T('entree_login_connexion_2')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_db\" SIZE='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_mot_passe_1')."</B><BR />\n</label>";
	echo _T('entree_mot_passe_2')."<BR />\n";
	echo "<INPUT TYPE='password' NAME='pass_db' CLASS='formo' VALUE=\"$pass_db\" SIZE='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";


	echo "</FORM>";

	install_fin_html();
}

function install_()
{
	global $spip_lang_right;

	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues)
		redirige_par_entete(generer_url_action('test_dirs'));
	else {
		install_debut_html();
	
		echo "<p align='center'><img src='" . _DIR_IMG_PACK . "logo-spip.gif'></p>",
		  "<p style='text-align: center; font-family: Verdana,Arial,Sans,sans-serif; font-size: 10px;'>",
		 info_copyright(),
		  "</p>",
		  "<p>" . _T('install_select_langue'),
		  "<p><div align='center'>",
		  $menu_langues,
		  "</div>",
		  "<p><form action='", generer_url_action('test_dirs'),
		  "'>",
		  '<input type="hidden" name="action" value="test_dirs" />',
		  "<div align='$spip_lang_right'><input type='submit' class='fondl'  VALUE='",
		  _T('bouton_suivant'),
		  " >>'>",
		  "</form>";
		install_fin_html();
	}
}

function install_ldap5()
{
	global $spip_lang_right, $statut_ldap;

	install_debut_html();

	include_once(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	include_spip('inc/meta');
	ecrire_meta("ldap_statut_import", $statut_ldap);
	ecrire_metas();

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='5'>";

	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";
}

function install_ldap4()
{
  global $adresse_ldap, $base_ldap, $base_ldap_text, $login_ldap, $pass_ldap, $port_ldap, $spip_lang_right;

	install_debut_html();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_chemin_acces_annuaire')."</B></FONT>";
		echo "<P>";

		echo "<B>"._T('avis_operation_echec')."</B> "._T('avis_chemin_invalide_1')." (<tt>".htmlspecialchars($base_ldap);
		echo "</tt>) "._T('avis_chemin_invalide_2');
	}
	else {
		echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_reglage_ldap')."</FONT>";
		echo "<P>";

		lire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);
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
		ecrire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap5'>";
		echo "<fieldset><label><B>"._T('info_statut_utilisateurs_1')."</B></label><BR />\n";
		echo _T('info_statut_utilisateurs_2')." ";
		echo "<p>";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"6forum\" id='visit'>";
		echo "<label for='visit'><b>"._T('info_visiteur_1')."</b></label> "._T('info_visiteur_2')."<br />\n";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"1comite\" id='redac' CHECKED>";
		echo "<label for='redac'><b>"._T('info_redacteur_1')."</b></label> "._T('info_redacteur_2')."<br />\n";
		echo "<INPUT TYPE='Radio' NAME='statut_ldap' VALUE=\"0minirezo\" id='admin'>";
		echo "<label for='admin'><b>"._T('info_administrateur_1')."</b></label> "._T('info_administrateur_2')."<br />\n";
	
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";

		echo "</FORM>";
	}

	install_fin_html();
}

function install_ldap3()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $spip_lang_right;

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

function install_ldap2()
{
	global $adresse_ldap, $login_ldap, $pass_ldap, $port_ldap, $spip_lang_right;

   install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('titre_connexion_ldap')."</FONT>";

	echo "<P>";

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	$r = @ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	if ($ldap_link && ($r || !$login_ldap)) {
		echo "<B>"._T('info_connexion_ldap_ok');

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_ldap' VALUE=\"$adresse_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='port_ldap' VALUE=\"$port_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='login_ldap' VALUE=\"$login_ldap\">";
		echo "<INPUT TYPE='hidden' NAME='pass_ldap' VALUE=\"$pass_ldap\">";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_ldap_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_ldap_echec_2');
		echo "<br />\n"._T('avis_connexion_ldap_echec_3');
	}

	install_fin_html();
}

function install_ldap1()
{
	global $spip_lang_right;

	install_debut_html();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('titre_connexion_ldap')."</FONT>";

	echo "<P>"._T('entree_informations_connexion_ldap');

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

	echo generer_url_post_ecrire('install');
	echo "<p><INPUT TYPE='hidden' NAME='etape' VALUE='ldap2'>";
	echo "<fieldset><label><B>"._T('entree_adresse_annuaire')."</B><BR />\n</label>";
	echo _T('texte_adresse_annuaire_1')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='adresse_ldap' CLASS='formo' VALUE=\"$adresse_ldap\" SIZE='20'><P>";

	echo "<label><B>"._T('entree_port_annuaire')."</B><BR />\n</label>";
	echo _T('texte_port_annuaire')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='port_ldap' CLASS='formo' VALUE=\"$port_ldap\" SIZE='20'><P></fieldset>";

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

function install_unpack()
{
  global  $connect_id_auteur;

  include_spip('inc/admin');

  $action = _T('texte_unpack');
 
  debut_admin(generer_url_post_ecrire("install"),$action);

  $hash = calculer_action_auteur("unpack");

  fin_admin($action);

	## ??????? a verifier
  if (@file_exists(_DIR_RACINE . "spip_loader" . '.php'))
    redirige_par_entete(generer_url_public("spip_loader"), "?hash=$hash&id_auteur=$connect_id_auteur");
  else if (@file_exists(_DIR_RACINE . "spip_unpack" . '.php'))
    redirige_par_entete(generer_url_public("spip_unpack"), "?hash=$hash&id_auteur=$connect_id_auteur");
  else
    redirige_par_entete(generer_url_public("spip_loader"), "?hash=$hash&id_auteur=$connect_id_auteur");
}

?>
