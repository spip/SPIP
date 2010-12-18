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

// http://doc.spip.org/@action_editer_article_dist
function action_editer_article_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// si id_article n'est pas un nombre, c'est une creation
	// mais on verifie qu'on a toutes les donnees qu'il faut.
	if (!$id_article = intval($arg)) {
		$id_parent = _request('id_parent');
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
		if (!($id_parent AND $id_auteur)) {
			include_spip('inc/headers');
			redirige_url_ecrire();
		}
		if (($id_article = insert_article($id_parent)) > 0)

		# cf. GROS HACK ecrire/inc/getdocument
		# rattrapper les documents associes a cet article nouveau
		# ils ont un id = 0-id_auteur

			sql_updateq("spip_documents_liens", array("id_objet" => $id_article), array("id_objet = ".(0-$id_auteur),"objet='article'"));
	}

	// Enregistre l'envoi dans la BD
	if ($id_article > 0) $err = articles_set($id_article);

	if (_request('redirect')) {
		$redirect = parametre_url(urldecode(_request('redirect')),
			'id_article', $id_article, '&') . $err;

		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
	else
		return array($id_article,$err);
}

// Appelle toutes les fonctions de modification d'un article
// $err est de la forme '&trad_err=1'
// http://doc.spip.org/@articles_set
function articles_set($id_article, $set=null) {
	$err = '';

	// unifier $texte en cas de texte trop long
	trop_longs_articles();

	$c = array();
	foreach (array(
		'surtitre', 'titre', 'soustitre', 'descriptif',
		'nom_site', 'url_site', 'chapo', 'texte', 'ps'
	) as $champ)
		$c[$champ] = _request($champ,$set);

	if (_request('changer_virtuel',$set) == 'oui') {
		$r = _request('virtuel',$set);
		$c['chapo'] = (strlen($r) ? '='.$r : '');
	}

	include_spip('inc/modifier');
	revision_article($id_article, $c);

	// Modification de statut, changement de rubrique ?
	$c = array();
	foreach (array(
		'date', 'statut', 'id_parent'
	) as $champ)
		$c[$champ] = _request($champ,$set);
	$err .= instituer_article($id_article, $c);

	// Un lien de trad a prendre en compte
	$err .= article_referent($id_article, array('lier_trad' => _request('lier_trad',$set)));

	return $err;
}

// http://doc.spip.org/@insert_article
function insert_article($id_rubrique) {


	// Si id_rubrique vaut 0 ou n'est pas definie, creer l'article
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = sql_fetsel("id_rubrique, id_secteur, lang", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
		$id_rubrique = $row['id_rubrique'];
	} else $row = sql_fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");

	$id_secteur = $row['id_secteur'];
	$lang_rub = $row['lang'];

	// La langue a la creation : si les liens de traduction sont autorises
	// dans les rubriques, on essaie avec la langue de l'auteur,
	// ou a defaut celle de la rubrique
	// Sinon c'est la langue de la rubrique qui est choisie + heritee
	if ($GLOBALS['meta']['multi_articles'] == 'oui') {
		lang_select($GLOBALS['visiteur_session']['lang']);
		if (in_array($GLOBALS['spip_lang'],
		explode(',', $GLOBALS['meta']['langues_multilingue']))) {
			$lang = $GLOBALS['spip_lang'];
			$choisie = 'oui';
		}
	}

	if (!$lang) {
		$choisie = 'non';
		$lang = $lang_rub ? $lang_rub : $GLOBALS['meta']['langue_site'];
	}

	$champs = array(
		'id_rubrique' => $id_rubrique,
		'id_secteur' =>  $id_secteur,
		'statut' =>  'prepa',
		'date' => date('Y-m-d H:i:s'),
		'accepter_forum' =>
			substr($GLOBALS['meta']['forums_publics'],0,3),
		'lang' => $lang,
		'langue_choisie' =>$choisie);

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_articles',
			),
			'data' => $champs
		)
	);

	$id_article = sql_insertq("spip_articles", $champs);

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article
			),
			'data' => $champs
		)
	);

	// controler si le serveur n'a pas renvoye une erreur
	if ($id_article > 0 AND $GLOBALS['visiteur_session']['id_auteur'])
		sql_insertq('spip_auteurs_articles', array('id_auteur' => $GLOBALS['visiteur_session']['id_auteur'], 'id_article' => $id_article));

	return $id_article;
}


