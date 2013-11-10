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
 * Boite de configuration des objets articles
 *
 * @param array $flux
 * @return array
 */
function petitions_afficher_config_objet($flux){
	if ((($type = $flux['args']['type'])=='article')
	AND ($id = $flux['args']['id'])){
		if (autoriser('modererpetition', $type, $id)){
			$table = table_objet($type);
			$id_table_objet = id_table_objet($type);
			$flux['data'] .= recuperer_fond("prive/configurer/petitionner",array($id_table_objet=>$id));
		}
	}
	return $flux;
}


/**
 * Optimiser la base de donnee en supprimant les forums orphelins
 *
 * @param int $n
 * @return int
 */
function petitions_optimiser_base_disparus($flux){
	$n = &$flux['data'];
	$mydate = $flux['args']['date'];

	//
	// Signatures poubelles
	//

	Sql::delete("spip_petitions", "statut='poubelle' AND maj < $mydate");

	// rejeter les signatures non confirmees trop vieilles (20jours)
	if (!defined('_PETITIONS_DELAI_SIGNATURES_REJETEES'))
		define('_PETITIONS_DELAI_SIGNATURES_REJETEES',20);
	Sql::delete("spip_signatures", "NOT (statut='publie' OR statut='poubelle') AND NOT(" . Sql::date_proche('date_time', -_PETITIONS_DELAI_SIGNATURES_REJETEES, ' DAY') . ')');


	return $flux;

}

?>
