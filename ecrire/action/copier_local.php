<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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
	$s = spip_query("SELECT fichier FROM spip_documents WHERE id_document=$id_document");
	$row = spip_fetch_array($s);
	$source = $row['fichier'];

	include_spip('inc/distant'); // pour 'copie_locale'
	include_spip('inc/documents'); // pour 'set_spip_doc'

	if ($fichier = copie_locale($source)) {
		// $fichier contient IMG/distant/...
		// or, dans la table documents, IMG doit etre exclu.
		$taille = filesize($fichier);
		$fichier = set_spip_doc($fichier);
		spip_log("convertit doc $id_document en local: $source => $fichier");
		spip_query("UPDATE spip_documents SET fichier="._q($fichier).", distant='non', taille='$taille' WHERE id_document=".$id_document);
		
	} else {
		spip_log("echec copie locale $source");
	}
}

?>
