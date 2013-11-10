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

include_spip('inc/actions');
include_spip('inc/editer');

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_charger_dist($id_mot='new', $id_groupe=0, $retour='', $associer_objet='', $dummy1='', $dummy2='', $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('mot',$id_mot,$id_groupe,'',$retour,$config_fonc,$row,$hidden);
	if ($valeurs['id_parent'] && !$valeurs['id_groupe'])
		$valeurs['id_groupe'] = $valeurs['id_parent'];
	
	if ($associer_objet){
		if (intval($associer_objet)){
			// compat avec l'appel de la forme ajouter_id_article
			$objet = 'article';
			$id_objet = intval($associer_objet);
		}
		else {
			list($objet,$id_objet) = explode('|',$associer_objet);
		}
	}
	$valeurs['table'] = ($associer_objet?table_objet($objet):'');

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
function formulaires_editer_mot_identifier_dist($id_mot='new', $id_groupe=0, $retour='', $associer_objet='', $dummy1='', $dummy2='', $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_mot),$associer_objet));
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function mots_edit_config($row)
{
	global $spip_ecran, $spip_lang;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['langue'] = $spip_lang;
	$config['restreint'] = false;
	return $config;
}

function formulaires_editer_mot_verifier_dist($id_mot='new', $id_groupe=0, $retour='', $associer_objet='', $dummy1='', $dummy2='', $config_fonc='mots_edit_config', $row=array(), $hidden=''){

	$erreurs = formulaires_editer_objet_verifier('mot',$id_mot,array('titre'));
	// verifier qu'un mot du meme groupe n'existe pas avec le meme titre
	// la comparaison accepte un numero absent ou different
	// sinon avertir
	if (!count($erreurs) AND !_request('confirm_titre_mot')){
		if (Sql::countsel("spip_mots", 
						"titre REGEXP ".Sql::quote("^([0-9]+[.] )?".preg_quote(supprimer_numero(_request('titre')))."$")
						." AND id_mot<>".intval($id_mot)))
			$erreurs['titre'] =
						_T('mots:avis_doublon_mot_cle')
						." <input type='hidden' name='confirm_titre_mot' value='1' />";
	}
	return $erreurs;
}

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_traiter_dist($id_mot='new', $id_groupe=0, $retour='', $associer_objet='', $dummy1='', $dummy2='', $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$res = array();
	set_request('redirect','');
	$action_editer = charger_fonction("editer_mot",'action');
	list($id_mot,$err) = $action_editer();
	if ($err){
		$res['message_erreur'] = $err;
	}
	else {
		$res['message_ok'] = "";
		if ($retour){
			if (strncmp($retour,'javascript:',11)==0){
				$res['message_ok'] .= '<script type="text/javascript">/*<![CDATA[*/'.substr($retour,11).'/*]]>*/</script>';
				$res['editable'] = true;
			}
			else {
				$res['redirect'] = $retour;
				if (strlen(parametre_url($retour,'id_mot')))
					$res['redirect'] = parametre_url($res['redirect'],'id_mot',$id_mot);
			}
		}

		if ($associer_objet){
			if (intval($associer_objet)){
				// compat avec l'appel de la forme ajouter_id_article
				$objet = 'article';
				$id_objet = intval($associer_objet);
			}
			else {
				list($objet,$id_objet) = explode('|',$associer_objet);
			}
			if ($objet AND $id_objet AND autoriser('modifier',$objet,$id_objet)){
				include_spip('action/editer_mot');
				mot_associer($id_mot, array($objet=>$id_objet));
				if (isset($res['redirect']))
					$res['redirect'] = parametre_url ($res['redirect'], "id_lien_ajoute", $id_mot, '&');
			}
		}

	}
	return $res;
}


?>
