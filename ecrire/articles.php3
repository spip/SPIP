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

include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_abstract_sql.php3");

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
		$id_rubrique = intval($id_rubrique);
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

// 'publie' => reindexer
if ($ok_nouveau_statut AND $statut_nouv == 'publie' AND $statut_nouv != $statut_ancien AND (lire_meta('activer_moteur') == 'oui')) {
	include_ecrire ("inc_index.php3");
	indexer_article($id_article);
}

// 'dŽpublie' => invalider les caches
if ($ok_nouveau_statut AND $statut_ancien == 'publie' AND $statut_nouv != $statut_ancien AND $invalider_caches) {
	include_ecrire ("inc_invalideur.php3");
	suivre_invalideur("id='id_article/$id_article'");
}

if ($jour && $flag_editable) {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_articles SET date='$annee-$mois-$jour' WHERE id_article=$id_article";
	$result = spip_query($query);
	calculer_rubriques();
}

if ($jour_redac && $flag_editable) {
	if ($annee_redac<>'' AND $annee_redac < 1001) $annee_redac += 9000;

	if ($mois_redac == "00") $jour_redac = "00";

	if ($avec_redac=="non"){
		$annee_redac = '0000';
		$mois_redac = '00';
		$jour_redac = '00';
	}

	$query = "UPDATE spip_articles SET date_redac='$annee_redac-$mois_redac-$jour_redac' WHERE id_article=$id_article";
	$result = spip_query($query);
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
while ($nb_texte ++ < 100){		// 100 pour eviter une improbable boucle infinie
	$varname = "texte$nb_texte";
	$texte_plus = $$varname;	// double $ pour obtenir $texte1, $texte2...
	if ($texte_plus){
		$texte_plus = ereg_replace("<!--SPIP-->[\n\r]*","",$texte_plus);
		$texte_ajout .= $texte_plus;
	} else {
		break;
	}
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

if ($titre && !$ajout_forum && $flag_editable) {
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
	if ($flag_auteur OR acces_rubrique($id_rubrique)) {
		$change_rubrique = "id_rubrique=\"$id_rubrique\",";
	} else {
		$change_rubrique = "";
	}

	// Stockage des versions : creer une premier version si non-existante
	if ($articles_versions) {
		include("lab_revisions.php");
		if  ($new != 'oui') {
			$query = "SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 0,1";
			if (!spip_num_rows(spip_query($query))) {
				spip_log("version initiale de l'article $id_article");
				$select = join(", ", $champs);
				$query = "SELECT $select FROM spip_articles WHERE id_article=$id_article";
				$champs_originaux = spip_fetch_array(spip_query($query));
				$id_version = ajouter_version($id_article, $champs_originaux, _T('version_initiale'));

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
	calculer_rubriques();

	// invalider et reindexer
	if ($statut_article == 'publie') {
		if ($invalider_caches) {
			include_ecrire ("inc_invalideur.php3");
			suivre_invalideur("id='id_article/$id_article'");
		}
		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
			indexer_article($id_article);
		}
	}

	// Stockage des versions
	if ($articles_versions) {
		ajouter_version($id_article, $champs_versions);
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



//
// Suivi forums publics
//

// fonction dupliquee dans inc-forum.php3
function get_forums_publics($id_article=0) {
	$forums_publics = lire_meta("forums_publics");
	if ($id_article) {
		$query = "SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article";
		$res = spip_query($query);
		if ($obj = spip_fetch_array($res))
			$forums_publics = $obj['accepter_forum'];
	} else { // dans ce contexte, inutile
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}
	return $forums_publics;
}


//
// Lire l'article
//

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_article = $row["id_article"];
	$surtitre = $row["surtitre"];
	$titre = $row["titre"];
	$soustitre = $row["soustitre"];
	$id_rubrique = $row["id_rubrique"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$id_version = $row["id_version"];
}

// pour l'affichage du virtuel
unset($virtuel);
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_redac, $regs)) {
        $mois_redac = $regs[2];
        $jour_redac = $regs[3];
        $annee_redac = $regs[1];
        if ($annee_redac > 4000) $annee_redac -= 9000;
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
        $mois = $regs[2];
        $jour = $regs[3];
        $annee = $regs[1];
}



debut_page("&laquo; $titre_article &raquo;", "documents", "articles");

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();



//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();

debut_boite_info();

echo "<div align='center'>\n";


echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('info_numero_article')."</b></font>\n";
echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_article</b></font>\n";

voir_en_ligne ('article', $id_article, $statut_article);

if ($connect_statut=='0minirezo' AND acces_rubrique($id_rubrique)) {
	$query = "SELECT count(*) AS count FROM spip_forum WHERE id_article=$id_article AND statut IN ('publie', 'off', 'prop')";
	if ($row = spip_fetch_array(spip_query($query))) {
		$nb_forums = $row['count'];
		if ($nb_forums) {
			icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)),
				"articles_forum.php3?id_article=$id_article", "suivi-forum-24.gif", "");
		}
	}
}


$activer_statistiques = lire_meta("activer_statistiques");

if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $activer_statistiques != "non" AND $options == "avancees"){
	icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), "statistiques_visites.php3?id_article=$id_article", "statistiques-24.gif","rien.gif");
}

