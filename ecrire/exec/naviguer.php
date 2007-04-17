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

include_spip('inc/presentation');
include_spip('inc/forum');

// http://doc.spip.org/@exec_naviguer_dist
function exec_naviguer_dist()
{
	global $spip_display;

	$cherche_mot = _request('cherche_mot');
	$id_rubrique = intval(_request('id_rubrique'));
	$select_groupe = intval(_request('select_groupe'));

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'"));
	if ($row) {
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$statut = $row['statut'];
		$extra = $row["extra"];
		$lang = $row["lang"];
	} elseif ($id_rubrique)
	      {include_spip('minipres');
		echo minipres();
		exit;
	      }

	else $lang = $statut = $titre = $descriptif = $texte = $extra = $id_parent='';

	if ($id_rubrique ==  0) $ze_logo = "racine-site-24.gif";
	else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

	$flag_editable = autoriser('publierdans','rubrique',$id_rubrique);

	pipeline('exec_init',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(($titre ? ("&laquo; ".textebrut(typo($titre))." &raquo;") :
		    _T('titre_naviguer_dans_le_site')),
		   "naviguer",
		   "rubriques",
		   $id_rubrique);

	  debut_grand_cadre();

	  if ($id_rubrique  > 0) echo afficher_hierarchie($id_parent);
	  else $titre = _T('info_racine_site').": ". $GLOBALS['meta']["nom_site"];
	  fin_grand_cadre();

	  changer_typo($lang);
	  
	  if (!autoriser('voir','rubrique',$id_rubrique)){
			echo "<strong>"._T('avis_acces_interdit')."</strong>";
			fin_page();
			exit;
	  }

	  debut_gauche();

	if ($spip_display != 4) {

		infos_naviguer($id_rubrique, $statut, $ze_logo);

		//
		// Logos de la rubrique
		//
		if ($flag_editable AND ($spip_display != 4)) {
			$iconifier = charger_fonction('iconifier', 'inc');
			echo $iconifier('id_rubrique', $id_rubrique, 'naviguer');
		}
	}

		echo pipeline('affiche_gauche',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

		//
		// Afficher les boutons de creation d'article et de breve
		//
	if ($spip_display != 4) {
		raccourcis_naviguer($id_rubrique, $id_parent);
	}
		

		creer_colonne_droite();
		echo pipeline('affiche_droite',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));	  
		debut_droite();

	  debut_cadre_relief($ze_logo);

	  montre_naviguer($id_rubrique, $titre, $descriptif, $ze_logo, $flag_editable);

	  if ($extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "rubriques");
	  }

/// Mots-cles
	if ($id_rubrique > 0) {
	      $editer_mot = charger_fonction('editer_mot', 'inc');
	      echo $editer_mot('rubrique', $id_rubrique,  $cherche_mot,  $select_groupe, $flag_editable);
	}


	if (strlen($texte) > 1) {
		echo "\n<div class='verdana1 spip_medium'>", justifier(propre($texte)), "</div>";
	}
	
	langue_naviguer($id_rubrique, $id_parent, $flag_editable);
	    
	fin_cadre_relief();

	echo afficher_enfant_rub($id_rubrique, autoriser('creerrubriquedans','rubrique',$id_rubrique), false);

	echo contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);


/// Documents associes a la rubrique
	if ($id_rubrique > 0) {

		echo naviguer_doc($id_rubrique, "rubrique", 'naviguer', $flag_editable);
	}

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));	  


////// Supprimer cette rubrique (si vide)

	echo bouton_supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);

	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@infos_naviguer
function infos_naviguer($id_rubrique, $statut, $ze_logo)
{
	if ($id_rubrique > 0) {
		$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>"
		  .  _T('titre_numero_rubrique')
		  . "<br /><span class='spip_xx-large'>"
		  . $id_rubrique
		  . '</span></div>';

		debut_boite_info();
		echo $res;
		voir_en_ligne ('rubrique', $id_rubrique, $statut);
	
		if (autoriser('publierdans','rubrique',$id_rubrique)) {
			$id_parent = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			if (!$id_parent['id_parent']) {
			  list($from, $where) = critere_statut_controle_forum('prop', $id_rubrique);
			  $n = spip_num_rows(spip_query("SELECT id_forum FROM $from" .($where ? (" WHERE $where") : '')));

			  if ($n)
			    icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $n)), generer_url_ecrire("controle_forum","id_rubrique=$id_rubrique"), "suivi-forum-24.gif", "");
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
			    " <a href='", generer_url_ecrire('auteur_infos', "id_auteur=$id"),
				"'>",
				extraire_multi($row['nom']),
				'</a><br />';
			}
			fin_cadre_relief();
		  }
	}
}


