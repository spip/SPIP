<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de redirection publique à la volée d'un objet éditorial en
 * recalculant au passage son URL
 *
 * @package SPIP\Core\Redirections
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Script utile pour recalculer une URL symbolique dès son changement
 *
 * Cette action est appelé par les boutons 'Voir en ligne' ou par
 * le fichier `.htaccess` activé lors d'une URL du genre : http://site/1234
 *
 * @example
 *   ```
 *   [(#VAL{redirect}
 *      |generer_url_action{type=article&id=#ID_ARTICLE}
 *      |parametre_url{var_mode,calcul}
 *      |icone_horizontale{<:icone_voir_en_ligne:>,racine})]
 *   ```
**/
function action_redirect_dist()
{
	$type = _request('type');
	if (!preg_match('/^\w+$/', $type)) return;
	if ($m = _request('var_mode')) {
		// forcer la mise a jour de l'url de cet objet !
		if (!defined('_VAR_URLS')) define('_VAR_URLS',true);
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
