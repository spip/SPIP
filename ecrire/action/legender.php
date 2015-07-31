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

include_spip('inc/filtres');

// En Ajax on utilise GET et sinon POST.
// De plus Ajax en POST ne remplit pas $_POST 
// spip_register_globals ne fournira donc pas les globales esperees
// ==> passer par _request() qui simule $_REQUEST sans $_COOKIE

// http://doc.spip.org/@action_legender_dist
function action_legender_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^\W*(-?\d+)$,", $arg, $r)) {
		 spip_log("action_legender_dist $arg pas compris");
	} else action_legender_post($r);
}

// http://doc.spip.org/@action_legender_post
function action_legender_post($r)
{
	$id_document = $r[1];

	$modifs = array();

	// taille du document (cas des embed)
	if ($largeur_document = intval(_request('largeur_document'))
	AND $hauteur_document = intval(_request('hauteur_document'))) {
		$modifs['largeur'] = $largeur_document;
		$modifs['hauteur'] = $hauteur_document;
	}

	// Date du document (uniquement dans les rubriques)
	if (_request('jour_doc') !== null) {
		$mois_doc = _request('mois_doc');
		$jour_doc = _request('jour_doc');
		$heure_doc = _request('heure_doc'); 
		$minute_doc = _request('minute_doc');
		if (_request('annee_doc') == "0000")
			$mois_doc = "00";
		if ($mois_doc == "00")
			$jour_doc = "00";
		if ($jour_doc == "00"){ 
			$heure_doc = "00"; 
			$minute_doc = "00"; 
		} 
		$date = sprintf('%04d',intval(_request('annee_doc')))
			.'-'.sprintf('%02d', intval($mois_doc))
			.'-'.sprintf('%02d',intval($jour_doc))
			.' '.sprintf('%02d',intval($heure_doc))
			.':'.sprintf('%02d',intval($minute_doc))
			.':00'; 
		$modifs['date'] = $date;
	}
	
	if (($t = _request('titre_document')) !== NULL)
		$modifs['titre'] = $t;
	if (($t = _request('descriptif_document')) !== NULL)
		$modifs['descriptif'] = $t;

	include_spip('inc/modifier');
	revision_document($id_document, $modifs);

}

?>
