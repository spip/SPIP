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

// En Ajax on utilise GET et sinon POST.
// De plus Ajax en POST ne remplit pas $_POST 
// spip_register_globals ne fournira donc pas les globales esperees
// ==> passer par _request() qui simule $_REQUEST sans $_COOKIE

// http://doc.spip.org/@action_documenter_dist
function action_documenter_dist() {
	
	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_documenter_dist $arg pas compris");
	} else {

		$id_document = $r[1];

		$titre_document = (corriger_caracteres($_REQUEST['titre_document']));
		$descriptif_document = (corriger_caracteres($_REQUEST['descriptif_document']));

			// taille du document (cas des embed)
		if ($largeur_document = intval($_REQUEST['largeur_document'])
		AND $hauteur_document = intval($_REQUEST['hauteur_document']))
				$wh = ", largeur='$largeur_document',
					hauteur='$hauteur_document'";
		else $wh = "";

			// Date du document (uniquement dans les rubriques)
		if (!$_REQUEST['jour_doc'])
		  $d = '';
		else {
			if ($_REQUEST['annee_doc'] == "0000")
					$_REQUEST['mois_doc'] = "00";
			if ($_REQUEST['mois_doc'] == "00")
					$_REQUEST['jour_doc'] = "00";
			$date = $_REQUEST['annee_doc'].'-'	.$_REQUEST['mois_doc'].'-'.$_REQUEST['jour_doc'];

			if (preg_match('/^[0-9-]+$/', $date)) $d=" date='$date',";
		}
				  
		spip_query("UPDATE spip_documents SET$d titre=" . spip_abstract_quote($titre_document) . ", descriptif=" . spip_abstract_quote($descriptif_document) . " $wh WHERE id_document=".$id_document);


		if ($date) {
			include_spip('inc/rubriques');
			// Changement de date, ce qui nous oblige a :
			calculer_rubriques();
		}

		// Demander l'indexation du document
		include_spip('inc/indexation');
		marquer_indexer('spip_documents', $id_document);
	}
}
?>
