<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");

$var_f = find_in_path("inc_articles.php");
if ($var_f)
  include($var_f);
 else
   include_ecrire("inc_articles.php");

if ($id_article==0) {
	if ($new!='oui')  redirige_par_entete("./index.php3");
	// Avec l'Ajax parfois id_rubrique vaut 0... ne pas l'accepter
	if (!$id_rubrique = intval($id_parent)) {
		list($id_rubrique) = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
	}
	if ($titre=='') $titre = _T('info_sans_titre');

	$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

	$id_article = spip_abstract_insert("spip_articles",
			"(id_rubrique, statut, date, accepter_forum, lang, langue_choisie)", 
			"($id_rubrique, 'prepa', NOW(), '" .
				substr($GLOBALS['meta']['forums_publics'],0,3)
				. "', '"
				. ($row["lang"] ? $row["lang"] : $GLOBALS['meta']['langue_site'])
				. "', 'non')");
}

$clean_link = new Link("articles.php3?id_article=$id_article");


//////////////////////////////////////////////////////
// Determiner les droits d'edition de l'article
//

if ($row = spip_fetch_array(spip_query("SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article"))) {
	$statut_article = $row['statut'];
	$titre_article = $row['titre'];
	$id_rubrique = $row['id_rubrique'];
	$statut_rubrique = acces_rubrique($id_rubrique);
}
else {
	$statut_article = '';
	$statut_rubrique = false;
	$id_rubrique = '0';
}

$flag_auteur = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur LIMIT 1"));

$flag_editable = ($statut_rubrique
		  OR ($flag_auteur
		      AND ($statut_article == 'prepa'
			   OR $statut_article == 'prop' 
			   OR $statut_article == 'poubelle')));

//
// Appliquer les modifications
//

$ok_nouveau_statut = false;


if ($statut_nouv) {
	if ($statut_rubrique) $ok_nouveau_statut = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok_nouveau_statut = true;
		else if ($statut_nouv == 'prepa' AND $statut_article == 'poubelle')
			$ok_nouveau_statut = true;
	}
	if ($ok_nouveau_statut) {
		spip_query("UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article");

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article)
			spip_query("UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article");

		$statut_ancien = $statut_article;	// message pour les traitements de fond (indexation ; envoi mail)
		$statut_article = $statut_nouv;
		$flag_editable = ($statut_rubrique
			OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop')));
	}
}


// 'depublie' => invalider les caches
if ($ok_nouveau_statut AND $statut_ancien == 'publie' AND $statut_nouv != $statut_ancien AND $invalider_caches) {
	include_ecrire ("inc_invalideur.php3");
	suivre_invalideur("id='id_article/$id_article'");
}

if ($jour && $flag_editable) {
	$date = format_mysql_date($annee, $mois, $jour, $heure, $minute);
	spip_query("UPDATE spip_articles SET date='$date'
		WHERE id_article=$id_article");
	calculer_rubriques();
}

if ($jour_redac && $flag_editable) {
	if ($annee_redac<>'' AND $annee_redac < 1001) $annee_redac += 9000;

	if ($avec_redac == 'non')
		$date_redac = format_mysql_date();
	else
		$date_redac = format_mysql_date(
			$annee_redac, $mois_redac, $jour_redac,
			$heure_redac, $minute_redac);

	spip_query("UPDATE spip_articles SET date_redac='$date_redac'
		WHERE id_article=$id_article");
}


if ($flag_editable) modif_langue_articles($id_article, $id_rubrique, $changer_lang);

inclus_non_articles($id_article);

# modifs de la description d'un des docs joints
if ($flag_editable) maj_documents($id_article, 'article');

// preparer le virtuel

if ($changer_virtuel && $flag_editable) {
	$virtuel = eregi_replace("^http://$", "", trim($virtuel));
	if ($virtuel) $chapo = addslashes(corriger_caracteres("=$virtuel"));
	else $chapo = "";
	spip_query("UPDATE spip_articles SET chapo='$chapo' WHERE id_article=$id_article");
}

if (strval($titre)!=='' AND !$ajout_forum AND $flag_editable) {

	$champs = array(
			'surtitre' => corriger_caracteres($surtitre),
			'titre' => ($titre_article=corriger_caracteres($titre)),
			'soustitre' => corriger_caracteres($soustitre),
			'descriptif' => corriger_caracteres($descriptif),
			'nom_site' => corriger_caracteres($nom_site),
			'url_site' => corriger_caracteres($url_site),
			'chapo' => corriger_caracteres($chapo),
			'texte' => corriger_caracteres(trop_longs_articles($texte_plus) . $texte),
			'ps' => corriger_caracteres($ps))  ;

	revisions_articles ($id_article, $champs_extra, $id_secteur, $id_parent, $flag_auteur, $new, $champs, $id_rubrique_old);
	$id_article_bloque = $id_article;   // message pour inc_presentation

 }

///////////////// Affichage

if (function_exists('affiche_articles'))
  $var_nom = 'affiche_articles';
 else
  $var_nom = 'affiche_articles_dist';


// on pourrait supprimer les arguments issus des meta
// mais l'URL admet de fait une vingtaine de parametres differents

$var_nom($id_article, $ajout_auteur, $change_accepter_forum, $change_petition, $changer_virtuel, $cherche_auteur, $cherche_mot, $debut, $email_unique, $flag_auteur, $flag_editable, $langue_article, $message, $nom_select, $nouv_auteur, $nouv_mot, $id_rubrique, $site_obli, $site_unique, $supp_auteur, $supp_mot, $texte_petition, $titre_article, $lier_trad);

// Taches lentes

if ($ok_nouveau_statut AND $statut_ancien != $statut_nouv) {
  cron_articles($id_article, $statut_nouv, $statut_ancien);
}

?>
