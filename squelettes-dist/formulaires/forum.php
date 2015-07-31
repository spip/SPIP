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

function formulaires_forum_charger_dist(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour) {

	// exiger l'authentification des posteurs pour les forums sur abo
	if ($type == "abo") {
		if (!$GLOBALS["visiteur_session"]['statut']) {
			return array(
				'action' => '', #ne sert pas dans ce cas, on la vide pour mutualiser le cache
				'editable'=>false,
				'login_forum_abo'=>' ',
				'inscription' => generer_url_public('identifiants', 'lang='.$GLOBALS['spip_lang']),
				'oubli' => generer_url_public('spip_pass','lang='.$GLOBALS['spip_lang'],true),
				);
		}
	}

	// Tableau des valeurs servant au calcul d'une signature de securite.
	// Elles seront placees en Input Hidden pour que inc/forum_insert
	// recalcule la meme chose et verifie l'identite des resultats.
	// Donc ne pas changer la valeur de ce tableau entre le calcul de
	// la signature et la fabrication des Hidden
	// Faire attention aussi a 0 != ''

	// id_rubrique est parfois passee pour les articles, on n'en veut pas
	$ids = array();
	if ($id_rubrique > 0 AND ($id_article OR $id_breve OR $id_syndic))
		$id_rubrique = 0;
	foreach (array('id_article', 'id_breve', 'id_forum', 'id_rubrique', 'id_syndic') as $o) {
		$ids[$o] = ($x = intval($$o)) ? $x : '';
	}


	// ne pas mettre '', sinon le squelette n'affichera rien.
	$previsu = ' ';

	// au premier appel (pas de Post-var nommee "retour_forum")
	// memoriser eventuellement l'URL de retour pour y revenir apres
	// envoi du message ; aux appels suivants, reconduire la valeur.
	// Initialiser aussi l'auteur
	if ($retour_forum = rawurldecode(_request('retour')))
		$retour_forum =  str_replace('&var_mode=recalcul','',$retour_forum);
	else {
		// par defaut, on veut prendre url_forum(), mais elle ne sera connue
		// qu'en sortie, on inscrit donc une valeur absurde ("!")
		$retour_forum = "!";
		// sauf si on a passe un parametre en argument (exemple : {#SELF})
		if ($url_param_retour)
			$retour_forum = str_replace('&amp;', '&', $url_param_retour);
		$retour_forum = rawurlencode($retour_forum);
	}
	if (_request('retour_forum')){
		$arg = forum_fichier_tmp(join('', $ids));
		
		$securiser_action = charger_fonction('securiser_action', 'inc');
		// on sait que cette fonction est dans le fichier associe
		$hash = calculer_action_auteur("ajout_forum-$arg");
	}

	// pour les hidden
	$script_hidden = "";
	foreach ($ids as $id => $v)
		$script_hidden .= "<input type='hidden' name='$id' value='$v' />";
		
	$script_hidden .= "<input type='hidden' name='arg' value='$arg' />";
	$script_hidden .= "<input type='hidden' name='hash' value='$hash' />";
	$script_hidden .= "<input type='hidden' name='verif_".substr($hash,0,32)."' value='ok' />";
	$script_hidden .= "<input type='hidden' name='afficher_texte' value='$afficher_texte' />";
	$script_hidden .= "<input type='hidden' name='retour_forum' value='$retour_forum' />";

	// l'ajout de documents est-il autorise ?
	// cf. verifier.php
	if ($formats = forum_documents_acceptes()) {
		include_spip('inc/securiser_action');
		$cle_ajouter_document = calculer_cle_action('ajouter-document-'.join('-',array_map('intval',$ids)));
	}

	return array(
		'modere' => (($type != 'pri') ? '' : ' '),
		'nom_site' => '',
		'table' => $table,
		'texte' => '',
		'config' => array('afficher_barre' => ($GLOBALS['meta']['forums_afficher_barre']!='non'?' ':'')),
		'titre' => str_replace('~', ' ', extraire_multi($titre)),
		'action' => $script, # ce sur quoi on fait le action='...'
		'_hidden' => $script_hidden, # pour les variables hidden
		'url_site' => "http://",
		'cle_ajouter_document' => $cle_ajouter_document,
		'formats_documents_forum' => $formats,
		'ajouter_document' => $_FILES['ajouter_document']['name'],
		'nobot' => _request('nobot'),
		'ajouter_groupe' => $ajouter_groupe,
		'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
		'id_forum' => $id_forum, // passer id_forum au formulaire pour lui permettre d'afficher a quoi l'internaute repond
		'_sign'=>implode('_',$ids)
	);
}


// Une securite qui nous protege contre :
// - les doubles validations de forums (derapages humains ou des brouteurs)
// - les abus visant a mettre des forums malgre nous sur un article (??)
// On installe un fichier temporaire dans _DIR_TMP (et pas _DIR_CACHE
// afin de ne pas bugguer quand on vide le cache)
// Le lock est leve au moment de l'insertion en base (inc-messforum)
// Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
// si $afficher_texte = 'non')

// http://doc.spip.org/@forum_fichier_tmp
function forum_fichier_tmp($arg)
{
# astuce : mt_rand pour autoriser les hits simultanes
	while (($alea = time() + @mt_rand()) + intval($arg)
	       AND @file_exists($f = _DIR_TMP."forum_$alea.lck"))
	  {};
	spip_touch ($f);

# et maintenant on purge les locks de forums ouverts depuis > 4 h

	if ($dh = @opendir(_DIR_TMP))
		while (($file = @readdir($dh)) !== false)
			if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
			AND (time()-@filemtime(_DIR_TMP.$file) > 4*3600))
				spip_unlink(_DIR_TMP.$file);
	return $alea;
}

