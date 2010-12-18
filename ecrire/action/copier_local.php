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

// Fonction appellee lorsque l'utilisateur clique sur le bouton
// 'copier en local' (document/portfolio).
// Il s'agit de la partie logique, c'est a dire que cette fonction
// realise la copie.

// http://doc.spip.org/@action_copier_local_dist
function action_copier_local_dist() {

	// Recupere les arguments.
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_document = intval($arg);

	if (!$id_document) {
		spip_log("action_copier_local_dist $arg pas compris");
	} else  {
		// arguments recuperes, on peut maintenant appeler la fonction.
		action_copier_local_post($id_document);
	}
}

// http://doc.spip.org/@action_copier_local_post
function action_copier_local_post($id_document) {

	// Il faut la source du document pour le copier
	$row = sql_fetsel("fichier, descriptif", "spip_documents", "id_document=$id_document");
	$source = $row['fichier'];

	include_spip('inc/distant'); // pour 'copie_locale'
	include_spip('inc/documents'); // pour 'set_spip_doc'
	$fichier = copie_locale($source);
	if ($fichier) {
		$fichier = _DIR_RACINE . $fichier;
		$taille = filesize($fichier);
		// On le sort du repertoire IMG/distant/
		$dest = preg_replace(',^.*/distant/[^/_]+[/_],', '', $fichier);
		$dest = sous_repertoire(_DIR_IMG, preg_replace(',^.*\.,', '', $fichier)) . $dest;
		if ($dest != $fichier
		AND @rename($fichier, $dest))
			$fichier = $dest;

		// On indique l'ancien URL dans le descriptif (pis-aller)
		$row['descriptif'] .= ($row['descriptif'] ? "\n\n":'') . "[->$source]";

		// $fichier contient IMG/distant/...
		// or, dans la table documents, IMG doit etre exclu.
		$fichier = set_spip_doc($fichier);
		spip_log("convertit doc $id_document en local: $source => $fichier");
		sql_updateq('spip_documents', array('fichier' =>$fichier, 'distant'=>'non', 'taille'=>$taille, 'descriptif'=> $row['descriptif']),"id_document=".$id_document);
		
	} else {
		spip_log("echec copie locale $source");
	}
}

?>
