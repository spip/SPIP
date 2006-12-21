<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@action_editer_article_dist
function action_editer_article_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// si id_article n'est pas un nombre, c'est une creation 
	// mais on verifie qu'on a toutes les données qu'il faut.
	if (!$id_article = intval($arg)) {
		$id_parent = _request('id_parent');
		$id_auteur = $GLOBALS['auteur_session']['id_auteur'];
		if (!($id_parent AND $id_auteur)) redirige_par_entete('./');
		$id_article = insert_article($id_parent);
		
		# cf. GROS HACK ecrire/inc/getdocument
		# rattrapper les documents associes a cet article nouveau
		# ils ont un id = 0-id_auteur

		spip_query("UPDATE spip_documents_articles SET id_article = $id_article WHERE id_article = ".(0-$id_auteur));
	} 

	// Enregistre l'envoi dans la BD
	$err = articles_set($id_article);

	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_article', $id_article, '&') . $err;

	redirige_par_entete($redirect);
}

// Appelle toutes les fonctions de modification d'un article
// $err est de la forme '&trad_err=1'
// http://doc.spip.org/@articles_set
function articles_set($id_article, $c=false) {
	$err = '';

	// Edition du contenu ?
	$err .= revisions_articles($id_article, $c);

	// Modification de statut, changement de rubrique ?
	$err .= instituer_article($id_article, $c);

	// Un lien de trad a prendre en compte
	$err .= article_referent($id_article, $c);

	return $err;
}

