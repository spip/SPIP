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

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/modifier');

// Nota: quand on edite un forum existant, il est de bon ton d'appeler
// au prealable conserver_original($id_forum)
// http://doc.spip.org/@revision_forum
if(!function_exists('revision_forum')){
	function revision_forum($id_forum, $c=false) {

		$t = Sql::fetsel("*", "spip_forum", "id_forum=".intval($id_forum));
		if (!$t) {
			spip_log("erreur forum $id_forum inexistant");
			return;
		}

		// Calculer l'invalideur des caches lies a ce forum
		if ($t['statut'] == 'publie') {
			include_spip('inc/invalideur');
			$invalideur = array("id='forum/$id_forum'","id='".$t['objet']."/".$t['id_objet']."'");
		} else
			$invalideur = '';

		// Supprimer 'http://' tout seul
		if (isset($c['url_site'])) {
			include_spip('inc/filtres');
			$c['url_site'] = vider_url($c['url_site'], false);
		}

		$err = objet_modifier_champs('forum', $id_forum,
			array(
				'nonvide' => array('titre' => _T('info_sans_titre')),
				'invalideur' => $invalideur
			),
			$c);

		$t = $t["id_thread"];
		$cles = array();
		foreach (array('id_objet', 'objet') as $k) {
			if (isset($c[$k]) AND $c[$k]) $cles[$k] = $c[$k];
		}

		// Modification des id_article etc
		// (non autorise en standard mais utile pour des crayons)
		// on deplace tout le thread {sauf les originaux}.
		if (count($cles)) {
			spip_log("update thread id_thread=$t avec ".var_export($cles,1),'forum.'. _LOG_INFO_IMPORTANTE);
			Sql::updateq("spip_forum", $cles, "id_thread=".intval($t)." AND statut!='original'");
			// on n'affecte pas $r, car un deplacement ne change pas l'auteur
		}

		// s'il y a vraiment eu une modif, on
		// enregistre le nouveau date_thread
		if ($err==='') {
			// on ne stocke ni le numero IP courant ni le nouvel id_auteur
			// dans le message modifie (trop penible a l'usage) ; mais du
			// coup attention a la responsabilite editoriale
			/*
			Sql::updateq('spip_forum', array('ip'=>($GLOBALS['ip']), 'id_auteur'=>($GLOBALS['visiteur_session']['id_auteur'])),"id_forum=".intval($id_forum));
			*/

			// & meme ca ca pourrait etre optionnel
			Sql::updateq("spip_forum", array("date_thread" => date('Y-m-d H:i:s')), "id_thread=".$t);
		}
	}
}