// http://doc.spip.org/@raccourcis_naviguer
function raccourcis_naviguer($id_rubrique, $id_parent)
{
	global $connect_statut;

	$res = icone_horizontale(_T('icone_tous_articles'), generer_url_ecrire("articles_page"), "article-24.gif", '',false);
	
	$n = spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"));
	if ($n) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $res .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif","creer.gif", false);
	
		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent))) {
		  $res .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif","creer.gif", false);
		}
	}
	else {
		if ($connect_statut == '0minirezo') {
			$res .= "<br />"._T('info_creation_rubrique');
		}
	}
	
	echo bloc_des_raccourcis($res);
}

// http://doc.spip.org/@langue_naviguer
function langue_naviguer($id_rubrique, $id_parent, $flag_editable)
{

if ($id_rubrique>0 AND $GLOBALS['meta']['multi_rubriques'] == 'oui' AND ($GLOBALS['meta']['multi_secteurs'] == 'non' OR $id_parent == 0) AND $flag_editable) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$langue_rubrique = $row['lang'];
	$langue_choisie_rubrique = $row['langue_choisie'];
	if ($id_parent) {
		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
		$langue_parent = $row['lang'];
	} 
	if (!$langue_parent)
		$langue_parent = $GLOBALS['meta']['langue_site'];
	if (!$langue_rubrique)
		$langue_rubrique = $langue_parent;

	debut_cadre_enfonce('langues-24.gif');
	echo "<table border='0' cellspacing='0' cellpadding='3' width='100%'><tr><td style='background-color: #eeeecc' class='serif2'>";
	echo bouton_block_invisible('languesrubrique');
	echo "<b>";
	echo _T('titre_langue_rubrique');
	echo "&nbsp; (".traduire_nom_langue($langue_rubrique).")";
	echo "</b>";
	echo "</td></tr></table>";

	echo debut_block_invisible('languesrubrique');
	echo "<div class='verdana2' style='text-align: center;'>";
	echo menu_langues('changer_lang', $langue_rubrique, '', $langue_parent, redirige_action_auteur('instituer_langue_rubrique', "$id_rubrique-$id_parent","naviguer","id_rubrique=$id_rubrique"));
	echo "</div>\n";
	echo fin_block();

	fin_cadre_enfonce();
 }
}

// http://doc.spip.org/@contenu_naviguer
function contenu_naviguer($id_rubrique, $id_parent) {

	global  $connect_toutes_rubriques, $spip_lang_right, $spip_lang_left;

//
// Verifier les boucles a mettre en relief
//

	$relief = spip_num_rows(spip_query("SELECT id_article FROM spip_articles AS articles WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1"));

	if (!$relief) {
		$relief = spip_num_rows(spip_query("SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop') LIMIT 1"));
 }

	if (!$relief AND $GLOBALS['meta']['activer_syndic'] != 'non') {
		$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1"));
 }

	if (!$relief AND $GLOBALS['meta']['activer_syndic'] != 'non' AND $connect_toutes_rubriques) {
		$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') AND statut='publie' LIMIT 1"));
	}


	$res = '';

	if ($relief) {

	$res .= debut_cadre_couleur('',true);
	$res .= "<div class='verdana2' style='color: black;'><b>"._T('texte_en_cours_validation')."</b></div>";

	//
	// Les articles a valider
	//
	$res .= afficher_articles(_T('info_articles_proposes'),	array('WHERE' => "id_rubrique='$id_rubrique' AND statut='prop'", 'ORDER BY' => "date DESC"));

	//
	// Les breves a valider
	//
	$res .= afficher_breves('<b>' . _T('info_breves_valider') . '</b>', array("FROM" => 'spip_breves', 'WHERE' => "id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop')", 'ORDER BY' => "date_heure DESC"), true);


	//
	// Les sites references a valider
	//
	if ($GLOBALS['meta']['activer_sites'] != 'non') {
		include_spip('inc/sites_voir');
		$res .= afficher_sites('<b>' . _T('info_site_valider') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "id_rubrique='$id_rubrique' AND statut='prop'", 'ORDER BY' => "nom_site"));
	}

	//
	// Les sites a probleme
	//
	if ($GLOBALS['meta']['activer_sites'] != 'non' AND $connect_toutes_rubriques) {
		include_spip('inc/sites_voir');
		$res .= afficher_sites('<b>' . _T('avis_sites_syndiques_probleme') . '</b>', array('FROM' => 'spip_syndic', 'WHERE' => "id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') AND statut='publie'", 'ORDER BY' => "nom_site"));
	}

	// Les articles syndiques en attente de validation
	if ($id_rubrique == 0 AND $connect_toutes_rubriques) {
		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_syndic_articles WHERE statut='dispo'"));
		if ($cpt = $cpt['n'])
			$res .= "<br /><small><a href='" .
				generer_url_ecrire("sites_tous") .
				"' style='color: black;'>" .
				$cpt .
				" " .
				_T('info_liens_syndiques_1') .
				" " .
				_T('info_liens_syndiques_2') .
				"</a></small>";
	}

	$res .= fin_cadre_couleur(true);
	}

