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
	global $connect_id_auteur;

	$res = spip_fetch_array(spip_query("SELECT variables, hash FROM spip_ajax_fonc	WHERE id_ajax_fonc =" . intval(_request('id_ajax_fonc')) . " AND id_auteur=$connect_id_auteur"));

	if ($res) {
		
	  foreach(unserialize($res["variables"]) as $i => $k){ $$i = $k; }

	  include_spip('inc/presentation');		

	  if (_request('trad'))
	    ajax_retour(afficher_articles_trad ($param, $id_ajax_fonc, $titre_table, $requete, $afficher_visites, $afficher_auteurs));

	  else ajax_retour(afficher_articles ($titre_table, $requete, $afficher_visites, $afficher_auteurs));
	}
}
?>
