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

/**
 * Fonction appellee lorsque l'utilisateur clique sur le bouton
 * 'copier en local' (document/portfolio).
 * Il s'agit de la partie logique, c'est a dire que cette fonction
 * realise la copie.
 *
 * http://doc.spip.org/@action_copier_local_dist
 *
 * @param null $id_document
 * @return bool|mixed|string
 */
function action_copier_local_dist($id_document=null) {

	if (!$id_document){
		// Recupere les arguments.
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	
		$id_document = intval($arg);
	}

	if (!$id_document) {
		spip_log("action_copier_local_dist $arg pas compris");
		return false;
	} else  {
		// arguments recuperes, on peut maintenant appeler la fonction.
		return action_copier_local_post($id_document);
	}
}

/**
 * http://doc.spip.org/@action_copier_local_post
 *
 * @param  $id_document
 * @return bool|mixed|string
 */
function action_copier_local_post($id_document) {

	// Il faut la source du document pour le copier
	$row = Sql::fetsel("mode,fichier, descriptif, credits", "spip_documents", "id_document=$id_document");
	$source = $row['fichier'];

	include_spip('inc/distant'); // pour 'copie_locale'
	$fichier = copie_locale($source);
	if ($fichier) {
		$fichier = _DIR_RACINE . $fichier;
		$files[] = array('tmp_name'=>$fichier,'name'=>basename($fichier));
		$ajouter_documents = charger_fonction('ajouter_documents','action');
		spip_log("convertit doc $id_document en local: $source => $fichier");
		$liste = array();
		$ajouter_documents($id_document,$files,'',0,$row['mode'],$liste);

		spip_unlink($fichier);
		
		// ajouter l'origine du document aux credits
		include_spip('action/editer_document');
		document_modifier($id_document,array('credits'=>($row['credits']?$row['credits'].', ':'').$source));
		return true;
		
	} else {
		spip_log("echec copie locale $source");
	}
	return _T('medias:erreur_copie_fichier',array('nom'=>$source));
}

?>
