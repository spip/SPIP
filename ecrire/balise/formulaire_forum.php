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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/meta');
include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/lang');
include_spip('inc/mail');
include_spip('inc/forum');
include_spip('base/abstract_sql');
spip_connect();

charger_generer_url();

/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

// Contexte du formulaire
// Mots-cles dans les forums :
// Si la variable de personnalisation $afficher_groupe[] est definie
// dans le fichier d'appel, et si la table de reference est OK, proposer
// la liste des mots-cles

function balise_FORMULAIRE_FORUM ($p) {

	$p = calculer_balise_dynamique($p,'FORMULAIRE_FORUM', array('id_rubrique', 'id_forum', 'id_article', 'id_breve', 'id_syndic', 'ajouter_mot', 'ajouter_groupe', 'afficher_texte'));

	// Ajouter le code d'invalideur specifique aux forums
	include_spip('inc/invalideur');
	if (function_exists($i = 'code_invalideur_forums'))
		$p->code = $i($p, $p->code);

	return $p;
}

// verification des droits a faire du forum
function balise_FORMULAIRE_FORUM_stat($args, $filtres) {

	// Note : ceci n'est pas documente !!
	// $filtres[0] peut contenir l'url sur lequel faire tourner le formulaire
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM|forum)]
	// ou encore [(#FORMULAIRE_FORUM|forumspip.php)]

	// le denier arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]

	// recuperer les donnees du forum auquel on repond, false = forum interdit
	list ($idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url) = $args;
	$idr = intval($idr);
	$idf = intval($idf);
	$ida = intval($ida);
	$idb = intval($idb);
	$ids = intval($ids);
	if (!$r = sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids))
		return '';

	list ($titre, $table, $forums_publics) = $r;

	if (($GLOBALS['meta']["mots_cles_forums"] != "oui"))
		$table = '';

	// Sur quelle adresse va-t-on "boucler" pour la previsualisation ?
	if ($script = $filtres[0])
		$script = preg_match(',[.]php3?$,', $script) ?
			$script : generer_url_public($script);
	else
		$script = self(); # sur soi-meme

	return
		array($titre, $table, $forums_publics, $script,
		$idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url);
}

