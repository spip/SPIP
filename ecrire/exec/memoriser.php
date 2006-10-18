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
	global $flag_ob,$connect_id_auteur, $id_ajax_fonc, $trad;

	$res = spip_fetch_array(spip_query("SELECT variables, hash FROM spip_ajax_fonc	WHERE id_ajax_fonc =" . spip_abstract_quote($id_ajax_fonc) . " AND id_auteur=$connect_id_auteur"));

	if ($res) {
		
	  foreach(unserialize($res["variables"]) as $i => $k){ $$i = $k; }

	  include_spip('inc/presentation');		

	  if (_request('trad'))
		afficher_articles_trad ($param, $id_ajax_fonc, $titre_table, $requete, $afficher_visites, $afficher_auteurs);

	  else {
	    if (!$flag_ob) {spip_log("flag_ob pas la pour memoriser");exit;}
	    ob_start();
	    afficher_articles ($titre_table, $requete, $afficher_visites, $afficher_auteurs);
	    $res = ob_get_contents();
	    ob_end_clean();
	    ajax_retour($res);
	  }
	}
}
?>