function formulaires_forum_verifier_dist(
	$titre, $table, $type, $script,
	$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
	$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour)
{
	include_spip('inc/acces');
	include_spip('inc/texte');
	include_spip('inc/forum');
	include_spip('inc/session');
	include_spip('base/abstract_sql');

	$erreurs = array();

	// desactiver id_rubrique si un id_article ou autre existe dans le contexte
	if ($id_article OR $id_breve OR $id_forum OR $id_syndic)
		$id_rubrique = 0;

	// stocker un eventuel document dans un espace temporaire
	// portant la cle du formulaire ; et ses metadonnees avec

	if (!isset($GLOBALS['visiteur_session']['tmp_forum_document']))
		session_set('tmp_forum_document',
		sous_repertoire(_DIR_TMP,'documents_forum').md5(uniqid(rand())));
	$tmp = $GLOBALS['visiteur_session']['tmp_forum_document'];
	$doc = &$_FILES['ajouter_document'];
	if (isset($_FILES['ajouter_document'])
	AND $_FILES['ajouter_document']['tmp_name']) {
		// securite :
		// verifier si on possede la cle (ie on est autorise a poster)
		// (sinon tant pis) ; cf. charger.php pour la definition de la cle
		if (_request('cle_ajouter_document') != calculer_cle_action($a = "ajouter-document-$id_article-$id_breve-$id_forum-$id_rubrique-$id_syndic")) {
			$erreurs['document_forum'] = _T('public:documents_interdits_forum')
				. "ajouter-document-$id_article-$id_breve-$id_forum-$id_rubrique-$id_syndic"
				.", "
				._request('cle_ajouter_document')
			
			;
			unset($_FILES['ajouter_document']);
		} else {
			include_spip('inc/ajouter_documents');
			list($extension,$doc['name']) = fixer_extension_document($doc);
			$acceptes = forum_documents_acceptes();

			if (!in_array($extension, $acceptes)) {
				# normalement on n'arrive pas ici : pas d'upload si aucun format
				if (!$formats = join(', ',$acceptes))
					$formats = '-'; //_L('aucun');
				$erreurs['document_forum'] = _T('public:formats_acceptes', array('formats' => $formats));
			}
			else {
				include_spip('inc/getdocument');
				if (!deplacer_fichier_upload($doc['tmp_name'], $tmp.'.bin'))
					$erreurs['document_forum'] = _T('copie_document_impossible');

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
		if (_request('supprimer_document_ajoute')) {
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
			$previsu = inclure_previsu($texte, $titre, _request('url_site'), _request('nom_site'), _request('ajouter_mot'), $doc,
				$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic);
			$erreurs['previsu'] = $previsu;
		}
	}

	return $erreurs;
}

function forum_documents_acceptes()
{
	$formats = trim($GLOBALS['meta']['formats_documents_forum']);
	if (!$formats) return array();
	if ($formats !== '*') 
		$formats = array_filter(preg_split(',[^a-zA-Z0-9/+_],', $formats));
	else {
		include_spip('base/typedoc');
		$formats =  array_keys($GLOBALS['tables_mime']);
	}
	sort($formats);
	return $formats;
}

// http://doc.spip.org/@inclure_previsu
function inclure_previsu($texte,$titre, $url_site, $nom_site, $ajouter_mot, $doc,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic) {
	global $table_des_traitements;

	$bouton = _T('forum_message_definitif');
	include_spip('public/assembler');
	include_spip('public/composer');

	// appliquer les traitements de #TEXTE a la previsu
	// comme on voit c'est complique... y a peut-etre plus simple ?
	// recuperer les filtres eventuels de 'mes_fonctions.php' sur les balises
	include_spip('public/parametrer');
	$evaltexte = isset($table_des_traitements['TEXTE']['forums'])
		? $table_des_traitements['TEXTE']['forums']
		: $table_des_traitements['TEXTE'][0];
	$evaltexte = '$tmptexte = '.str_replace('%s', '$texte', $evaltexte).';';
	// evaluer...
	eval($evaltexte);

	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace("@<(/?)form\b@ism",
			    '<\1div',
		inclure_balise_dynamique(array('formulaires/inc-forum_previsu',
		      0,
		      array(
			'titre' => safehtml(typo($titre)),
			'texte' => $tmptexte,
			'notes' => safehtml(calculer_notes()),
			'url_site' => vider_url($url_site),
			'nom_site' => safehtml(typo($nom_site)),
			'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
			'ajouter_document' => $doc,
			'erreur' => $erreur,
			'bouton' => $bouton,
			'id_rubrique' => $id_rubrique,
			'id_forum' => $id_forum,
			'id_article' => $id_article,
			'id_breve' => $id_breve,
			'id_syndic' => $id_syndic
			)
		), false));
}


function formulaires_forum_traiter_dist() {
	// le formulaire forum n'est jamais traite en ajax car il s'acheve par une redirection vers
	// la bonne page qui doit etre reaffichee dans son ensemble
	refuser_traiter_formulaire_ajax();

	$forum_insert = charger_fonction('forum_insert', 'inc');

	list($redirect,$id_forum) = $forum_insert();
	return array('redirect'=>$redirect,'id_forum'=>$id_forum);
}


?>
