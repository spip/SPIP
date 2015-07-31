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

// http://doc.spip.org/@exec_memoriser_dist
function exec_memoriser_dist()
{
	$id_ajax = intval(_request('id_ajax_fonc'));

	// le champ id_auteur sert finalement a memoriser le nombre de lignes
	// (a renommer)

	$res = spip_fetch_array(spip_query($q = "SELECT variables, id_auteur, hash FROM spip_ajax_fonc WHERE id_ajax_fonc = $id_ajax"));

	if ($res) {
		
	  foreach(unserialize($res["variables"]) as $i => $k){ $$i = $k; }

	  include_spip('inc/presentation');		

	  $formater_article = _request('trad') ? '' : charger_fonction('formater_article', 'inc');

	  ajax_retour(afficher_articles_trad($titre_table, $requete, $formater_article, $param, $id_ajax, $res['id_auteur']));

	} else spip_log("memoriser $q vide");
}
?>
