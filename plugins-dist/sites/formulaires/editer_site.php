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
include_spip('inc/filtres'); // pour vider_url()

function formulaires_editer_site_charger_dist($id_syndic='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='sites_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('site',$id_syndic,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
	# pour recuperer le logo issu d'analyse auto
	$valeurs['logo']='';
	$valeurs['format_logo']='';
	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_site_identifier_dist($id_syndic='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='sites_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_syndic),$lier_trad));
}

// Choix par defaut des options de presentation
function sites_edit_config($row)
{
	global $spip_lang;

	$config = $GLOBALS['meta'];
	$config['lignes'] = 8;
	$config['langue'] = $spip_lang;

	$config['restreint'] = false;
	return $config;
}

function formulaires_editer_site_verifier_dist($id_syndic='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='sites_edit_config', $row=array(), $hidden=''){
	include_spip('inc/filtres');
	include_spip('inc/site');
	$oblis = array('nom_site','url_site','id_parent');
	// Envoi depuis le formulaire d'analyse automatique d'un site
	if (_request('ajoute_url_auto') AND strlen(vider_url($u = _request('url_auto')))) {
		if ($auto = analyser_site($u)) {
			// Si pas de logo, on va le chercher dans le ou les feeds
			if(isset($auto['url_syndic']) && !$auto['logo'] && ($auto['url_syndic'] != _request('ajouter_url_auto')) && preg_match(',^select: (.+),', $auto['url_syndic'], $regs)){
				$url_syndic = str_replace('select: ','',$auto['url_syndic']);
				$feeds = explode(' ',$regs[1]);
				foreach ($feeds as $feed) {
					if(($auto_syndic = analyser_site($feed)) && isset($auto_syndic['format_logo'])){
						$auto['format_logo'] = $auto_syndic['format_logo'];
						$auto['logo'] = $auto_syndic['logo'];
						break;
					}
				}
			}
			foreach($auto as $k=>$v){
				set_request($k,$v);
			}
			$erreurs['verif_url_auto'] = _T('sites:texte_referencement_automatique_verifier', array('url' => $u));
		}
		else{
			$erreurs['url_auto'] = _T('sites:avis_site_introuvable');
		}
	}
	else{
		// auto-renseigner le titre si il n'existe pas
		// d'abord a partir du descriptif en coupant
		titre_automatique('nom_site',array('descriptif'));
		// et sinon l'url du site, sans couper
		titre_automatique('nom_site',array('url_site'),255);
		$erreurs = formulaires_editer_objet_verifier('site',$id_syndic,$oblis);
	}
	return $erreurs;
}

function formulaires_editer_site_traiter_dist($id_syndic='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='sites_edit_config', $row=array(), $hidden=''){
	// netoyer les entrees
	if (!is_null(_request('url_site')))
		set_request('url_site',vider_url(_request('url_site')));

	return formulaires_editer_objet_traiter('site',$id_syndic,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
}


?>
