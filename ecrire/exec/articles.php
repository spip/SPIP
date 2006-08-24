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

include_spip('inc/presentation');
include_spip('inc/texte');
include_spip('inc/rubriques');
include_spip('inc/mots');
include_spip('inc/date');
include_spip('inc/documents');
include_spip('inc/petition');
include_spip('exec/virtualiser');
include_spip('exec/discuter');
include_spip('base/abstract_sql');

// http://doc.spip.org/@exec_articles_dist
function exec_articles_dist()
{
	global $cherche_auteur, $ids, $cherche_mot,  $select_groupe, $debut, $id_article, $trad_err; 

	global  $connect_id_auteur, $connect_statut, $options, $spip_display, $spip_lang_left, $spip_lang_right, $dir_lang;

	$id_article= intval($id_article);

	pipeline('exec_init',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	if (!$row) {
	   // cas du numero hors table
		$titre = _T('public:aucun_article');
		debut_page("&laquo; $titre &raquo;", "naviguer", "articles");
		debut_grand_cadre();
		fin_grand_cadre();
		echo $titre;
		exit;
	}

	$id_rubrique = $row['id_rubrique'];
	$statut_article = $row['statut'];
	$surtitre = $row["surtitre"];
	$titre = sinon($row["titre"],_T('info_sans_titre'));
	$soustitre = $row["soustitre"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$id_version = $row["id_version"];
	
	$statut_rubrique = acces_rubrique($id_rubrique);

	$flag_auteur = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur LIMIT 1"));

	$flag_editable = ($statut_rubrique OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR $statut_article == 'poubelle')));

	debut_page("&laquo; $titre &raquo;", "naviguer", "articles", "", "", $id_rubrique);

	debut_grand_cadre();

	afficher_hierarchie($id_rubrique);

	fin_grand_cadre();

//
// Affichage de la colonne de gauche
//

debut_gauche();

boite_info_articles($id_article, $statut_article, $visites, $id_version);

//
// Logos de l'article
//

  if ($flag_editable AND ($spip_display != 4)) {
	  include_spip('inc/chercher_logo');
	  echo afficher_boite_logo('id_article', $id_article,
			      _T('logo_article').aide ("logoart"), _T('logo_survol'), 'articles');
  }

// pour l'affichage du virtuel
$virtuel = '';
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
}

// Boites de configuration avancee

if ($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable)
  {
	boites_de_config_articles($id_article);
 
	boite_article_virtuel($id_article, $virtuel);
  }

//
// Articles dans la meme rubrique
//

meme_rubrique_articles($id_rubrique, $id_article, $options);

echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

//
// Affichage de la colonne de droite
//

creer_colonne_droite();
 echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

debut_droite();

changer_typo('','article'.$id_article);

debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

$modif = titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique);


 echo "<div class='serif' align='$spip_lang_left'>";

 debut_cadre_couleur();
 echo formulaire_dater($id_article, $flag_editable, $statut_article, $date, $date_redac);
 fin_cadre_couleur();

//
// Liste des auteurs de l'article
//

echo "<a name='auteurs'></a>";

if ($flag_editable AND $options == 'avancees') {
	$bouton = bouton_block_invisible("auteursarticle");
}

debut_cadre_enfonce("auteur-24.gif", false, "", $bouton._T('texte_auteurs').aide ("artauteurs"));

//
// complement de action/ajouter.php pour notifier la recherche d'auteur
//

 $bouton_creer_auteur =  $GLOBALS['connect_toutes_rubriques'];

 if ($cherche_auteur) {

	echo "<p align='$spip_lang_left'>";
	debut_boite_info();
	rechercher_auteurs_articles($cherche_auteur, $ids,  $id_article);

	if ($bouton_creer_auteur) {

		echo "<div style='width: 200px;'>";
		$nom = rawurlencode($cherche_auteur);
		icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","ajouter_id_article=$id_article&nom=$nom&redirect=" . generer_url_retour("articles","id_article=$id_article")), "redacteurs-24.gif", "creer.gif");
		echo "</div> ";
		$bouton_creer_auteur = false;
	}

	fin_boite_info();
	echo '</p>';
 }

