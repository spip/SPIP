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

include_spip('inc/cookie');

// changer de langue espace prive (ou login)

function action_converser_dist()
{
	$lang = _request('var_lang_ecrire');

	if (_FILE_CONNECT AND $lang) {
		$var_f = charger_fonction('securiser_action', 'inc');
		$var_f();
		spip_query("UPDATE spip_auteurs SET lang = " . _q($lang) . " WHERE id_auteur = " . $GLOBALS['auteur_session']['id_auteur']);
		$auteur_session['lang'] = $lang;
		$var_f = charger_fonction('session', 'inc');
		$var_f($auteur_session);
	}
}

function action_converser_post()
{
	if ($lang = _request('var_lang_ecrire')) {
		include_spip('inc/lang');

		spip_setcookie('spip_lang_ecrire', $lang, time() + 365 * 24 * 3600);
		spip_setcookie('spip_lang', $lang, time() + 365 * 24 * 3600);
	}
	$redirect = rawurldecode(_request('url'));
	spip_log("acp $redirect");
	if (!$redirect) $redirect = _DIR_RESTREINT_ABS;
	$redirect = parametre_url($redirect,'lang',$lang,'&');
	redirige_par_entete($redirect, true);
}

?>
