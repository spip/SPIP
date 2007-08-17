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

include_spip('inc/autoriser');

// http://doc.spip.org/@gerer_deplacements
function gerer_deplacements($deplacements){
	foreach(explode("\n",$deplacements) as $dep){
		$mouvement=explode(":",$dep);
		list($quoi,$id_quoi) = explode("-",$mouvement[0]);
		list($cible, $id_cible) =explode("-",$mouvement[1]);
		$f = 'reorganiser_' . $quoi . '_' . $cible;
		if (function_exists($f))
			$f(intval($id_quoi), intval($id_cible));
		else spip_log("reorganiser $dep: incompris");
	}
}

// http://doc.spip.org/@reorganiser_article_rubrique
function reorganiser_article_rubrique($id_article, $id_rubrique)
{
	if ($id_rubrique
	AND autoriser('modifier','rubrique',$id_rubrique)
	AND autoriser('modifier','article',$id_article)) {

		include_spip('action/editer_article');
		include_spip('inc/rubriques');
		$s = spip_query("SELECT statut, id_rubrique FROM spip_articles WHERE id_article=$id_article");
		$s = sql_fetch($s);
		editer_article_heritage($id_article,
					$s['id_rubrique'], 
					$s['statut'],
					array('id_rubrique' => $id_rubrique));
	}
}

// http://doc.spip.org/@reorganiser_rubrique_rubrique
function reorganiser_rubrique_rubrique($id_quoi, $id_cible)
{
	if (($id_quoi != $id_cible)
	AND autoriser('modifier','rubrique',$id_cible)
	AND autoriser('modifier','rubrique',$id_quoi)) {
		if (!$id_cible)
			$id_secteur = $id_quoi;
		else {
			$s = spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_cible");
			$s = sql_fetch($s);
			$id_secteur = $s['id_secteur'];
		}

		$s = spip_query("SELECT statut, id_parent FROM spip_rubriques WHERE id_rubrique=$id_quoi");

		spip_query("UPDATE spip_rubriques SET id_parent="._q($id_cible).", id_secteur=$id_secteur WHERE id_rubrique="._q($id_quoi));

		if ($s['statut'] == 'publie') {
			include_spip('inc/rubriques');
			calculer_rubriques_if($s['id_parent'],
					      array('id_rubrique' => $id_cible),
					      'publie');
		}
	}
}

// http://doc.spip.org/@action_reorganiser_dist
function action_reorganiser_dist(){

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	if (_request('deplacements')!==NULL)
		  gerer_deplacements(_request('deplacements'));

	$redirect = _request('redirect');
	if ($redirect==NULL) $redirect="";

	redirige_par_entete(str_replace("&amp;","&",urldecode($redirect)));
}


?>
