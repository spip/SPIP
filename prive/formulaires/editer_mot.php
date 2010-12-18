<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/editer');

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_charger_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('mot',$id_mot,$id_groupe,'',$retour,$config_fonc,$row,$hidden);
	if ($valeurs['id_parent'] && !$valeurs['id_groupe'])
		$valeurs['id_groupe'] = $valeurs['id_parent'];
	$valeurs['table'] = $table;

	// Si nouveau et titre dans l'url : fixer le titre
	if ($id_mot == 'oui'
	AND strlen($titre = _request('titre')))
		$valeurs['titre'] = $titre;

	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_mot_identifier_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	return serialize(array($id_mot,$ajouter_id_article,$row));
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function mots_edit_config($row)
{
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['langue'] = $spip_lang;

	$config['restreint'] = ($row['statut'] == 'publie');
	return $config;
}

function formulaires_editer_mot_verifier_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){

	$erreurs = formulaires_editer_objet_verifier('mot',$id_mot,array('titre'));
	// verifier qu'un mot du meme groupe n'existe pas avec le meme titre
	// la comparaison accepte un numero absent ou different
	// sinon avertir
	if (!count($erreurs) AND !_request('confirm_titre_mot')){
		if (sql_countsel("spip_mots", 
						"titre REGEXP ".sql_quote("^([0-9]+[.] )?".preg_quote(supprimer_numero(_request('titre')))."$")
						." AND id_mot<>".intval($id_mot)))
			$erreurs['titre'] =
						_T('avis_doublon_mot_cle')
						." <input type='hidden' name='confirm_titre_mot' value='1' />";
	}
	return $erreurs;
}

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_traiter_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$res = '';
	set_request('redirect','');
	$action_editer = charger_fonction("editer_mot",'action');
	list($id_mot,$err) = $action_editer();
	if ($err){
		$res['message_erreur'] = $err;
	}
	else {
		if ($ajouter_id_article){
			$id_groupe = intval(_request('id_groupe'));
			ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);
		}
		if ($retour)
			$res['redirect'] = $retour;
	}
	return $res;
}


// http://doc.spip.org/@ajouter_nouveau_mot
function ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $id)
{
	if (un_seul_mot_dans_groupe($id_groupe)) {
		sql_delete("spip_mots_$table", "$table_id=$id AND " . sql_in_select("id_mot", "id_mot", "spip_mots", "id_groupe = $id_groupe"));
	}
	sql_insertq("spip_mots_$table", array("id_mot" => $id_mot, $table_id => $id));
}

// http://doc.spip.org/@un_seul_mot_dans_groupe
function un_seul_mot_dans_groupe($id_groupe)
{
	return sql_countsel('spip_groupes_mots', "id_groupe=$id_groupe AND unseul='oui'");
}
?>
