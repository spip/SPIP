<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/headers');
include_spip('auth/ldap');

// http://doc.spip.org/@install_etape_ldap5_dist
function install_etape_ldap5_dist()
{
	etape_ldap5_save();
	etape_ldap5_suite();
}

function etape_ldap5_save()
{
	if (!@file_exists(_FILE_CONNECT_TMP))
		redirige_url_ecrire('install');

	ecrire_meta('ldap_statut_import', _request('statut_ldap'));

	lire_fichier(_FILE_CONNECT_TMP, $conn);

	if ($p = strpos($conn, "'');")) {
			ecrire_fichier(_FILE_CONNECT_TMP, 
				       substr($conn, 0, $p+1) 
				       . _FILE_LDAP
				       . substr($conn, $p+1));
	}

	$adresse_ldap = addcslashes(_request('adresse_ldap'),"'\\");
	$login_ldap = addcslashes(_request('login_ldap'),"'\\");
	$pass_ldap = addcslashes(_request('pass_ldap'),"'\\");
	$port_ldap = addcslashes(_request('port_ldap'),"'\\");
	$tls_ldap = addcslashes(_request('tls_ldap'),"'\\");
	$protocole_ldap = addcslashes(_request('protocole_ldap'),"'\\");
	$base_ldap = addcslashes(_request('base_ldap'),"'\\");
	$base_ldap_text = addcslashes(_request('base_ldap_text'),"'\\");

	$conn = "\$GLOBALS['ldap_base'] = '$base_ldap';\n"
		. "\$GLOBALS['ldap_link'] = @ldap_connect('$adresse_ldap','$port_ldap');\n"
		. "@ldap_set_option(\$GLOBALS['ldap_link'],LDAP_OPT_PROTOCOL_VERSION,'$protocole_ldap');\n"
		. (($tls_ldap != 'oui') ? '' :
		   "@ldap_start_tls(\$GLOBALS['ldap_link']);\n")
		. "@ldap_bind(\$GLOBALS['ldap_link'],'$login_ldap','$pass_ldap');\n";

	$champs = is_array($GLOBALS['ldap_attributes']) ? $GLOBALS['ldap_attributes'] : array();
	$res = '';
	foreach ($champs as $champ => $v ) {
		$nom = 'ldap_' . $champ;
		$val = trim(_request($nom));
		if (preg_match('/^\w*$/', $val)) {
			if ($val) $val = _q($val);
		} else $val = "array(" . _q(preg_split('/\W+/', $val)) . ')';;
		if ($val) $res .= "'$champ' => " . $val . ",";
	}
	$conn .= "\$GLOBALS['ldap_champs'] = array($res);\n";
	  
	install_fichier_connexion(_DIR_CONNECT . _FILE_LDAP, $conn);
}

function etape_ldap5_suite()
{
	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	echo info_etape(_T('info_ldap_ok'), info_progression_etape(5,'etape_ldap','install/'), _T('info_terminer_installation'));

	echo generer_form_ecrire('install', (
		"<input type='hidden' name='etape' value='3' />" .
		"<input type='hidden' name='ldap_present' value='true' />" 
		. bouton_suivant()));

	echo install_fin_html();
}


?>
