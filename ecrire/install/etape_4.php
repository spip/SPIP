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

// http://doc.spip.org/@inc_install_4
function install_etape_4_dist()
{
	global $adresse_db, $choix_db, $login_db, $pass_db, $spip_lang_right, $spip_version, $table_new;

	install_debut_html();

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
	spip_query("SELECT COUNT(*) FROM spip_meta");
	$nouvelle = spip_sql_errno();
	creer_base();
	include_spip('base/upgrade');
	maj_base();
	
	// Tester $mysql_rappel_nom_base
	$GLOBALS['mysql_rappel_nom_base'] = true;
	$GLOBALS['spip_mysql_db'] = $sel_db;
	$ok_rappel_nom = spip_query("INSERT INTO spip_meta (nom,valeur) VALUES ('mysql_rappel_nom_base', 'test')");
	if ($ok_rappel_nom) {
		echo " (ok rappel nom base `$sel_db`.spip_meta) ";
		$ligne_rappel = '';
		spip_query("DELETE FROM spip_meta WHERE nom='mysql_rappel_nom_base'");
	} else {
		echo " (erreur rappel nom base `$sel_db`.spip_meta $nouvelle) ";
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
	echo "($result_ok) -->";

	if ($result_ok) {
		if (preg_match(',(.*):(.*),', $adresse_db, $r))
			list(,$adresse_db, $port) = $r;
		else
			$port = '';
		$conn = "<"."?php\n";
		$conn .= "if (!defined(\"_ECRIRE_INC_VERSION\")) return;\n";
		$conn .= "\$GLOBALS['spip_connect_version'] = 0.4;\n";
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

?>