if ($articles_versions AND $id_version>1 AND $options == "avancees") {
	icone_horizontale(_T('info_historique_lien'), "articles_versions.php3?id_article=$id_article", "historique-24.gif", "rien.gif");
}

	// Correction orthographique
	if (lire_meta('articles_ortho') == 'oui') {
		$js_ortho = "onclick=\"window.open(this.href, 'spip_ortho', 'scrollbars=yes, resizable=yes, width=740, height=580'); return false;\"";
		icone_horizontale(_T('ortho_verifier'), "articles_ortho.php?id_article=$id_article", "ortho-24.gif", "rien.gif", 'echo', $js_ortho);
	}

echo "</div>\n";

fin_boite_info();


// Logos de l'article

if ($id_article AND $flag_editable)
	afficher_boite_logo('art', 'id_article', $id_article,
	_T('logo_article').aide ("logoart"), _T('logo_survol'));


//
// Boites de configuration avancee
//

if ($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable) {
	echo "<p>";
	debut_cadre_relief("forum-interne-24.gif");
	$visible = $change_accepter_forum || $change_petition;


	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("forumpetition");
	else
		echo bouton_block_invisible("forumpetition");
	echo _T('bouton_forum_petition');
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("forumpetition");
	else
		echo debut_block_invisible("forumpetition");


	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'>\n";

	// Forums et petitions

	$forums_publics = get_forums_publics($id_article);

	if (isset($change_accepter_forum)
	AND $change_accepter_forum <> $forums_publics) {
		$query_forum = "UPDATE spip_articles
			SET accepter_forum='$change_accepter_forum'
			WHERE id_article='$id_article'";
		$result_forum = spip_query($query_forum);
		$forums_publics = $change_accepter_forum;
		if ($change_accepter_forum == 'abo') {
			ecrire_meta('accepter_visiteurs', 'oui');
			ecrire_metas();
		}
		include_ecrire('inc_invalideur.php3');
		suivre_invalideur("id='id_forum/a$id_article'");
	}

	echo "\n<form action='articles.php3' method='get'>";

	echo "\n<input type='hidden' name='id_article' value='$id_article'>";
	echo "<br>"._T('info_fonctionnement_forum')."\n";
	if ($forums_publics == "pos") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos' checked>";
		echo "<B><label for='accepterforumpos'> "._T('bouton_radio_modere_posteriori')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos'>";
		echo "<label for='accepterforumpos'> "._T('bouton_radio_modere_posteriori')."</label>";
	}
	if ($forums_publics == "pri") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri' checked>";
		echo "<B><label for='accepterforumpri'> "._T('bouton_radio_modere_priori')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri'>";
		echo "<label for='accepterforumpri'> "._T('bouton_radio_modere_priori')."</label>";
	}
	if ($forums_publics == "abo") {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo' checked>";
		echo "<B><label for='accepterforumabo'> "._T('bouton_radio_modere_abonnement')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo'>";
		echo "<label for='accepterforumabo'> "._T('bouton_radio_modere_abonnement')."</label>";
	}
	if ($forums_publics == "non") {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon' checked>";
		echo "<B><label for='accepterforumnon'> "._T('info_pas_de_forum')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon'>";
		echo "<label for='accepterforumnon'> "._T('info_pas_de_forum')."</label>";
	}

	echo "<div align='$spip_lang_right'><input type='submit' name='Changer' class='fondo' value='"._T('bouton_changer')."' STYLE='font-size:10px'></div>\n";
	echo "</form>";

	echo "<br>";

	
	// Petitions

	if ($change_petition) {
		if ($change_petition == "on") {
			if (!$email_unique) $email_unique = "non";
			if (!$site_obli) $site_obli = "non";
			if (!$site_unique) $site_unique = "non";
			if (!$message) $message = "non";

			$texte_petition = addslashes($texte_petition);

			$query_pet = "REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) ".
				"VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', '$texte_petition')";
			$result_pet = spip_query($query_pet);
		}
		else if ($change_petition == "off") {
			$query_pet = "DELETE FROM spip_petitions WHERE id_article=$id_article";
			$result_pet = spip_query($query_pet);
		}
	}

	$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
	$result_petition = spip_query($query_petition);
	$petition = (spip_num_rows($result_petition) > 0);

	while ($row = spip_fetch_array($result_petition)) {
		$id_rubrique=$row["id_article"];
		$email_unique=$row["email_unique"];
		$site_obli=$row["site_obli"];
		$site_unique=$row["site_unique"];
		$message=$row["message"];
		$texte_petition=$row["texte"];
	}

	echo "\n<FORM ACTION='articles.php3' METHOD='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($petition){
		echo "<input type='radio' name='change_petition' value='on' id='petitionon' checked>";
		echo "<B><label for='petitionon'>"._T('bouton_radio_petition_activee')."</label></B>";
		$query_signatures = "SELECT COUNT(*) AS nb FROM spip_signatures WHERE id_article=$id_article";
		$result = spip_fetch_array(spip_query($query_signatures));
		if ($result['nb'] > 0) {
			echo "<p><font size=1><a href='controle_petition.php3?id_article=$id_article'>".$result['nb']." "._T('info_signatures')."</a></font>\n";
		}

		echo "<p>";
		if ($email_unique=="oui")
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><BR>";
		if ($site_obli=="oui")
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><BR>";
		if ($site_unique=="oui")
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><BR>";
		if ($message=="oui")
			echo "<input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		echo "<P>"._T('texte_descriptif_petition')."&nbsp;:<BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo $texte_petition;
		echo "</TEXTAREA><P>\n";

	}
	else {
		echo "<input type='radio' name='change_petition' value='on' id='petitionon'>";
		echo "<label for='petitionon'>"._T('bouton_radio_activer_petition')."</label>";
	}
	if (!$petition){
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff' checked>";
		echo "<B><label for='petitionoff'>"._T('bouton_radio_pas_petition')."</label></B>";
	}else{
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff'>";
		echo "<label for='petitionoff'>"._T('bouton_radio_supprimer_petition')."</label>";
	}

	echo "<P align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'>";
	echo "</FORM>";

	echo "</font>";
	echo fin_block();

	fin_cadre_relief();



	// Redirection (article virtuel)
	debut_cadre_relief("site-24.gif");
	$visible = ($changer_virtuel || $virtuel);

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("redirection");
	else
		echo bouton_block_invisible("redirection");
	echo _T('bouton_redirection');
	echo aide ("artvirt");
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("redirection");
	else
		echo debut_block_invisible("redirection");

	echo "<form action='articles.php3?id_article=$id_article' method='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";
	echo "\n<INPUT TYPE='hidden' NAME='changer_virtuel' VALUE='oui'>";
	$virtuelhttp = ($virtuel ? "" : "http://");

	echo "<INPUT TYPE='text' NAME='virtuel' CLASS='formo' style='font-size:9px;' VALUE=\"$virtuelhttp$virtuel\" SIZE='40'><br>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
	echo "(<b>"._T('texte_article_virtuel')."&nbsp;:</b> "._T('texte_reference_mais_redirige').")";
	echo "</font>";
	echo "<div align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'></div>";
	echo "</form>";
	echo fin_block();

	fin_cadre_relief();
}