//
// Afficher les auteurs
//

	$les_auteurs = array();

	$result = spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article");

	while ($row = spip_fetch_array($result))
		$les_auteurs[]= $row['id_auteur'];

	if ($les_auteurs = join(',', $les_auteurs)) 
		echo afficher_auteurs_articles($id_article, $flag_editable, $les_auteurs);

//
// Ajouter un auteur
//

 if ($flag_editable AND $options == 'avancees') {
	echo debut_block_invisible("auteursarticle");
	echo "<table width='100%'><tr>";

	if ($bouton_creer_auteur) {

		echo "<td width='200'>";
		icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","ajouter_id_article=$id_article&redirect=" .generer_url_retour("articles","id_article=$id_article")), "redacteurs-24.gif", "creer.gif");
		echo "</td>";
		echo "<td width='20'>&nbsp;</td>";
	}

	echo "<td>";

	echo ajouter_auteurs_articles($id_article, $les_auteurs, $bouton_creer_auteur);
	echo "</td></tr></table>";

	echo fin_block();
 }
fin_cadre_enfonce(false);

//
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $GLOBALS['meta']["articles_mots"] != 'non') {
  echo formulaire_mots('article', $id_article, $cherche_mot, $select_groupe, $flag_editable);
}

// Les langues

  if (($GLOBALS['meta']['multi_articles'] == 'oui')
	OR (($GLOBALS['meta']['multi_rubriques'] == 'oui') AND ($GLOBALS['meta']['gerer_trad'] == 'oui'))) {

	langues_articles($id_article, $flag_editable, $id_rubrique, $id_trad,  $trad_err);
  }

 echo pipeline('affiche_milieu',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

 if ($statut_rubrique)
   echo debut_cadre_relief('', true),
     "<div id='instituer_article-$id_article'>",     
     formulaire_instituer_article($id_article, $statut_article, 'articles', "id_article=$id_article"),
     '</div>',
     fin_cadre_relief('', true);

 afficher_corps_articles($virtuel, $chapo, $texte, $ps, $extra);

if ($flag_editable) {
	echo "\n\n<div align='$spip_lang_right'><br />";
	bouton_modifier_articles($id_article, $id_rubrique, $modif,_T('texte_travail_article', $modif), "warning-24.gif", "");
	echo "</div>";
}

//
// Documents associes a l'article
//

 if ($spip_display != 4)
 afficher_documents_et_portfolio($id_article, "article", $flag_editable);

 if ($flag_auteur AND  $statut_article == 'prepa' AND !$statut_rubrique)
	echo demande_publication($id_article);

 echo "</div>";
 echo "</div>";
 fin_cadre_relief();

  echo "<br /><br />";
  
  $tm = rawurlencode($titre);
  echo "\n<div align='center'>";
  icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi","statut=prive&id_article=$id_article&titre_message=$tm&url=" . generer_url_retour("articles","id_article=$id_article")), "forum-interne-24.gif", "creer.gif");
  echo "</div><br />";
  
  echo exec_discuter_dist($id_article, $debut);

  fin_page();

}

// http://doc.spip.org/@demande_publication
function demande_publication($id_article)
{
	return debut_cadre_relief('',true) .
		"<center>" .
		"<b>" ._T('texte_proposer_publication') . "</b>" .
		aide ("artprop") .
			redirige_action_auteur('instituer_article', "$id_article-prop",
			'articles',
			"id_article=$id_article",
			("<input type='submit' class='fondo' value=\"" . 
			    _T('bouton_demande_publication') .
			    "\" />\n"),
			"method='post'") .
		"</center>" .
		fin_cadre_relief(true);
}

// http://doc.spip.org/@boite_info_articles
function boite_info_articles($id_article, $statut_article, $visites, $id_version)
{
  global $connect_statut, $options, $flag_revisions;

	debut_boite_info();
 
	echo "<div align='center'>\n";

	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('info_numero_article')."</b></font>\n";
	echo "<br /><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_article</b></font>\n";

	voir_en_ligne('article', $id_article, $statut_article);

	if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $GLOBALS['meta']["activer_statistiques"] != "non" AND $options == "avancees"){
	icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif");
	}

	if ((($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions)
		AND $id_version>1 AND $options == "avancees") {
	icone_horizontale(_T('info_historique_lien'), generer_url_ecrire("articles_versions","id_article=$id_article"), "historique-24.gif", "rien.gif");
}

	// Correction orthographique
	if ($GLOBALS['meta']['articles_ortho'] == 'oui') {
		$js_ortho = "onclick=\"window.open(this.href, 'spip_ortho', 'scrollbars=yes, resizable=yes, width=740, height=580'); return false;\"";
		icone_horizontale(_T('ortho_verifier'), generer_url_ecrire("articles_ortho", "id_article=$id_article"), "ortho-24.gif", "rien.gif", 'echo', $js_ortho);
	}

	echo "</div>\n";
	
	fin_boite_info();
}


