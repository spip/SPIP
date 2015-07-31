<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/cookie');

// changer de langue: pas de secu si espace public ou login ou installation
// mais alors on n'accede pas a la base, on pose seulement le cookie.

// http://doc.spip.org/@action_converser_dist
function action_converser_dist()
{
	$update_session = false;
	if ( _request('arg') AND spip_connect()) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$securiser_action();
		$update_session = true;
	}

	$lang = action_converser_changer_langue($update_session);
	$redirect = rawurldecode(_request('redirect'));

	if (!$redirect) $redirect = _DIR_RESTREINT_ABS;
	$redirect = parametre_url($redirect,'lang',$lang,'&');
	redirige_par_entete($redirect, true);
}

function action_converser_changer_langue($update_session){
	if ($lang = _request('var_lang'))
		action_converser_post($lang);
	elseif ($lang = _request('var_lang_ecrire')) {
		if ($update_session) {
			sql_updateq("spip_auteurs", array("lang" => $lang), "id_auteur = " . $GLOBALS['visiteur_session']['id_auteur']);
			$GLOBALS['visiteur_session']['lang'] = $lang;
			$session = charger_fonction('session', 'inc');
			if ($spip_session = $session($GLOBALS['visiteur_session'])) {
				spip_setcookie(
					'spip_session',
					$spip_session,
					time() + 3600 * 24 * 14
				);
			}
		}
		action_converser_post($lang, 'spip_lang_ecrire');
	}
	return $lang;
}

// http://doc.spip.org/@action_converser_post
function action_converser_post($lang, $ecrire=false)
{
	if ($lang) {
		include_spip('inc/lang');
		if (changer_langue($lang)) {
			spip_setcookie('spip_lang', $_COOKIE['spip_lang'] = $lang, time() + 365 * 24 * 3600);
			if ($ecrire)
				spip_setcookie('spip_lang_ecrire', $_COOKIE['spip_lang_ecrire'] = $lang, time() + 365 * 24 * 3600);
		}
	}
}
?>
