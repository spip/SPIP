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
 * Charger #FORMULAIRE_RESTAURER
 * @return array
 */
function formulaires_restaurer_charger_dist(){
	// ici on liste tout, les tables exclue sont simplement non cochees

	$valeurs = array(
		'_dir_dump' => dump_repertoire(),
		'choisi' => _request('fichier')?_request('fichier'):_request('nom'),
		'nom_sauvegarde' => '',
		'tout_restaurer' => (_request('check_tables') AND !_request('tout_restaurer'))?'':'oui',
		'fichier' => '',
		'tri' => 'nom',
	);

	return $valeurs;
}

/**
 * Verifier
 * @return array
 */
function formulaires_restaurer_verifier_dist() {
	$erreurs = array();
	if (!$fichier=_request('fichier') AND !$nom = _request('nom_sauvegarde'))
		$erreurs['fichier'] = _T('info_obligatoire');
	elseif ($fichier) {
		$fichier = basename($fichier); // securite
		if (!file_exists(dump_repertoire().$fichier))
			$erreurs['fichier'] = _T('dump:erreur_nom_fichier');
		else
			$nom = $fichier;
	}
	else {
		$nom = basename($nom); // securite
		if (!file_exists(dump_repertoire().$nom)) {
			$erreurs['nom_sauvegarde'] = _T('dump:erreur_nom_fichier');
			$nom = "";
		}
	}
	if (!$nom) {
		$erreurs['message_erreur'] = _T('dump:erreur_restaurer_verifiez');
	}
	
	if ($nom) {
		$archive = dump_repertoire().$nom;
		if (!$args = dump_connect_args($archive))
			$erreurs['tout_restaurer'] = _T('dump:erreur_sqlite_indisponible');
		dump_serveur($args);
		$tables = base_lister_toutes_tables('dump');
		$tables = base_saisie_tables('tables', $tables, array(), _request('tables')?_request('tables'):array(),'dump');
		$erreurs['tables'] = "<ol class='spip'><li class='choix'>\n" . join("</li>\n<li class='choix'>",
		  $tables
			) . "</li></ol><input type='hidden' name='check_tables' value='oui' />\n";
		if (!count(_request('tables')) AND !_request('tout_restaurer') AND _request('check_tables'))
			$erreurs['tout_restaurer'] = _T('dump:selectionnez_table_a_restaurer');
	}

	if ($nom
		AND (!count($erreurs) OR (count($erreurs)==1 AND isset($erreurs['tables'])))
		) {
		if (_request('confirm')!==$nom) {
			$erreurs['message_confirm'] =
				_T('dump:info_selection_sauvegarde',array('fichier'=>  '<i>'.joli_repertoire(dump_repertoire().$nom)."</i>"))
				."<br /><input type='checkbox' name='confirm' value='$nom' id='confirm' /> ";
			$erreurs['message_confirm'].="<label for='confirm'><strong>";
			if (_request('tables'))
				$erreurs['message_confirm'].=_T('dump:confirmer_ecraser_tables_selection');
			else
				$erreurs['message_confirm'].=_T('dump:confirmer_ecraser_base');
			$erreurs['message_confirm'].="</strong></label>";
		}
		else
			// passer a traiter()
			unset($erreurs['tables']);
	}

	return $erreurs;
}

/**
 * Traiter
 * @return array
 */
function formulaires_restaurer_traiter_dist() {

	$archive = (_request('fichier')?_request('fichier'):_request('nom'));
	$dir_dump = dump_repertoire();
	$archive = $dir_dump . basename($archive,".sqlite");
	
	$status_file = base_dump_meta_name(0)."_restauration";

	if (_request('tout_restaurer')) {
		$args = dump_connect_args($archive);
		dump_serveur($args);
		$tables = base_lister_toutes_tables('dump');
	}
	else
		$tables = _request('tables');

	include_spip('inc/dump');
	$res = dump_init($status_file, $archive, $tables,array('spip_meta'=>"impt='oui'"));

	if ($res===true) {
		// on lance l'action restaurer qui va realiser la sauvegarde
		// et finira par une redirection vers la page sauvegarde_fin
		include_spip('inc/actions');
		$redirect = generer_action_auteur('restaurer', $status_file);
		return array('message_ok'=>'ok','redirect'=>$redirect);
	}
	else
		return array('message_erreur'=>$res);
}


?>