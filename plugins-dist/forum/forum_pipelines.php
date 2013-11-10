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

/**
 * Bloc sur les encours editoriaux en page d'accueil
 *
 * @param string $texte
 * @return string
 */
function forum_accueil_encours($texte){
	// si aucun autre objet n'est a valider, on ne dit rien sur les forum
	if ($GLOBALS['visiteur_session']['statut'] == '0minirezo') {
		// Les forums en attente de moderation
		$cpt = Sql::countsel("spip_forum", "statut='prop'");
		if ($cpt) {
			if ($cpt>1)
				$lien = _T('forum:info_liens_syndiques_3')." "._T('forum:info_liens_syndiques_4');
			else
				$lien = _T('forum:info_liens_syndiques_5')." "._T('forum:info_liens_syndiques_6');
			$lien = "<small>$cpt $lien " ._T('forum:info_liens_syndiques_7'). "</small>";
			if ($GLOBALS['connect_toutes_rubriques'])
				$lien = "<a href='" . generer_url_ecrire("controler_forum","statut=prop") . "' style='color: black;'>". $lien . ".</a>";
			$texte .= "\n<br />" . $lien;
		}
		if (strlen($texte) AND $GLOBALS['meta']['forum_prive_objets'] != 'non')
			$cpt2 = Sql::countsel("spip_articles", "statut='prop'");
			if ($cpt2)
				$texte = _T('forum:texte_en_cours_validation_forum') . $texte;
	}

	return $texte;
}


/**
 * Bloc sur les informations generales concernant chaque type d'objet
 *
 * @param string $texte
 * @return string
 */
function forum_accueil_informations($texte)
{
	$q = Sql::select('COUNT(*) AS cnt, statut', 'spip_forum', Sql::in('statut', array('publie', 'prop')), 'statut', '','', "COUNT(*)<>0");

	$where = count($GLOBALS['connect_id_rubrique']) ? Sql::in('id_rubrique', $GLOBALS['connect_id_rubrique'])	: '';
	$cpt = array();
	$cpt2 = array();
	$defaut = $where ? '0/' : '';
	while($row = Sql::fetch($q)) {
	  $cpt[$row['statut']] = $row['cnt'];
	  $cpt2[$row['statut']] = $defaut;
	}

	if ($cpt) {
		if ($where) {
		  include_spip('inc/forum');
		  list($f, $w) = critere_statut_controle_forum('public');
		  $q = Sql::select("COUNT(*) AS cnt, F.statut", "$f", "$w ", "F.statut");
		  while($row = Sql::fetch($q)) {
				$r = $row['statut'];
				$cpt2[$r] = intval($row['cnt']) . '/';
			}
		}

		$texte .= "<div class='accueil_informations forum liste'>";
		$titre = _T('forum:onglet_messages_publics');
		if (autoriser('modererforum')) {
			$plus = afficher_plus_info(generer_url_ecrire("controler_forum"), "", $titre);
			$texte .= "<h4>$plus</h4>";
		} else {
			$texte .= "<h4>$titre</h4>";
		}
		$texte .= "<ul class='liste-items'>";
		if (isset($cpt['prop'])) $texte .= "<li class='item'>"._T("texte_statut_attente_validation").": ".$cpt2['prop'] .$cpt['prop'] . '</li>';
		if (isset($cpt['publie'])) $texte .= "<li class='item'>"._T("texte_statut_publies").": ".$cpt2['publie'] .$cpt['publie'] .'</li>';
		$texte .= "</ul>";
		$texte .= "</div>";
	}
	return $texte;
}

/**
 * Affichage de la fiche complete des articles et rubriques
 *
 * @param array $flux
 * @return array
 */
function forum_afficher_fiche_objet($flux){

	if (in_array($type = $flux['args']['type'],array('article','breve','site'))
	  AND $GLOBALS['meta']['forum_prive_objets'] != 'non'){
		$id = $flux['args']['id'];
		$table = table_objet($type);
		$id_table_objet = id_table_objet($type);
		$contexte = array_merge($flux['args']['contexte'],
			array(
				'objet'=>$type,
				'id_objet'=>$id,
				'quoi'=>'interne',
				'statut'=>'prive'
			)
		);
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/discuter_forum',$contexte,array('ajax'=>true));
	}
	if (($type = $flux['args']['type'])=='rubrique'){
		$id_rubrique = $flux['args']['id'];
		if (autoriser('publierdans','rubrique',$id_rubrique)
		  AND !Sql::getfetsel('id_parent','spip_rubriques','id_rubrique='.intval($id_rubrique))) {
			include_spip('inc/forum');
			list($from, $where) = critere_statut_controle_forum('prop', $id_rubrique);
			$n_forums = Sql::countsel($from, $where);
		}
		else
			$n_forums = 0;
		if ($n_forums)
	  	$flux['data'] .= icone_verticale(_T('forum:icone_suivi_forum', array('nb_forums' => $n_forums)), generer_url_ecrire("controler_forum","objet=rubrique&id_objet=$id_rubrique"), "forum-24.png", "", 'center');
	}
	return $flux;
}

