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

/**
 * Charger l'env du squelette de #FORMULAIRE_FORUM
 * @param string $objet
 * @param int $id_objet
 * @param int $id_forum
 * @param int|array $ajouter_mot
 *   mots ajoutés cochés par defaut
 * @param $ajouter_groupe
 *   groupes ajoutables
 * @param $afficher_previsu
 *   previsu oui ou non
 * @param $retour
 *   url de retour
 * @return array|bool
 */
function formulaires_forum_charger_dist($objet, $id_objet, $id_forum,
                                        $ajouter_mot, $ajouter_groupe, $afficher_previsu, $retour){

	if (!function_exists($f = 'forum_recuperer_titre'))
		$f = 'forum_recuperer_titre_dist';
	if (!$titre = $f($objet, $id_objet, $id_forum))
		return false;

	// ca s'apparenterait presque a une autorisation...
	// si on n'avait pas a envoyer la valeur $accepter_forum au formulaire
	$accepter_forum = controler_forum($objet, $id_objet);
	if ($accepter_forum=='non'){
		return false;
	}

	$primary = id_table_objet($objet);

	// table a laquelle sont associes les mots :
	if ($GLOBALS['meta']["mots_cles_forums"]!="oui")
		$table = '';
	else
		$table = table_objet($objet);

	// exiger l'authentification des posteurs pour les forums sur abo
	if ($accepter_forum=="abo"){
		if (!isset($GLOBALS["visiteur_session"]['statut']) OR !$GLOBALS["visiteur_session"]['statut']){
			return array(
				'action' => '', #ne sert pas dans ce cas, on la vide pour mutualiser le cache
				'editable' => false,
				'login_forum_abo' => ' ',
				'inscription' => generer_url_public('identifiants', 'lang=' . $GLOBALS['spip_lang']),
				'oubli' => generer_url_public('spip_pass', 'lang=' . $GLOBALS['spip_lang'], true),
			);
		}
	}


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

	if (_request('formulaire_action')){
		$arg = forum_fichier_tmp(join('', $ids));

		$securiser_action = charger_fonction('securiser_action', 'inc');
		// on sait que cette fonction est dans le fichier associe
		$hash = calculer_action_auteur("ajout_forum-$arg");
	}
	else {
		$arg = $hash = '';
	}

	// pour les hidden
	$script_hidden = "";
	foreach ($ids as $id => $v)
		$script_hidden .= "<input type='hidden' name='$id' value='$v' />";

	$script_hidden .= "<input type='hidden' name='arg' value='$arg' />";
	$script_hidden .= "<input type='hidden' name='hash' value='$hash' />";
	$script_hidden .= "<input type='hidden' name='verif_$hash' value='ok' />";

	if ($formats = forum_documents_acceptes()){
		include_spip('inc/securiser_action');
		$cle = calculer_cle_action('ajouter-document-' . $objet . '-' . $id_objet);
	}
	else {
		$cle = null;
	}

	// Valeurs par defaut du formulaire
	// si le formulaire a ete sauvegarde, restituer les valeurs de session
	$vals = array(
		'titre' => $titre,
		'texte' => '',
		'nom_site' => '',
		'url_site' => 'http://'
	);

	return array_merge($vals, array(
		'modere' => (($accepter_forum!='pri') ? '' : ' '),
		'table' => $table,
		'config' => array('afficher_barre' => ($GLOBALS['meta']['forums_afficher_barre']!='non' ? ' ' : '')),
		'_hidden' => $script_hidden, # pour les variables hidden qui seront inserees dans le form et dans le form de previsu
		'cle_ajouter_document' => $cle,
		'formats_documents_forum' => forum_documents_acceptes(),
		'ajouter_document' => isset($_FILES['ajouter_document']['name']) ? $_FILES['ajouter_document']['name'] : '',
		'nobot' => ($cle ? _request($cle) : _request('nobot')),
		'ajouter_groupe' => $ajouter_groupe,
		'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
		'id_forum' => $id_forum, // passer id_forum au formulaire pour lui permettre d'afficher a quoi l'internaute repond
		'_sign' => implode('_', $ids),
		'_autosave_id' => $ids,
	));
}


