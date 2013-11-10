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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Definir les meta de configuration liee aux mots
 *
 * @param array $metas
 * @return array
 */
function mots_configurer_liste_metas($metas){
	$metas['articles_mots'] =  'non';
	$metas['config_precise_groupes'] =  'non';
	#$metas['mots_cles_forums'] =  'non';
	return $metas;
}

/**
 * Configuration des mots
 *
 * @param array $flux
 * @return array
 */
function mots_affiche_milieu($flux){
	if ($flux["args"]["exec"] == "configurer_contenu") {
		$flux["data"] .=  recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_mots'));
	}

	// si on est sur une page ou il faut inserer les mots cles...
	if ($en_cours = trouver_objet_exec($flux['args']['exec'])
		AND $en_cours['edition']!==true // page visu
		AND $type = $en_cours['type']
		AND $id_table_objet = $en_cours['id_table_objet']
		AND ($id = intval($flux['args'][$id_table_objet]))){
		$texte = recuperer_fond(
				'prive/objets/editer/liens',
				array(
					'table_source'=>'mots',
					'objet'=>$type,
					'id_objet'=>$id,
				)
		);
		if ($p=strpos($flux['data'],"<!--affiche_milieu-->"))
			$flux['data'] = substr_replace($flux['data'],$texte,$p,0);
		else
			$flux['data'] .= $texte;
	}
		
	return $flux;
}

/**
 * Optimiser la base de donnee en supprimant les liens orphelins
 *
 * @param int $n
 * @return int
 */
function mots_optimiser_base_disparus($flux){
	$n = &$flux['data'];
	$mydate = $flux['args']['date'];
	
	$result = Sql::delete("spip_mots", "length(titre)=0 AND maj < $mydate");

	include_spip('action/editer_liens');
	// optimiser les liens morts :
	// entre mots vers des objets effaces
	// depuis des mots effaces
	$n+= objet_optimiser_liens(array('mot'=>'*'),'*');

	return $flux;

}


/**
 * Copier le type des groupes sur la table spip_mots
 * a chaque changement d'un groupe.
 *
 * @param array $flux
 * @return array
 */
function mots_post_edition($flux){
	if (($flux['args']['table'] == 'spip_groupes_mots')
		and isset($flux['data']['titre']))
	{
		Sql::updateq('spip_mots', array('type' => $flux['data']['titre']),
			'id_groupe=' . $flux['args']['id_objet']);
	}

	return $flux;
}

?>
