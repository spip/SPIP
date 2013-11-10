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
 * Definir les meta de configuration liee aux breves
 *
 * @param array $metas
 * @return array
 */
function breves_configurer_liste_metas($metas){
	$metas['activer_breves'] =  'non';
	return $metas;
}

/**
 * Ajouter les breves a valider sur les rubriques 
 *
 * @param array $flux
 * @return array
**/
function breves_rubrique_encours($flux){
	if ($flux['args']['type'] == 'rubrique') {
		$lister_objets = charger_fonction('lister_objets','inc');

		$id_rubrique = $flux['args']['id_objet'];

		//
		// Les breves a valider
		//
		$flux['data'] .= $lister_objets('breves', array(
			'titre'=>_T('breves:info_breves_valider'),
			'statut'=>array('prepa','prop'),
			'id_rubrique'=>$id_rubrique,
			'par'=>'date_heure'));
	}
	return $flux;
}




/**
 * Ajouter les breves references sur les vues de rubriques
 *
 * @param array $flux
 * @return array
**/
function breves_affiche_enfants($flux) {
	if ($e = trouver_objet_exec($flux['args']['exec'])
	  AND $e['type'] == 'rubrique'
	  AND $e['edition'] == false) {
		$id_rubrique = $flux['args']['id_rubrique'];

		if ($GLOBALS['meta']["activer_breves"] == 'oui') {
			$lister_objets = charger_fonction('lister_objets','inc');
			$bouton_breves = '';
			$id_parent = Sql::getfetsel('id_parent', 'spip_rubriques', 'id_rubrique='.$id_rubrique);
			if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent))) {
				$bouton_breves .= icone_verticale(_T('breves:icone_nouvelle_breve'), generer_url_ecrire("breve_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.png","new", 'right')
				. "<br class='nettoyeur' />";
			}

			$flux['data'] .= $lister_objets('breves', array('titre'=>_T('breves:icone_ecrire_nouvel_article'), 'where'=>"statut != 'prop' AND statut != 'prepa'", 'id_rubrique'=>$id_rubrique, 'par'=>'date_heure'));
			$flux['data'] .= $bouton_breves;
		}
	}
	return $flux;
}




/**
 * Bloc sur les informations generales concernant chaque type d'objet
 *
 * @param string $texte
 * @return string
 */
function breves_accueil_informations($texte)
{
	$q = Sql::select('COUNT(*) AS cnt, statut', 'spip_breves', '', 'statut', '','', 'COUNT(*)<>0');

	$cpt = array();
	$cpt2 = array();
	$where = false;
	if ($GLOBALS['visiteur_session']['statut']=='0minirezo'){
		$where = Sql::allfetsel('id_objet','spip_auteurs_liens',"objet='rubrique' AND id_auteur=".intval($GLOBALS['visiteur_session']['id_auteur']));
		if ($where){
			$where = Sql::in('id_rubrique',array_map('reset',$where));
		}
	}
	$defaut = $where ? '0/' : '';
	while($row = Sql::fetch($q)) {
	  $cpt[$row['statut']] = $row['cnt'];
	  $cpt2[$row['statut']] = $defaut;
	}

	if ($cpt) {
		if ($where) {
			$q = Sql::select("COUNT(*) AS cnt, statut", 'spip_breves', $where, "statut");
			while($row = Sql::fetch($q)) {
				$r = $row['statut'];
				$cpt2[$r] = intval($row['cnt']) . '/';
			}
		}
		$texte .= "<div class='accueil_informations breves liste'>";
		$texte .= "<h4>" . afficher_plus_info(generer_url_ecrire("breves"), "", _T('breves:info_breves_02')) . "</h4>";
		$texte .= "<ul class='liste-items'>";
		if (isset($cpt['prop'])) $texte .= "<li class='item'>"._T("texte_statut_attente_validation").": ".$cpt2['prop'].$cpt['prop'] . '</li>';
		if (isset($cpt['publie'])) $texte .= "<li class='item on'>"._T("texte_statut_publies").": ".$cpt2['publie'] .$cpt['publie'] . '</li>';
		$texte .= "</ul>";
		$texte .= "</div>";
	}
	return $texte;
}


/**
 * Compter les breves dans une rubrique
 * 
 * @param array $flux
 * @return array
 */
