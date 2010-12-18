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
include_spip('inc/forum');
include_spip('inc/filtres');
include_spip('inc/actions');

// Ce fichier est inclus par dist/formulaires/forum.php

// http://doc.spip.org/@controler_forum_abo
function controler_forum_abo($retour)
{
	global $visiteur_session;
	if ($visiteur_session) {
		$statut = $visiteur_session['statut'];
		if (!$statut OR $statut == '5poubelle') {
			ask_php_auth(_T('forum_acces_refuse'),
				     _T('forum_cliquer_retour',
						array('retour_forum' => $retour)));
		}
	} else {
		ask_php_auth(_T('forum_non_inscrit'),
			     _T('forum_cliquer_retour',
					array('retour_forum' => $retour)));
		}
}

// http://doc.spip.org/@controler_forum
function controler_forum($id) {

	// Reglage forums d'article

	if ($id) $id = sql_getfetsel('accepter_forum','spip_articles',"id_article=$id");
	// Valeur par defaut
	return $id ? $id: substr($GLOBALS['meta']["forums_publics"],0,3);


}

// http://doc.spip.org/@mots_du_forum
function mots_du_forum($ajouter_mot, $id_message)
{
	$t = array('id_forum' => $id_message);
	foreach ($ajouter_mot as $id_mot)
		if ($t['id_mot'] = intval($id_mot))
			sql_insertq('spip_mots_forum', $t);
}


// http://doc.spip.org/@reduce_strlen
function reduce_strlen($n, $c)
{
  return $n - strlen($c);
}


// http://doc.spip.org/@tracer_erreur_forum
function tracer_erreur_forum($type='') {
	spip_log("erreur forum ($type): ".print_r($_POST, true));

	define('_TRACER_ERREUR_FORUM', false);
	if (_TRACER_ERREUR_FORUM) {
		$envoyer_mail = charger_fonction('envoyer_mail','inc');
		$envoyer_mail($GLOBALS['meta']['email_webmaster'], "erreur forum ($type)",
			"erreur sur le forum ($type) :\n\n".
			'$_POST = '.print_r($_POST, true)."\n\n".
			'$_SERVER = '.print_r($_SERVER, true));
	}
}

// Un parametre permet de forcer le statut (exemple: plugin antispam)
// http://doc.spip.org/@inc_forum_insert_dist
function inc_forum_insert_dist($force_statut = NULL) {
	$id_article = intval(_request('id_article'));
	$id_breve = intval(_request('id_breve'));
	$id_forum = intval(_request('id_forum'))>0?intval(_request('id_forum')):0;
	$id_rubrique = intval(_request('id_rubrique'));
	$id_syndic = intval(_request('id_syndic'));

	$reqret = rawurldecode(_request('retour_forum'));
	$retour = ($reqret !== '!') ? $reqret : forum_insert_nopost($id_forum, $id_article, $id_breve, $id_syndic, $id_rubrique);

	$c = array('statut'=>'off');
	foreach(array('id_article','id_breve','id_rubrique','id_syndic') as $k)
		if ($$k)
			$c[$k] = $$k;
	foreach (array(
		'titre', 'texte', 'nom_site', 'url_site'
	) as $champ)
		$c[$champ] = _request($champ);

	$c['auteur'] = sinon($GLOBALS['visiteur_session']['nom'],
		$GLOBALS['visiteur_session']['session_nom']);
	$c['email_auteur'] = sinon($GLOBALS['visiteur_session']['email'],
		$GLOBALS['visiteur_session']['session_email']);
		
	$c = pipeline('pre_edition',array(
		'args'=>array(
				'table' => 'spip_forum',
				'id_objet' => $id_forum,
				'action'=>'instituer'
		),
		'data'=>forum_insert_statut($c, $retour, $force_statut)
	));

	$id_message = forum_insert_base($c, $id_forum, $id_article, $id_breve, $id_syndic, $id_rubrique, $c['statut'], $retour);

	if (!$id_message) return array($retour,0); // echec

	// En cas de retour sur (par exemple) {#SELF}, on ajoute quand
	// meme #forum12 a la fin de l'url, sauf si un #ancre est explicite
	if ($reqret !== '!')
	  return array(strpos($retour, '#') ?
			$retour
			: $retour.'#forum'.$id_message,$id_message);

	// le retour par defaut envoie sur le thread, ce qui permet
	// de traiter elegamment le cas des forums moderes a priori.
	// Cela assure aussi qu'on retrouve son message dans le thread
	// dans le cas des forums moderes a posteriori, ce qui n'est
	// pas plus mal.
	$url = function_exists('generer_url_forum')
		? generer_url_forum($id_message)
		: generer_url_entite($id_message, 'forum');
	
	return array($url,$id_message);
}

