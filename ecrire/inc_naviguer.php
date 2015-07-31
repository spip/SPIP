<?php

include_ecrire ("inc_rubriques.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_abstract_sql.php3");

function naviguer_dist($action)
{
	global $id_parent, $id_rubrique, $nouv_mot, $spip_display,  $connect_statut, $supp_mot, $champs_extra, $cherche_mot, $descriptif, $texte, $titre;


	$flag_editable = ($connect_statut == '0minirezo' AND (acces_rubrique($id_parent) OR acces_rubrique($id_rubrique))); // id_parent necessaire en cas de creation de sous-rubrique
	// si action vide, simple visite
	if ($flag_editable AND $action) 
		$id_rubrique = maj_naviguer($action, $id_rubrique, $id_parent, $titre, $texte, $descriptif, $flag_editable);

//
// recuperer les infos sur cette rubrique
//

	if ($row=spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'"))){
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$statut = $row['statut'];
		$extra = $row["extra"];
	}

	if ($id_rubrique ==  0) $ze_logo = "racine-site-24.gif";
	else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

///// debut de la page


	  debut_page(($titre ? ("&laquo; ".textebrut(typo($titre))." &raquo;") :
	    _T('titre_naviguer_dans_le_site')), "documents", "rubriques");

	  if ($id_rubrique == 0) {
	    $titre = _T('info_racine_site').": ". lire_meta("nom_site");
	  }

//////// parents

	  debut_grand_cadre();

	  if ($id_rubrique  > 0) afficher_hierarchie($id_parent);

	  fin_grand_cadre();

	  changer_typo('', 'rubrique'.$id_rubrique);

	  debut_gauche();

	  if ($spip_display != 4) {

		infos_naviguer($id_rubrique, $statut);

//
// Logos de la rubrique
//
		if ($flag_editable) 
			logo_naviguer($id_rubrique);
//
// Afficher les boutons de creation d'article et de breve
//
		raccourcis_naviguer($id_rubrique, $id_parent);
	  }

	  debut_droite();

	  debut_cadre_relief($ze_logo);

	  montre_naviguer($id_rubrique, $titre, $descriptif, $ze_logo, $flag_editable);

	  if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "rubriques");
	  }

/// Mots-cles
	    if (lire_meta("articles_mots") != 'non' AND $id_rubrique > 0) {
		echo "\n<p>";
		formulaire_mots('rubriques', $id_rubrique,  $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
	    }


	    if (strlen($texte) > 1) {
	      echo "\n<p><div align='justify'><font size=3 face='Verdana,Arial,Sans,sans-serif'>", justifier(propre($texte)), "&nbsp;</font></div>";
	    }


//
// Langue de la rubrique
//

	    langue_naviguer($id_rubrique, $id_parent, $flag_editable);
	    
	    fin_cadre_relief();


//
// Gerer les modifications...
//

	    contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);

	    fin_page();
}

function infos_naviguer($id_rubrique, $statut)
{
	global $connect_statut, $connect_toutes_rubriques;

	if ($id_rubrique > 0) {
		debut_boite_info();
		echo "<CENTER>";
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_numero_rubrique')."</B></FONT>";
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_rubrique</B></FONT>";
		echo "</CENTER>";
	
		voir_en_ligne ('rubrique', $id_rubrique, $statut);
	
		if ($connect_statut == "0minirezo" && acces_rubrique($id_rubrique)) {
			list($id_parent) = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			if (!$id_parent) {
			  list($n) = spip_fetch_array(spip_query("SELECT COUNT(*) " .
								 critere_statut_controle_forum('prop', $id_rubrique)));
			  if ($n)
			    icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $n)),
		"controle_forum.php3?id_rubrique=$id_rubrique", "suivi-forum-24.gif", "");
			}
		}
		fin_boite_info();

		$res = spip_query("SELECT DISTINCT A.nom, A.id_auteur FROM  spip_auteurs AS A, spip_auteurs_rubriques AS B WHERE A.id_auteur=B.id_auteur AND id_rubrique=$id_rubrique  AND A.statut='0minirezo'");
		if (spip_num_rows($res))
		  {
			echo '<br />';
			debut_cadre_relief("fiche-perso-24.gif", false, '', _T('info_administrateurs'));
			while ($row = spip_fetch_array($res)) {
			  $id = $row['id_auteur'];

			  echo 
				http_img_pack('admin-12.gif','',''),
				$logo,
				" <a href='auteurs_edit.php3?id_auteur=",
				$id,
				"'>",
				extraire_multi($row['nom']),
				'</a><br />';
			}
			fin_cadre_relief();
		  }
	}
}