//
// Boites de configuration avancee
//

// http://doc.spip.org/@boites_de_config_articles
function boites_de_config_articles($id_article)
{
	  debut_cadre_relief("forum-interne-24.gif");

	  $nb_forums = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_forum WHERE id_article=$id_article 	AND statut IN ('publie', 'off', 'prop')"));

	  $nb_signatures = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_signatures WHERE id_article=$id_article AND statut IN ('publie', 'poubelle')"));

	  $nb_forums = $nb_forums['count'];
	  $nb_signatures = $nb_signatures['count'];
	  $visible = $nb_forums || $nb_signatures;

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("forumpetition");
	else
		echo bouton_block_invisible("forumpetition");
	echo _T('bouton_forum_petition') .aide('confforums');
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("forumpetition");
	else
		echo debut_block_invisible("forumpetition");

	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'>\n";

	// Forums

	if ($nb_forums) {
		echo "<br />\n";
		icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)), generer_url_ecrire("articles_forum","id_article=$id_article"), "suivi-forum-24.gif", "");
	}

	echo "<div id='poster-$id_article'>",
	  formulaire_poster($id_article,"articles","id_article=$id_article"),
	  '</div>';

	echo '<br />';

	// Petitions

	echo "<div id='petitionner-$id_article'>",
	  formulaire_petitionner($id_article,"articles","id_article=$id_article"),
	  '</div>';

	echo fin_block();

	fin_cadre_relief();
}

// http://doc.spip.org/@boite_article_virtuel
function boite_article_virtuel($id_article, $virtuel)
{

	debut_cadre_relief("site-24.gif");

	echo "\n<div class='verdana1' style='text-align: center;'>";
	if ($virtuel)
		echo bouton_block_visible("redirection");
	else
		echo bouton_block_invisible("redirection");

	echo '<b>', _T('bouton_redirection'), '</b>';
	echo aide ("artvirt");
	echo "</div>";

	if ($virtuel)
		echo debut_block_visible("redirection");
	else
		echo debut_block_invisible("redirection");
	echo "<div id='virtualiser-$id_article'>";
	echo formulaire_virtualiser($id_article, $virtuel, "articles", "id_article=$id_article");
	echo "</div>";
	echo fin_block();
	fin_cadre_relief();
}

// http://doc.spip.org/@meme_rubrique_articles
function meme_rubrique_articles($id_rubrique, $id_article, $options, $order='date', $limit=30)
{
	global $spip_lang_right, $spip_lang_left;

	$vos_articles = spip_query("SELECT id_article, titre, statut FROM spip_articles WHERE id_rubrique=$id_rubrique AND (statut = 'publie' OR statut = 'prop') AND id_article != $id_article ORDER BY $order DESC LIMIT $limit");
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
				echo "<a class='$ze_statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$ze_article") . "'>$numero$ze_titre</a>";
			}
			echo "</div>";
			echo "</div>";
		}
}

// http://doc.spip.org/@bouton_modifier_articles
function bouton_modifier_articles($id_article, $id_rubrique, $flag_modif, $mode, $ip, $im)
{
	if ($flag_modif) {
	  icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), $ip, $im);
		echo "<font face='arial,helvetica,sans-serif' size='2'>$mode</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), "article-24.gif", "edit.gif");
	}

}

