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

/**
 * Pipelines utilisés du plugin révisions
 *
 * @package SPIP\Revisions\Pipelines
**/
if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Ajoute dans le bloc d'info d'un objet un bouton permettant d'aller voir
 * l'historique de ses révisions
 * 
 * @param array $flux   Données du pipeline
 * @return array $flux  Données du pipeline
 */
function revisions_boite_infos($flux){
	$type = $flux['args']['type'];
	if ($id = intval($flux['args']['id'])
		AND $tables = unserialize($GLOBALS['meta']['objets_versions'])
		AND in_array(table_objet_sql($type),$tables)
		AND autoriser('voirrevisions',$type,$id)
		// regarder le numero de revision le plus eleve, et afficher le bouton
		// si c'est interessant (id_version>1)
		AND Sql::countsel('spip_versions', 'id_objet='.intval($id).' AND objet = '.Sql::quote($type)) > 1
	) {
		include_spip('inc/presentation');
		$flux['data'] .= icone_horizontale(_T('revisions:info_historique_lien'), generer_url_ecrire('revision',"id_objet=$id&objet=$type"), "revision-24.png");
	}

	return $flux;
}

/**
 * Afficher les dernières révisions sur l'accueil et le suivi
 *
 * Liste les révisions en bas de la page d'accueil de ecrire/
 * et sur la page de suivi de l'activité du site
 *
 * @param array $flux   Données du pipeline
 * @return array $flux  Données du pipeline
 */
function revisions_affiche_milieu($flux) {
	// la bonne page et des objets révisables cochées !
	if (in_array($flux['args']['exec'], array('accueil', 'suivi_edito'))
		AND unserialize($GLOBALS['meta']['objets_versions'])) {
		$contexte = array();
		if ($GLOBALS['visiteur_session']['statut']!=='0minirezo') {
			$contexte['id_auteur'] = $GLOBALS['visiteur_session']['id_auteur'];
		}
		$flux['data'] .= recuperer_fond('prive/objets/liste/versions',$contexte,array('ajax'=>true));
	}
	return $flux;
}

/**
 * Définir les metas de configuration liées aux révisions
 * 
 * Utilisé par inc/config
 *
 * @param array $metas  Liste des métas et leurs valeurs par défaut
 * @return array        Liste des métas et leurs valeurs par défaut
 */
function revisions_configurer_liste_metas($metas){
	// Dorénavant dans les metas on utilisera un array serialisé de types d'objets
	// qui correspondront aux objets versionnés
	$metas['objets_versions'] =  '';

	return $metas;
}

/**
 * Charge les données d'une révision donnée dans le formulaire d'édition d'un objet
 * 
 * @param array $flux   Données du pipeline
 * @return array $flux  Données du pipeline
 */
function revisions_formulaire_charger($flux){
	if (strncmp($flux['args']['form'],'editer_',7)==0
		AND $id_version = _request('id_version')
		AND $objet = substr($flux['args']['form'],7)
		AND $id_table_objet = id_table_objet($objet)
		AND isset($flux['data'][$id_table_objet])
		AND $id = intval($flux['data'][$id_table_objet])
		AND !$flux['args']['je_suis_poste']){
		// ajouter un message convivial pour indiquer qu'on a restaure la version
		$flux['data']['message_ok'] = _T('revisions:icone_restaurer_version',array('version'=>$id_version));
		$flux['data']['message_ok'] .= "<br />"._T('revisions:message_valider_recuperer_version');
		// recuperer la version
		include_spip('inc/revisions');
		$champs = recuperer_version($id,$objet, $id_version);
		foreach($champs as $champ=>$valeur){
			if (!strncmp($champ,'jointure_',9)==0){
				if ($champ=='id_rubrique')
					$flux['data']['id_parent'] = $valeur;
				else
					$flux['data'][$champ] = $valeur;
			}
		}
	}
	return $flux;
}

/**
 * Sur une insertion en base, lever un flag pour ne pas creer une premiere révision vide
 * dans pre_edition mais attendre la post_edition pour cela
 * 
 * @param array $x   Données du pipeline
 * @return array $x  Données du pipeline
 */
function revisions_post_insertion($x){
	$table = $x['args']['table'];
	include_spip('inc/revisions');
	if($champs = liste_champs_versionnes($table)) {
		$GLOBALS['premiere_revision']["$table:".$x['args']['id_objet']] = true;
	}
	return $x;
}

/**
 * Avant toute modification en base
 * vérifier qu'une version initiale existe bien pour cet objet
 * et la creer sinon avec l'etat actuel de l'objet
 *
 * @param array $x   Données du pipeline
 * @return array $x  Données du pipeline
 */
function revisions_pre_edition($x) {
	// ne rien faire quand on passe ici en controle md5
	if (!isset($x['args']['action'])
		OR $x['args']['action']!=='controler'){
		$table = $x['args']['table'];
		include_spip('inc/revisions');
		// si flag leve passer son chemin, post_edition le fera (mais baisser le flag en le gardant en memoire tout de meme)
		if (isset($GLOBALS['premiere_revision']["$table:".$x['args']['id_objet']])){
			$GLOBALS['premiere_revision']["$table:".$x['args']['id_objet']] = 0;
		}
		// sinon creer une premiere revision qui date et dont on ne connait pas l'auteur
		elseif  ($versionnes = liste_champs_versionnes($table)) {
			$objet = isset($x['args']['type']) ? $x['args']['type'] : objet_type($table);
			verifier_premiere_revision($table, $objet, $x['args']['id_objet'], $versionnes, -1);
		}
	}
	return $x;
}

