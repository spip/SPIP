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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/headers');
function install_bases(){
	global $spip_lang_right, $spip_version;

	$adresse_db = defined('_INSTALL_HOST_DB')
		? _INSTALL_HOST_DB
		: _request('adresse_db');

	$login_db = defined('_INSTALL_USER_DB')
		? _INSTALL_USER_DB
		: _request('login_db');

	$pass_db = defined('_INSTALL_PASS_DB')
		? _INSTALL_PASS_DB
		: _request('pass_db');

	$choix_db = defined('_INSTALL_NAME_DB')
		? _INSTALL_NAME_DB
		: _request('choix_db');

	$chmod = defined('_SPIP_CHMOD')
		? _SPIP_CHMOD
		: _request('chmod');


	$link = mysql_connect($adresse_db, $login_db, $pass_db);

	// Prefix des tables :
	// contrairement a ce qui est dit dans le message (trop strict mais c'est
	// pour notre bien), on va tolerer les chiffres en plus des minuscules
	if (!isset($GLOBALS['table_prefix'])) {
		$p = trim(preg_replace(',[^a-z0-9],', '',
			strtolower(_request('tprefix'))));
		$rappel_prefix = false;
		if ($p AND $p != 'spip') {
			$GLOBALS['table_prefix'] = $p;
			$rappel_prefix = true;
		}
	}

	echo "<"."!-- $link ";
	echo "(".$GLOBALS['table_prefix'].")";

	if ($choix_db == "new_spip") {
		$sel_db = _request('table_new');
		if (preg_match(',^[a-z_0-9-]+$,i', $sel_db)
			mysql_query("CREATE DATABASE `$sel_db`");
	}
	else {
		$sel_db = $choix_db;
	}
	echo "$sel_db ";

	mysql_select_db($sel_db);
	spip_query("SELECT COUNT(*) FROM spip_meta");
	$nouvelle = spip_sql_errno();
	if ($nouvelle){
		include_spip('base/db_mysql');
		// mettre les nouvelles install en utf-8 si mysql le supporte
		if ($charset = spip_mysql_character_set('utf-8')){
			$GLOBALS['meta']['charset_sql_base'] = $charset['charset'];
			$GLOBALS['meta']['charset_collation_sql_base'] = $charset['collation'];
			$GLOBALS['meta']['charset_sql_connexion'] = $charset['charset'];
			spip_query("SET NAMES "._q($charset['charset']));
		}
	}
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

	if ($rappel_prefix) {
		$ligne_rappel .= "\$GLOBALS['table_prefix'] = '" . $GLOBALS['table_prefix'] . "';\n";
	}

	if ($nouvelle) {
		if (isset($GLOBALS['meta']['charset_sql_base']))
			spip_query("INSERT INTO spip_meta (nom, valeur, impt) VALUES ('charset_sql_base', '".$GLOBALS['meta']['charset_sql_base']."', 'non')");
		if (isset($GLOBALS['meta']['charset_collation_sql_base']))
			spip_query("INSERT INTO spip_meta (nom, valeur, impt) VALUES ('charset_sql_base', '".$GLOBALS['meta']['charset_collation_sql_base']."', 'non')");
		if (isset($GLOBALS['meta']['charset_sql_connexion']))
			spip_query("INSERT INTO spip_meta (nom, valeur, impt) VALUES ('charset_sql_connexion', '".$GLOBALS['meta']['charset_sql_connexion']."', 'non')");
		
		spip_query("INSERT INTO spip_meta (nom, valeur) VALUES ('nouvelle_install', '1')");
		$result_ok = !spip_sql_errno();
	} else {
	  // en cas de reinstall sur mise a jour mal passee
	  spip_query("DELETE FROM spip_meta WHERE nom='import_all'");
		$result = spip_query("SELECT COUNT(*) FROM spip_articles");
		$result_ok = (spip_num_rows($result) > 0);
	}
	echo "($result_ok) -->";

	if($chmod) {
		$conn = "<"."?php\n";
		$conn .= "if (!defined(\"_ECRIRE_INC_VERSION\")) return;\n";
		$conn .= "define('_SPIP_CHMOD', ".$chmod.");\n";
		$conn .= "?".">";
		if (!ecrire_fichier(_FILE_CHMOD_INS . _FILE_TMP . '.php',
		$conn))
			redirige_par_entete(generer_url_ecrire('install'));
	}
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
	}
	return $result_ok;
}


function install_etape_3_dist()
{
	global $email, $ldap_present, $login, $nom, $pass, $spip_lang_right;

	echo install_debut_html();
	$result_ok = install_bases();
	if ($result_ok) {
		echo "<p class='resultat'><b>"._T('info_base_installee')."</b></p>";

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	if (@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php'))
		include(_FILE_CHMOD_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	echo info_etape(_T('info_informations_personnelles'),
		"<b>"._T('texte_informations_personnelles_1')."</b>" .
		aide ("install5") .
		"</p><p>" .
		_T('texte_informations_personnelles_2') . " " .
		_T('info_laisser_champs_vides')
	);

	echo generer_form_ecrire('install', (

	  "\n<input type='hidden' name='etape' value='4' />"

	. fieldset(_T('info_identification_publique'),
		array(
			'nom' => array(
				'label' => "<b>"._T('entree_signature')."</b><br />\n"._T('entree_nom_pseudo_1')."\n",
				'valeur' => $nom
			),
			'email' => array(
				'label' => "<b>"._T('entree_adresse_email')."</b>\n",
				'valeur' => $email
			)
		)
	)

	. fieldset(_T('entree_identifiants_connexion'),
		array(
			'login' => array(
				'label' => "<b>"._T('entree_login')."</b><br />\n"._T('info_plus_trois_car')."\n",
				'valeur' => $login
			),
			'pass' => array(
				'label' => "<b>"._T('entree_mot_passe')."</b><br />\n"._T('info_plus_cinq_car_2')."\n",
				'valeur' => $pass
			),
			'pass_verif' => array(
				'label' => "<b>"._T('info_confirmer_passe')."</b><br />\n",
				'valeur' => $pass
			)
		)
	)

	. bouton_suivant()));

	if (function_exists('ldap_connect') AND !$ldap_present) {
		echo generer_form_ecrire('install', (
			fieldset(_T('info_authentification_externe'),
				array(
				'etape' => array(
					'label' => _T('texte_annuaire_ldap_1'),
					'valeur' => 'ldap1',
					'hidden' => true
					)),
				 bouton_suivant(_T('bouton_acces_ldap'))
				 )));
	}
	}
	else if ($result_ok) {
		echo _T('alerte_maj_impossible', array('version' => $spip_version));
	}
	else {
		echo "<p class='resultat'><b>"._T('avis_operation_echec')."</b></p>"._T('texte_operation_echec');
	}

	echo info_progression_etape(3,'etape_','install/');
	echo install_fin_html();
}

?>
