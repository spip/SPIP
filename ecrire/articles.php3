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

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_urlref = lire_meta("articles_urlref");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");
$articles_versions = (lire_meta("articles_versions")=='oui') && $flag_revisions;

if ($id_article==0) {
	if ($new=='oui') {
		// Avec l'Ajax parfois id_rubrique vaut 0... ne pas l'accepter
		if (!$id_rubrique = intval($id_parent)) {
			$s = spip_query("SELECT id_rubrique FROM spip_rubriques
			WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1");
			list($id_rubrique) = spip_fetch_array($s);
		}
		if ($titre=='') $titre = _T('info_sans_titre');

		$langue_new = '';
		$result_lang_rub = spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
		if ($row = spip_fetch_array($result_lang_rub))
			$langue_new = $row["lang"];

		if (!$langue_new) $langue_new = lire_meta('langue_site');
		$langue_choisie_new = 'non';

		$forums_publics = substr(lire_meta('forums_publics'),0,3);

		$id_article = spip_abstract_insert("spip_articles",
			"(id_rubrique, statut, date, accepter_forum, lang, langue_choisie)", 
			"($id_rubrique, 'prepa', NOW(), '$forums_publics', '$langue_new', '$langue_choisie_new')");

		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article = $id_article");
		spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($connect_id_auteur, $id_article)");

		// Modifier le lien de base pour qu'il prenne en compte le nouvel id
		unset($_POST['id_rubrique']);
		$_POST['id_article'] = $id_article;
		$clean_link = new Link();
	} else {
		redirige_par_entete("./index.php3");
	}
}


$clean_link = new Link("articles.php3?id_article=$id_article");



//////////////////////////////////////////////////////
// Determiner les droits d'edition de l'article
//

$query = "SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article";
$result = spip_query($query);
if ($row = spip_fetch_array($result)) {
	$statut_article = $row['statut'];
	$titre_article = $row['titre'];
	$rubrique_article = $row['id_rubrique'];
}
else {
	$statut_article = '';
}

$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
$result_auteur = spip_query($query);

$flag_auteur = (spip_num_rows($result_auteur) > 0);
$flag_editable = (acces_rubrique($rubrique_article)
	OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR $statut_article == 'poubelle')));



//
// Appliquer les modifications
//

$suivi_edito = lire_meta("suivi_edito");

$ok_nouveau_statut = false;


