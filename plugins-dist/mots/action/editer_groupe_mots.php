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

include_spip('inc/filtres');

// Modification d'un groupe de mots
// http://doc.spip.org/@action_editer_groupe_mots_dist
function action_editer_groupe_mots_dist($id_groupe=null)
{
	if (is_null($id_groupe)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_groupe = $securiser_action();
	}

	if (!intval($id_groupe)) {
		$id_groupe = groupemots_inserer();
	}

	if ($id_groupe>0)
		$err = groupemots_modifier($id_groupe);

	return array($id_groupe,$err);
}

/**
 * Creer un groupe de mots
 *
 * @param string $table
 * @return int 
 */
function groupemots_inserer($table='') {
	$champs = array(
		'titre' => '',
		'unseul' => 'non',
		'obligatoire' => 'non',
		'tables_liees' => $table,
		'minirezo' =>  'oui',
		'comite' =>  'non',
		'forum' => 'non'
	);

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_groupes_mots',
			),
			'data' => $champs
		)
	);

	$id_groupe = Sql::insertq("spip_groupes_mots", $champs) ;

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_groupes_mots',
				'id_objet' => $id_groupe
			),
			'data' => $champs
		)
	);

	return $id_groupe;
}

/**
 * Modifier un groupe de mot
 * @param int $id_groupe
 * @param array|null $set
 * @return string
 */
function groupemots_modifier($id_groupe, $set=null) {
	$err = '';

	include_spip('inc/modifier');
	$c = collecter_requests(
		// white list
		array(
		 'titre', 'descriptif', 'texte', 'tables_liees',
		 'obligatoire', 'unseul',
		 'comite', 'forum', 'minirezo',
		),
		// black list
		array(),
		// donnees eventuellement fournies
		$set
	);
	// normaliser les champ oui/non
	foreach (array(
		'obligatoire', 'unseul',
		'comite', 'forum', 'minirezo'
	) as $champ)
		if (isset($c[$champ]))
			$c[$champ] = ($c[$champ]=='oui'?'oui':'non');

	if (isset($c['tables_liees']) AND is_array($c['tables_liees']))
		$c['tables_liees'] = implode(',',array_diff($c['tables_liees'],array('')));

	$err = objet_modifier_champs('groupe_mot', $id_groupe,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);

	return $err;
}


// obsolete
function revision_groupe_mot($id_groupe, $c=false) {
	return groupemots_modifier($id_groupe,$c);
}
?>
