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

	// Prefix des tables :
	// contrairement a ce qui est dit dans le message (trop strict mais c'est
	// pour notre bien), on va tolerer les chiffres en plus des minuscules
	// S'il n'est pas defini par mes_options/inc/mutualiser, on va le creer
	// a partir de ce qui est envoye a l'installation
	if (!defined('_INSTALL_TABLE_PREFIX')) {
		$table_prefix = ($GLOBALS['table_prefix'] != 'spip')
		? $GLOBALS['table_prefix']
		: trim(preg_replace(',[^a-z0-9],','',strtolower(_request('tprefix'))));
		// S'il est vide on remet spip
		if (!$table_prefix)
			$table_prefix = 'spip';
		// Et si ce n'est pas spip, on le stocke dans le config/connect.php
		$rappel_prefix = ($table_prefix != 'spip');
	} else {
		$table_prefix = _INSTALL_TABLE_PREFIX;
	}
	// Enfin on l'installe pour notre connexion sur ce hit
	$GLOBALS['table_prefix'] = $table_prefix;

	$link = mysql_connect($adresse_db, $login_db, $pass_db);

	if ($choix_db == "new_spip") {
		$sel_db = _request('table_new');
		if (preg_match(',^[a-z_0-9-]+$,i', $sel_db))
			mysql_query("CREATE DATABASE `$sel_db`");
	}
	else {
		$sel_db = $choix_db;
	}
	mysql_select_db($sel_db);
	spip_query("SELECT COUNT(*) FROM spip_meta");
	$nouvelle = spip_sql_errno();
	if ($nouvelle) {
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
		$res ='';
		$ligne_rappel = '';
		spip_query("DELETE FROM spip_meta WHERE nom='mysql_rappel_nom_base'");
	} else {
		$res = " $link (". $GLOBALS['table_prefix']
		  . ") $sel_db (erreur rappel nom base `$sel_db`.spip_meta $nouvelle) ";
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
		$result_ko = spip_sql_errno();
	} else {
	  // en cas de reinstall sur mise a jour mal passee
		spip_query("DELETE FROM spip_meta WHERE nom='import_all'");
		$result = spip_query("SELECT COUNT(*) FROM spip_articles");
		$result_ko = (spip_num_rows($result) == 0);
	}

	if ($result_ko) return "<!--\n$res\n-->";

	if($chmod) {
		install_fichier_connexion(_FILE_CHMOD_INS . _FILE_TMP . '.php', "@define('_SPIP_CHMOD', ". sprintf('0%3o',$chmod).");\n");
	}

	if (preg_match(',(.*):(.*),', $adresse_db, $r))
		list(,$adresse_db, $port) = $r;
	else
		$port = '';

	$conn =  "\$GLOBALS['spip_connect_version'] = 0.4;\n"
	. $ligne_rappel
	. "spip_connect_db("
	. "'$adresse_db','$port','$login_db','"
	. addcslashes($pass_db, "'\\") . "','$sel_db'"
	. ");\n";

	install_fichier_connexion(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);
	return '';
}

function install_propose_ldap()
{
	return generer_form_ecrire('install', (
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


function install_premier_auteur($email, $login, $nom, $pass)
{
	return info_etape(_T('info_informations_personnelles'),
		     "<b>"._T('texte_informations_personnelles_1')."</b>" .
			     aide ("install5") .
			     "<p>" .
			     _T('texte_informations_personnelles_2') . " " .
			     _T('info_laisser_champs_vides')
			     )
	. generer_form_ecrire('install', (
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
}

function install_etape_3_dist()
{
	$ldap_present = _request('ldap_present');
	$res = $ldap_present ? '' : install_bases();

	if (!function_exists('ldap_connect')) $ldap_present = true;

	if ($res)
		$res .= "<p class='resultat'><b>"._T('avis_operation_echec')."</b></p>"._T('texte_operation_echec');
	
	else {
		if (file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
			include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
		else
			redirige_par_entete(generer_url_ecrire('install'));
	
		if (file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php'))
			include(_FILE_CHMOD_INS . _FILE_TMP . '.php');
		else
			redirige_par_entete(generer_url_ecrire('install'));

		$res =  "<p class='resultat'><b>"
		. _T('info_base_installee')
		. "</b></p>"
		. install_premier_auteur(_request('email'),
					   _request('login'),
					   _request('nom'),
					   _request('pass'))
		. ($ldap_present ?  '' : install_propose_ldap());
	}

	echo install_debut_html();
	echo $res;
	echo info_progression_etape(3,'etape_','install/');
	echo install_fin_html();
}
?>
