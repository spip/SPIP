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
include_spip('base/dump');
include_spip('inc/dump');

/**
 * Charger #FORMULAIRE_SAUVEGARDER
 * @return array
 */
function formulaires_sauvegarder_charger_dist(){	
	$dir_dump = dump_repertoire();

	// ici on liste tout, les tables exclue sont simplement non cochees
	$exclude = lister_tables_noexport();
	list($tables,) = base_liste_table_for_dump($exclude);
	$tables = base_lister_toutes_tables('',$tables);

	$valeurs = array(
		'_dir_dump'=>joli_repertoire($dir_dump),
		'_dir_img'=>joli_repertoire(_DIR_IMG),
		'_spipnet' => $GLOBALS['home_server'] . '/' .  $GLOBALS['spip_lang'] . '_article1489.html',
		'nom_sauvegarde' => basename(dump_nom_fichier($dir_dump,'sqlite'),'.sqlite'),
		'tout_sauvegarder' => (_request('nom_sauvegarde') AND !_request('tout_sauvegarder'))?'':'oui',
		'_tables' => "<ol class='spip'><li class='choix'>\n" . join("</li>\n<li class='choix'>",
		  base_saisie_tables('tables', $tables, $exclude, _request('nom_sauvegarde')?(_request('tables')?_request('tables'):array()):null)
			) . "</li></ol>\n",
	);

	return $valeurs;
}

/**
 * Verifier
 * @return array
 */
function formulaires_sauvegarder_verifier_dist() {
	$erreurs = array();
	if (!$nom = _request('nom_sauvegarde'))
		$erreurs['nom_sauvegarde'] = _T('info_obligatoire');
	elseif (!preg_match(',^[\w_][\w_.]*$,', $nom)
		OR basename($nom)!==$nom)
		$erreurs['nom_sauvegarde'] = _T('dump:erreur_nom_fichier');

	return $erreurs;
}

/**
 * Traiter
 * @return array
 */
function formulaires_sauvegarder_traiter_dist() {
	$status_file = base_dump_meta_name(0);
	$dir_dump = dump_repertoire();
	$archive = $dir_dump . basename(_request('nom_sauvegarde'),".sqlite");

	if (_request('tout_sauvegarder')) {
		// ici on prend toutes les tables sauf celles exclues par defaut
		// (tables de cache en pratique)
		$exclude = lister_tables_noexport();
		list($tables,) = base_liste_table_for_dump($exclude);
		$tables = base_lister_toutes_tables('',$tables,$exclude);
	}
	else
		$tables = _request('tables');

	include_spip('inc/dump');
	$res = dump_init($status_file, $archive, $tables);

	if ($res===true) {
		// on lance l'action sauvegarder qui va realiser la sauvegarde
		// et finira par une redirection vers la page sauvegarde_fin
		include_spip('inc/actions');
		$redirect = generer_action_auteur('sauvegarder', $status_file);
		return array('message_ok'=>'ok','redirect'=>$redirect);
	}
	else
		return array('message_erreur'=>$res);
}

?>