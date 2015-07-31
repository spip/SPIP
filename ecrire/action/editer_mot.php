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

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_editer_mot_dist
function action_editer_mot_dist($arg=null)
{
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	$id_mot = intval($arg);

	$id_groupe = intval(_request('id_groupe'));
	if (!$id_mot AND $id_groupe) {
		$id_mot = sql_insertq("spip_mots", array('id_groupe' => $id_groupe));
	}

	// modifier le contenu via l'API
	include_spip('inc/modifier');

	$c = array();
	foreach (array(
		'titre', 'descriptif', 'texte', 'id_groupe'
	) as $champ)
		$c[$champ] = _request($champ);

	revision_mot($id_mot, $c);
	if ($redirect = _request('redirect')) {
		include_spip('inc/headers');
		redirige_par_entete(parametre_url(urldecode($redirect),
			'id_mot', $id_mot, '&'));
	} else
		return array($id_mot,'');
}
?>
