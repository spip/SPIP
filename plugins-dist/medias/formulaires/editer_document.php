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
include_spip('inc/documents');
include_spip('inc/config');

function formulaires_editer_document_charger_dist($id_document='new', $id_parent='', $retour='', $lier_trad=0, $config_fonc='documents_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('document',$id_document,$id_parent,$lier_trad,$retour,$config_fonc,$row,$hidden);

	// relier les parents
	$valeurs['parents'] = array();
	$valeurs['_hidden'] = "";
	$parents = Sql::allfetsel('objet,id_objet','spip_documents_liens','id_document='.intval($id_document));
	foreach($parents as $p){
		if (in_array($p['objet'],array('article','rubrique')) AND $p['id_objet']>0)
			$valeurs['parents'][] = $p['objet'].'|'.$p['id_objet'];
		else
			$valeurs['_hidden'] .= "<input type='hidden' name='parents[]' value='".$p['objet'].'|'.$p['id_objet']."' />";
	}

	// en fonction de la config du site on a le droit ou pas de modifier la date
	if ($valeurs['_editer_date'] = (lire_config('documents_date') == 'oui' ? ' ' : '')){
		$valeurs['saisie_date'] = affdate($valeurs['date'],'d/m/Y');
		$valeurs['saisie_heure'] = affdate($valeurs['date'],'H:i');
	}
	elseif (isset($valeurs['date'])){
		unset($valeurs['date']);
	}
	
	// en fonction du format
	$valeurs['_editer_dimension'] = autoriser('tailler','document',$id_document)?' ':'';

	// type du document et inclusion
	$row = Sql::fetsel('titre as type_document,inclus','spip_types_documents','extension='.Sql::quote($valeurs['extension']));
	$valeurs['type_document'] = $row['type_document'];
	$valeurs['_inclus'] = $row['inclus'];
	if (in_array($valeurs['extension'],array('jpg','gif','png'))){
		$valeurs['apercu'] = get_spip_doc($valeurs['fichier']);
	}

	// verifier les infos de taille et dimensions sur les fichiers locaux
	// cas des maj de fichier directes par ftp
	if ($valeurs['distant']!=='oui'){
		include_spip('inc/renseigner_document');
		$infos = renseigner_taille_dimension_image(get_spip_doc($valeurs['fichier']),$valeurs['extension']);
		if ($infos['taille']!=$valeurs['taille']
			OR ($infos['type_image'] && ($infos['largeur']!=$valeurs['largeur']))
			OR ($infos['type_image'] && ($infos['hauteur']!=$valeurs['hauteur']))){
			$valeurs['_taille_modif'] = $infos['taille'];
			$valeurs['_largeur_modif'] = $infos['largeur'];
			$valeurs['_hauteur_modif'] = $infos['hauteur'];
			$valeurs['_hidden'].=
			"<input type='hidden' name='_taille_modif' value='".$infos['taille']."' />"
			. "<input type='hidden' name='_largeur_modif' value='".$infos['largeur']."' />"
			. "<input type='hidden' name='_hauteur_modif' value='".$infos['hauteur']."' />";
		}
	}


	// pour l'upload d'un nouveau doc
	if ($valeurs['fichier']){
		$charger = charger_fonction('charger','formulaires/joindre_document');
		$valeurs = array_merge($valeurs,$charger($id_document,0,'','choix'));
		$valeurs['_hidden'] .= "<input name='id_document' value='$id_document' type='hidden' />";
	}

	return $valeurs;
}

// Choix par defaut des options de presentation
function documents_edit_config($row)
{
	global $spip_lang;

	$config = array();//$GLOBALS['meta'];
	$config['lignes'] = 8;
	$config['langue'] = $spip_lang;

	$config['restreint'] = ($row['statut'] == 'publie');
	return $config;
}