function logo_naviguer($id_rubrique)
{
		if ($id_rubrique)
			afficher_boite_logo('rub', 'id_rubrique', $id_rubrique,
			_T('logo_rubrique')." ".aide ("rublogo"), _T('logo_survol'));
		else
			afficher_boite_logo('rub', 'id_rubrique', 0,
			_T('logo_standard_rubrique')." ".aide ("rublogo"),
			_T('logo_survol'));
}

function raccourcis_naviguer($id_rubrique, $id_parent)
{
	global $connect_statut;

	debut_raccourcis();
	
	icone_horizontale(_T('icone_tous_articles'), "articles_page.php3", "article-24.gif");
	
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1")) > 0) {
		if ($id_rubrique > 0)
			icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$id_rubrique&new=oui", "article-24.gif","creer.gif");
	
		$activer_breves = lire_meta("activer_breves");
		if ($activer_breves != "non" AND $id_parent == "0" AND $id_rubrique != "0") {
			icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$id_rubrique&new=oui", "breve-24.gif","creer.gif");
		}
	}
	else {
		if ($connect_statut == '0minirezo') {
			echo "<p>"._T('info_creation_rubrique');
		}
	}
	
	fin_raccourcis();
}

function langue_naviguer($id_rubrique, $id_parent, $flag_editable)
{

if ($id_rubrique>0 AND lire_meta('multi_rubriques') == 'oui' AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0) AND $flag_editable) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$langue_rubrique = $row['lang'];
	$langue_choisie_rubrique = $row['langue_choisie'];
	if ($id_parent) {
		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
		$langue_parent = $row[0];
	}
	else $langue_parent = lire_meta('langue_site');

	debut_cadre_enfonce('langues-24.gif');
	echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' class='serif2'>";
	echo bouton_block_invisible('languesrubrique');
	echo "<B>";
	echo _T('titre_langue_rubrique');
	echo "&nbsp; (".traduire_nom_langue($langue_rubrique).")";
	echo "</B>";
	echo "</TD></TR></TABLE>";

	echo debut_block_invisible('languesrubrique');
	echo "<div class='verdana2' align='center'>";
	echo menu_langues('changer_lang', $langue_rubrique, '', $langue_parent);
	echo "</div>\n";
	echo fin_block();

	fin_cadre_enfonce();
 }
}

function contenu_naviguer($id_rubrique, $id_parent, $ze_logo,$flag_editable) {

global $clean_link, $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_left, $spip_lang_right;

///// Afficher les rubriques 
afficher_enfant_rub($id_rubrique, $flag_editable);


//echo "<div align='$spip_lang_left'>";


//////////  Vos articles en cours de redaction
/////////////////////////

echo "<P>";

//
// Verifier les boucles a mettre en relief
//

$relief = spip_num_rows(spip_query("SELECT id_article FROM spip_articles AS articles WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1"));

if (!$relief) {
	$relief = spip_num_rows(spip_query("SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop') LIMIT 1"));
 }

if (!$relief AND lire_meta('activer_syndic') != 'non') {
	$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1"));
 }

if (!$relief AND lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') LIMIT 1"));
 }


if ($relief) {
	echo "<p>";
	debut_cadre_couleur();
	echo "<div class='verdana2' style='color: black;'><b>"._T('texte_en_cours_validation')."</b></div><p>";

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),
		"WHERE id_rubrique='$id_rubrique' AND statut='prop' ORDER BY date DESC");

	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop') ORDER BY date_heure DESC";
	afficher_breves(_T('info_breves_valider'), $query, true);

	//
	// Les sites references a valider
	//
	if (lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('info_site_valider'), "SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('avis_sites_syndiques_probleme'),
			"SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') AND statut='publie' ORDER BY nom_site");
	}

	// Les articles syndiques en attente de validation
	if ($id_rubrique == 0 AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_syndic_articles AND statut='dispo'");
		if (($row = spip_fetch_array($result)) AND $row['compte'])
			echo "<br><small><a href='sites_tous.php3'>".$row['compte']." "._T('info_liens_syndiques_1')."</a> "._T('info_liens_syndiques_2')."</small>";
	}

	fin_cadre_couleur();
}