/**
 * Une securite qui nous protege contre :
 * - les doubles validations de forums (derapages humains ou des brouteurs)
 * - les abus visant a mettre des forums malgre nous sur un article (??)
 * On installe un fichier temporaire dans _DIR_TMP (et pas _DIR_CACHE
 * afin de ne pas bugguer quand on vide le cache)
 * Le lock est leve au moment de l'insertion en base (inc-messforum)
 * Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
 * si $afficher_previsu = 'non')
 *
 * http://doc.spip.org/@forum_fichier_tmp
 *
 * @param $arg
 * @return int
 */
function forum_fichier_tmp($arg){
# astuce : mt_rand pour autoriser les hits simultanes
	while (($alea = time()+@mt_rand())+intval($arg)
		AND @file_exists($f = _DIR_TMP . "forum_$alea.lck")){
	}
	;
	spip_touch($f);

# et maintenant on purge les locks de forums ouverts depuis > 4 h

	if ($dh = @opendir(_DIR_TMP)){
		while (($file = @readdir($dh))!==false){
			if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
				AND (time()-@filemtime(_DIR_TMP . $file)>4*3600)
			)
				spip_unlink(_DIR_TMP . $file);
		}
	}
	return $alea;
}

/**
 * Verifier la saisie de #FORMULAIRE_FORUM
 * @param string $objet
 * @param int $id_objet
 * @param int $id_forum
 * @param int|array $ajouter_mot
 *   mots ajout�s coch�s par defaut
 * @param $ajouter_groupe
 *   groupes ajoutables
 * @param $afficher_previsu
 *   previsu oui ou non
 * @param $retour
 *   url de retour
 * @return array|bool
 */
