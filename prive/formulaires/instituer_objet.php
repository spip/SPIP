<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/editer');
include_spip('inc/autoriser');
include_spip('inc/puce_statut');

/**
 * filtres les statuts utilisable selon les droits de publication
 * @param array $desc
 * @param bool $publiable
 * @return array
 */
function lister_statuts_proposes($desc,$publiable = true){
	if (!isset($desc['statut_textes_instituer']))
		return false;

	$l = $desc['statut_textes_instituer'];
	if (!$publiable){
		unset($l['publie']);
		unset($l['refuse']);
	}
	return $l;
}

/**
 * Charger #FORMULAIRE_INSTITUER_OBJET
 * @param string $objet
 * @param int $id_objet
 * @param string $retour
 * @return array|bool
 */
function formulaires_instituer_objet_charger_dist($objet,$id_objet,$retour="",$editable = true){
	$editable = ($editable?true:false);

	$table = table_objet_sql($objet);
	$desc = lister_tables_objets_sql($table);

	if (!isset($desc['statut_textes_instituer']))
		return false;

	if (!autoriser('modifier', $objet, $id_objet))
		$editable = false;

	// charger le contenu de l'objet
	// dont son champ statut
	$v = formulaires_editer_objet_charger($objet,$id_objet,0,0,'','');

	$publiable = true;
	$statuts = lister_statuts_proposes($desc);
	// tester si on a le droit de publier, si un statut publie existe
	if (isset($statuts['publie'])){
		if (!autoriser('instituer', $objet, $id_objet, null, array('statut'=>'publie'))){
			if ($v['statut'] == 'publie')
				$editable = false;
			else
				$publiable = false;
		}
	}
	$valeurs = array(
		'editable' => $editable,
		'statut' => $v['statut'],
		'_objet' => $objet,
		'_id_objet' => $id_objet,
		'_statuts' => lister_statuts_proposes($desc, $editable?$publiable:true),
		'_publiable' => $publiable,
		'_label' => isset($desc['texte_changer_statut'])?$desc['texte_changer_statut']:'texte_article_statut',
		'_aide' => isset($desc['aide_changer_statut'])?$desc['aide_changer_statut']:'',
		'_hidden' => "<input type='hidden' name='statut_old' value='".$v['statut']."' />",
	);

	#if (!count($valeurs['statuts']))
	return $valeurs;
}

/**
 * Verifier #FORMULAIRE_INSTITUER_OBJET
 * @param string $objet
 * @param int $id_objet
 * @param string $retour
 * @return array
 */
function formulaires_instituer_objet_verifier_dist($objet,$id_objet,$retour="",$editable = true){
	$erreurs = array();
	// charger le contenu de l'objet
	// dont son champ statut
	$v = formulaires_editer_objet_charger($objet,$id_objet,0,0,'','');

	if ($v['statut']!==_request('statut_old'))
		$erreurs['statut'] = _T('instituer_erreur_statut_a_change');
	else {
		$table = table_objet_sql($objet);
		$desc = lister_tables_objets_sql($table);

		$publiable = true;
		if (isset($v['id_rubrique'])
			AND !autoriser('publierdans', 'rubrique', $v['id_rubrique'])) {
			$publiable = false;
		}
		$l = lister_statuts_proposes($desc, $publiable);
		$statut = _request('statut');
		if (!isset($l[$statut])
		  OR !autoriser('instituer',$objet,$id_objet,'',array('statut'=>$statut)))
			$erreurs['statut'] = _T('instituer_erreur_statut_non_autorise');
	}

	return $erreurs;
}

/**
 * Traiter #FORMULAIRE_INSTITUER_OBJET
 * @param string $objet
 * @param int $id_objet
 * @param string $retour
 * @return array
 */
function formulaires_instituer_objet_traiter_dist($objet,$id_objet,$retour="",$editable = true){

	$c = array('statut' => _request('statut'));
	// si on a envoye une 'date_posterieure', l'enregistrer
	// todo dans le HTML
	if ($d = _request('date_posterieure'))
		$c['date'] = $d;


	include_spip('action/editer_objet');
	if ($err=objet_instituer($objet, $id_objet, $c))
		$res = array('message_erreur'=>$err);
	else {
		$res = array('message_ok'=>_T('info_modification_enregistree'));
		if ($retour)
			$res['redirect'] = $retour;
		set_request('statut');
		set_request('date_posterieure');
	}

	return $res;
}