// http://doc.spip.org/@forum_insert_base
function forum_insert_base($c, $id_forum, $id_article, $id_breve, $id_syndic, $id_rubrique, $statut, $retour)
{
	$afficher_texte = (_request('afficher_texte') <> 'non');
	$ajouter_mot = _request('ajouter_mot');

	// si le statut est vide, c'est qu'on ne veut pas de ce presume spam !
	if (!$statut)
		return false;

	//  Si forum avec previsu sans bon hash de securite, echec silencieux
	if ($afficher_texte AND forum_insert_noprevisu()) {
		return false;
	}

	if (array_reduce($_POST, 'reduce_strlen', (20 * 1024)) < 0) {
		ask_php_auth(_T('forum_message_trop_long'),
			_T('forum_cliquer_retour',
				array('retour_forum' => $retour)));
	}

	// Entrer le message dans la base
	$id_message = sql_insertq('spip_forum', array(
		'date_heure'=> date('Y-m-d H:i:s'),
		'ip' => $GLOBALS['ip'],
		'id_auteur' => $GLOBALS['visiteur_session']['id_auteur']
	));

	if ($id_forum>0) {
		$id_thread = sql_getfetsel("id_thread", "spip_forum", "id_forum = $id_forum");
	}
	else
		$id_thread = $id_message; # id_thread oblige INSERT puis UPDATE.

	// id_rubrique est parfois passee pour les articles, on n'en veut pas
	if ($id_rubrique > 0 AND ($id_article OR $id_breve OR $id_syndic))
		$id_rubrique = 0;

	// Entrer les cles de jointures et assimilees
	sql_updateq('spip_forum', array('id_parent' => $id_forum, 'id_rubrique' => $id_rubrique, 'id_article' => $id_article, 'id_breve' => $id_breve, 'id_syndic' => $id_syndic, 'id_thread' => $id_thread, 'statut' => $statut), "id_forum = $id_message");

	// Entrer les mots-cles associes
	if ($ajouter_mot) mots_du_forum($ajouter_mot, $id_message);

	//
	// Entree du contenu et invalidation des caches
	//
	include_spip('inc/modifier');
	revision_forum($id_message, $c);

	// Ajouter un document
	if (isset($_FILES['ajouter_document'])
	AND $_FILES['ajouter_document']['tmp_name']) {
		$ajouter_documents = charger_fonction('ajouter_documents', 'inc');
		$ajouter_documents(
			$_FILES['ajouter_document']['tmp_name'],
			$_FILES['ajouter_document']['name'], 'forum', $id_message,
			'document', 0, $documents_actifs);
		// supprimer le temporaire et ses meta donnees
		spip_unlink($_FILES['ajouter_document']['tmp_name']);
		spip_unlink(preg_replace(',\.bin$,',
			'.txt', $_FILES['ajouter_document']['tmp_name']));
	}

	// Notification
	if ($notifications = charger_fonction('notifications', 'inc'))
		$notifications('forumposte', $id_message);

	return $id_message;
}

// calcul de l'adresse de retour en cas d'echec du POST
// mais la veritable adresse de retour sera calculee apres insertion

// http://doc.spip.org/@forum_insert_nopost
function forum_insert_nopost($id_forum, $id_article, $id_breve, $id_syndic, $id_rubrique)
{
	if ($id_forum>0)
		$r = generer_url_entite($id_forum, 'forum');
	elseif ($id_article)
		$r = generer_url_entite($id_article, 'article');
	elseif ($id_breve)
		$r = generer_url_entite($id_breve, 'breve');
	elseif ($id_syndic)
		$r = generer_url_entite($id_syndic, 'site');
	elseif ($id_rubrique) # toujours en dernier
		$r = generer_url_entite($id_rubrique, 'rubrique');
	else $r = ''; # ??
	return str_replace('&amp;','&',$r);
}

// http://doc.spip.org/@forum_insert_noprevisu
function forum_insert_noprevisu()
{
	// simuler une action venant de l'espace public
	// pour se conformer au cas general.
	set_request('action', 'ajout_forum');
	// Creer une session s'il n'y en a pas (cas du postage sans cookie)
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$file = _DIR_TMP ."forum_" . preg_replace('/[^0-9]/', '', $arg) .".lck";
	if (!file_exists($file)) {
		# ne pas tracer cette erreur, peut etre due a un double POST
		# tracer_erreur_forum('session absente');
		return true;
	}
	unlink($file);

	// antispam : si le champ au nom aleatoire verif_$hash n'est pas 'ok'
	// on meurt
	if (_request('verif_'.substr(_request('hash'),0,32)) != 'ok') {
			tracer_erreur_forum('champ verif manquant');
			return true;
	}
	return false;
}

// http://doc.spip.org/@forum_insert_statut
function forum_insert_statut($champs, $retour, $forcer_statut=NULL)
{
	$statut = controler_forum($champs['id_article']);

	// Ne pas autoriser d'envoi hacke si forum sur abonnement
	if ($statut == 'abo') {
		controler_forum_abo($retour); // demandera une auth http
	}
	
	if ($forcer_statut !== NULL)
		$champs['statut'] = $forcer_statut;
	else
		$champs['statut'] = ($statut == 'non') ? 'off' : (($statut == 'pri') ? 'prop' :	'publie');
		
	// Antispam basique : si 'nobot' a ete renseigne, ca ne peut etre qu'un bot
	if (strlen(_request('nobot'))) {
		tracer_erreur_forum('champ interdit (nobot) rempli');
		$champs['statut']=false;
	}

	return $champs;
}

?>