function formulaires_forum_verifier_dist($objet, $id_objet, $id_forum,
	$ajouter_mot, $ajouter_groupe, $afficher_previsu, $retour)
{
	include_spip('inc/acces');
	include_spip('inc/texte');
	include_spip('inc/session');

	$erreurs = array();

	// desactiver id_rubrique si un id_article ou autre existe dans le contexte
	// if ($id_article OR $id_breve OR $id_forum OR $id_syndic)
	//	$id_rubrique = 0;

	// stocker un eventuel document dans un espace temporaire
	// portant la cle du formulaire ; et ses metadonnees avec

	if (!isset($GLOBALS['visiteur_session']['tmp_forum_document']))
		session_set('tmp_forum_document',
			sous_repertoire(_DIR_TMP, 'documents_forum') . md5(uniqid(rand())));
	$tmp = $GLOBALS['visiteur_session']['tmp_forum_document'];
	$doc = &$_FILES['ajouter_document'];
	if (isset($_FILES['ajouter_document'])
		AND $_FILES['ajouter_document']['tmp_name']
	){
		// securite :
		// verifier si on possede la cle (ie on est autorise a poster)
		// (sinon tant pis) ; cf. charger.php pour la definition de la cle
		if (_request('cle_ajouter_document')!=calculer_cle_action($a = "ajouter-document-$objet-$id_objet")){
			$erreurs['document_forum'] = _T('forum:documents_interdits_forum');
			unset($_FILES['ajouter_document']);
		}
		else {
			include_spip('inc/joindre_document');
			include_spip('action/ajouter_documents');
			list($extension, $doc['name']) = fixer_extension_document($doc);
			$acceptes = forum_documents_acceptes();

			if (!in_array($extension, $acceptes)){
				# normalement on n'arrive pas ici : pas d'upload si aucun format
				if (!$formats = join(', ', $acceptes)){
					$formats = '-'; //_L('aucun');
				}
				$erreurs['document_forum'] = _T('public:formats_acceptes', array('formats' => $formats));
			}
			else {
				include_spip('inc/getdocument');
				if (!deplacer_fichier_upload($doc['tmp_name'], $tmp . '.bin'))
					$erreurs['document_forum'] = _T('copie_document_impossible');

				#		else if (...)
				#		verifier le type_document autorise
				#		retailler eventuellement les photos
			}

			// si ok on stocke les meta donnees, sinon on efface
			if (isset($erreurs['document_forum'])){
				spip_unlink($tmp . '.bin');
				unset ($_FILES['ajouter_document']);
			}
			else {
				$doc['tmp_name'] = $tmp . '.bin';
				ecrire_fichier($tmp . '.txt', serialize($doc));
			}
		}
	} // restaurer le document uploade au tour precedent
	elseif (file_exists($tmp . '.bin')){
		if (_request('supprimer_document_ajoute')){
			spip_unlink($tmp . '.bin');
			spip_unlink($tmp . '.txt');
		}
		elseif (lire_fichier($tmp . '.txt', $meta)){
			$doc = @unserialize($meta);
		}
	}

	$min_length = (defined('_FORUM_LONGUEUR_MINI') ? _FORUM_LONGUEUR_MINI : 10);
	if (strlen($texte = _request('texte'))<$min_length
		AND !$ajouter_mot AND $GLOBALS['meta']['forums_texte']=='oui'
	){
		$erreurs['texte'] = _T($min_length==10 ? 'forum:forum_attention_dix_caracteres' : 'forum:forum_attention_nb_caracteres_mini', array('min' => $min_length));
	}
	elseif (defined('_FORUM_LONGUEUR_MAXI')
	  AND _FORUM_LONGUEUR_MAXI>0
	  AND strlen($texte)>_FORUM_LONGUEUR_MAXI){
		$erreurs['texte'] = _T('forum:forum_attention_trop_caracteres',
			array(
				'compte' => strlen($texte),
				'max' => _FORUM_LONGUEUR_MAXI
			));
	}

	if (array_reduce($_POST, 'reduce_strlen', (20*1024))<0){
		$erreurs['erreur_message'] = _T('forum:forum_message_trop_long');
	}
	else {
		// Ne pas autoriser d'envoi hacke si forum sur abonnement
		if (controler_forum($objet, $id_objet)=='abo'
			AND !test_espace_prive()){
			if (!isset($GLOBALS['visiteur_session'])
				OR !isset($GLOBALS['visiteur_session']['statut'])){
				$erreurs['erreur_message'] = _T('forum_non_inscrit');
			}
			elseif ($GLOBALS['visiteur_session']['statut']=='5poubelle') {
				$erreurs['erreur_message'] = _T('forum:forum_acces_refuse');
			}
		}
	}

	if (strlen($titre = _request('titre'))<3
	  AND $GLOBALS['meta']['forums_titre']=='oui'){
		$erreurs['titre'] = _T('forum:forum_attention_trois_caracteres');
	}

	if (!count($erreurs) AND !_request('confirmer_previsu_forum')){
		if ($afficher_previsu!='non'){
			$previsu = inclure_previsu($texte, $titre, _request('url_site'), _request('nom_site'), _request('ajouter_mot'), $doc,
				$objet, $id_objet, $id_forum);
			$erreurs['previsu'] = $previsu;
		}
	}

	//  Si forum avec previsu sans bon hash de securite, echec
	if (!count($erreurs)){
		if (!test_espace_prive()
		  AND $afficher_previsu<>'non'
		  AND forum_insert_noprevisu()){
			$erreurs['erreur_message'] = _T('forum:forum_acces_refuse');
		}
	}

	return $erreurs;
}


/**
 * Lister les formats de documents joints acceptes dans les forum
 * @return array
 */
function forum_documents_acceptes(){
	$formats = trim($GLOBALS['meta']['formats_documents_forum']);
	if (!$formats) return array();
	if ($formats!=='*'){
		$formats = array_filter(preg_split(',[^a-zA-Z0-9/+_],', $formats));
	}
	else {
		include_spip('base/typedoc');
		$formats = array_keys($GLOBALS['tables_mime']);
	}
	sort($formats);
	return $formats;
}