function breves_objet_compte_enfants($flux){
	if ($flux['args']['objet']=='rubrique'
	  AND $id_rubrique=intval($flux['args']['id_objet'])) {
		// juste les publies ?
		if (array_key_exists('statut', $flux['args']) and ($flux['args']['statut'] == 'publie')) {
			$flux['data']['breve'] = Sql::countsel('spip_breves', "id_rubrique=".intval($id_rubrique)." AND (statut='publie')");
		} else {
			$flux['data']['breve'] = Sql::countsel('spip_breves', "id_rubrique=".intval($id_rubrique)." AND (statut='publie' OR statut='prop')");
		}
	}
	return $flux;
}


/**
 * Changer la langue des breves si la rubrique change
 * 
 * @param array $flux
 * @return array
 */
function breves_trig_calculer_langues_rubriques($flux){

	$s = Sql::select("A.id_breve AS id_breve, R.lang AS lang", "spip_breves AS A, spip_rubriques AS R", "A.id_rubrique = R.id_rubrique AND A.langue_choisie != 'oui' AND (A.lang='' OR R.lang<>'') AND R.lang<>A.lang");
	while ($row = Sql::fetch($s)) {
		$id_breve = $row['id_breve'];
		Sql::updateq('spip_breves', array("lang"=>$row['lang'], 'langue_choisie'=>'non'), "id_breve=$id_breve");
	}
		
	return $flux;
}


/**
 * Publier et dater les rubriques qui ont une breve publie
 * 
 * @param array $flux
 * @return array
 */
function breves_calculer_rubriques($flux){

	$r = Sql::select("R.id_rubrique AS id, max(A.date_heure) AS date_h", "spip_rubriques AS R, spip_breves AS A", "R.id_rubrique = A.id_rubrique AND R.date_tmp <= A.date_heure AND A.statut='publie' ", "R.id_rubrique");
	while ($row = Sql::fetch($r))
	  Sql::updateq('spip_rubriques', array('statut_tmp'=>'publie', 'date_tmp'=>$row['date_h']), "id_rubrique=".$row['id']);	
		
	return $flux;
}




/**
 * Ajouter les breves a valider sur la page d'accueil 
 *
 * @param array $flux
 * @return array
**/
function breves_accueil_encours($flux){
	$lister_objets = charger_fonction('lister_objets','inc');


	$flux .= $lister_objets('breves', array(
		'titre'=>afficher_plus_info(generer_url_ecrire('breves'))._T('breves:info_breves_valider'),
		'statut'=>array('prepa','prop'),
		'par'=>'date_heure'));

	return $flux;
}



/**
 * Optimiser la base de donnee en supprimant les liens orphelins
 *
 * @param array $flux
 * @return array
 */
function breves_optimiser_base_disparus($flux){
	$n = &$flux['data'];
	$mydate = $flux['args']['date'];


	# les breves qui sont dans une id_rubrique inexistante
	$res = Sql::select("B.id_breve AS id",
		        "spip_breves AS B
		        LEFT JOIN spip_rubriques AS R
		          ON B.id_rubrique=R.id_rubrique",
			"R.id_rubrique IS NULL
		         AND B.maj < $mydate");

	$n+= optimiser_sansref('spip_breves', 'id_breve', $res);


	//
	// Breves
	//

	Sql::delete("spip_breves", "statut='refuse' AND maj < $mydate");

	return $flux;

}

/**
 * Afficher le nombre de breves dans chaque rubrique
 *
 * @param array $flux
 * @return array
 */
function breves_boite_infos($flux)
{
	if (isset($flux['args']['type'])
	AND $flux['args']['type'] == 'rubrique'
	AND isset($flux['args']['id'])
	AND $id_rubrique = $flux['args']['id'])
	{
		if ($nb = Sql::countsel('spip_breves',"statut='publie' AND id_rubrique=".intval($id_rubrique))){
			$nb = "<div>". singulier_ou_pluriel($nb, "breves:info_1_breve", "breves:info_nb_breves") . "</div>";
			if ($p = strpos($flux['data'],"<!--nb_elements-->"))
				$flux['data'] = substr_replace($flux['data'],$nb,$p,0);
		}
	}
	return $flux;
}

/**
 * Configuration des contenus
 * @param array $flux
 * @return array
 */
function breves_affiche_milieu($flux){
	if ($flux["args"]["exec"] == "configurer_contenu") {
		$flux["data"] .=  recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_breves'));
	}
	return $flux;
}


?>
