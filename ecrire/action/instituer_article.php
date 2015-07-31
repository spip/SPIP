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

// http://doc.spip.org/@action_instituer_article_dist
function action_instituer_article_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_article, $statut) = preg_split('/\W/', $arg);
	if (!$statut) $statut = _request('statut_nouv'); // cas POST
	if (!$statut) return; // impossible mais sait-on jamais

	$id_article = intval($id_article);

	// si on passe un statut_old, le controler
	// http://trac.rezo.net/trac/spip/ticket/1932
	if ($old = _request('statut_old')
	AND $s = sql_fetsel('statut', 'spip_articles', 'id_article='.sql_quote($id_article))
	AND $s['statut'] != $old)
		return;

	include_spip('action/editer_article');

	$c = array('statut' => $statut);

	// si on a envoye une 'date_posterieure', l'enregistrer
	if ($d = _request('date_posterieure'))
		$c['date'] = $d;

	instituer_article($id_article, $c);

}

?>