/**
 * Preparer la previsu d'un message de forum
 *
 * http://doc.spip.org/@inclure_previsu
 *
 * @param string $texte
 * @param string $titre
 * @param string $url_site
 * @param string $nom_site
 * @param array $ajouter_mot
 * @param array $doc
 * @param string $objet
 * @param int $id_objet
 * @param int $id_forum
 * @return string
 */
function inclure_previsu($texte, $titre, $url_site, $nom_site, $ajouter_mot, $doc,
                         $objet, $id_objet, $id_forum){
	global $table_des_traitements;

	$bouton = _T('forum:forum_message_definitif');
	include_spip('public/assembler');
	include_spip('public/composer');

	// appliquer les traitements de #TEXTE a la previsu
	// comme on voit c'est complique... y a peut-etre plus simple ?
	// recuperer les filtres eventuels de 'mes_fonctions.php' sur les balises
	include_spip('public/parametrer');
	$tmptexte = "";
	$evaltexte = isset($table_des_traitements['TEXTE']['forums'])
		? $table_des_traitements['TEXTE']['forums']
		: $table_des_traitements['TEXTE'][0];
	$evaltexte = '$tmptexte = ' . str_replace('%s', '$texte', $evaltexte) . ';';
	// evaluer...
	// [fixme]
	// $connect et $Pile ne sont pas definis ici :/
	// mais font souvent partie des variables appelees par les traitements
	$connect = "";
	$Pile = array(0 => array());
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
				#'erreur' => $erreur, // non definie ?
				'bouton' => $bouton,
				'objet' => $objet,
				'id_objet' => $id_objet,
				'id_forum' => $id_forum
			)
		), false));
}


/**
 * Traiter la saisie de #FORMULAIRE_FORUM
 * tout est delegue a inc_forum_insert()
 *
 * @param string $objet
 * @param int $id_objet
 * @param int $id_forum
 * @param int|array $ajouter_mot
 *   mots ajoutes coches par defaut
 * @param $ajouter_groupe
 *   groupes ajoutables
 * @param $afficher_previsu
 *   previsu oui ou non
 * @param $retour
 *   url de retour
 * @return array|bool
 */
function formulaires_forum_traiter_dist($objet, $id_objet, $id_forum,
                                        $ajouter_mot, $ajouter_groupe, $afficher_previsu, $retour){

	$forum_insert = charger_fonction('forum_insert', 'inc');

	// Antispam basique :
	// si l'input invisible a ete renseigne, ca ne peut etre qu'un bot
	if (strlen(_request(_request('cle_ajouter_document')))){
		tracer_erreur_forum('champ interdit (nobot) rempli');
		return array('message_erreur' => _T('forum:erreur_enregistrement_message'));
	}

	if (defined('_FORUM_AUTORISER_POST_ID_FORUM')
	  AND _FORUM_AUTORISER_POST_ID_FORUM
	  AND _request('id_forum')){
		$id_forum = _request('id_forum');
	}

	$id_reponse = $forum_insert($objet, $id_objet, $id_forum);


	if ($id_reponse){
		// En cas de retour sur (par exemple) {#SELF}, on ajoute quand
		// meme #forum12 a la fin de l'url, sauf si un #ancre est explicite
		if ($retour){
			if (!strpos($retour, '#')){
				$retour .= '#forum' . $id_reponse;
			}
		}
		else {
			// le retour par defaut envoie sur le thread, ce qui permet
			// de traiter elegamment le cas des forums moderes a priori.
			// Cela assure aussi qu'on retrouve son message dans le thread
			// dans le cas des forums moderes a posteriori, ce qui n'est
			// pas plus mal.
			if (function_exists('generer_url_forum')){
				$retour = generer_url_forum($id_reponse);
			}
			else {
				$thread = Sql::fetsel('id_thread', 'spip_forum', 'id_forum=' . $id_reponse);
				spip_log('id_thread=' . $thread['id_thread'], 'forum');
				$retour = generer_url_entite($thread['id_thread'], 'forum');
			}
		}

		$res = array('redirect' => $retour, 'id_forum' => $id_reponse);
	}
	else {
		$res = array('message_erreur' => _T('forum:erreur_enregistrement_message'));
	}

	return $res;
}


?>
