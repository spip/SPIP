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

	// stocker un eventuel document dans un espace temporaire
	// portant la cle du formulaire ; et ses metadonnees avec
	if (!isset($GLOBALS['visiteur_session']['tmp_forum_document']))
		session_set('tmp_forum_document',
		sous_repertoire(_DIR_TMP,'documents_forum').md5(uniqid()));
	$tmp = $GLOBALS['visiteur_session']['tmp_forum_document'];
	$doc = &$_FILES['ajouter_document'];
	if (isset($_FILES['ajouter_document'])
	AND $_FILES['ajouter_document']['tmp_name']) {
		// securite :
		// verifier si on possede la cle (ie on est autorise a poster)
		// (sinon tant pis) ; cf. charger.php pour la definition de la cle
		if (_request('cle_ajouter_document') != calculer_cle_action($a = "ajouter-document-$id_article-$id_breve-$id_forum-$id_rubrique-$id_syndic")) {
			$erreurs['document_forum'] = _L('Documents interdits dans le forum');
			unset($_FILES['ajouter_document']);
		} else {
			include_spip('inc/ajouter_documents');
			list($extension,$doc['name']) = fixer_extension_document($doc);
			$acceptes = array_map('trim', explode(',',$GLOBALS['meta']['formats_documents_forum']));

			if (!in_array($extension, $acceptes)) {
				if (!$formats = join(', ',$acceptes))
					$formats = _L('aucun');
				$erreurs['document_forum'] = _L('Formats accept&#233;s : @formats@.', array('formats' => $formats));
			}
			else {
				include_spip('inc/getdocument');
				if (!deplacer_fichier_upload($doc['tmp_name'], $tmp.'.bin'))
					$erreurs['document_forum'] = _L('Impossible de copier le document');

#		else if (...)
#		verifier le type_document autorise
#		retailler eventuellement les photos
			}

			// si ok on stocke les meta donnees, sinon on efface
			if (isset($erreurs['document_forum'])) {
				spip_unlink($tmp.'.bin');
				unset ($_FILES['ajouter_document']);
			} else {
				$doc['tmp_name'] = $tmp.'.bin';
				ecrire_fichier($tmp.'.txt', serialize($doc));
			}
		}
	}
	// restaurer le document uploade au tour precedent
	else if (file_exists($tmp.'.bin')) {
		if (_request('suprimer_document_ajoute')) {
			spip_unlink($tmp.'.bin');
			spip_unlink($tmp.'.txt');
		} else if (lire_fichier($tmp.'.txt', $meta))
			$doc = @unserialize($meta);
	}

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
			$previsu = inclure_previsu($texte, $titre, _request('url_site'), _request('nom_site'), _request('ajouter_mot'), $doc);
			$erreurs['previsu'] = $previsu;
		}
	}

	return $erreurs;
}


// http://doc.spip.org/@inclure_previsu
function inclure_previsu($texte,$titre, $url_site, $nom_site, $ajouter_mot, $doc)
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
			'ajouter_document' => $doc,
			'erreur' => $erreur,
			'bouton' => $bouton
			)
					       ),
					 false));
}

?>