// http://doc.spip.org/@titres_articles
function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique)
{
	global  $dir_lang, $spip_lang_left, $connect_id_auteur;

	$tout = '';

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
	
	$flag_modif = false;
	$modif = array();

	if ($flag_editable) {
		echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
		echo "<td align='center'>";
	

		// Est-ce que quelqu'un a deja ouvert l'article en edition ?
		unset($modif);
		if ($GLOBALS['meta']['articles_modif'] != 'non') {
			include_spip('inc/drapeau_edition');
			$modif = qui_edite($id_article, 'article');
			if ($modif['id_auteur_modif'] == $connect_id_auteur)
				unset($modif);
		}

		bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif");
		echo "</td>";
	}
	echo "</tr></table>\n";
	echo "<div>&nbsp;</div>";
	return $modif;
}


// http://doc.spip.org/@formulaire_dater
function formulaire_dater($id_article, $flag_editable, $statut_article, $date, $date_redac)
{
	global $spip_lang_left, $spip_lang_right, $options;

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date_redac, $regs)) {
		$annee_redac = $regs[1];
		$mois_redac = $regs[2];
		$jour_redac = $regs[3];
		$heure_redac = $regs[4];
		$minute_redac = $regs[5];
		if ($annee_redac > 4000) $annee_redac -= 9000;
	}

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		$heure = $regs[4];
		$minute = $regs[5];
	}

  if ($flag_editable AND $options == 'avancees') {

	if ($statut_article == 'publie') {

		$js = "onchange=\"findObj_forcer('valider_date').style.visibility='visible';\"";
		$res = ajax_action_auteur("dater", 
			"$id_article",
			'articles',
			"id_article=$id_article",
			(
 bouton_block_invisible("datepub") .
 "<b><span class='verdana1'>".
 _T('texte_date_publication_article').
 '</span> ' . 
 majuscules(affdate($date)) .
 "</b>".
 aide('artdate') . 
 debut_block_invisible("datepub") .
 "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>" .
 afficher_jour($jour, "name='jour' size='1' class='fondl' $js", true) .
 afficher_mois($mois, "name='mois' size='1' class='fondl' $js", true) .
 afficher_annee($annee, "name='annee' size='1' class='fondl' $js") .
 ' - ' .
 afficher_heure($heure, "name='heure' size='1' class='fondl' $js") .
 afficher_minute($minute, "name='minute' size='1' class='fondl' $js") .
 "<span class='visible_au_chargement' id='valider_date'>" .
 " &nbsp;\n<input type='submit' class='fondo' value='".
 _T('bouton_changer')."' />" .
 "</span>" .
 "</div>" .
 fin_block()));
	} else {
		$res = "\n<div><b> <span class='verdana1'>"
		. _T('texte_date_creation_article')
		. "</span>\n"
		. majuscules(affdate($date))."</b>".aide('artdate')."</div>";
	}

	$possedeDateRedac= ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00');
	if (($options == 'avancees' AND $GLOBALS['meta']["articles_redac"] != 'non')
	OR $possedeDateRedac) {
		if ($possedeDateRedac)
			$date_affichee = majuscules(affdate($date_redac))
#			." " ._T('date_fmt_heures_minutes', array('h' =>$heure_redac, 'm'=>$minute_redac))
			;
		else
			$date_affichee = majuscules(_T('jour_non_connu_nc'));

		$js = "\"findObj_forcer('valider_date_redac').style.visibility='visible';\"";
		$res .= ajax_action_auteur("dater", 
			"$id_article",
			'articles',
			"id_article=$id_article",
			(bouton_block_invisible('dateredac') .
 "<b>" .
 "<span class='verdana1'>" .
 majuscules(_T('texte_date_publication_anterieure')) .
'</span> '.
 $date_affichee .
 " " .
 aide('artdate_redac') .
 "</b>" .
 debut_block_invisible('dateredac') .
 "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>" .
 '<table cellpadding="0" cellspacing="0" border="0" width="100%">' .
 '<tr><td align="$spip_lang_left">' .
 '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"' .
 ($possedeDateRedac ? '' : ' checked="checked"') .
 " onClick=$js" .
 ' /> <label for="avec_redac_on">'.
 _T('texte_date_publication_anterieure_nonaffichee').
 '</label>' .
 '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"' .
 (!$possedeDateRedac ? '' : ' checked="checked"') .
 " onClick=$js /> <label for='avec_redac_off'>" .
 _T('bouton_radio_afficher').
 ' :</label> ' .
 afficher_jour($jour_redac, "name='jour_redac' class='fondl' onchange=$js", true) .
 afficher_mois($mois_redac, "name='mois_redac' class='fondl' onchange=$js", true) .
 "<input type='text' name='annee_redac' class='fondl' value='".$annee_redac."' size='5' maxlength='4' onclick=$js />" .
 '<div align="center">' .
 afficher_heure($heure_redac, "name='heure_redac' class='fondl' onchange=$js", true) .
 afficher_minute($minute_redac, "name='minute_redac' class='fondl' onchange=$js", true) .
 "</div>\n" .

 '</td><td align="$spip_lang_right">' .
 "<span class='visible_au_chargement' id='valider_date_redac'>" .
 '<input type="submit" class="fondo" value="'.
 _T('bouton_changer').'" />' .
 "</span>" .
 '</td></tr>' .
 '</table>' .
 '</div>' .
 fin_block()) #, " method='post'"
);
	}
  } else {

	$res .= "<div style='text-align:center;'><b> <span class='verdana1'>"
	. (($statut_article == 'publie')
		? _T('texte_date_publication_article')
		: _T('texte_date_creation_article'))
	. "</span> "
	.  majuscules(affdate($date))."</b>".aide('artdate')."</div>";

	if ($possedeDateRedac) {
		$res .= "<div style='text-align:center;'><b><span class='verdana1'>"
		. _T('texte_date_publication_anterieure')
		. "</span> "
		. ' : '
		. majuscules(affdate($date_redac))
		. "</b>"
		. aide('artdate_redac')
		. "</div>";
	}
  }
  return ($flag_editable === 'ajax')
    ? $res
    : "<div id='dater-$id_article'>$res</div>";
}


