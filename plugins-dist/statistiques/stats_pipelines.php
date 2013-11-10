<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

// sur les envois html,
// compter les visites.
function stats_affichage_entetes_final($entetes){
	if ($GLOBALS['meta']["activer_statistiques"] != "non") {
		$html = preg_match(',^\s*text/html,', $entetes['Content-Type']);

		// decomptage des visites, on peut forcer a oui ou non avec le header X-Spip-Visites
		// par defaut on ne compte que les pages en html (ce qui exclue les js,css et flux rss)
		$spip_compter_visites = $html?'oui':'non';
		if (isset($page['entetes']['X-Spip-Visites'])){
			$spip_compter_visites = in_array($entetes['X-Spip-Visites'],array('oui','non'))
				?$entetes['entetes']['X-Spip-Visites']
				:$spip_compter_visites;
			unset($entetes['X-Spip-Visites']);
		}
			
		// Gestion des statistiques du site public
		
		if ($spip_compter_visites!='non') {
			$stats = charger_fonction('stats', 'public');
			$stats();
		}
	}
	return $entetes;
}


// contenus des pages exec
function stats_affiche_milieu($flux){
	// afficher le formulaire de configuration (activer ou desactiver les statistiques).
	if ($flux['args']['exec'] == 'configurer_avancees')
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_compteur'));

	
	// afficher le formulaire de suppression des visites (configuration > maintenance du site).
	if ($flux['args']['exec'] == 'admin_tech') {

		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/admin_effacer_stats',array());
		
	}
	return $flux;
}


// les boutons d'administration : ajouter les popularites et visites
function stats_formulaire_admin($flux) {
	if (
	 isset($flux['args']['contexte']['objet'])
	 AND $objet = $flux['args']['contexte']['objet']
	 AND isset($flux['args']['contexte']['id_objet'])
	 AND $id_objet = $flux['args']['contexte']['id_objet']
	 ) {
		if ($l = admin_stats($objet, $id_objet, defined('_VAR_PREVIEW') ? _VAR_PREVIEW : '')) {
			$btn = recuperer_fond('prive/bouton/statistiques', array(
				'visites' => $l[0],
				'popularite' => $l[1],
				'statistiques' => $l[2],
			));
			$flux['data'] = preg_replace('%(<!--extra-->)%is', $btn.'$1', $flux['data']);				
		}
	}
	return $flux;
}

// calculer les visites et popularite d'un objet/id_objet
// (uniquement valable pour les articles) ...
// http://doc.spip.org/@admin_stats
function admin_stats($objet, $id_objet, $var_preview)
{
	if ($GLOBALS['meta']["activer_statistiques"] != "non" 
	AND $objet == 'article'
	AND !$var_preview
	AND autoriser('voirstats')
	) {
		$row = Sql::fetsel("visites, popularite", "spip_articles", "id_article=$id_objet AND statut='publie'");

		if ($row) {
			return array(intval($row['visites']),
			       ceil($row['popularite']),
			       str_replace('&amp;', '&', generer_url_ecrire_statistiques($id_objet)));
		}
	}
	return false;
}

// http://doc.spip.org/@generer_url_ecrire_statistiques
function generer_url_ecrire_statistiques($id_article) {
	return generer_url_ecrire('stats_visites', "id_article=$id_article");
}



// les taches crons
function stats_taches_generales_cron($taches_generales){

	// stats : toutes les 5 minutes on peut vider un panier de visites
	if ($GLOBALS['meta']["activer_statistiques"] == "oui") {
		$taches_generales['visites'] = 300; 
		$taches_generales['popularites'] = 7200; # calcul lourd
	}
		
	return $taches_generales;
}

function stats_configurer_liste_metas($metas){
	$metas['activer_statistiques']='non';
	$metas['activer_captures_referers']='non';
	return $metas;
}

function stats_boite_infos($flux){
	if ($GLOBALS['meta']["activer_statistiques"] == "oui") {
		if ($flux['args']['type']=='article'
		AND $id_article=$flux['args']['id']
		AND autoriser('voirstats','article',$id_article))
		{
			$visites = Sql::getfetsel('visites','spip_articles','id_article='.intval($id_article));
			if ($visites>0){
				$icone_horizontale=chercher_filtre('icone_horizontale');
				$flux['data'].=$icone_horizontale(generer_url_ecrire("stats_visites","id_article=$id_article"),_T('statistiques:icone_evolution_visites', array('visites' => $visites)),"statistique-24.png");
			}
		}
	}
  return $flux;
}

?>