function balise_FORMULAIRE_FORUM_dyn(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour)
{
	// verifier l'identite des posteurs pour les forums sur abo
	if ($type == "abo") {
		if (!$GLOBALS["auteur_session"]) {
			return array('formulaires/formulaire_login_forum', 0,
				array('inscription' => generer_url_public('spip_inscription'),
					'oubli' => generer_url_public('spip_pass')));
		} else {
	  // forcer ces valeur
		$auteur = $GLOBALS['auteur_session']['nom'];
		$email_auteur = $GLOBALS['auteur_session']['email'];
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
	if (!$retour_forum = rawurldecode(_request('retour_forum'))) {
		if ($retour_forum = rawurldecode(_request('retour')))
			$retour_forum = str_replace('&var_mode=recalcul','',$retour_forum);
		else {
			// par defaut, on veut prendre url_forum(), mais elle ne sera connue
			// qu'en sortie, on inscrit donc une valeur absurde ("!")
			$retour_forum = "!";
			// sauf si on a passe un parametre en argument (exemple : {#SELF})
			if ($url_param_retour)
				$retour_forum = str_replace('&amp;', '&', $url_param_retour);
		}
		if (isset($_COOKIE['spip_forum_user'])
		AND is_array($cookie_user = unserialize($_COOKIE['spip_forum_user']))) {
			$auteur = $cookie_user['nom'];
			$email_auteur = $cookie_user['email'];
		} else {
			$auteur = $GLOBALS['auteur_session']['nom'];
			$email_auteur = $GLOBALS['auteur_session']['email'];
		}

	} else { // appels ulterieurs

		// Recuperer le message a previsualiser
		$titre = _request('titre');
		$texte = _request('texte');
		$auteur = _request('auteur');
		$email_auteur = _request('email_auteur');
		$nom_site_forum = _request('nom_site_forum');
		$url_site = _request('url_site');
		$ajouter_mot = _request('ajouter_mot');
		$ajouter_groupe = _request('ajouter_groupe');

		if ($afficher_texte != 'non') 
			$previsu = inclure_previsu($texte, $titre, $email_auteur, $auteur, $url_site, $nom_site_forum, $ajouter_mot);

		$alea = forum_fichier_tmp();

		include_spip('inc/actions');
		$hash = calculer_action_auteur('ajout_forum'.join(' ', $ids).' '.$alea);

		// Poser un cookie pour ne pas retaper les infos invariables
		include_spip('inc/cookie');
		spip_setcookie('spip_forum_user',
			serialize(array('nom' => $auteur,
				'email' => $email_auteur)));
	}

	// pour la chaine de hidden
	$script_hidden = $script = str_replace('&amp;', '&', $script);
	foreach ($ids as $id => $v)
		$script_hidden = parametre_url($script_hidden, $id, $v, '&');

	return array('formulaires/formulaire_forum', 0,
	array(
		'auteur' => $auteur,
		'readonly' => ($type == "abo")? "readonly" : '',
		'email_auteur' => $email_auteur,
		'modere' => (($type != 'pri') ? '' : ' '),
		'nom_site_forum' => $nom_site_forum,
		'retour_forum' => $retour_forum,
		'afficher_texte' => $afficher_texte,
		'previsu' => $previsu,
		'table' => $table,
		'texte' => $texte,
		'titre' => extraire_multi($titre),
		'url' => $script, # ce sur quoi on fait le action='...'
		'url_post' => $script_hidden, # pour les variables hidden
		'url_site' => ($url_site ? $url_site : "http://"),
		'alea' => $alea,
		'hash' => $hash,
		'nobot' => _request('nobot'),
		'ajouter_groupe' => $ajouter_groupe,
		'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),

		));
}

function inclure_previsu($texte,$titre, $email_auteur, $auteur, $url_site, $nom_site_forum, $ajouter_mot)
{
	$erreur = $bouton = '';

	if (strlen($texte) < 10 AND !$ajouter_mot)
		$erreur = _T('forum_attention_dix_caracteres');
	else if (strlen($titre) < 3)
		$erreur = _T('forum_attention_trois_caracteres');
	else
		$bouton = _T('forum_message_definitif');

	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace("@<(/?)f(orm[>[:space:]])@ism",
			    "<\\1no-f\\2",
		inclure_balise_dynamique(array('formulaires/formulaire_forum_previsu',
		      0,
		      array(
			'titre' => safehtml(typo($titre)),
			'email_auteur' => safehtml($email_auteur),
			'auteur' => safehtml(typo($auteur)),
			'texte' => safehtml(propre($texte)),
			'url_site' => vider_url($url_site),
			'nom_site_forum' => safehtml(typo($nom_site_forum)),
			'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
			'erreur' => $erreur,
			'bouton' => $bouton
			)
					       ),
					 false));
}

// Une securite qui nous protege contre :
// - les doubles validations de forums (derapages humains ou des brouteurs)
// - les abus visant a mettre des forums malgre nous sur un article (??)
// On installe un fichier temporaire dans _DIR_TMP (et pas _DIR_CACHE
// afin de ne pas bugguer quand on vide le cache)
// Le lock est leve au moment de l'insertion en base (inc-messforum)
// Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
// si $afficher_texte = 'non')

function forum_fichier_tmp()
{
# astuce : mt_rand pour autoriser les hits simultanes
	while (($alea = time() + @mt_rand())
	       AND @file_exists($f = _DIR_TMP."forum_$alea.lck"))
	  {};
	spip_touch ($f);

# et maintenant on purge les locks de forums ouverts depuis > 4 h

	if ($dh = @opendir(_DIR_TMP))
		while (($file = @readdir($dh)) !== false)
			if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
			AND (time()-@filemtime(_DIR_TMP.$file) > 4*3600))
				@unlink(_DIR_TMP.$file);
	return $alea;
}


/*******************************************************/
/* FONCTIONS DE CALCUL DES DONNEES DU FORMULAIRE FORUM */
/*******************************************************/

//
// Chercher le titre et la configuration du forum de l'element auquel on repond
//

function sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids) {

	// changer la table de reference s'il y a lieu (pour afficher_groupes[] !!)
	if ($ida) {
		$titre = spip_abstract_fetsel('titre', 'spip_articles', "statut = 'publie' AND id_article = $ida");
		$table = "articles";
	} else if ($idb) {
		$titre = spip_abstract_fetsel('titre', 'spip_breves', "statut = 'publie' AND id_breve = $idb");
		$table = "breves";
	} else if ($ids) {
		$titre = spip_abstract_fetsel('nom_site AS titre', 'spip_syndic', "statut = 'publie' AND id_syndic = $ids");
		$table = "syndic";
	} else if ($idr) {
		$titre = spip_abstract_fetsel('titre', 'spip_rubriques', "statut = 'publie' AND id_rubrique = $idr");
		$table = "rubriques";
	}

	if ($idf AND $titre)
		$titre = spip_abstract_fetsel('titre', 'spip_forum', "statut = 'publie' AND id_forum = $idf");

	if ($titre) {
		$titre = supprimer_numero($titre['titre']);
	} else 
		return false;

	// quelle est la configuration du forum ?
	$type = !$ida ? false : spip_abstract_fetsel('accepter_forum', 'spip_articles', "id_article=$ida");

	if ($type) $type = $type['accepter_forum'];

	if (!$type) $type = substr($GLOBALS['meta']["forums_publics"],0,3);

	// valeurs possibles : 'pos'teriori, 'pri'ori, 'abo'nnement
	if ($type == "non")
		return false;

	return array ($titre, $table, $type);
}

?>