// http://doc.spip.org/@langues_articles
function langues_articles($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err)
{

	global $connect_statut, $couleur_claire, $options, $connect_toutes_rubriques, $spip_lang_right, $dir_lang;

	$langue_article = spip_fetch_array(spip_query("SELECT lang FROM spip_articles WHERE id_article=$id_article"));

	$langue_article = $langue_article['lang'];
	if ($GLOBALS['meta']['gerer_trad'] == 'oui')
		$titre_barre = _T('titre_langue_trad_article');
	else
		$titre_barre = _T('titre_langue_article');

	if ($langue_article)
		$titre_barre .= "&nbsp; (".traduire_nom_langue($langue_article).")";

	debut_cadre_enfonce('langues-24.gif', false, "", bouton_block_invisible('languesarticle,ne_plus_lier,lier_traductions').$titre_barre);


	// Choix langue article
	if ($GLOBALS['meta']['multi_articles'] == 'oui' AND $flag_editable) {
		echo debut_block_invisible('languesarticle');

		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];

		if (!$langue_parent)
			$langue_parent = $GLOBALS['meta']['langue_site'];
		if (!$langue_article)
			$langue_article = $langue_parent;

		debut_cadre_couleur();
		echo "<div style='text-align: center;' id='instituer_langue_article-$id_article'>",
			menu_langues('changer_lang', $langue_article, _T('info_multi_cet_article').' ', $langue_parent, redirige_action_auteur('instituer_langue_article', "$id_article-$id_rubrique","articles","id_article=$id_article")),
			"</div>\n";
		fin_cadre_couleur();

		echo fin_block();
	}

	if ($trad_err)
		echo "<div><font color='red' size='2' face='verdana,arial,helvetica,sans-serif'>"._T('trad_deja_traduit'). "</font></div>";

		// Afficher la liste des traductions
	$table = !$id_trad ? array() : articles_traduction($id_article, $id_trad);

		// bloc traductions
	if (count($table) > 0) {

			echo "<div class='liste'>";
			bandeau_titre_boite2(_T('trad_article_traduction'),'');
			echo "<table width='100%' cellspacing='0' border='0' cellpadding='2'>";

			$largeurs = array(7, 12, '', 100);
			$styles = array('', '', 'arial2', 'arial2');
			echo afficher_liste ($largeurs, $table, $styles);
			echo "</table>";
			echo "</div>";
	}

	// changer les globales $dir_lang etc
	changer_typo($langue_article);

	echo debut_block_invisible('lier_traductions');

	echo "<table width='100%'><tr>";

	if ($flag_editable AND $options == "avancees" AND !$table) {
			// Formulaire pour lier a un article
			echo "<td class='arial2' width='60%'>";

			echo redirige_action_auteur("editer_article",
				$id_article,
				'articles',
				"id_article=$id_article",
				(_T('trad_lier') .
					 "<div align='$spip_lang_right'>\n<input type='text' class='fondl' name='lier_trad' size='5' />\n<input type='submit' VALUE='"._T('bouton_valider')."' CLASS='fondl' /></div>"),
				" method='post' style='margin:0px; padding:0px;'");

			echo "</td>\n";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
	}

	echo "<td>";
	icone_horizontale(_T('trad_new'), generer_url_ecrire("articles_edit","new=oui&lier_trad=$id_article&id_rubrique=$id_rubrique"), "traductions-24.gif", "creer.gif");
	echo "</td>";
	if ($flag_editable AND $options == "avancees" AND $table) {
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td>";
			icone_horizontale(_T('trad_delier'), redirige_action_auteur("supprimer_traduction","$id_article-$id_trad",'articles',	"id_article=$id_article"), "traductions-24.gif", "supprimer.gif");
			echo "</td>\n";
	}

	echo "</tr></table>";

	echo fin_block();

	fin_cadre_enfonce();
}


