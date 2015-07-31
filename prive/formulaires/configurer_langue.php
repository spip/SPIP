<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_configurer_langue_charger_dist(){
	$valeurs = array();

	include_spip('inc/lang');
	$l_site = $GLOBALS['meta']['langue_site'];
	$langue_site = traduire_nom_langue($l_site);

	$langues = explode(",",$GLOBALS['meta']['langues_proposees']);
	if (!in_array($l_site, $langues))
	 $langues[] = $l_site;
	sort($langues);

	$res = "";
	foreach ($langues as $l) {
		$res .= "<option value='$l'"
			. ($l==$l_site ? " selected='selected'":"")
			.">".traduire_nom_langue($l)."</option>\n";
	}

	$valeurs = array(
		'_langues' => $res,
		'_langue_site' => $langue_site,
		'changer_langue_site' => '',
	);

	return $valeurs;
}


function formulaires_configurer_langue_traiter_dist(){
	$res = array('editable'=>true);

	if ($lang = _request('changer_langue_site')) {
		include_spip('inc/lang');
		// verif que la langue demandee est licite
		if (changer_langue($lang)) {
			ecrire_meta('langue_site', $lang);
			// le test a defait ca:
			utiliser_langue_visiteur();
			$res['message_ok'] = _T('config_info_enregistree');
			include_spip('inc/rubriques');
			calculer_langues_rubriques();
		}
		// le test a defait ca:
		utiliser_langue_visiteur();
	}
	if (!$res['message_ok'])
		$res['message_erreur'] = _L('erreur');
	return $res;
}