/**
 * Avant modification en base d'un lien,
 * enregistrer une première révision de l'objet si nécessaire
 *
 * @param array $x   Données du pipeline
 * @return array $x  Données du pipeline
 */
function revisions_pre_edition_lien($x) {
	if (intval($x['args']['id_objet_source'])>0
	    AND intval($x['args']['id_objet'])>0) {
		$table = table_objet_sql($x['args']['objet']);
		$id_objet = $x['data'];
		include_spip('inc/revisions');
		if (isset($GLOBALS['premiere_revision']["$table:".$id_objet])){
			$GLOBALS['premiere_revision']["$table:".$id_objet] = 0;
		}
		// ex : si le champ jointure_mots est versionnable sur les articles
		elseif ($versionnes = liste_champs_versionnes($table)
			AND in_array($j='jointure_'.table_objet($x['args']['objet_source']),$versionnes)){
			verifier_premiere_revision($table,$x['args']['objet'],$id_objet,$versionnes,-1);
		}

		$table = table_objet_sql($x['args']['objet_source']);
		$id_objet = $x['args']['id_objet_source'];
		if (isset($GLOBALS['premiere_revision']["$table:".$id_objet])){
			$GLOBALS['premiere_revision']["$table:".$id_objet] = 0;
		}
		// ex : si le champ jointure_articles est versionnable sur les mots
		elseif ($versionnes = liste_champs_versionnes($table)
			AND in_array($j='jointure_'.table_objet($x['args']['objet']),$versionnes)){
			verifier_premiere_revision($table,$x['args']['objet_source'],$id_objet,$versionnes,-1);
		}
	}

	return $x;
}

/**
 * Après modification en base, versionner l'objet
 *
 * @param array $x   Données du pipeline
 * @return array $x  Données du pipeline
 */
function revisions_post_edition($x) {
	include_spip('inc/revisions');
	if ($versionnes = liste_champs_versionnes($x['args']['table'])){
		// Regarder si au moins une des modifs est versionnable
		$champs = array();
		$table = $x['args']['table'];
		$objet = isset($x['args']['type']) ? $x['args']['type'] : objet_type($table);
		if (isset($GLOBALS['premiere_revision']["$table:".$x['args']['id_objet']])){
			unset($GLOBALS['premiere_revision']["$table:".$x['args']['id_objet']]);
			// verifier la premiere version : sur une version initiale on attend ici pour la creer
			// plutot que de creer une version vide+un diff
			verifier_premiere_revision($table, $objet, $x['args']['id_objet'], $versionnes, $GLOBALS['visiteur_session']['id_auteur']);
		}
		else {
			// on versionne les differences
			foreach ($versionnes as $key)
				if (isset($x['data'][$key]))
					$champs[$key] = $x['data'][$key];

			if (count($champs))
				ajouter_version($x['args']['id_objet'],$objet, $champs, '', $GLOBALS['visiteur_session']['id_auteur']);
		}
	}
	return $x;
}


/**
 * Après modification en base d'un lien, versionner l'objet si nécessaire
 *
 * @param array $x   Données du pipeline
 * @return array $x  Données du pipeline
 */
function revisions_post_edition_lien($x) {
	/*pipeline('post_edition_lien',
		array(
			'args' => array(
				'table_lien' => $table_lien,
				'objet_source' => $objet_source,
				'id_objet_source' => $l[$primary],
				'objet' => $l['objet'],
				'id_objet' => $id_o,
				'action'=>'delete',
			),
			'data' => $id_o
		)
	*/
	if (intval($x['args']['id_objet_source'])>0
	    AND intval($x['args']['id_objet'])>0) {

		$table = table_objet_sql($x['args']['objet']);
		$id_objet = $x['data'];
		include_spip('inc/revisions');
		if (isset($GLOBALS['premiere_revision']["$table:".$id_objet])){
			$GLOBALS['premiere_revision']["$table:".$id_objet] = 0;
		}
		// ex : si le champ jointure_mots est versionnable sur les articles
		elseif ($versionnes = liste_champs_versionnes($table)
			AND in_array($j='jointure_'.table_objet($x['args']['objet_source']),$versionnes)){
			$champs = array($j=>recuperer_valeur_champ_jointure($x['args']['objet'],$id_objet,$x['args']['objet_source']));
			ajouter_version($id_objet,$x['args']['objet'], $champs, '', $GLOBALS['visiteur_session']['id_auteur']);
		}

		$table = table_objet_sql($x['args']['objet_source']);
		$id_objet = $x['args']['id_objet_source'];
		if (isset($GLOBALS['premiere_revision']["$table:".$id_objet])){
			$GLOBALS['premiere_revision']["$table:".$id_objet] = 0;
		}
		// ex : si le champ jointure_articles est versionnable sur les mots
		elseif ($versionnes = liste_champs_versionnes($table)
			AND in_array($j='jointure_'.table_objet($x['args']['objet']),$versionnes)){
			$champs = array($j=>recuperer_valeur_champ_jointure($x['args']['objet_source'],$id_objet,$x['args']['objet']));
			ajouter_version($id_objet,$x['args']['objet_source'], $champs, '', $GLOBALS['visiteur_session']['id_auteur']);
		}
	}

	return $x;
}

/**
 * Ajoute la tâche d'optimisation des tables dans la liste des tâches périodiques
 *
 * @pipeline taches_generales_cron
 *
 * @param array $taches_generales
 *     Tableau des tâches et leur périodicité en seconde
 * @return array
 *     Tableau des tâches et leur périodicité en seconde
 */
function revisions_taches_generales_cron($taches_generales){
	$taches_generales['optimiser_revisions'] = 86400;
	return $taches_generales;
}
?>