//
// Articles dans la meme rubrique
//

		$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.id_rubrique='$id_rubrique' AND (articles.statut = 'publie' OR articles.statut = 'prop') AND articles.id_article != '$id_article' ".
			" ORDER BY articles.date DESC LIMIT 0,30");
		if (spip_num_rows($vos_articles) > 0) {
			echo "<div>&nbsp;</div>";
			echo "<div class='bandeau_rubriques' style='z-index: 1;'>";
			bandeau_titre_boite2(_T('info_meme_rubrique'), "article-24.gif");
			echo "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$ze_article = $row['id_article'];
				$ze_titre = typo($row['titre']);
				$ze_statut = $row['statut'];
				
				if ($options == "avancees") {
					$numero = "<div class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>"._T('info_numero_abbreviation')."$ze_article</b></div>";
				}
				echo "<a class='$ze_statut' style='font-size: 10px;' href='articles.php3?id_article=$ze_article'>$numero$ze_titre</a>";
			}
			echo "</div>";
			echo "</div>";
		}


//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//

debut_droite();


changer_typo('','article'.$id_article);


function afficher_mois($mois){
	echo mySel("00",$mois,_T('mois_non_connu'));
	echo mySel("01",$mois,_T('date_mois_1'));
	echo mySel("02",$mois,_T('date_mois_2'));
	echo mySel("03",$mois,_T('date_mois_3'));
	echo mySel("04",$mois,_T('date_mois_4'));
	echo mySel("05",$mois,_T('date_mois_5'));
	echo mySel("06",$mois,_T('date_mois_6'));
	echo mySel("07",$mois,_T('date_mois_7'));
	echo mySel("08",$mois,_T('date_mois_8'));
	echo mySel("09",$mois,_T('date_mois_9'));
	echo mySel("10",$mois,_T('date_mois_10'));
	echo mySel("11",$mois,_T('date_mois_11'));
	echo mySel("12",$mois,_T('date_mois_12'));
}

function afficher_annee($annee){
	// Cette ligne permettrait de faire des articles sans date de publication
	// echo mySel("0000",$annee,"n.c.");

	if($annee<1996 AND $annee <> 0){
		echo mySel($annee,$annee,$annee);
	}
	for($i=1996;$i<date(Y)+2;$i++){
		echo mySel($i,$annee,$i);
	}
}

function afficher_jour($jour){
	echo mySel("00",$jour,_T('jour_non_connu_nc'));
	for($i=1;$i<32;$i++){
		$aff = _T('date_jnum'.$i);
		if ($i<10)
			$aff="&nbsp;".$aff;
		echo mySel($i,$jour,$aff);
	}
}


debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

$logo_statut = "puce-".puce_statut($statut_article).".gif";

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
if ($surtitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
	echo typo($surtitre);
	echo "</b></font></span>\n";
}
	gros_titre($titre, $logo_statut);

if ($soustitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
	echo typo($soustitre);
	echo "</b></font></span>\n";
}


if ($descriptif OR $url_site OR $nom_site) {
	echo "<p><div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo propre($texte_case);
	echo "</font>";
	echo "</div>";
}


if ($statut_article == 'prop') {
	echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='red'><B>"._T('text_article_propose_publication')."</B></FONT></P>";
}


echo "</td>";


