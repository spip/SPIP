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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');

// http://doc.spip.org/@action_editer_article_dist
function action_editer_article_dist() {

	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!$id_article = intval($arg)) {
		if ($arg != 'oui') redirige_par_entete('./');
		$id_article = insert_article(_request('id_parent'));
		
		# cf. GROS HACK ecrire/inc/getdocument
		# rattrapper les documents associes a cet article nouveau
		# ils ont un id = 0-id_auteur
		if ($GLOBALS['auteur_session']['id_auteur']>0)
			spip_query("UPDATE spip_documents_articles
			SET id_article = $id_article
			WHERE id_article = ".(0-$GLOBALS['auteur_session']['id_auteur']));
	} 

	// Enregistre l'envoi dans la BD
	$err = articles_set($id_article);

	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_article', $id_article, '&') . ($err ? '&trad_err=1' : '');

	redirige_par_entete($redirect);
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

// http://doc.spip.org/@articles_set
function articles_set($id_article) {

	// si editer_article='oui', on modifie le contenu
	if (_request('editer_article') == 'oui') {
		revisions_articles($id_article);
	}

	// Un lien de trad a prendre en compte
	if ($lier_trad = _request('lier_trad'))
		$err = article_referent($id_article, $lier_trad);

	return $err;
}

// Enregistre une revision d'article
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_articles
function revisions_articles ($id_article, $c=false) {
	global $flag_revisions;
	include_spip('inc/filtres');
	include_spip('inc/rubriques');

	// Ces champs seront pris nom pour nom (_POST[x] => spip_articles.x)
	$champs_normaux = array('surtitre', 'titre', 'soustitre', 'descriptif',
		'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	// unifier $texte en cas de texte trop long (sur methode POST seulement)
	if (!is_array($c)) trop_longs_articles();

	// ne pas accepter de titre vide
	if (_request('titre', $c) === '')
		$c = set_request('titre', _T('ecrire:info_sans_titre'), $c);

	$champs = array();
	foreach ($champs_normaux as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// Changer le statut de l'article ?
	include_spip('inc/auth');
	auth_rubrique($GLOBALS['auteur_session']['id_auteur'], $GLOBALS['auteur_session']['statut']);
	$s = spip_query("SELECT statut, id_rubrique FROM spip_articles WHERE id_article=$id_article");
	$row = spip_fetch_array($s);
	$id_rubrique = $row['id_rubrique'];
	$statut = $row['statut'];

	if (_request('statut', $c)
	AND _request('statut', $c) != $statut) {
		if (acces_rubrique($id_rubrique))
			$statut = $champs['statut'] = _request('statut', $c);
		// else erreur ?
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

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update('articles', $id_article, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_enregistre_contenu',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article
			),
			'data' => $champs
		)
	);

	// Stockage des versions : creer une premiere version si non-existante
	if  ($flag_revisions
	AND $GLOBALS['meta']["articles_versions"]=='oui') {
		include_spip('inc/revisions');
		$query = spip_query("SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1");
		if (!spip_num_rows($query)) {
			$select = join(", ", $champs_normaux);
			$query = spip_query("SELECT $select, date, date_modif FROM spip_articles WHERE id_article=$id_article");
			$champs_originaux = spip_fetch_array($query);
			// Si le titre est vide, c'est qu'on vient de creer l'article
			if ($champs_originaux['titre'] != '') {
				$date_modif = $champs_originaux['date_modif'];
				$date = $champs_originaux['date'];
				unset ($champs_originaux['date_modif']);
				unset ($champs_originaux['date']);
				$id_version = ajouter_version($id_article, $champs_originaux,
					_T('version_initiale'), 0);
				// Inventer une date raisonnable pour la version initiale
				if ($date_modif>'1970-')
					$date_modif = strtotime($date_modif);
				else if ($date>'1970-')
					$date_modif = strtotime($date);
				else
					$date_modif = time()-7200;
				spip_query("UPDATE spip_versions SET date=FROM_UNIXTIME($date_modif) WHERE id_article=$id_article AND id_version=$id_version");
			}
		}
	}

	if (!count($champs)) return;

	$update = array();
	foreach ($champs as $champ => $val)
		$update[] = $champ . '=' . _q($val);

	// Si on ne modifie que le statut, ne pas faire tout le tralala
	// de la gestion de revisions
	$statut_seulement = (count($champs) == 1 AND isset($champs['statut']));
	if (!$statut_seulement)
		$update[] = 'date_modif=NOW()';

	spip_query("UPDATE spip_articles SET ".join(', ',$update)." WHERE id_article=$id_article");

	// Stockage des versions
	if (!$statut_seulement
	AND $GLOBALS['meta']["articles_versions"]=='oui'
	AND $flag_revisions)
		ajouter_version($id_article, $champs, '', $GLOBALS['auteur_session']['id_auteur']);

	// marquer le fait que l'article est travaille par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=articles
	if (!$statut_seulement
	AND $GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		if ($id_article)
			signale_edition ($id_article, $GLOBALS['auteur_session'], 'article');
	}

	// Si on deplace l'article
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


	//
	// Post-modifications
	//

	// Invalider les caches si l'article est publie, ou si on le depublie
	if ($statut == 'publie'
	OR ($statut_ancien=='publie' AND $champ['statut'])) {
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_article/$id_article'");
	}

	// Demander une reindexation de l'article s'il est publie
	if ($statut == 'publie') {
		include_spip('inc/indexation');
		marquer_indexer('spip_articles', $id_article);
	}

	// Recalculer les rubriques (statuts et dates) si l'on deplace
	// un article publie, ou si on le depublie
	if (($statut == 'publie' AND isset($champ['id_rubrique']))
	OR ($statut_ancien=='publie' AND $champ['statut']))
		calculer_rubriques();
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
function article_referent ($id_article, $lier_trad) {

	$lier_trad = intval($lier_trad);

	// selectionner l'article cible, qui doit etre different de nous-meme,
	// et quitter s'il n'existe pas
	if (!$row = spip_fetch_array(
	spip_query("SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad AND NOT(id_article=$id_article)")))
	{
		spip_log("echec lien de trad vers article inexistant ($lier_trad)");
		return 'erreur';
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

	return false; # pas d'erreur
}


?>