/**
 * Boite de configuration des objets articles
 *
 * @param array $flux
 * @return array
 */
function forum_afficher_config_objet($flux){
	if (($type = $flux['args']['type'])
		AND $id = $flux['args']['id']){
		if (autoriser('modererforum', $type, $id)) {
			$id_table_objet = id_table_objet($type);
			$flux['data'] .= recuperer_fond("prive/objets/configurer/moderation",array('id_objet'=>$id,'objet'=>  objet_type(table_objet($type))));
		}
	}
	return $flux;
}

/**
 * Message d'information relatif au statut d'un objet
 *
 * @param array $flux
 * @return array
 */
function forum_afficher_message_statut_objet($flux){
	if ($type = $flux['args']['type']=='article'){
		$statut = $flux['args']['statut'];
		if ($GLOBALS['meta']['forum_prive_objets'] != 'non'
		  AND $statut == 'prop')
			$flux['data'] .= "<p class='article_prop'>"._T('forum:text_article_propose_publication_forum').'</p>';
	}
	return $flux;
}

/**
 * Nombre de forums d'un secteur dans la boite d'info
 *
 * @param array $flux
 * @return array
 */
function forum_boite_infos($flux){
	if ($flux['args']['type']=='rubrique'
	  AND $id_rubrique = $flux['args']['id']){
		if (autoriser('publierdans','rubrique',$id_rubrique)
		// [doc] d'ou il vient ce row ?
		AND (!isset($flux['args']['row']['id_parent']) OR !$flux['args']['row']['id_parent'])) {
			include_spip('inc/forum');
			list($from, $where) = critere_statut_controle_forum('prop', $id_rubrique);
			$n_forums = Sql::countsel($from, $where);
		}
		else
			$n_forums = 0;
		if ($n_forums){
			$aff = "<p class='forums'>".singulier_ou_pluriel($n_forums, "forum:info_1_message_forum", "forum:info_nb_messages_forum").'</p>';
			if (($pos = strpos($flux['data'],'<!--nb_elements-->'))!==FALSE)
				$flux['data'] = substr($flux['data'],0,$pos) . $aff . substr($flux['data'],$pos);
			else
				$flux['data'] .= $aff;
		}
	}
	elseif ($flux['args']['type']=='auteur'
		AND $id_auteur = $flux['args']['id']){
		if ($nb = Sql::countsel('spip_forum',"statut!='poubelle' AND id_auteur=".intval($id_auteur))){
			$nb = "<div>". singulier_ou_pluriel($nb, "forum:info_1_message_forum", "forum:info_nb_messages_forum") . "</div>";
			if ($p = strpos($flux['data'],"<!--nb_elements-->"))
				$flux['data'] = substr_replace($flux['data'],$nb,$p,0);
		}
	}
	return $flux;
}

/**
 * Compter et afficher les contributions d'un visiteur
 * pour affichage dans la page auteurs
 *
 * @param array $flux
 * @return array
 */
function forum_compter_contributions_auteur($flux){
	$id_auteur = intval($flux['args']['id_auteur']);
	if ($cpt = Sql::countsel("spip_forum AS F", "F.id_auteur=".intval($flux['args']['id_auteur']))){
		// manque "1 message de forum"
		$contributions = singulier_ou_pluriel($cpt,'forum:info_1_message_forum','forum:info_nb_messages_forum');
		$flux['data'][] = $contributions;
	}
	return $flux;
}

/**
 * Definir les meta de configuration liee aux forums
 *
 * @param array $metas
 * @return array
 */
function forum_configurer_liste_metas($metas){
	$metas['mots_cles_forums'] =  'non';
	$metas['forums_titre'] = 'oui';
	$metas['forums_texte'] = 'oui';
	$metas['forums_urlref'] = 'non';
	$metas['forums_afficher_barre'] = 'oui';
	$metas['formats_documents_forum'] = '';
	$metas['forums_publics'] = 'posteriori';
	$metas['forum_prive'] = 'oui'; # forum global dans l'espace prive
	$metas['forum_prive_objets'] = 'oui'; # forum sous chaque article de l'espace prive
	$metas['forum_prive_admin'] = 'non'; # forum des administrateurs

	return $metas;
}



/**
 * Optimiser la base de donnee en supprimant les forums orphelins
 *
 * @param int $n
 * @return int
 */