// http://doc.spip.org/@articles_traduction
function articles_traduction($id_article, $id_trad)
{
	global $connect_toutes_rubriques, $dir_lang;

	$result_trad = spip_query("SELECT id_article, id_rubrique, titre, lang, statut FROM spip_articles WHERE id_trad = $id_trad");
	
	$table= array();

	while ($row = spip_fetch_array($result_trad)) {
		$vals = array();
		$id_article_trad = $row["id_article"];
		$id_rubrique_trad = $row["id_rubrique"];
		$titre_trad = $row["titre"];
		$lang_trad = $row["lang"];
		$statut_trad = $row["statut"];

		changer_typo($lang_trad);
		$titre_trad = "<span $dir_lang>$titre_trad</span>";

		$vals[] = http_img_pack("puce-".puce_statut($statut_trad).'.gif', "", "width='7' height='7' border='0' NAME='statut'");
		
		if ($id_article_trad == $id_trad) {
			$vals[] = http_img_pack('langues-12.gif', "", "width='12' height='12' border='0'");
			$titre_trad = "<b>$titre_trad</b>";
		} else {
		  if ($connect_toutes_rubriques)
		    $vals[] = "<a href='" . redirige_action_auteur("referencer_traduction", "$id_trad,$id_article_trad", 'articles', "id_article=$id_article") . "'>". 
		    http_img_pack('langues-off-12.gif', _T('trad_reference'), "width='12' height='12' border='0'", _T('trad_reference')) . "</a>";
		  else $vals[] = http_img_pack('langues-off-12.gif', "", "width='12' height='12' border='0'");
		}

		$s = typo($titre_trad);
		if ($id_article_trad != $id_article) 
			$s = "<a href='" . generer_url_ecrire("articles","id_article=$id_article_trad&id_rubrique=$id_rubrique_trad") . "'>$s</a>";
		if ($id_article_trad == $id_trad)
			$s .= " "._T('trad_reference');

		$vals[] = $s;
		$vals[] = traduire_nom_langue($lang_trad);
		$table[] = $vals;
	}

	return $table;
}

// http://doc.spip.org/@rechercher_auteurs_articles
function rechercher_auteurs_articles($cherche_auteur, $ids, $id_article)
{
	if (!$ids) {
		echo "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR />";
	}
	elseif ($ids == -1) {
		echo "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR />";
	}
	elseif (!strpos($ids,',')) {

		$row = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$ids"));
		echo "<B>"._T('texte_ajout_auteur')."</B><BR /><UL><LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($row['nom'])."</FONT></B></UL>";
	}
	else {
		$ids = preg_replace('/[^0-9,]/','',$ids); // securite
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN ($ids) ORDER BY nom");

		echo "<B>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</B><BR />";
		echo "<UL class='verdana1'>";
		while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				echo "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A href='", redirige_action_auteur('ajouter', "$id_article-$id_auteur","articles","id_article=$id_article#auteurs") . "'>",_T('lien_ajouter_auteur'),"</A>";

				if (trim($bio_auteur)) {
					echo "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				echo "</li>\n";
			}
		echo "</UL>";
	}

}