// $c est un array ('statut', 'id_parent' = changement de rubrique)
//
// statut et rubrique sont lies, car un admin restreint peut deplacer
// un article publie vers une rubrique qu'il n'administre pas
// http://doc.spip.org/@instituer_article
function instituer_article($id_article, $c, $calcul_rub=true) {

	include_spip('inc/autoriser');
	include_spip('inc/rubriques');
	include_spip('inc/modifier');

	$row = sql_fetsel("statut, date, id_rubrique", "spip_articles", "id_article=$id_article");
	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$date_ancienne = $date = $row['date'];
	$champs = array();

	$d = isset($c['date'])?$c['date']:null;
	$s = isset($c['statut'])?$c['statut']:$statut;

	// cf autorisations dans inc/instituer_article
	if ($s != $statut OR ($d AND $d != $date)) {
		if (autoriser('publierdans', 'rubrique', $id_rubrique))
			$statut = $champs['statut'] = $s;
		else if (autoriser('modifier', 'article', $id_article) AND $s != 'publie')
			$statut = $champs['statut'] = $s;
		else
			spip_log("editer_article $id_article refus " . join(' ', $c));

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		// ou si l'article est deja date dans le futur
		// En cas de proposition d'un article (mais pas depublication), idem
		if ($champs['statut'] == 'publie'
		 OR ($champs['statut'] == 'prop' AND ($d OR !in_array($statut_ancien, array('publie', 'prop'))))
		) {
			if ($d OR strtotime($d=$date)>time())
				$champs['date'] = $date = $d;
			else
				$champs['date'] = $date = date('Y-m-d H:i:s');
		}
	}

	// Verifier que la rubrique demandee existe et est differente
	// de la rubrique actuelle
	if ($id_parent = $c['id_parent']
	AND $id_parent != $id_rubrique
	AND (sql_fetsel('1', "spip_rubriques", "id_rubrique=$id_parent"))) {
		$champs['id_rubrique'] = $id_parent;

		// si l'article etait publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser l'article en statut 'propose'.
		if ($statut == 'publie'
		AND !autoriser('publierdans', 'rubrique', $id_rubrique))
			$champs['statut'] = 'prop';
	}


	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	if (!count($champs)) return;

	// Envoyer les modifs.

	editer_article_heritage($id_article, $id_rubrique, $statut_ancien, $champs, $calcul_rub);

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_article/$id_article'");

	if ($date) {
		$t = strtotime($date);
		$p = @$GLOBALS['meta']['date_prochain_postdate'];
		if ($t > time() AND (!$p OR ($t < $p))) {
			ecrire_meta('date_prochain_postdate', $t);
		}
	}

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerarticle', $id_article,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien, 'date'=>$date)
		);
	}

	return ''; // pas d'erreur
}

// fabrique la requete de modification de l'article, avec champs herites

// http://doc.spip.org/@editer_article_heritage
function editer_article_heritage($id_article, $id_rubrique, $statut, $champs, $cond=true) {

	// Si on deplace l'article
	//  changer aussi son secteur et sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {

		$row_rub = sql_fetsel("id_secteur, lang", "spip_rubriques", "id_rubrique=".sql_quote($champs['id_rubrique']));

		$langue = $row_rub['lang'];
		$champs['id_secteur'] = $row_rub['id_secteur'];
		if (sql_fetsel('1', 'spip_articles', "id_article=$id_article AND langue_choisie<>'oui' AND lang<>" . sql_quote($langue))) {
			$champs['lang'] = $langue;
		}
	}

	if (!$champs) return;

	sql_updateq('spip_articles', $champs, "id_article=$id_article");

	// Changer le statut des rubriques concernees

	if ($cond) {
		include_spip('inc/rubriques');
		$postdate = ($GLOBALS['meta']["post_dates"] == "non" AND isset($champs['date']) AND (strtotime($champs['date']) < time()))?$champs['date']:false;
		calculer_rubriques_if($id_rubrique, $champs, $statut, $postdate);
	}
}

//
// Reunit les textes decoupes parce que trop longs
//

// http://doc.spip.org/@trop_longs_articles
function trop_longs_articles() {
	if (is_array($plus = _request('texte_plus'))) {
		foreach ($plus as $n=>$t) {
			$plus[$n] = preg_replace(",<!--SPIP-->[\n\r]*,","", $t);
		}
		set_request('texte', join('',$plus) . _request('texte'));
	}
}

// Poser un lien de traduction vers un article de reference
// http://doc.spip.org/@article_referent
function article_referent ($id_article, $c) {

	if (!$c = intval($c['lier_trad'])) return;

	// selectionner l'article cible, qui doit etre different de nous-meme,
	// et quitter s'il n'existe pas
	$id_lier = sql_getfetsel('id_trad', 'spip_articles', "id_article=$c AND NOT(id_article=$id_article)");

	if ($id_lier === NULL)
	{
		spip_log("echec lien de trad vers article incorrect ($lier_trad)");
		return '&trad_err=1';
	}

	// $id_lier est le numero du groupe de traduction
	// Si l'article vise n'est pas deja traduit, son identifiant devient
	// le nouvel id_trad de ce nouveau groupe et on l'affecte aux deux
	// articles
	if ($id_lier == 0) {
		sql_updateq("spip_articles", array("id_trad" => $c), "id_article IN ($c, $id_article)");
	}
	// sinon ajouter notre article dans le groupe
	else {
		sql_updateq("spip_articles", array("id_trad" => $id_lier), "id_article = $id_article");
	}

	return ''; // pas d'erreur
}



// obsolete, utiliser revision_article dans inc/modifier
// http://doc.spip.org/@revisions_articles
function revisions_articles ($id_article, $c=false) {
	include_spip('inc/modifier');
	return revision_article($id_article,$c);
}


?>