//////////  Les articles en cours de redaction
/////////////////////////

	  $res .= afficher_articles(_T('info_tous_articles_en_redaction'), array("WHERE" => "statut='prepa' AND id_rubrique='$id_rubrique'", 'ORDER BY' => "date DESC"));


//////////  Les articles publies
/////////////////////////

	  $res .= afficher_articles(_T('info_tous_articles_presents'), array("WHERE" => "statut='publie' AND id_rubrique='$id_rubrique'", 'ORDER BY' => "date DESC"));

	if (autoriser('creerarticledans','rubrique',$id_rubrique)){
	  $res .= icone(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif", "creer.gif", $spip_lang_right, 'non');
	}

//// Les breves

	$res .= afficher_breves('<b>' . _T('icone_ecrire_nouvel_article') . '</b>', array("FROM" => 'spip_breves', 'WHERE' => "id_rubrique='$id_rubrique' AND statut != 'prop' AND statut != 'prepa'", 'ORDER BY' => "date_heure DESC"));


	if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent))){
	  $res .= icone(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif", "creer.gif",$spip_lang_right, 'non');
	}

//// Les sites references

	if ($GLOBALS['meta']["activer_sites"] == 'oui') {
		include_spip('inc/sites_voir');
		$res .= '<br />' . afficher_sites('<b>' . _T('titre_sites_references_rubrique') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "id_rubrique='$id_rubrique' AND statut!='refuse' AND statut != 'prop' AND syndication NOT IN ('off','sus')", 'ORDER BY' => 'nom_site'));

		if ($id_rubrique > 0
		AND (autoriser('creersitedans','rubrique',$id_rubrique))) {
			$res .= icone(_T('info_sites_referencer'), generer_url_ecrire('sites_edit', "id_rubrique=$id_rubrique"), "site-24.gif", "creer.gif",$spip_lang_right, 'non');
		}
	}
	return $res;
}

// http://doc.spip.org/@naviguer_doc
function naviguer_doc ($id, $type = "article", $script, $flag_editable) {
	global $spip_lang_left, $spip_lang_right;

	if ($GLOBALS['meta']["documents_$type"]!='non' AND $flag_editable) {

	  $joindre = charger_fonction('joindre', 'inc');
	  $res = debut_cadre_relief("image-24.gif", true, "", _T('titre_joindre_document'))
	  . $joindre($script, "id_$type=$id", $id, _T('info_telecharger_ordinateur'), 'document', $type,'',0,generer_url_ecrire("documenter","id_rubrique=$id&type=$type",true))
	  . fin_cadre_relief(true);

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

	  if ($GLOBALS['browser_name']!="MSIE") {
		$res = "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td>&nbsp;</td><td width='50%' style='text-align: $spip_lang_left;'>\n$res</td></tr></table>";
	  }

	  $res .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>\n";
    $res .= <<<EOF
    <script type='text/javascript'>
    $(".form_upload").async_upload(async_upload_portfolio_documents);
    </script>
EOF;
	} else $res ='';

	$documenter = charger_fonction('documenter', 'inc');

	return "<div id='portfolio'>".$documenter($id, $type, 'portfolio', $flag_editable)."</div>"
	."<div id='documents'>". $documenter($id, $type, 'documents', $flag_editable)."</div>"
	. $res;
}

// http://doc.spip.org/@montre_naviguer
function montre_naviguer($id_rubrique, $titre, $descriptif, $logo, $flag_editable)
{
  global $spip_lang_right, $spip_lang_left;

  echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
  echo "<tr><td style='width: 100%' valign='top'>";
  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
		http_img_pack("admin-12.gif",'', "width='12' height='12'",
			      _T('info_administrer_rubrique'))) .
	     $titre);
  echo "</td>";

  if ($id_rubrique > 0 AND $flag_editable) {
	echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
	echo "<td  valign='top'>", icone_inline(_T('icone_modifier_rubrique'), generer_url_ecrire("rubriques_edit","id_rubrique=$id_rubrique&retour=nav"), $logo, "edit.gif", $spip_lang_right), "</td>";
}
  echo "</tr>\n";

  if (strlen($descriptif) > 1) {
	echo "<tr><td align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; ' class='verdana1 spip_small'>", propre($descriptif."~"), "</td></tr>\n";
  }
  echo "</table>\n";
}

// http://doc.spip.org/@tester_rubrique_vide
function tester_rubrique_vide($id_rubrique) {
	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_rubriques WHERE id_parent='$id_rubrique' LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_documents_rubriques WHERE id_rubrique='$id_rubrique' LIMIT 1"));
	if ($n['n'] > 0) return false;

	return true;
}

// http://doc.spip.org/@bouton_supprimer_naviguer
function bouton_supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable)
{
	if (($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable) {

	  return "<br /><div class='centered'>" . icone_inline(_T('icone_supprimer_rubrique'), redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "supprimer.gif") . "</div>";
	}
}

?>
