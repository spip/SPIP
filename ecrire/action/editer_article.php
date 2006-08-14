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

function action_editer_article_dist() {


	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$lier_trad = _request('lier_trad');
	$id_parent =_request('id_parent');

	if (!$id_article = intval($arg)) {
		if ($arg != 'oui') redirige_par_entete('./');
	        $id_article = insert_article($id_parent);
	} 
	  
	$err = false;

	// Enregistre l'envoi dans la BD et positionne $err si pb

	articles_set($id_article, $id_parent, $lier_trad, $arg=='oui');

	// id_article_bloque,  globale dans inc/presentation 

	$redirect = urldecode(_request('redirect'))
		. "&id_article=$id_article&id_article_bloque=$id_article"
		. ($GLOBALS['err'] ? '&trad_err=1' : '');

	redirige_par_entete($redirect);
}

function insert_article($id_parent)
{
	include_spip('base/abstract_sql');
	$id_auteur =  _request('id_auteur');
	$id_parent =  _request('id_parent');

	// Avec l'Ajax parfois id_rubrique vaut 0... ne pas l'accepter
	if (!$id_rubrique = intval($id_parent)) {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
		$id_rubrique = $row['id_rubrique'];
	}

	$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

	$id_article = spip_abstract_insert("spip_articles",
			"(id_rubrique, statut, date, accepter_forum, lang, langue_choisie)", 
			"($id_rubrique, 'prepa', NOW(), '" .
				substr($GLOBALS['meta']['forums_publics'],0,3)
				. "', '"
				. ($row["lang"] ? $row["lang"] : $GLOBALS['meta']['langue_site'])
				. "', 'non')");
	spip_abstract_insert('spip_auteurs_articles', "(id_auteur,id_article)", "('$id_auteur','$id_article')");
	return $id_article;
}

function articles_set($id_article, $id_rubrique, $lier_trad, $new)
{
	include_spip('inc/filtres');
	include_spip('inc/rubriques');

	$row = spip_fetch_array(spip_query("SELECT id_trad FROM spip_articles WHERE id_article=$id_article"));

	$id_trad = (!$lier_trad) ? 0 : article_referent ($id_article, $row['id_trad'], $lier_trad);

	if (_request('titre')) // retour de articles_edit.php
	  revisions_articles($id_article, $id_rubrique, $id_trad, $new);
	else // retour articles.php
		spip_query("UPDATE spip_articles SET id_trad = $id_trad WHERE id_article = $id_article");
}

function revisions_articles ($id_article, $id_rubrique, $id_trad) {
{
	global $flag_revisions, $champs_extra;

	$id_auteur =  _request('id_auteur');
	$texte = trop_longs_articles(_request('texte_plus')) . _request('texte');
	if (!strlen($titre_article=corriger_caracteres(_request('titre'))))
		$titre_article = _T('info_sans_titre');

	$champs = array(
		'surtitre' => corriger_caracteres(_request('surtitre')),
		'titre' => $titre_article,
		'soustitre' => corriger_caracteres(_request('soustitre')),
		'descriptif' => corriger_caracteres(_request('descriptif')),
		'nom_site' => corriger_caracteres(_request('nom_site')),
		'url_site' => corriger_caracteres(_request('url_site')),
		'chapo' => corriger_caracteres( _request('chapo')),
		'texte' => corriger_caracteres($texte),
		'ps' => corriger_caracteres(_request('ps')))  ;

	// Stockage des versions : creer une premier version si non-existante
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		include_spip('inc/revisions');
		if  (!$new) {
			$query = spip_query("SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1");
			if (!spip_num_rows($query)) {
				$select = join(", ", array_keys($champs));
				$query = spip_query("SELECT $select FROM spip_articles WHERE id_article=$id_article");
				$champs_originaux = spip_fetch_array($query);
				$id_version = ajouter_version($id_article, $champs_originaux, _T('version_initiale'), 0);

				// Remettre une date un peu ancienne pour la version initiale 
				if ($id_version == 1) // test inutile ?
				spip_query("UPDATE spip_versions SET date=DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE id_article=$id_article AND id_version=1");
			}
		}
	}

	if ($champs_extra) {
		include_spip('inc/extra');
		$champs_extra = extra_recup_saisie("articles", _request('id_secteur'));
	}

	spip_query("UPDATE spip_articles SET id_trad = $id_trad WHERE id_article = $id_article");


	spip_query("UPDATE spip_articles SET id_rubrique=$id_rubrique, id_trad=$id_trad, surtitre=" . spip_abstract_quote($champs['surtitre']) . ", titre=" . spip_abstract_quote($champs['titre']) . ", soustitre=" . spip_abstract_quote($champs['soustitre']) . ", descriptif=" . spip_abstract_quote($champs['descriptif']) . ", chapo=" . spip_abstract_quote($champs['chapo']) . ", texte=" . spip_abstract_quote($champs['texte']) . ", ps=" . spip_abstract_quote($champs['ps']) . ", url_site=" . spip_abstract_quote($champs['url_site']) . ", nom_site=" . spip_abstract_quote($champs['nom_site']) . ", date_modif=NOW() " . ($champs_extra ? (", extra = " . spip_abstract_quote($champs_extra)) : '') . " WHERE id_article=$id_article");

	// Stockage des versions
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		ajouter_version($id_article, $champs, '', $id_auteur);
	}

	// marquer le fait que l'article est travaille par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=articles
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		if ($id_article)
			signale_edition ($id_article, $id_auteur, 'article');
	}


	// Changer la langue heritee
	if ($id_rubrique != _request('id_rubrique_old')) {
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

	calculer_rubriques();
 }
}


//
// Reunit les textes decoupes parce que trop longs
//

function trop_longs_articles($texte_plus)
{
	$nb_texte = 0;
	while ($nb_texte ++ < count($texte_plus)+1){
		$texte_ajout .= ereg_replace("<!--SPIP-->[\n\r]*","",
					     $texte_plus[$nb_texte]);
	}
	return $texte_ajout;
}

function article_referent ($id_article, $id_trad, $lier_trad)
{ 
	global $err; // pour avertir l'appelant

	$row = spip_fetch_array(spip_query("SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad"));

	$id_lier = $row['id_trad'];

	spip_log("$id_article, $id_trad, $lier_trad, $id_lier");
// Si l'article vise n'a pas deja de traduction, creer nouveau id_trad
	if ($id_lier == 0) {
			$nouveau_trad = $lier_trad;
			spip_query("UPDATE spip_articles SET id_trad = $lier_trad WHERE id_article = $lier_trad");
	} else {
	  // insuffisant pour prevenir les traductions redondantes a mon avis
		if ($id_lier == $id_trad) $err = true;
		$nouveau_trad = $id_lier;
		spip_query("UPDATE spip_articles SET id_trad = $id_lier WHERE id_trad = $id_lier");
	}

	if ($id_trad > 0)
		  spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_trad");

	return $nouveau_trad;
}

?>