//////////  Les articles en cours de redaction
/////////////////////////

	if ($connect_statut == "0minirezo" AND $options == 'avancees') {
	  afficher_articles(_T('info_tous_articles_en_redaction'),
		"WHERE statut='prepa' AND id_rubrique='$id_rubrique' ORDER BY date DESC");
	}


//////////  Les articles publies
/////////////////////////

	afficher_articles(_T('info_tous_articles_presents'),
			  "WHERE statut='publie' AND id_rubrique='$id_rubrique' ORDER BY date DESC", true);



	if ($id_rubrique > 0){
	  echo "<div align='$spip_lang_right'>";
	  icone(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$id_rubrique&new=oui", "article-24.gif", "creer.gif");
	  echo "</div><p>";
	}

//// Les breves

	afficher_breves(_T('icone_ecrire_nouvel_article'), "SELECT * FROM spip_breves WHERE id_rubrique='$id_rubrique' AND statut != 'prop' AND statut != 'prepa' ORDER BY date_heure DESC");

	$activer_breves=lire_meta("activer_breves");

	if ($id_parent == "0" AND $id_rubrique != "0" AND $activer_breves!="non"){
	  echo "<div align='$spip_lang_right'>";
	  icone(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$id_rubrique&new=oui", "breve-24.gif", "creer.gif");
	  echo "</div><p>";
	}

//// Les sites references

	if (lire_meta("activer_sites") == 'oui') {
	  include_ecrire("inc_sites.php3");
	  afficher_sites(_T('titre_sites_references_rubrique'), "SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut!='refuse' AND statut != 'prop' AND syndication NOT IN ('off','sus') ORDER BY nom_site");

	  $proposer_sites=lire_meta("proposer_sites");
	  if ($id_rubrique > 0 AND ($flag_editable OR $proposer_sites > 0)) {
		$link = new Link('sites_edit.php3');
		$link->addVar('id_rubrique', $id_rubrique);
		$link->addVar('target', 'sites.php3');
		$link->addVar('redirect', $clean_link->getUrl());
	
		echo "<div align='$spip_lang_right'>";
		icone(_T('info_sites_referencer'), $link->getUrl(), "site-24.gif", "creer.gif");
		echo "</div><p>";
	  }
	}

/// Documents associes a la rubrique
	if ($id_rubrique > 0) {
	# modifs de la description d'un des docs joints
	  if ($flag_editable) maj_documents($id_rubrique, 'rubrique');
	  afficher_documents_non_inclus($id_rubrique, "rubrique", $flag_editable);
	}

////// Supprimer cette rubrique (si vide)

	supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);
}

