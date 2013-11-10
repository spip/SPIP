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


// On liste tous les champs susceptibles de contenir des documents ou images si on veut que ces derniers soient lies a l objet lorsqu on y fait reference par imgXX docXX ou embXX
// la dist ne regarde que chapo et texte, on laisse comme ca, mais ca permet d etendre a descriptif ou toto depuis d autre plugin comme agenda ou grappe
$GLOBALS['medias_liste_champs'][] = 'texte';
$GLOBALS['medias_liste_champs'][] = 'chapo';
 
// http://doc.spip.org/@marquer_doublons_documents
function inc_marquer_doublons_doc_dist($champs,$id,$type,$id_table_objet,$table_objet,$spip_table_objet, $desc=array(), $serveur=''){
	$champs_selection=array();

	foreach ($GLOBALS['medias_liste_champs'] as $champs_choisis) {
		if ( isset($champs[$champs_choisis]) )
			array_push($champs_selection,$champs_choisis);
	}
	if (count($champs_selection) == 0)
		return;
	if (!$desc){
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($table_objet, $serveur);
	}
	$load = "";
	// charger le champ manquant en cas de modif partielle de l	'objet
	// seulement si le champ existe dans la table demande

	$champs_a_traiter = "";
	foreach ($champs_selection as $champs_a_parcourir) {
		if (isset($desc['field'][$champs_a_parcourir])) {
			$load = $champs_a_parcourir;
			$champs_a_traiter .= $champs[$champs_a_parcourir];
		}
	}

	if ($load) {
		$champs[$load] = '';
		$row = Sql::fetsel($load, $spip_table_objet, "$id_table_objet=".Sql::quote($id));
		if ($row AND isset($row[$load]))
			$champs[$load] = $row[$load];
	}
	include_spip('inc/texte');
	include_spip('action/editer_liens');
	include_spip('base/objets');
	$modeles = lister_tables_objets_sql('spip_documents');
	$modeles = $modeles['modeles'];
	$GLOBALS['doublons_documents_inclus'] = array();
	$env = array(
		'objet' => $type,
		'id_objet' => $id,
		$id_table_objet => $id
	);
	traiter_modeles($champs_a_traiter,array('documents'=>$modeles),'','',null,$env); // detecter les doublons
	objet_qualifier_liens(array('document'=>'*'),array($type=>$id),array('vu'=>'non'));
	if (count($GLOBALS['doublons_documents_inclus'])){
		// on repasse par une requete sur spip_documents pour verifier que les documents existent bien !
		$in_liste = Sql::in('id_document',$GLOBALS['doublons_documents_inclus']);
		$res = Sql::allfetsel("id_document", "spip_documents", $in_liste);
		$res = array_map('reset',$res);
		// Creer le lien s'il n'existe pas deja
		objet_associer(array('document'=>$res),array($type=>$id),array('vu'=>'oui'));
		objet_qualifier_liens(array('document'=>$res),array($type=>$id),array('vu'=>'oui'));
	}
}

?>