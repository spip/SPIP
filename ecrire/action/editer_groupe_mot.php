<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');

// Modification d'un groupe de mots
// http://doc.spip.org/@action_editer_groupe_mot_dist
function action_editer_groupe_mot_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_groupe = intval($securiser_action());

	if (!$id_groupe) {
		$id_groupe = sql_insertq("spip_groupes_mots");
	}

	// modifier le contenu via l'API
	include_spip('inc/modifier');

	$c = array();
	foreach (array(
		'titre', 'descriptif', 'texte', 'tables_liees'
	) as $champ)
		$c[$champ] = _request($champ);
	foreach (array(
		'obligatoire', 'unseul'
	) as $champ)
		$c[$champ] = _request($champ)=='oui'?'oui':'non';
	foreach (array(
		'comite', 'forum', 'minirezo'
	) as $champ)
		$c[$champ] = _request("acces_$champ")=='oui'?'oui':'non';
		
	if (is_array($c['tables_liees']))
		$c['tables_liees'] = implode(',',$c['tables_liees']);

	revision_groupe_mot($id_groupe, $c);
	if ($redirect = _request('redirect')) {
		include_spip('inc/headers');
		redirige_par_entete(parametre_url(urldecode($redirect),
			'id_groupe', $id_groupe, '&'));
	} else
		return array($id_groupe,'');
}

/**
 * Creer un groupe de mots
 *
 * @param string $table
 * @return int 
 */
function insert_groupe_mot($table) {
	$titre = _T('info_mot_sans_groupe');
	$id_groupe = sql_insertq("spip_groupes_mots", array(
		'titre' => $titre,
		'unseul' => 'non',
		'obligatoire' => 'non',
		'tables_liees'=>$table,
		'minirezo' =>  'oui',
		'comite' =>  'non',
		'forum' => 'non')) ;


	return $id_groupe;
}

?>