// http://doc.spip.org/@afficher_auteurs_articles
function afficher_auteurs_articles($id_article, $flag_editable, $les_auteurs)
{
	global $connect_statut, $options,$connect_id_auteur;

	$table = array();

	$result = spip_query("SELECT * FROM spip_auteurs AS A WHERE A.id_auteur IN ($les_auteurs) ORDER BY A.nom");

	while ($row = spip_fetch_array($result)) {
			$vals = array();
			$id_auteur = $row["id_auteur"];
			$nom_auteur = $row["nom"];
			$email_auteur = $row["email"];
			if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			  $bio_auteur = " TITLE=\"$bio_auteur\"";
			$url_site_auteur = $row["url_site"];
			$statut_auteur = $row["statut"];
			if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';

			$vals[] = bonhomme_statut($row);

			$vals[] = "<a href='" . generer_url_ecrire('auteurs_edit', "id_auteur=$id_auteur") . "' $bio_auteur>".typo($nom_auteur)."</a>";

			$vals[] = bouton_imessage($id_auteur);
		
			if ($email_auteur) $vals[] =  "<A href='mailto:$email_auteur'>"._T('email')."</A>";
			else $vals[] =  "&nbsp;";

			if ($url_site_auteur) $vals[] =  "<A href='$url_site_auteur'>"._T('info_site_min')."</A>";
			else $vals[] =  "&nbsp;";

			$cpt = spip_fetch_array(spip_query("SELECT COUNT(articles.id_article) AS n FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article AND articles.statut IN " . ($connect_statut == "0minirezo" ? "('prepa', 'prop', 'publie', 'refuse')" : "('prop', 'publie')") . " GROUP BY lien.id_auteur"));

			$nombre_articles = intval($cpt['n']);

			if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
			else if ($nombre_articles == 1) $vals[] =  _T('info_1_article');
			else $vals[] =  "&nbsp;";

			if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
				$vals[] =  "<a href='" . redirige_action_auteur('supprimer', "auteur_article-$id_auteur-$id_article","articles","id_article=$id_article#auteurs") . "'>"._T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") . "</a>";
			} else {
			  $vals[] = "";
			}
		
			$table[] = $vals;
	}
	
	$largeurs = array('14', '', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial11', 'arial1');

	return "<div class='liste'><table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>"
	. afficher_liste($largeurs, $table, $styles)
	. "</table></div>\n";
}


// http://doc.spip.org/@ajouter_auteurs_articles
function ajouter_auteurs_articles($id_article, $les_auteurs)
{
	$result = spip_query("SELECT * FROM spip_auteurs WHERE " . (!$les_auteurs ? '' : "id_auteur NOT IN ($les_auteurs) AND ") . "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY statut, nom");

	if (!$num = spip_num_rows($result)) return '';

	return redirige_action_auteur('ajouter', $id_article,'articles', "id_article=$id_article",
				      (
			"<span class='verdana1'><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></span>\n" .

			($num > 200 ? 
			 ("<input type='text' name='cherche_auteur' onClick=\"setvisibility('valider_ajouter_auteur','visible');\" CLASS='fondl' VALUE='' SIZE='20' />" .
			  "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>\n<input type='submit' value='"._T('bouton_chercher')."' CLASS='fondo' /></span>") :
			 ("<select name='nouv_auteur' size='1' style='width:150px;' CLASS='fondl' onChange=\"setvisibility('valider_ajouter_auteur','visible');\">" .
			   articles_auteur_select($result) .
			   "</select>" .
			   "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>" .
			   " <input type='submit' value='"._T('bouton_ajouter')."' CLASS='fondo'>" .
			   "</span>"))),
					    " method='post'");
}

