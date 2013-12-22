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
	$id = intval(_request('id'));

	if ($m = _request('var_mode')) {
		$GLOBALS['var_urls'] = true; // forcer la mise a jour de l'url de cet objet !
	}

	if (preg_match('/^\w+$/', $type)) {
		$h = generer_url_entite_absolue($id, $type, '', '', true);
	}
	else if ($page = _request('page')
	AND preg_match('/^\w+$/', $page)) {
		$h = generer_url_public($page, '', true);
	}
	else return;

	if ($m > '')
		$h = parametre_url($h, 'var_mode', $m);

	if ($m == 'preview'
	AND defined('_PREVIEW_TOKEN')
	AND _PREVIEW_TOKEN
	AND autoriser('previsualiser')
	AND $aut = $GLOBALS['visiteur_session']['id_auteur'] ) {
		include_spip('inc/securiser_action');
		$token = _action_auteur('previsualiser', $aut, null, 'alea_ephemere');
		$h = parametre_url($h, 'var_previewtoken', "$aut*$token");
	}

	$status = '302';
	if (_request('status') AND _request('status')=='301')
		$status = '301';

	if ($h)
		redirige_par_entete(str_replace('&amp;', '&', $h),'',$status);
	else
		redirige_par_entete('/','',$status);
}

?>