if ($flag_editable) {
  echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
	echo "<td align='center'>";
	$flag_modif = false;

	// Recuperer les donnees de l'article
	if (lire_meta('articles_modif') != 'non') {
		$query = "SELECT auteur_modif, UNIX_TIMESTAMP(date_modif) AS modification, UNIX_TIMESTAMP(NOW()) AS maintenant FROM spip_articles WHERE id_article='$id_article'";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$auteur_modif = $row["auteur_modif"];
			$modification = $row["modification"];
			$maintenant = $row["maintenant"];

			$date_diff = floor(($maintenant - $modification)/60);

			if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
				$flag_modif = true;
				$query_auteur = "SELECT nom FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
				$result_auteur = spip_query($query_auteur);
				if ($row_auteur = spip_fetch_array($result_auteur)) {
					$nom_auteur_modif = typo($row_auteur["nom"]);
				}
			}
		}
	}
	if ($flag_modif) {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
		echo "<font face='arial,helvetica,sans-serif' size='2'>"._T('avis_article_modifie', array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff))."</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
	}

	echo "</td>";
}
echo "</tr></table>\n";


echo "<div>&nbsp;</div>";
echo "<div class='serif' align='$spip_lang_left'>";

//
// Affichage date redac et date publi
//

if ($flag_editable AND $options == 'avancees') {
	debut_cadre_couleur();

	echo "<FORM ACTION='articles.php3' METHOD='GET' style='margin: 0px; padding: 0px;'>";
	echo "<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($statut_article == 'publie') {

		echo "<div><b>";
		echo bouton_block_invisible("datepub");
		echo "<span class='verdana1'>"._T('texte_date_publication_article').'</span> ';
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";

		echo debut_block_invisible("datepub");
		echo "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>";
		echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl' onChange=\"setvisibility('valider_date', 'visible')\">";
		afficher_jour($jour);
		echo "</SELECT> ";
		echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl' onChange=\"setvisibility('valider_date', 'visible')\">";
		afficher_mois($mois);
		echo "</SELECT> ";
		echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl' onChange=\"setvisibility('valider_date', 'visible')\">";
		afficher_annee($annee);
		echo "</SELECT>";
		echo "<span class='visible_au_chargement' id='valider_date'>";
		echo " &nbsp; <INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'>";
		echo "</span>";
		echo "</div>";
		echo fin_block();
	}
	else {
		echo "<div><b> <span class='verdana1'>"._T('texte_date_creation_article').'</span> ';
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";
	}

	if (($options == 'avancees' AND $articles_redac != 'non') OR ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00')) {
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') $date_affichee = affdate($date_redac);
		else $date_affichee = _T('jour_non_connu_nc');
		
		echo "<div><b>";
		echo bouton_block_invisible('dateredac');
		echo "<span class='verdana1'>"._T('texte_date_publication_anterieure').'</span> '.majuscules($date_affichee)." ".aide('artdate_redac')."</b></div>";


		echo debut_block_invisible('dateredac');
		echo "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>";
		echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		echo '<tr><td align="$spip_lang_left">';
		echo '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"';
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac == '0000-00-00') echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_on">'._T('texte_date_publication_anterieure_nonaffichee').'</label>';
		echo '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"';
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_off">'._T('bouton_radio_afficher').' :</label> ';

		echo "<select name='jour_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\">";
		afficher_jour($jour_redac);
		echo '</select> &nbsp;';
		echo "<select name='mois_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\">";
		afficher_mois($mois_redac);
		echo '</select> &nbsp;';
		echo "<input type='text' name='annee_redac' class='fondl' value='".$annee_redac."' size='5' maxlength='4' onClick=\"setvisibility('valider_date_prec', 'visible')\"/>";

		echo '</td><td align="$spip_lang_right">';
		echo "<span class='visible_au_chargement' id='valider_date_prec'>";
		echo '<input type="submit" name="Changer" class="fondo" value="'._T('bouton_changer').'" />';
		echo "</span>";
		echo '</td></tr>';
		echo '</table>';
		echo "</div>";
		echo fin_block();
	}

	echo "</FORM>";
	fin_cadre_couleur();
}
else {
	if ($statut_article == 'publie') $texte_date = _T('texte_date_publication_article');
	else $texte_date = _T('texte_date_creation_article');

	debut_cadre_couleur();
		echo "<div style='text-align:center;'><b> <span class='verdana1'>$texte_date</span> ";
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";


		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') {
			$date_affichee = ' : '.majuscules(affdate($date_redac));		
			echo "<div style='text-align:center;'><b> <span class='verdana1'>"._T(texte_date_publication_anterieure)."</span> ";
			echo $date_affichee."</b>".aide('artdate_redac')."</div>";
		}

	fin_cadre_couleur();
}



//
// Liste des auteurs de l'article
//

echo "<a name='auteurs'></a>";

if ($flag_editable AND $options == 'avancees') {
	$bouton = bouton_block_invisible("auteursarticle");
}

debut_cadre_enfonce("auteur-24.gif", false, "", $bouton._T('texte_auteurs').aide ("artauteurs"));



////////////////////////////////////////////////////
// Gestion des auteurs
//

// Creer un nouvel auteur et l'ajouter
#spip_log("$creer_auteur AND $connect_statut");
# ce code n'est jamais execute. A tirer au clair
if ($creer_auteur AND $connect_statut=='0minirezo'){
	$creer_auteur = addslashes($creer_auteur);

	$nouv_auteur = spip_abstract_insert('spip_auteurs', "(nom, statut)",
				      "(\"$creer_auteur\", '1comite')");
	$ajout_auteur = true;
}


//
// Recherche d'auteur
//

