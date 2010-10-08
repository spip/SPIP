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
		'editable'=>autoriser('modifier',$objet,$id_objet),
		'_oups' => _request('_oups'),
	);

	return $valeurs;
}

function formulaires_editer_liens_traiter_dist($table_source,$objet,$id_objet){

	if (_request('tout_voir'))
		set_request('recherche','');


	if (autoriser('modifier',$objet,$id_objet)) {
		// annuler les suppressions du coup d'avant !
		if (_request('annuler_oups')
			AND $oups = _request('_oups')
			AND $oups = unserialize($oups)){
			$objet_source = objet_type($table_source);
			include_spip('action/editer_liens');
			foreach($oups as $oup) {
				objet_associer(array($objet_source=>$oup[$objet_source]), array($objet=>$oup[$objet]),$oup);
			}
			# oups ne persiste que pour la derniere action, si suppression
			set_request('_oups');
		}
		
		if ($ajouter = _request('ajouter_lien')){
			$ajouter_lien = charger_fonction('ajouter_lien','action');
			foreach($ajouter as $lien=>$dummy)
				$ajouter_lien($lien);
			# oups ne persiste que pour la derniere action, si suppression
			set_request('_oups');
		}

		if ($supprimer = _request('supprimer_lien')){
			include_spip('action/editer_liens');
			$oups = array();
			foreach($supprimer as $lien=>$dummy) {
				$lien = explode("-",$lien);
				list($objet_source,$ids,$objet_lie,$idl) = $lien;
				$oups = array_merge($oups,  objet_trouver_liens(array($objet_source=>$ids), array($objet_lie=>$idl)));
				objet_dissocier(array($objet_source=>$ids), array($objet_lie=>$idl));
			}
			set_request('_oups',$oups?serialize($oups):null);
		}
	}

	$res = array('editable'=>true);
	return $res;
}

?>