function forum_optimiser_base_disparus($flux){
	$n = &$flux['data'];
	$mydate = $flux['args']['date'];
	# les forums lies a une id_objet inexistant
	$r = Sql::select("DISTINCT objet",'spip_forum');
	while ($t = Sql::fetch($r)){
		if ($type = $t['objet']) {
			$spip_table_objet = table_objet_sql($type);
			$id_table_objet = id_table_objet($type);
			# les forums lies a un objet inexistant
			$res = Sql::select("forum.id_forum AS id",
							"spip_forum AS forum
								LEFT JOIN $spip_table_objet AS O
									ON O.$id_table_objet=forum.id_objet",
					"forum.objet=".Sql::quote($type)." AND O.$id_table_objet IS NULL AND forum.id_objet>0");

			$n+= optimiser_sansref('spip_forum', 'id_forum', $res);
		}
	}

	//
	// Forums
	//
	Sql::delete("spip_forum", "statut='redac' AND maj < $mydate");

	// nettoyer les documents des forums en spam&poubelle pour eviter de sortir des quota disques
	// bizarrement on ne nettoie jamais les messages eux meme ?
	include_spip('action/editer_liens');
	if (objet_associable('document')){
		$res = Sql::select('L.id_document,F.id_forum',"spip_documents_liens AS L JOIN spip_forum AS F ON (F.id_forum=L.id_objet AND L.objet='forum')","F.statut IN ('off','spam')");
		while ($row = Sql::fetch($res)){
			include_spip('inc/autoriser');
			// si un seul lien (ce forum donc), on supprime le document
			// si un document est attache a plus d'un forum, c'est un cas bizarre ou gere a la main
			// on ne touche a rien !
			if (count(objet_trouver_liens(array('document'=>$row['id_document']),'*'))==1){
				autoriser_exception('supprimer','document',$row['id_document']);
				if ($supprimer_document = charger_fonction('supprimer_document','action',true))
					$supprimer_document($row['id_document']);
			}
		}
	}


	//
	// CNIL -- Informatique et libertes
	//
	// masquer le numero IP des vieux forums
	//
	## date de reference = 4 mois
	## definir a 0 pour desactiver
	if (!defined('_CNIL_PERIODE')) {
		define('_CNIL_PERIODE', 3600*24*31*4);
	} 
	if (_CNIL_PERIODE) {
		$critere_cnil = 'date_heure<"'.date('Y-m-d', time()-_CNIL_PERIODE).'"'
			. ' AND statut != "spam"'
			. ' AND (ip LIKE "%.%" OR ip LIKE "%:%")'; # ipv4 ou ipv6

		$c = Sql::countsel('spip_forum', $critere_cnil);

		if ($c>0) {
			spip_log("CNIL: masquer IP de $c forums anciens");
			Sql::update('spip_forum', array('ip' => 'MD5(ip)'), $critere_cnil);
		}
	}

	return $flux;

}


/**
 * Remplissage des champs a la creation d'objet
 *
 * @param array $flux
 * @return array
 */
function forum_pre_insertion($flux){
	if ($flux['args']['table']=='spip_articles'){
		$flux['data']['accepter_forum'] =	substr($GLOBALS['meta']['forums_publics'],0,3);
	}
	return $flux;
}

/**
 * Regrouper les resultats de recherche par threads
 * sauf si {plat} est present
 *
 * @param array $flux
 * @return array
 */
function forum_prepare_recherche($flux){
	# Pour les forums, unifier par id_thread et forcer statut='publie'
	if ($flux['args']['type']=='forum'
	  AND $points = $flux['data']){
	  $serveur =  $flux['args']['serveur'];
	  $modificateurs = (isset($flux['args']['modificateurs'])?$flux['args']['modificateurs']:array());

	  // pas de groupe par thread si {plat}
	  if (!isset($modificateurs['plat'])){
			$p2 = array();
			// si critere statut dans la boucle, ne pas filtrer par statut publie ici
			$cond = (isset($modificateurs['criteres']['statut'])?"":"statut='publie' AND ");
			$s = Sql::select("id_thread, id_forum", "spip_forum", $cond.Sql::in('id_forum', array_keys($points)), '','','','',$serveur);
			while ($t = Sql::fetch($s, $serveur))
				$p2[intval($t['id_thread'])]['score']
					+= $points[intval($t['id_forum'])]['score'];
			$flux['data'] = $p2;
	  }
	}
	return $flux;
}


/**
 * Bloc en sur les encours d'une rubrique (page naviguer)
 *
 * @param array $flux
 * @return array
 */
function forum_rubrique_encours($flux){
	if (strlen($flux['data'])
	  AND $GLOBALS['meta']['forum_prive_objets'] != 'non')
		$flux['data'] = _T('forum:texte_en_cours_validation_forum') . $flux['data'];
	return $flux;
}

/**
 * Supprimer les forums lies aux objets du core lors de leur suppression
 *
 * @param array $objets
 * @return array
 */
function forum_trig_supprimer_objets_lies($objets){
	foreach($objets as $objet){
		if ($objet['type']=='message')
			Sql::delete("spip_forum", "id_message=".intval($objet['id']));
	}
	return $objets;
}

?>
