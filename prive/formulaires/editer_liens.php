<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;



/**
 * #FORMULAIRE_EDITER_LIENS{auteurs,article,23}
 *
 * @param string $table_source
 *		table des objets associes, doit correspondre a une table xxx_liens
 * @param string $objet
 *    objet auquel associer
 * @param int $id_objet
 *		id_objet auquel associer
 * @return array
 */
function formulaires_editer_liens_charger_dist($table_source,$objet,$id_objet){
	$objet_source = objet_type($table_source);
	$table_sql_source = table_objet_sql($objet_source);

	// verifier existence de la table xxx_liens
	include_spip('action/editer_liens');
	if (!objet_associable($objet_source))
		return false;
	
	$valeurs = array(
		'id'=>"$table_source-$objet-$id_objet", // identifiant unique pour les id du form
		'_vue_liee' => $table_source."_lies",
		'_vue_ajout' => $table_source."_associer",
		'objet'=>$objet,
		'id_objet'=>$id_objet,
		'objet_source'=>$objet_source,
		'recherche'=>'',
		'visible'=>0,
	);

	return $valeurs;
}

function formulaires_editer_liens_traiter_dist($table_source,$objet,$id_objet){

	if ($ajouter = _request('ajouter_lien')){
		$ajouter_lien = charger_fonction('ajouter_lien','action');
		foreach($ajouter as $lien=>$dummy)
			$ajouter_lien($lien);
	}

	if ($supprimer = _request('supprimer_lien')){
		$supprimer_lien = charger_fonction('supprimer_lien','action');
		foreach($supprimer as $lien=>$dummy)
			$supprimer_lien($lien);
	}

	$res = array('editable'=>true);
	return $res;
}

?>