function formulaires_editer_document_verifier_dist($id_document='new', $id_parent='', $retour='', $lier_trad=0, $config_fonc='documents_edit_config', $row=array(), $hidden=''){
	$erreurs = formulaires_editer_objet_verifier('document',$id_document,is_numeric($id_document)?array():array('titre'));

	// verifier l'upload si on a demande a changer le document
	if (_request('joindre_upload') OR _request('joindre_ftp') OR _request('joindre_distant')){
		if (_request('copier_local')){
		}
		else {
			$verifier = charger_fonction('verifier','formulaires/joindre_document');
			$erreurs = array_merge($erreurs,$verifier($id_document));
		}
	}
	
	// On ne vérifie la date que si on avait le droit de la modifier
	if (lire_config('documents_date') == 'oui'){
		if (!$date = recup_date(_request('saisie_date').' '._request('saisie_heure').':00')
		  OR !($date = mktime($date[3],$date[4],0,$date[1],$date[2],$date[0])))
		  $erreurs['saisie_date'] = _T('medias:format_date_incorrect');
		else {
			set_request('saisie_date',date('d/m/Y',$date));
			set_request('saisie_heure',date('H:i',$date));
			set_request('date',date("Y-m-d H:i:s",$date));
		}
	}

	return $erreurs;
}

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_editer_document_traiter_dist($id_document='new', $id_parent='', $retour='', $lier_trad=0, $config_fonc='documents_edit_config', $row=array(), $hidden=''){
	if (is_null(_request('parents')))
		set_request('parents',array());

	// verifier les infos de taille et dimensions sur les fichiers locaux
	// cas des maj de fichier directes par ftp
	foreach(array('taille','largeur','hauteur') as $c)
	if (($v=_request("_{$c}_modif")) AND !_request($c)){
		set_request($c,$v);
	}

	$res = formulaires_editer_objet_traiter('document',$id_document,$id_parent,$lier_trad,$retour,$config_fonc,$row,$hidden);
	$autoclose = "<script type='text/javascript'>if (window.jQuery) jQuery.modalboxclose();</script>";
	if (_request('copier_local')
	  OR _request('joindre_upload')
	  OR _request('joindre_ftp')
	  OR _request('joindre_distant')
	  OR _request('joindre_zip')){
		$autoclose = "";
		if (_request('copier_local')){
			$copier_local = charger_fonction('copier_local','action');
			$res = array('editable'=>true);
			if (($err=$copier_local($id_document))===true)
				$res['message_ok'] = (isset($res['message_ok'])?$res['message_ok'].'<br />':'')._T('medias:document_copie_locale_succes');
			else
				$res['message_erreur'] = (isset($res['message_erreur'])?$res['message_erreur'].'<br />':'').$err;
			set_request('credits'); // modifie par la copie locale
		}
		else {
			// liberer le nom de l'ancien fichier pour permettre le remplacement par un fichier du meme nom
			if ($ancien_fichier = Sql::getfetsel('fichier','spip_documents','id_document='.intval($id_document))
				AND @file_exists($rename = get_spip_doc($ancien_fichier))){
				@rename($rename,"$rename--.old");

			}
			$traiter = charger_fonction('traiter','formulaires/joindre_document');
			$res2 = $traiter($id_document);
			if (isset($res2['message_erreur'])){
				$res['message_erreur'] = $res2['message_erreur'];
				// retablir le fichier !
				if ($rename)
					@rename("$rename--.old",$rename);
			}
			else
				// supprimer vraiment le fichier initial
				spip_unlink("$rename--.old");
		}
		// on annule les saisies largeur/hauteur : l'upload a pu charger les siens
		set_request('largeur');
		set_request('hauteur');
	}
	else{
		// regarder si une demande de rotation a eu lieu
		// c'est un bouton image, dont on a pas toujours le name en request, on fait avec
		$angle = 0;
		if (_request('tournerL90') OR _request('tournerL90_x'))
			$angle = -90;
		if (_request('tournerR90') OR _request('tournerR90_x'))
			$angle = 90;
		if (_request('tourner180') OR _request('tourner180_x'))
			$angle = 180;
		if ($angle){
			$autoclose = "";
			$tourner = charger_fonction('tourner','action');
			action_tourner_post($id_document,$angle);
		}
	}

	if (!isset($res['redirect']))
		$res['editable'] = true;
	if (!isset($res['message_erreur']))
		$res['message_ok'] = _T('info_modification_enregistree').$autoclose;

	if ($res['message_ok'])
		$res['message_ok'].='<script type="text/javascript">if (window.jQuery) ajaxReload("document_infos");</script>';

	return $res;
}

?>
