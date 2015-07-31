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


// Recupere et affiche (en ajax) une fonction memorisee dans inc/presentation
// http://doc.spip.org/@exec_memoriser_dist
function exec_memoriser_dist()
{
	$hash = _request('hash');
	lire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt', $ajax_fonctions);
	$ajax_fonctions = @unserialize($ajax_fonctions);

	if ($res = $ajax_fonctions[$hash]) {
		list(,$t,$r,$p,$f) = $res;
		$cpt = sql_countsel($r['FROM'], $r['WHERE'], $r['GROUP BY']);
		include_spip('inc/presentation');
		include_spip('inc/afficher_objets');
		$res = afficher_articles_trad($t, $r, $f, $p, $hash, $cpt, _request('trad'));

	} else spip_log("memoriser $q vide");
	ajax_retour($res);
}

?>
