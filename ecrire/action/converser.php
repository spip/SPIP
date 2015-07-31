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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/cookie');

// changer de langue: pas de secu si espace public ou login ou installation
// mais alors on n'accede pas a la base, on pose seulement le cookie.

// http://doc.spip.org/@action_converser_dist
function action_converser_dist()
{
	if ($lang = _request('var_lang'))
		action_converser_post($lang);
	elseif ($lang = _request('var_lang_ecrire')) {
		if ( _request('arg') AND _FILE_CONNECT) {
			$securiser_action = charger_fonction('securiser_action', 'inc');
			$securiser_action();

			spip_query("UPDATE spip_auteurs SET lang = " . _q($lang) . " WHERE id_auteur = " . $GLOBALS['auteur_session']['id_auteur']);
			$GLOBALS['auteur_session']['lang'] = $lang;
			$session = charger_fonction('session', 'inc');
			if ($spip_session = $session($GLOBALS['auteur_session'])) {
				preg_match(',^[^/]*//[^/]*(.*)/$,',
					   url_de_base(),
					   $r);
				spip_setcookie('spip_session', $spip_session, time() + 3600 * 24 * 14, $r[1]);
			}
		}
		action_converser_post($lang, 'spip_lang_ecrire');
	} 

	$redirect = rawurldecode(_request('redirect'));

	if (!$redirect) $redirect = _DIR_RESTREINT_ABS;
	$redirect = parametre_url($redirect,'lang',$lang,'&');
	redirige_par_entete($redirect, true);
}

// http://doc.spip.org/@action_converser_post
function action_converser_post($lang, $ecrire=false)
{
	if ($lang) {
		include_spip('inc/lang');
		if (changer_langue($lang)) {
			spip_setcookie('spip_lang', $lang, time() + 365 * 24 * 3600);
			if ($ecrire)
				spip_setcookie('spip_lang_ecrire', $lang, time() + 365 * 24 * 3600);
		}
	}
}
?>
