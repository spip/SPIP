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

include_spip('inc/forum');

function formulaires_forum_prive_charger_dist($objet, $id_objet, $id_forum, $afficher_previsu, $statut, $retour='') {

	if (!function_exists($f='forum_recuperer_titre'))
		$f = 'forum_recuperer_titre_dist';
	// si objet, il faut un titre, sinon on est dans un statut privrac/privadm qui permet un forum sans objet
	if ($objet AND $id_objet AND !$titre = $f($objet,$id_objet,$id_forum,false))
		return false;

	$primary = id_table_objet($objet);
	$table = table_objet($objet);

	// Tableau des valeurs servant au calcul d'une signature de securite.
	// Elles seront placees en Input Hidden pour que inc/forum_insert
	// recalcule la meme chose et verifie l'identite des resultats.
	// Donc ne pas changer la valeur de ce tableau entre le calcul de
	// la signature et la fabrication des Hidden
	// Faire attention aussi a 0 != ''
	$ids = array();
	$ids[$primary] = ($x = intval($id_objet)) ? $x : '';
	$ids['id_objet'] = ($x = intval($id_objet)) ? $x : '';
	$ids['objet'] = $objet;
	$ids['id_forum'] = ($x = intval($id_forum)) ? $x : '';

	// ne pas mettre '', sinon le squelette n'affichera rien.
	$previsu = ' ';

	// pour les hidden
	$script_hidden = "";
	foreach ($ids as $id => $v)
		$script_hidden .= "<input type='hidden' name='$id' value='$v' />";
		
	$config = array();
	foreach(array('afficher_barre','forum_titre','forums_texte','forums_urlref') as $k)
		$config[$k] = ' ';

	return array(
		'nom_site' => '',
		'table' => $table,
		'texte' => '',
		'config' => $config,
		'titre' => $titre,
		'_hidden' => $script_hidden, # pour les variables hidden
		'url_site' => "http://",
		'id_forum' => $id_forum, // passer id_forum au formulaire pour lui permettre d'afficher a quoi l'internaute repond
		'_sign'=>implode('_',$ids),
		'_autosave_id' => $ids,
	);
}


function formulaires_forum_prive_verifier_dist($objet, $id_objet, $id_forum, $afficher_previsu, $statut, $retour='')
{
	include_spip('inc/acces');
	include_spip('inc/texte');
	include_spip('inc/forum');
	include_spip('inc/session');

	$erreurs = array();

	if (strlen($texte = _request('texte')) < 10 AND $GLOBALS['meta']['forums_texte'] == 'oui')
		$erreurs['texte'] = _T('forum:forum_attention_dix_caracteres');
	else if (defined('_FORUM_LONGUEUR_MAXI')
	AND _FORUM_LONGUEUR_MAXI > 0
	AND strlen($texte) > _FORUM_LONGUEUR_MAXI)
		$erreurs['texte'] = _T('forum:forum_attention_trop_caracteres',
			array(
				'compte' => strlen($texte),
				'max' => _FORUM_LONGUEUR_MAXI
			));

	if (strlen($titre=_request('titre')) < 3
	AND $GLOBALS['meta']['forums_titre'] == 'oui')
		$erreurs['titre'] = _T('forum:forum_attention_trois_caracteres');

	if (array_reduce($_POST, 'reduce_strlen', (20 * 1024)) < 0) {
		$erreurs['erreur_message'] = _T('forum:forum_message_trop_long');
	}

	if (!count($erreurs) AND !_request('confirmer_previsu_forum')){
		if ($afficher_previsu != 'non') {
			$previsu = inclure_forum_prive_previsu($texte, $titre, _request('url_site'), _request('nom_site'), _request('ajouter_mot'));
			$erreurs['previsu'] = $previsu;
		}
	}

	return $erreurs;
}


function inclure_forum_prive_previsu($texte,$titre, $url_site, $nom_site, $ajouter_mot, $doc=""){
	$bouton = _T('forum:forum_message_definitif');
	include_spip('public/assembler');
	include_spip('public/composer');
	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace("@<(/?)form\b@ism",
			    '<\1div',
		inclure_balise_dynamique(array('formulaires/inc-forum_prive_previsu',
		      0,
		      array(
			'titre' => safehtml(typo($titre)),
			'texte' => safehtml(propre($texte)),
			'notes' => safehtml(calculer_notes()),
			'url_site' => vider_url($url_site),
			'nom_site' => safehtml(typo($nom_site)),
			'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
			'ajouter_document' => $doc,
			'erreur' => $erreur,
			'bouton' => $bouton
			)
					       ),
					 false));
}


function formulaires_forum_prive_traiter_dist($objet, $id_objet, $id_forum, $afficher_previsu, $statut, $retour='') {

	$forum_insert = charger_fonction('forum_insert', 'inc');
	$id_reponse = $forum_insert($objet, $id_objet, $id_forum,$statut);
	if ($id_reponse){
		// En cas de retour sur (par exemple) {#SELF}, on ajoute quand
		// meme #forum12 a la fin de l'url, sauf si un #ancre est explicite
		if ($retour){
			if (!strpos($retour, '#'))
				$retour .= '#forum'.$id_reponse;
		}
		else {
			// le retour par defaut envoie sur le thread, ce qui permet
			// de traiter elegamment le cas des forums moderes a priori.
			// Cela assure aussi qu'on retrouve son message dans le thread
			// dans le cas des forums moderes a posteriori, ce qui n'est
			// pas plus mal.
			if (function_exists('generer_url_forum')) {
				$retour = generer_url_forum($id_reponse);
			}
			else {
				$thread = Sql::fetsel('id_thread', 'spip_forum', 'id_forum='.$id_reponse);
				spip_log('id_thread='.$thread['id_thread'], 'forum');
				$retour = generer_url_entite($thread['id_thread'], 'forum');
			}
		}

		$res = array('redirect'=>$retour,'id_forum'=>$id_forum);
	}
	else
		$res = array('message_erreur'=>_T('forum:erreur_enregistrement_message'));

	return $res;
}


?>
