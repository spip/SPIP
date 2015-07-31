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


// Une fonction generique pour l'API de modification de contenu
// $options est un array() avec toutes les options
//
// Pour l'instant fonctionne pour les types : document
//
// http://doc.spip.org/@modifier_contenu
function modifier_contenu($type, $id, $options, $c=false) {
	include_spip('inc/filtres');

	$table_objet = table_objet($type);
	$id_table_objet = id_table_objet($type);

	// Gerer les champs non vides
	if (is_array($options['nonvide']))
	foreach ($options['nonvide'] as $champ => $sinon) {
		if (_request($champ, $c) === '') {
			$c = set_request($champ, $sinon, $c);
		}
	}

	$champs = array();
	if (is_array($options['champs']))
	foreach ($options['champs'] as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update($table_objet, $id, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_'.$table_objet,
				'id_objet' => $id
			),
			'data' => $champs
		)
	);

	$update = array();
	foreach ($champs as $champ => $val)
		$update[] = $champ . '=' . _q($val);

	if (!count($update)) return;

	spip_query($q = "UPDATE spip_$table_objet SET ".join(', ',$update)." WHERE $id_table_objet=$id");


	// marquer le fait que l'objet est travaille par toto a telle date
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id, $GLOBALS['auteur_session'], $type);
	}

	// Notification ?
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_'.$table_objet,
				'id_objet' => $id
			),
			'data' => $champs
		)
	);

}

// http://doc.spip.org/@revisions_documents
function revision_document($id_document, $c=false) {

	return modifier_contenu('document', $id_document,
		array(
			'champs' => array('titre', 'descriptif')
			//,'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);

}

?>