// http://doc.spip.org/@articles_auteur_select
function articles_auteur_select($result)
{
	global $couleur_claire ;

	$statut_old = $premiere_old = $res = '';

	while ($row = spip_fetch_array($result)) {
		$id_auteur = $row["id_auteur"];
		$nom = $row["nom"];
		$email = $row["email"];
		$statut = $row["statut"];

		$statut=str_replace("0minirezo", _T('info_administrateurs'), $statut);
		$statut=str_replace("1comite", _T('info_redacteurs'), $statut);
		$statut=str_replace("6visiteur", _T('info_visiteurs'), $statut);
				
		$premiere = strtoupper(substr(trim($nom), 0, 1));

		if ($connect_statut != '0minirezo')
			if ($p = strpos($email, '@'))
				  $email = substr($email, 0, $p).'@...';
		if ($email)
			$email = " ($email)";

		if ($statut != $statut_old) {
			$res .= "\n<OPTION VALUE=\"x\">";
			$res .= "\n<OPTION VALUE=\"x\" style='background-color: $couleur_claire;'> $statut";
		}

		if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old))
			$res .= "\n<OPTION VALUE=\"x\">";
				
		$res .= "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;" . supprimer_tags(couper(typo("$nom$email"), 40));
		$statut_old = $statut;
		$premiere_old = $premiere;
	}
	return $res;
}

// http://doc.spip.org/@afficher_corps_articles
function afficher_corps_articles($virtuel, $chapo, $texte, $ps,  $extra)
{
  global $revision_nbsp, $activer_revision_nbsp, $champs_extra, $les_notes, $dir_lang;

	echo "\n\n<div align='justify' style='padding: 10px;'>";

	if ($virtuel) {
		debut_boite_info();
		echo "<div id='renvoi' style='text-align: center'>",
		  _T('info_renvoi_article'),
		  " ",
		  propre("[->$virtuel]"),
		  '</div>';
		fin_boite_info();
	} else {
		$revision_nbsp = $activer_revision_nbsp;

		if (strlen($chapo) > 0) {
			echo "<div $dir_lang><b>";
			echo propre($chapo);
			echo "</b></div>\n\n";
		}

		echo "<div $dir_lang>";
#	echo reduire_image(propre($texte), 500,10000);
		echo propre($texte);
		echo "<br clear='all' />";
		echo "</div>";

		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div $dir_lang><font style='font-family:Verdana,Arial,Sans,sans-serif; font-size: small;'>";
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
			include_spip('inc/extra');
			extra_affichage($extra, "articles");
		}
	}
}

// http://doc.spip.org/@formulaire_instituer_article
function formulaire_instituer_article($id_article, $statut, $script, $args)
{
  $res =
	("\n<center>" . 
	"<b>" ._T('texte_article_statut') ."</b>" .
	"\n<select name='statut_nouv' size='1' class='fondl'\n" .
	"onChange=\"document.statut.src='" .
	_DIR_IMG_PACK .
	"' + puce_statut(options[selectedIndex].value);" .
	" setvisibility('valider_statut', 'visible');\">\n" .
	"<option"  . mySel("prepa", $statut)  ." style='background-color: white'>" ._T('texte_statut_en_cours_redaction') ."</option>\n" .
	"<option"  . mySel("prop", $statut)  . " style='background-color: #FFF1C6'>" ._T('texte_statut_propose_evaluation') ."</option>\n" .
	"<option"  . mySel("publie", $statut)  . " style='background-color: #B4E8C5'>" ._T('texte_statut_publie') ."</option>\n" .
	"<option"  . mySel("poubelle", $statut) .
	http_style_background('rayures-sup.gif')  . '>'  ._T('texte_statut_poubelle') ."</option>\n" .
	"<option"  . mySel("refuse", $statut)  . " style='background-color: #FFA4A4'>" ._T('texte_statut_refuse') ."</option>\n" .
	"</select>" .
	" &nbsp; " .
	http_img_pack("puce-".puce_statut($statut).'.gif', "", "border='0' NAME='statut'") .
	"  &nbsp;\n" .
	"<span class='visible_au_chargement' id='valider_statut'>" .
	"<input type='submit' value='"._T('bouton_valider')."' CLASS='fondo' />" .
	"</span>" .
	aide("artstatut") .
	 "</center>");
  
  return redirige_action_auteur('instituer_article',$id_article,'articles', "id_article=$id_article", $res, " method='post'");

  /* quand la mise en page sera plus regroupee
  return ajax_action_auteur("instituer_article", $id_article, $script, $args, $res, $args);
  */
}

?>