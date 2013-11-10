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

function formulaires_changer_fichier_document_charger_dist($id_document){
	$valeurs = Sql::fetsel('id_document,fichier,distant','spip_documents','id_document='.intval($id_document));
	if (!$valeurs)
		return array('editable'=>false);

	$charger = charger_fonction('charger','formulaires/joindre_document');
	$valeurs = array_merge($valeurs,$charger($id_document,0,'','choix'));

	$valeurs['_hidden'] .= "<input name='id_document' value='$id_document' type='hidden' />";
	
	return $valeurs;
}

function formulaires_changer_fichier_document_verifier_dist($id_document){
	$erreurs = array();
	if (_request('copier_local')){
	}
	else {
		$verifier = charger_fonction('verifier','formulaires/joindre_document');
		$erreurs = $verifier($id_document);
	}
	return $erreurs;
}

function formulaires_changer_fichier_document_traiter_dist($id_document){
	if (_request('copier_local')){
		$copier_local = charger_fonction('copier_local','action');
		$res = array('editable'=>true);
		if (($err=$copier_local($id_document))===true)
			$res['message_ok'] = _T('medias:document_copie_locale_succes');
		else
			$res['message_erreur'] = $err;
	}
	else {
		// liberer le nom de l'ancien fichier pour permettre le remplacement par un fichier du meme nom
		if ($ancien_fichier = Sql::getfetsel('fichier','spip_documents','id_document='.intval($id_document))
		  AND @file_exists($f = get_spip_doc($ancien_fichier))){
			spip_unlink($f);
		}
		$traiter = charger_fonction('traiter','formulaires/joindre_document');
		$res = $traiter($id_document);
	}
	return $res;
}

?>