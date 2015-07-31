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

// Un script utile pour recalculer une URL symbolique des son changement

function action_redirect_dist()
{
	$type = _request('type');
	if (!preg_match('/^\w+$/', $type)) return;
	if ($m = _request('var_mode')) {
		$GLOBALS['var_urls'] = true; // forcer la mise a jour de l'url de cet objet !
		$m = 'var_mode='.urlencode($m);
	}
	$h = generer_url_entite_absolue(intval(_request('id')), $type, $m, '', true);
	$status = '302';
	if (_request('status') AND _request('status')=='301')
		$status = '301';

	if ($h)
		redirige_par_entete(str_replace('&amp;', '&', $h),'',$status);
	else
		redirige_par_entete('/','',$status);
}

?>