if ($cherche_auteur) {
	echo "<P ALIGN='$spip_lang_left'>";
	$query = "SELECT id_auteur, nom FROM spip_auteurs";
	$result = spip_query($query);
	unset($table_auteurs);
	unset($table_ids);
	while ($row = spip_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR>";
	}
	else if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>"._T('texte_ajout_auteur')."</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = spip_query($query);
		echo "<UL>";
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];

			echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($nom_auteur)."</FONT></B>";
			echo "</FONT>\n";
		}
		echo "</UL>";
	}
	else if (count($resultat) < 16) {
		reset($resultat);
		unset($les_auteurs);
		while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<B>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = spip_query($query);
			echo "<UL class='verdana1'>";
			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				echo "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A HREF=\"articles.php3?id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur#auteurs\">"._T('lien_ajouter_auteur')."</A>";

				if (trim($bio_auteur)) {
					echo "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				echo "</li>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
	}

	if ($GLOBALS['connect_statut'] == '0minirezo') {
		echo "<div style='width: 200px;'>";
		$retour = urlencode($GLOBALS['clean_link']->getUrl());
		$titre = urlencode($cherche_auteur);
		icone_horizontale(_T('icone_creer_auteur'), "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&titre=$titre&redirect=$retour", "redacteurs-24.gif", "creer.gif");
		echo "</div> ";

		// message pour ne pas afficher le second bouton "creer un auteur"
		$supprimer_bouton_creer_auteur = true;
	}

	fin_boite_info();
	echo "<P>";

}



//
// Appliquer les modifications sur les auteurs
//

if ($ajout_auteur && $flag_editable) {
	if ($nouv_auteur > 0) {
		$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$nouv_auteur' AND id_article='$id_article'";
		$result=spip_query($query);
		$query="INSERT INTO spip_auteurs_articles (id_auteur,id_article) VALUES ('$nouv_auteur','$id_article')";
		$result=spip_query($query);
	}

	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		indexer_article($id_article);
	}
}

if ($supp_auteur && $flag_editable) {
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$supp_auteur' AND id_article='$id_article'";
	$result=spip_query($query);
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		indexer_article($id_article);
	}
}


//
// Afficher les auteurs
//

unset($les_auteurs);

$query = "SELECT * FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	"WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=$id_article ".
	"GROUP BY auteurs.id_auteur ORDER BY auteurs.nom";
$result = spip_query($query);

if (spip_num_rows($result)) {
	echo "<div class='liste'>";
	echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>";
	$table = '';
	while ($row = spip_fetch_array($result)) {
		$vals = '';
		$id_auteur = $row["id_auteur"];
		$nom_auteur = $row["nom"];
		$email_auteur = $row["email"];
		if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			$bio_auteur = " TITLE=\"$bio_auteur\"";
		$url_site_auteur = $row["url_site"];
		$statut_auteur = $row["statut"];
		if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';

		$les_auteurs[] = $id_auteur;

		if ($connect_statut == "0minirezo") $aff_articles = "('prepa', 'prop', 'publie', 'refuse')";
		else $aff_articles = "('prop', 'publie')";

		$query2 = "SELECT COUNT(articles.id_article) AS compteur ".
			"FROM spip_auteurs_articles AS lien, spip_articles AS articles ".
			"WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article ".
			"AND articles.statut IN $aff_articles GROUP BY lien.id_auteur";
		$result2 = spip_query($query2);
		if ($result2) list($nombre_articles) = spip_fetch_array($result2);
		else $nombre_articles = 0;

		$url_auteur = "auteurs_edit.php3?id_auteur=$id_auteur";

		$vals[] = bonhomme_statut($row);

		$vals[] = "<A HREF=\"$url_auteur\"$bio_auteur>".typo($nom_auteur)."</A>";

		$vals[] = bouton_imessage($id_auteur);

		
		
		if ($email_auteur) $vals[] =  "<A HREF='mailto:$email_auteur'>"._T('email')."</A>";
		else $vals[] =  "&nbsp;";

		if ($url_site_auteur) $vals[] =  "<A HREF='$url_site_auteur'>"._T('info_site_min')."</A>";
		else $vals[] =  "&nbsp;";

		if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
		else if ($nombre_articles == 1) $vals[] =  _T('info_1_article');
		else $vals[] =  "&nbsp;";

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
		  $vals[] =  "<A HREF='articles.php3?id_article=$id_article&supp_auteur=$id_auteur#auteurs'>"._T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") . "</A>";
		} else {
			$vals[] = "";
		}
		
		$table[] = $vals;
	}
	
	
	$largeurs = array('14', '', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial11', 'arial1');
	afficher_liste($largeurs, $table, $styles);

	
	echo "</table></div>\n";

	$les_auteurs = join(',', $les_auteurs);
}


//
// Ajouter un auteur
//

