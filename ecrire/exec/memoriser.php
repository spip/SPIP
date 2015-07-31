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


// Recupere et affiche (en ajax) une fonction memorisee dans inc/presentation
// http://doc.spip.org/@exec_memoriser_dist
function exec_memoriser_dist()
{
	$hash = _request('hash');
	$order = _request('order');
	$by = _request('by');
	$trad = _request('trad');
	lire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt', $ajax_fonctions);
	$ajax_fonctions = @unserialize($ajax_fonctions);

	if ($res = $ajax_fonctions[$hash]) {
		include_spip('inc/afficher_objets');
		list(,$t,$r,$f) = $res;
		if (preg_match('/^[a-z0-9+.,]+$/', $by)
		AND preg_match('/^\w*$/', $order)) {
			$r['ORDER BY'] = str_replace(',', " $order, ", $by) .  " $order";
			sauver_requete($t, $r, $f);
		}
		$cpt = sql_countsel($r['FROM'], $r['WHERE'], $r['GROUP BY']);
		include_spip('inc/presentation');
		$res = afficher_articles_trad($t, $r, $f, $hash, $cpt, $trad);
	} else spip_log("memoriser $q vide");
	include_spip('inc/actions');
	ajax_retour($res);
}

?>
