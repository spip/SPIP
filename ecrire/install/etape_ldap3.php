<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// https://code.spip.net/@install_etape_ldap3_dist
function install_etape_ldap3_dist() {
	$adresse_ldap = _request('adresse_ldap');
	$login_ldap = _request('login_ldap');
	$pass_ldap = _request('pass_ldap');
	$port_ldap = _request('port_ldap');

	$base_ldap_text = defined('_INSTALL_BASE_LDAP')
		? _INSTALL_BASE_LDAP
		: 'ou=users, dc=mon-domaine, dc=com';

	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	echo info_etape(
		_T('info_chemin_acces_1'),
		info_progression_etape(3, 'etape_ldap', 'install/')
	),
	_T('info_chemin_acces_2');

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	if ($ldap_link) {
		@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");
		$result = @ldap_read($ldap_link, '', 'objectclass=*', ['namingContexts']);
		$info = @ldap_get_entries($ldap_link, $result);
		@ldap_close($ldap_link);
	}

	$checked = false;
	$res = '';
	if (is_array($info) and $info['count'] > 0) {
		$res .= '<p>' . _T('info_selection_chemin_acces') . '</p>';
		$res .= '<ul>';
		$n = 0;
		for ($i = 0; $i < $info['count']; $i++) {
			$names = $info[$i]['namingcontexts'];
			if (is_array($names)) {
				for ($j = 0; $j < $names['count']; $j++) {
					$n++;
					$res .= '<li><input name="base_ldap" value="' . spip_htmlspecialchars($names[$j]) . "\" type='radio' id='tab$n'";
					if (!$checked) {
						$res .= ' checked="checked"';
						$checked = true;
					}
					$res .= ' />';
					$res .= "<label for='tab$n'>" . spip_htmlspecialchars($names[$j]) . "</label></li>\n";
				}
			}
		}
		$res .= '</ul>';
		$res .= _T('info_ou') . ' ';
	}
	$res .= "<br />\n<input name=\"base_ldap\" value=\"\" type='radio' id='manuel'";
	if (!$checked) {
		$res .= ' checked="checked"';
		$checked = true;
	}

	$res .= ' />'
		. "\n<label for='manuel'>" . _T('entree_chemin_acces') . '</label> '
		. "\n<fieldset>"
		. "<input type='text' name='base_ldap_text' class='text' value=\"$base_ldap_text\" size='40' />"
		. "\n</fieldset>"
		. "\n<input type='hidden' name='etape' value='ldap4' />"
		. install_propager(['adresse_ldap', 'port_ldap', 'login_ldap', 'pass_ldap', 'protocole_ldap', 'tls_ldap'])
		. bouton_suivant();

	echo generer_form_ecrire('install', $res);

	echo install_fin_html();
}