if ($flag_editable AND $options == 'avancees') {
	echo debut_block_invisible("auteursarticle");

	$query = "SELECT * FROM spip_auteurs WHERE ";
	if ($les_auteurs) $query .= "id_auteur NOT IN ($les_auteurs) AND ";
	$query .= "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY statut, nom";
	$result = spip_query($query);
	
	echo "<table width='100%'>";
	echo "<tr>";

	if ($connect_statut == '0minirezo'
	AND acces_rubrique($rubrique_article)
	AND $options == "avancees"
	AND !$supprimer_bouton_creer_auteur) {
		echo "<td width='200'>";
		$retour = urlencode($clean_link->getUrl());
		icone_horizontale(_T('icone_creer_auteur'), "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "redacteurs-24.gif", "creer.gif");
		echo "</td>";
		echo "<td width='20'>&nbsp;</td>";
	}
	
	echo "<td>";
	
	
	if (spip_num_rows($result) > 0) {
		echo "<FORM ACTION='articles.php3?id_article=$id_article#auteurs' METHOD='post'>";
		echo "<span class='verdana1'><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></span>\n";
		echo "<DIV><INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (spip_num_rows($result) > 200) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' onClick=\"setvisibility('valider_ajouter_auteur','visible');\" CLASS='fondl' VALUE='' SIZE='20'>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo'>";
			echo "</span>";
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='width:150px;' CLASS='fondl' onChange=\"setvisibility('valider_ajouter_auteur','visible');\">";
			$group = false;
			$group2 = false;

			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row["id_auteur"];
				$nom = $row["nom"];
				$email = $row["email"];
				$statut = $row["statut"];

				$statut=ereg_replace("0minirezo", _T('info_administrateurs'), $statut);
				$statut=ereg_replace("1comite", _T('info_redacteurs'), $statut);
				$statut=ereg_replace("2redac", _T('info_redacteurs'), $statut);

				$premiere = strtoupper(substr(trim($nom), 0, 1));

				if ($connect_statut != '0minirezo')
					if ($p = strpos($email, '@'))
						$email = substr($email, 0, $p).'@...';
				if ($email)
					$email = " ($email)";

				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\" style='background-color: $couleur_claire;'> $statut";
				}

				if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old)) {
					echo "\n<OPTION VALUE=\"x\">";
				}

				$texte_option = supprimer_tags(couper(typo("$nom$email"), 40));
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}

			echo "</SELECT>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Ajouter' VALUE="._T('bouton_ajouter')." CLASS='fondo'>";
			echo "</span>";
		}
		echo "</div></FORM>";
	}
	
	echo "</td></tr></table>";


	echo fin_block();
}

fin_cadre_enfonce(false);