if ($statut_nouv) {
	if (acces_rubrique($rubrique_article)) $ok_nouveau_statut = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok_nouveau_statut = true;
		else if ($statut_nouv == 'prepa' AND $statut_article == 'poubelle')
			$ok_nouveau_statut = true;
	}
	if ($ok_nouveau_statut) {
		$query = "UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article";
		$result = spip_query($query);

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article)
			spip_query("UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article");

		$statut_ancien = $statut_article;	// message pour les traitements de fond (indexation ; envoi mail)
		$statut_article = $statut_nouv;
		$flag_editable = (acces_rubrique($rubrique_article)
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


// Appliquer la modification de langue
if (lire_meta('multi_articles') == 'oui' AND $flag_editable) {
  $row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=" . intval($rubrique_article)));
	$langue_parent = $row['lang'];

	if ($changer_lang) {
		if ($changer_lang != "herit")
			spip_query("UPDATE spip_articles SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_article=$id_article");
		else
			spip_query("UPDATE spip_articles SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_article=$id_article");
	}
}

// Passer les images/docs en "inclus=non"
$query = "SELECT docs.id_document FROM spip_documents AS docs, spip_documents_articles AS lien WHERE lien.id_article=$id_article AND lien.id_document=docs.id_document";
$result = spip_query($query);

while($row=spip_fetch_array($result)){
	$ze_doc[]=$row['id_document'];
}

if (count($ze_doc)>0){
	$ze_docs = join($ze_doc,",");
	spip_query("UPDATE spip_documents SET inclus='non' WHERE id_document IN ($ze_docs)");
}


# modifs de la description d'un des docs joints
if ($flag_editable) maj_documents($id_article, 'article');


//
// Reunit les textes decoupes parce que trop longs
//

$nb_texte = 0;
while ($nb_texte ++ < count($texte_plus)+1){
	$texte_ajout .= ereg_replace("<!--SPIP-->[\n\r]*","",$texte_plus[$nb_texte]);
}
$texte = $texte_ajout . $texte;

// preparer le virtuel

if ($changer_virtuel && $flag_editable) {
	$virtuel = eregi_replace("^http://$", "", trim($virtuel));
	if ($virtuel) $chapo = addslashes(corriger_caracteres("=$virtuel"));
	else $chapo = "";
	$query = "UPDATE spip_articles SET chapo='$chapo' WHERE id_article=$id_article";
	$result = spip_query($query);
}

if (strval($titre)!=='' AND !$ajout_forum AND $flag_editable) {
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');
	$champs_version = array();
	foreach ($champs as $nom_champ) {
		$t = $champs_versions[$nom_champ] = corriger_caracteres($$nom_champ);
		$$nom_champ = addslashes($t);
	}

	// recoller les champs du extra
	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		$add_extra = ", extra = '".addslashes(extra_recup_saisie("articles", $id_secteur))."'";
	} else
		$add_extra = '';

	// Verifier qu'on envoie bien dans une rubrique autorisee
	if ($id_rubrique=intval($id_parent)
	AND ($flag_auteur OR acces_rubrique($id_rubrique))) {
		$change_rubrique = "id_rubrique=$id_rubrique,";
	} else {
		$change_rubrique = "";
	}

	// Stockage des versions : creer une premier version si non-existante
	if ($articles_versions) {
		include("lab_revisions.php");
		if  ($new != 'oui') {
			$query = "SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1";
			if (!spip_num_rows(spip_query($query))) {
				spip_log("version initiale de l'article $id_article");
				$select = join(", ", $champs);
				$query = "SELECT $select FROM spip_articles WHERE id_article=$id_article";
				$champs_originaux = spip_fetch_array(spip_query($query));
				$id_version = ajouter_version($id_article, $champs_originaux, _T('version_initiale'), 0);

				// Remettre une date un peu ancienne pour la version initiale 
				if ($id_version == 1) // test inutile ?
				spip_query("UPDATE spip_versions
				SET date=DATE_SUB(NOW(), INTERVAL 2 HOUR)
				WHERE id_article=$id_article AND id_version=1");
			}
		}
	}

	$query = "UPDATE spip_articles SET surtitre='$surtitre', titre='$titre', soustitre='$soustitre', $change_rubrique descriptif='$descriptif', chapo='$chapo', texte='$texte', ps='$ps', url_site='$url_site', nom_site='$nom_site' $add_extra WHERE id_article=$id_article";
	$result = spip_query($query);
	if ($change_rubrique) propager_les_secteurs();
	calculer_rubriques();

	// Stockage des versions
	if ($articles_versions) {
		ajouter_version($id_article, $champs_versions, '', $connect_id_auteur);
	}

	// Changer la langue heritee
	if ($id_rubrique != $id_rubrique_old) {
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
			if ($langue_new != $langue_old) spip_query("UPDATE spip_articles SET lang = '$langue_new' WHERE id_article = $id_article");
		}
	}

	// afficher le nouveau titre dans la barre de fenetre
	$titre_article = stripslashes($titre);

	// marquer l'article (important pour les articles nouvellement crees)
	spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");
	$id_article_bloque = $id_article;   // message pour inc_presentation
 }

///////////////// Affichage

if (function_exists('affiche_articles'))
  $var_nom = 'affiche_articles';
 else
  $var_nom = 'affiche_articles_dist';

// on pourrait supprimer les arguments issus des meta
// mais l'URL admet de fait une vingtaine de parametres differents

$var_nom($id_article, $ajout_auteur, $articles_mots, $articles_redac, $articles_versions, $change_accepter_forum, $change_petition, $changer_virtuel, $cherche_auteur, $cherche_mot, $debut, $dir_lang, $email_unique, $flag_auteur, $flag_editable, $langue_article, $message, $nom_select, $nouv_auteur, $nouv_mot, $rubrique_article, $site_obli, $site_unique, $supp_auteur, $supp_mot, $texte_petition, $titre_article, $lier_trad);

// Taches lentes

if ($ok_nouveau_statut AND $statut_ancien != $statut_nouv) {
  cron_articles($id_article, $statut_nouv, $statut_ancien);
}

?>