function maj_naviguer($action, $id_rubrique, $id_parent, $titre, $texte, $descriptif, $flag_editable)
{
	if ($action == 'supprimer') {
		  spip_query("DELETE FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
		  $id_rubrique = $id_parent;
		  unset($_POST['id_parent']);
		  $_POST['id_rubrique'] = $id_rubrique;
		  $GLOBALS['clean_link'] = new Link();
		}
	  // pour le cas 'calculer_rubriques' (retour de spip_image),
	  // i.e. document/logo ajoute/supprime/tourne
	  // suffit seulement de faire le calculer_rubriques() final
	  // mais il faudrait s'en dispenser dans le cas "tourne" etc

	elseif ($action !='calculer_rubriques') {
		if ($action =='creer') {
			$id_rubrique = spip_abstract_insert("spip_rubriques", 
				"(titre, id_parent)",
				"('"._T('item_nouvelle_rubrique')."', '$id_parent')");

	// Modifier le lien de base pour qu'il prenne en compte le nouvel id
			unset($_POST['id_parent']);
			$_POST['id_rubrique'] = $id_rubrique;
			$GLOBALS['clean_link'] = new Link();
		}
		// alors action = modifier
		else {
	  // si c'est une rubrique-secteur contenant des breves, ne deplacer
	  // que si $confirme_deplace == 'oui'

			if (($GLOBALS['confirme_deplace'] == 'oui') AND
			    (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_breves WHERE id_rubrique='$id_rubrique' LIMIT 1")) > 0))
				$id_parent = 0;
		}
		if ($GLOBALS['champs_extra']) {
			  include_ecrire("inc_extra.php3");
			  $GLOBALS['champs_extra'] = ", extra = '".addslashes(extra_recup_saisie("rubriques"))."'";
		}
		spip_query("UPDATE spip_rubriques SET " .
		   (acces_rubrique($id_parent) ? "id_parent='$id_parent'," : "") . "
titre='" . addslashes($titre) ."',
descriptif='" . addslashes($descriptif) . "',
texte='" . addslashes($texte) . "'
$champs_extra
WHERE id_rubrique=$id_rubrique");
		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
			marquer_indexer('rubrique', $id_rubrique);
		}
	}

	// toute action entraine ceci:
	calculer_rubriques();

	  // invalider et reindexer
	if ($GLOBALS['invalider_caches']) {
			include_ecrire ("inc_invalideur.php3");
			suivre_invalideur("id='id_rubrique/$id_rubrique'");
	}
//
// Appliquer le changement de langue
//
	if ($GLOBALS['changer_lang']
		    AND $id_rubrique>0
		    AND lire_meta('multi_rubriques') == 'oui'
		    AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0)) {
		  if ($changer_lang != "herit")
			spip_query("UPDATE spip_rubriques SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_rubrique=$id_rubrique");
		  else {
			if ($id_parent == 0)
				$langue_parent = lire_meta('langue_site');
			else {
				$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
				$langue_parent = $row['lang'];
			}
			spip_query("UPDATE spip_rubriques SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_rubrique=$id_rubrique");
		  }
	}
	calculer_langues_rubriques();
	return $id_rubrique;
}

function montre_naviguer($id_rubrique, $titre, $descriptif, $logo, $flag_editable)
{
  global $spip_lang_right, $spip_lang_left;

  echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
  echo "<tr width='100%'><td width='100%' valign='top'>";
  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
		http_img_pack("admin-12.gif",'', "width='12' height='12'",
			      _T('info_administrer_rubrique'))) .
	     $titre);
  echo "</td>";

  if ($id_rubrique > 0 AND $flag_editable) {
	echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
	echo "<td  align='$spip_lang_right' valign='top'>";
	icone(_T('icone_modifier_rubrique'), "rubriques_edit.php3?id_rubrique=$id_rubrique&retour=nav", $logo, "edit.gif");
	echo "</td>";
}
  echo "</tr>\n";

  if (strlen($descriptif) > 1) {
	echo "<tr><td>\n";
	echo "<div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa;'>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	echo propre($descriptif."~");
	echo "</font>";
	echo "</div></td></tr>\n";
  }
  echo "</table>\n";
}


function supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable)
{
  if (($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable) {
	$link = "naviguer.php3?id_rubrique=$id_rubrique&action=supprimer&id_parent=$id_parent";

	echo "<p><div align='center'>";
	icone(_T('icone_supprimer_rubrique'), $link, $ze_logo, "supprimer.gif");
	echo "</div><p>";
 }
}

?>