//////////////////////////////////////////////////////
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $articles_mots != 'non') {
	formulaire_mots('articles', $id_article, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}


//
// Langue de l'article
//
if ((lire_meta('multi_articles') == 'oui')
	OR ((lire_meta('multi_rubriques') == 'oui') AND (lire_meta('gerer_trad') == 'oui'))) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
	$langue_article = $row['lang'];
	$langue_choisie_article = $row['langue_choisie'];

	if (lire_meta('gerer_trad') == 'oui')
		$titre_barre = _T('titre_langue_trad_article');
	else
		$titre_barre = _T('titre_langue_article');

	$titre_barre .= "&nbsp; (".traduire_nom_langue($langue_article).")";

	debut_cadre_enfonce('langues-24.gif', false, "", bouton_block_invisible('languesarticle,ne_plus_lier,lier_traductions').$titre_barre);


	// Choix langue article
	if (lire_meta('multi_articles') == 'oui' AND $flag_editable) {
		echo debut_block_invisible('languesarticle');

		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];

		if ($langue_choisie_article == 'oui') $herit = false;
		else $herit = true;

		debut_cadre_couleur();
		echo "<div style='text-align: center;'>";
		echo menu_langues('changer_lang', $langue_article, _T('info_multi_cet_article').' ', $langue_parent);
		echo "</div>\n";
		fin_cadre_couleur();

		echo fin_block();
	}


	// Gerer les groupes de traductions
	if (lire_meta('gerer_trad') == 'oui') {
		if ($flag_editable AND $supp_trad == 'oui') { // Ne plus lier a un groupe de trad
			spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_article = $id_article");

			// Verifier si l'ancien groupe ne comporte plus qu'un seul article. Alors mettre a zero.
			$result_autres_trad= spip_query("SELECT COUNT(id_article) AS total FROM spip_articles WHERE id_trad = $id_trad");
			if ($row = spip_fetch_array($result_autres_trad))
				$nombre_autres_trad = $row["total"];
			if ($nombre_autres_trad == 1)
				spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_trad = $id_trad");

			$id_trad = 0;
		}

		// Changer article de reference de la trad
		if ($id_trad_new = intval($id_trad_new)
		AND $id_trad_old = intval($id_trad_old)
		AND $connect_statut=='0minirezo'
		AND $connect_toutes_rubriques) { 
			spip_query("UPDATE spip_articles SET id_trad = $id_trad_new WHERE id_trad = $id_trad_old");
			$id_trad = $id_trad_new;
		}

		if ($flag_editable AND $lier_trad > 0) { // Lier a un groupe de trad
			$query_lier = "SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad";
			$result_lier = spip_query($query_lier);
			if ($row = spip_fetch_array($result_lier)) {
				$id_lier = $row['id_trad'];

				if ($id_lier == 0) { // Si l'article vise n'a pas deja de traduction, creer nouveau id_trad
					$nouveau_trad = $lier_trad;
				}
				else {
					if ($id_lier == $id_trad) $err = "<div>"._T('trad_deja_traduit')."</div>";
					$nouveau_trad = $id_lier;
				}

				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $lier_trad");
				if ($id_lier > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_lier");
				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $id_article");
				if ($id_trad > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_trad");

				$id_trad = $nouveau_trad;
			}
			else
				$err .= "<div>"._T('trad_article_inexistant')."</div>";

			if ($err) echo "<font color='red' size=2' face='verdana,arial,helvetica,sans-serif'>$err</font>";
		}


		// Afficher la liste des traductions
		if ($id_trad != 0) {
			$query_trad = "SELECT id_article, titre, lang, statut FROM spip_articles WHERE id_trad = $id_trad";
			$result_trad = spip_query($query_trad);
			
			
			$table='';
			while ($row = spip_fetch_array($result_trad)) {
				$vals = '';
				$id_article_trad = $row["id_article"];
				$titre_trad = $row["titre"];
				$lang_trad = $row["lang"];
				$statut_trad = $row["statut"];

				changer_typo($lang_trad);
				$titre_trad = "<span $dir_lang>$titre_trad</span>";

				if ($ifond == 1) {
					$ifond = 0;
					$bgcolor = "white";
				} else {
					$ifond = 1;
					$bgcolor = $couleur_claire;
				}


				$vals[] = http_img_pack("puce-".puce_statut($statut_trad).'.gif', "", "width='7' height='7' border='0' NAME='statut'");
				
				if ($id_article_trad == $id_trad) {
				  $vals[] = http_img_pack('langues-12.gif', "", "width='12' height='12' border='0'");
					$titre_trad = "<b>$titre_trad</b>";
				} else {
				  if ($connect_statut=='0minirezo'
				  AND $connect_toutes_rubriques)
				  	$vals[] = "<a href='articles.php3?id_article=$id_article&id_trad_old=$id_trad&id_trad_new=$id_article_trad'>". 
				    http_img_pack('langues-off-12.gif', _T('trad_reference'), "width='12' height='12' border='0'", _T('trad_reference')) . "</a>";
				  else $vals[] = http_img_pack('langues-off-12.gif', "", "width='12' height='12' border='0'");
				}

				$ret .= "</td>";

				$s = typo($titre_trad);
				if ($id_article_trad != $id_article) 
					$s = "<a href='articles.php3?id_article=$id_article_trad'>$s</a>";
				if ($id_article_trad == $id_trad)
					$s .= " "._T('trad_reference');

				$vals[] = $s;
				$vals[] = traduire_nom_langue($lang_trad);
				$table[] = $vals;
			}

			// changer_typo($spip_lang); (probleme d'affichage rtl?)

			// bloc traductions
			if (count($vals) > 0) {

				echo "<div class='liste'>";
				bandeau_titre_boite2(_T('trad_article_traduction'),'');
				echo "<table width='100%' cellspacing='0' border='0' cellpadding='2'>";
				//echo "<tr bgcolor='#eeeecc'><td colspan='4' class='serif2'><b>"._T('trad_article_traduction')."</b></td></tr>";

				$largeurs = array(7, 12, '', 100);
				$styles = array('', '', 'arial2', 'arial2');
				afficher_liste($largeurs, $table, $styles);

				echo "</table>";
				echo "</div>";

			}

			changer_typo($langue_article);
		}

		echo debut_block_invisible('lier_traductions');

		echo "<table width='100%'><tr>";
		if ($flag_editable AND $options == "avancees" AND !$ret) {
			// Formulaire pour lier a un article
			echo "<td class='arial2' width='60%'>";
			$lien = $GLOBALS['clean_link'];
			$lien->delVar($nom_select);
			$lien = $lien->getUrl();

			echo "<form action='$lien' method='post' style='margin:0px; padding:0px;'>";
			echo _T('trad_lier');
			echo "<div align='$spip_lang_right'><input type='text' class='fondl' name='lier_trad' size='5'> <INPUT TYPE='submit' NAME='Modifier' VALUE='"._T('bouton_modifier')."' CLASS='fondl'></div>";
			echo "</form>";
			echo "</td>\n";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
		}
		echo "<td>";
		icone_horizontale(_T('trad_new'), "articles_edit.php3?new=oui&lier_trad=$id_article&id_rubrique=$id_rubrique", "traductions-24.gif", "creer.gif");
		echo "</td>";
		if ($flag_editable AND $options == "avancees" AND $ret) {
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td>";
			icone_horizontale(_T('trad_delier'), "articles.php3?id_article=$id_article&supp_trad=oui", "traductions-24.gif", "supprimer.gif");
			echo "</td>\n";
		}

		echo "</tr></table>";

		echo fin_block();
	}

	fin_cadre_enfonce();
}



//////////////////////////////////////////////////////
// Modifier le statut de l'article
//


?>
<script type='text/javascript'>
<!--
function puce_statut(selection){
	if (selection=="publie"){
		return "puce-verte.gif";
	}
	if (selection=="prepa"){
		return "puce-blanche.gif";
	}
	if (selection=="prop"){
		return "puce-orange.gif";
	}
	if (selection=="refuse"){
		return "puce-rouge.gif";
	}
	if (selection=="poubelle"){
		return "puce-poubelle.gif";
	}
}
// -->
</script>
<?php

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article)) {
	echo "<FORM ACTION='articles.php3' METHOD='get'>";
	debut_cadre_relief("racine-site-24.gif");
	echo "<CENTER>";
	
	echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

	echo "<B>"._T('texte_article_statut')."</B> ";

	// $statut_url_javascript="\"articles.php3?id_article=$id_article&methode=image&alea=\"+Math.random()+\"&statut_nouv=\"+options[selectedIndex].value";
	$statut_url_javascript="'" . _DIR_IMG_PACK . "' + puce_statut(options[selectedIndex].value);";
	echo "<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl' onChange=\"document.statut.src=$statut_url_javascript; setvisibility('valider_statut', 'visible');\">";
	echo "<OPTION" . mySel("prepa", $statut_article) ." style='background-color: white'>"._T('texte_statut_en_cours_redaction')."\n";
	echo "<OPTION" . mySel("prop", $statut_article) . " style='background-color: #FFF1C6'>"._T('texte_statut_propose_evaluation')."\n";
	echo "<OPTION" . mySel("publie", $statut_article) . " style='background-color: #B4E8C5'>"._T('texte_statut_publie')."\n";
	echo "<OPTION" . mySel("poubelle", $statut_article)
	  . http_style_background('rayures-sup.gif') . '>' ._T('texte_statut_poubelle')."\n";
	echo "<OPTION" . mySel("refuse", $statut_article) . " style='background-color: #FFA4A4'>"._T('texte_statut_refuse')."\n";
	echo "</SELECT>";

	echo " &nbsp; ". http_img_pack("puce-".puce_statut($statut_article).'.gif', "", "border='0' NAME='statut'") . "  &nbsp; ";

	// echo "<noscript><INPUT TYPE='submit' NAME='Modifier' VALUE='"._T('bouton_modifier')."' CLASS='fondo'></noscript>";
	echo "<span class='visible_au_chargement' id='valider_statut'>";
	echo "<INPUT TYPE='submit' NAME='Modifier' VALUE='"._T('bouton_modifier')."' CLASS='fondo'>";
	echo "</span>";
	echo aide ("artstatut");
	echo "</CENTER>";
	fin_cadre_relief();
	echo "</FORM>";
}



