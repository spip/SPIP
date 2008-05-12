<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/forum');
include_spip('base/abstract_sql');
spip_connect();

function formulaires_forum_verifier_dist(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour){

	$erreurs = array();
	if (strlen($texte = _request('texte')) < 10
	AND !$ajouter_mot AND $GLOBALS['meta']['forums_texte'] == 'oui')
		$erreurs['texte'] = _T('forum_attention_dix_caracteres');
	else if (defined('_FORUM_LONGUEUR_MAXI')
	AND _FORUM_LONGUEUR_MAXI > 0
	AND strlen($texte) > _FORUM_LONGUEUR_MAXI)
		$erreurs['texte'] = _T('forum_attention_trop_caracteres',
			array(
				'compte' => strlen($texte),
				'max' => _FORUM_LONGUEUR_MAXI
			));

	if (strlen($titre=_request('titre')) < 3
	AND $GLOBALS['meta']['forums_titre'] == 'oui')
		$erreurs['titre'] = _T('forum_attention_trois_caracteres');
	
	if (!count($erreurs) AND !_request('confirmer_previsu_forum')){
		if ($afficher_texte != 'non') {
			$previsu = inclure_previsu(_request('texte'), _request('titre'), _request('url_site'), _request('nom_site'), _request('ajouter_mot'));
			$erreurs['previsu'] = $previsu;
		}
	}

	return $erreurs;
}


// http://doc.spip.org/@inclure_previsu
function inclure_previsu($texte,$titre, $url_site, $nom_site, $ajouter_mot)
{
	$bouton = _T('forum_message_definitif');

	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace("@<(/?)form\b@ism",
			    '<\1div',
		inclure_balise_dynamique(array('formulaires/forum_previsu',
		      0,
		      array(
			'titre' => safehtml(typo($titre)),
			'texte' => safehtml(propre($texte)),
			'url_site' => vider_url($url_site),
			'nom_site' => safehtml(typo($nom_site)),
			'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
			'erreur' => $erreur,
			'bouton' => $bouton
			)
					       ),
					 false));
}

?>