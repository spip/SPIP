<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');

function action_documenter_dist() {
	
	global $action, $arg, $hash, $id_auteur, $redirect;
	include_spip('inc/actions');
	if (!verifier_action_auteur("$action-$arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_documenter_dist $arg pas compris");
	} else {

		$id_document = $r[1];

		$titre_document = (corriger_caracteres($_POST['titre_document']));
		$descriptif_document = (corriger_caracteres($_POST['descriptif_document']));

			// taille du document (cas des embed)
		if ($largeur_document = intval($_POST['largeur_document'])
		AND $hauteur_document = intval($_POST['hauteur_document']))
				$wh = ", largeur='$largeur_document',
					hauteur='$hauteur_document'";
		else $wh = "";

			// Date du document (uniquement dans les rubriques)
		if (!$_POST['jour_doc'])
		  $date = '';
		else {
			if ($_POST['annee_doc'] == "0000")
					$_POST['mois_doc'] = "00";
			if ($_POST['mois_doc'] == "00")
					$_POST['jour_doc'] = "00";
			$d = $_POST['annee_doc'].'-'	.$_POST['mois_doc'].'-'.$_POST['jour_doc'];

			if (preg_match('/^[0-9-]+$/', $d)) $date=" date='$d',";
		}
				  
		spip_query("UPDATE spip_documents SET$date titre=" . spip_abstract_quote($titre_document) . ", descriptif=" . spip_abstract_quote($descriptif_document) . " $wh WHERE id_document=".$id_document);


		if ($date) {
			include_spip('inc/rubriques');
			// Changement de date, ce qui nous oblige a :
			calculer_rubriques();
		}

		// Demander l'indexation du document
		include_spip('inc/indexation');
		marquer_indexer('document', $id_document);
	}
}