//////////////////////////////////////////////////////
// Corps de l'article
//

echo "\n\n<div align='justify' style='padding: 10px;'>";

if ($virtuel) {
	debut_boite_info();
	echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
	fin_boite_info();
}
else {
	$revision_nbsp = $activer_revision_nbsp;

	if (strlen($chapo) > 0) {
		echo "<div $dir_lang style='font-size: small;'><b>";
		echo propre($chapo);
		echo "</b></div>\n\n";
	}

	echo "<div $dir_lang style='font-size: small;'>";
	echo propre($texte);
	echo "<br clear='both' />";
	echo "</div>";

	if ($ps) {
		echo debut_cadre_enfonce();
		echo "<div $dir_lang><font size=2 face='Verdana,Arial,Sans,sans-serif'>";
		echo justifier("<b>"._T('info_ps')."</b> ".propre($ps));
		echo "</font></div>";
		echo fin_cadre_enfonce();
	}
	$revision_nbsp = false;

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<div $dir_lang class='arial11'>";
		echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		echo "</div>";
		echo fin_cadre_relief();
	}

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "articles");
	}
}

//
// Bouton "modifier cet article"
//

if ($flag_editable) {
	echo "\n\n<div align='$spip_lang_right'><br>";
	
	if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
		$query_auteur = "SELECT * FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
		$result_auteur = spip_query($query_auteur);
		while ($row_auteur = spip_fetch_array($result_auteur)) {
			$nom_auteur_modif = typo($row_auteur["nom"]);
		}
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "warning-24.gif", "");
		echo "<font face='arial,helvetica,sans-serif' size=1>"._T('texte_travail_article', array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff))."</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
	}
	
	echo "</div>";
}





//
// Documents associes a l'article
//

if ($spip_display != 4) afficher_documents_non_inclus($id_article, "article", $flag_editable);

//
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo "<center>";
	echo "<B>"._T('texte_proposer_publication')."</B>";
	echo aide ("artprop");
	bouton(_T('bouton_demande_publication'), "articles.php3?id_article=$id_article&statut_nouv=prop");
	echo "</center>";
	fin_cadre_relief();
}

echo "</div>";

echo "</div>";
fin_cadre_relief();

//
// Forums
//

echo "<BR><BR>";

$forum_retour = urlencode("articles.php3?id_article=$id_article");


echo "\n<div align='center'>";
	icone(_T('icone_poster_message'), "forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_article=$id_article&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<P align='$spip_lang_left'>";


$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0";
$result_forum = spip_query($query_forum);
$total = 0;
if ($row = spip_fetch_array($result_forum)) $total = $row["cnt"];

if (!$debut) $debut = 0;
$total_afficher = 8;
if ($total > $total_afficher) {
	echo "<div class='serif2' align='center'>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	}
	echo "</div>";
}



$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,$total_afficher";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);


if (!$debut) $debut = 0;
$total_afficher = 8;
if ($total > $total_afficher) {
	echo "<div class='serif2' align='center'>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	}
	echo "</div>";
}


echo "</div>\n";

fin_page();

// Taches lentes
if ($ok_nouveau_statut) {
	@flush();
	calculer_rubriques();
	if ($statut_nouv == 'publie' AND $statut_ancien != $statut_nouv) {
		include_ecrire("inc_mail.php3");
		envoyer_mail_publication($id_article);
	}
	if ($statut_nouv == "prop" AND $statut_ancien != $statut_nouv AND $statut_ancien != 'publie') {
		include_ecrire("inc_mail.php3");
		envoyer_mail_proposition($id_article);
	}
}

?>