// http://doc.spip.org/@insert_article
function insert_article($id_rubrique) {

	include_spip('base/abstract_sql');
	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer l'article
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
		$id_rubrique = $row['id_rubrique'];
	}

	// La langue a la creation : si les liens de traduction sont autorises
	// dans les rubriques, on essaie avec la langue de l'auteur,
	// ou a defaut celle de la rubrique
	// Sinon c'est la langue de la rubrique qui est choisie + heritee
	if ($GLOBALS['meta']['multi_articles'] == 'oui') {
		lang_select($GLOBALS['auteur_session']['lang']);
		if (in_array($GLOBALS['spip_lang'],
		explode(',', $GLOBALS['meta']['langues_multilingue']))) {
			$lang = $GLOBALS['spip_lang'];
			$choisie = 'oui';
		}
	}

	$row = spip_fetch_array(spip_query("SELECT lang, id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

	$id_secteur = $row['id_secteur'];

	if (!$lang) {
		$lang = $GLOBALS['meta']['langue_site'];
		$choisie = 'non';
		$lang = $row['lang'];
	}

	$id_article = spip_abstract_insert("spip_articles",
		"(id_rubrique, id_secteur, statut, date, accepter_forum, lang, langue_choisie)",
		"($id_rubrique, $id_secteur, 'prepa', NOW(), '"
			. substr($GLOBALS['meta']['forums_publics'],0,3)
			. "', '$lang', '$choisie')");
	spip_abstract_insert('spip_auteurs_articles', "(id_auteur,id_article)", "('" . $GLOBALS['auteur_session']['id_auteur'] . "','$id_article')");

	return $id_article;
}

// Enregistre une revision d'article
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_articles
function revisions_articles ($id_article, $c=false) {
	include_spip('inc/modifier');

	// unifier $texte en cas de texte trop long (sur methode POST seulement)
	if (!is_array($c)) trop_longs_articles();

	// Si l'article est publie, invalider les caches et demander sa reindexation
	$t = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_articles WHERE id_article=$id_article"));
	if ($t['statut'] == 'publie') {
		$invalideur = "id='id_article/$id_article'";
		$indexation = true;
	}

	$r = modifier_contenu('article', $id_article,
		array(
			'champs' => array(
				'surtitre', 'titre', 'soustitre', 'descriptif',
				'nom_site', 'url_site', 'chapo', 'texte', 'ps',
				'url_propre'
			),
			'nonvide' => array('titre' => _T('info_sans_titre')),
			'invalideur' => $invalideur,
			'indexation' => $indexation
		),
		$c);

	if ($r) {
		spip_query("UPDATE spip_articles SET date_modif=NOW() WHERE id_article="._q($id_article));
	}

	return ''; // pas d'erreur
}


// $c est un array ('statut', 'id_rubrique')
//
// statut et rubrique sont lies, car un admin restreint peut deplacer
// un article publie vers une rubrique qu'il n'administre pas
// http://doc.spip.org/@instituer_article
function instituer_article($id_article, $c) {

	include_spip('inc/autoriser');
	include_spip('inc/rubriques');

	$s = spip_query("SELECT statut, id_rubrique FROM spip_articles WHERE id_article=$id_article");
	$row = spip_fetch_array($s);
	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$champs = array();

	$s = _request('statut', $c);
	if ($s AND _request('statut', $c) != $statut) {
		if (autoriser('publierdans', 'rubrique', $id_rubrique))
			$statut = $champs['statut'] = $s;
		else if (autoriser('modifier', 'article', $id_article) AND $s != 'publie')
			$statut = $champs['statut'] = $s;
		else
			spip_log("editer_article $id_article refus " . join(' ', $c));

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		if ($champs['statut'] == 'publie') {
			if ($d = _request('date', $c))
				$champs['date'] = $d;
			else
				$champs['date'] = date('Y-m-d H:i:s');
		}
	}

	// Verifier que la rubrique demandee existe et est differente
	// de la rubrique actuelle
	if ($id_parent = _request('id_parent', $c)
	AND $id_parent != $id_rubrique
	AND (spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_rubrique=$id_parent")))) {
		$champs['id_rubrique'] = $id_parent;

		// si l'article etait publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser l'article en statut 'propose'.
		if ($statut == 'publie') {
			if ($GLOBALS['auteur_session']['statut'] != '0minirezo')
				$champs['statut'] = 'prop';
			else {
				include_spip('inc/auth');
				$r = auth_rubrique($GLOBALS['auteur_session']['id_auteur'], $GLOBALS['auteur_session']['statut']);
				if (is_array($r) AND !$r[$id_rubrique])
					$champs['statut'] = 'prop';
			}
		}
	}


	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article
			),
			'data' => $champs
		)
	);

	if (!count($champs)) return;

	// Creer la requete SQL
	$update = array();
	foreach ($champs as $champ => $val)
		$update[] = $champ . '=' . _q($val);

	spip_query("UPDATE spip_articles SET ".join(', ',$update)." WHERE id_article=$id_article");


	// Si on a deplace l'article
	// - propager les secteurs
	// - changer sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {
		propager_les_secteurs();

		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
			if ($langue_new != $langue_old)
				spip_query("UPDATE spip_articles SET lang = '$langue_new' WHERE id_article = $id_article");
		}
	}

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_article/$id_article'");

	// Recalculer les rubriques (statuts et dates) si l'on deplace
	// un article publie, ou si on le depublie
	if (($statut == 'publie' AND isset($champ['id_rubrique']))
	OR ($statut_ancien=='publie' AND $champ['statut']))
		calculer_rubriques();

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerarticle', $id_article,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien)
		);
	}

	return ''; // pas d'erreur
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

	if (!$lier_trad = intval(_request('lier_trad', $c))) return;

	// selectionner l'article cible, qui doit etre different de nous-meme,
	// et quitter s'il n'existe pas
	if (!$row = spip_fetch_array(
	spip_query("SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad AND NOT(id_article=$id_article)")))
	{
		spip_log("echec lien de trad vers article inexistant ($lier_trad)");
		return '&trad_err=1';
	}

	// $id_lier est le numero du groupe de traduction
	$id_lier = $row['id_trad'];

	// Si l'article vise n'est pas deja traduit, son identifiant devient
	// le nouvel id_trad de ce nouveau groupe et on l'affecte aux deux
	// articles
	if ($id_lier == 0) {
		spip_query("UPDATE spip_articles SET id_trad = $lier_trad WHERE id_article IN ($lier_trad, $id_article)");
	}
	// sinon on ajouter notre article dans le groupe
	else {
		spip_query("UPDATE spip_articles SET id_trad = $id_lier WHERE id_article = $id_article");
	}

	return ''; // pas d'erreur
}


?>
