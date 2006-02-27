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

include_ecrire("inc_presentation");
include_ecrire("inc_rubriques");
include_ecrire ("inc_logos");
include_ecrire ("inc_mots");
include_ecrire ("inc_date");
include_ecrire ("inc_abstract_sql");
include_spip("inc/indexation");

function afficher_breves_voir($id_breve, $changer_lang, $cherche_mot, $supp_mot, $nouv_mot )
{
	global $champs_extra, $options, $connect_statut, $les_notes;

$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
$result = spip_query($query);

while ($row = spip_fetch_array($result)) {
	$id_breve=$row['id_breve'];
	$date_heure=$row['date_heure'];
	$titre_breve=$row['titre'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$extra=$row['extra'];
	$lien_titre=$row['lien_titre'];
	$lien_url=$row['lien_url'];
	$statut=$row['statut'];
	$id_rubrique=$row['id_rubrique'];
}

$flag_editable = (($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) OR $statut == 'prop');


debut_page("&laquo; $titre_breve &raquo;", "documents", "breves");

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();

debut_gauche();

debut_boite_info();

echo "<CENTER>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='1'><B>"._T('info_gauche_numero_breve')."&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='6'><B>$id_breve</B></FONT>";
echo "</CENTER>";

voir_en_ligne ('breve', $id_breve, $statut);

fin_boite_info();


//////////////////////////////////////////////////////
// Logos de la breve
//

if ($id_breve>0 AND ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)))
	afficher_boite_logo('breve', 'id_breve', $id_breve,
			    _T('logo_breve').aide ("breveslogo"),
			    _T('logo_survol'), 'breves_voir'); 


debut_raccourcis();
icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui"), "breve-24.gif","creer.gif");
fin_raccourcis();

debut_droite();

debut_cadre_relief("breve-24.gif");
echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
echo "<TR><td class='serif'>";



echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
gros_titre($titre);
echo "</td>";

if ($flag_editable) {
	echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_modifier_breve'), generer_url_ecrire("breves_edit","id_breve=$id_breve&retour=nav"), "breve-24.gif", "edit.gif");
	echo "</td>";
}
echo "</tr></table>\n";

if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {

	if ($statut == 'publie') {	
		echo "<p>";

		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
		        $mois = $regs[2];
		        $jour = $regs[3];
		        $annee = $regs[1];
		}


		debut_cadre_enfonce();
		echo afficher_formulaire_date("breves_voir", "id_breve=$id_breve&options=$options",
					      _T('texte_date_publication_article'), $jour, $mois, $annee);
		fin_cadre_enfonce();	
	}
	else {
		echo "<br /><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='3'><B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}



if ($GLOBALS['meta']["articles_mots"]!='non' AND $flag_editable AND $options == 'avancees') {
  formulaire_mots('breves', $id_breve, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable, generer_url_ecrire("breves_voir", "id_breve=$id_breve"));
}


//
// Langue de la breve
//
if (($GLOBALS['meta']['multi_articles'] == 'oui') AND ($flag_editable)) {
	$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$langue_parent = $row['lang'];

	if ($changer_lang) {
		if ($changer_lang != "herit")
			spip_query("UPDATE spip_breves SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_breve=$id_breve");
		else {
			spip_query("UPDATE spip_breves SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_breve=$id_breve");
		}
	}

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_breves WHERE id_breve=$id_breve"));
	$langue_breve = $row['lang'];
	$langue_choisie_breve = $row['langue_choisie'];

	if ($langue_choisie_breve == 'oui') $herit = false;
	else $herit = true;

	debut_cadre_enfonce('langues-24.gif');

	echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' class='serif2'>";
	echo bouton_block_invisible('languesbreve');
	echo "<B>";
	echo _T('titre_langue_breve');
	echo "&nbsp; (".traduire_nom_langue($langue_breve).")";
	echo "</B>";
	echo "</TD></TR></TABLE>";

	echo debut_block_invisible('languesbreve');
	echo "<center><font face='Verdana,Arial,Sans,sans-serif' size='2'>";
	echo menu_langues('changer_lang', $langue_breve, '', $langue_parent);
	echo "</font></center>\n";
	echo fin_block();

	fin_cadre_enfonce();
}




echo justifier(propre($texte))."\n";

if (strlen($lien_url)>7 AND strlen($lien_titre)>2){
	echo "<P><font size=1>"._T('lien_voir_en_ligne')."</font> <A HREF='$lien_url'><B>".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_titre)>2) {
	echo "<P><font size=1>"._T('lien_nom_site')."</font> ".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_url)>7) {
	echo "<P><font size=1>"._T('info_url_site')."</font> <tt>$lien_url</tt>\n";
}

if ($les_notes) {
	echo "<hr width='70%' height=1 align='left'><font size=2>$les_notes</font>\n";
}

	// afficher les extra
	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra");
		extra_affichage($extra, "breves");
	}

if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
	echo "<div align='right'>";
	
	echo "<table>";
	echo "<td  align='right'>";
	icone(_T('icone_publier_breve'), generer_url_ecrire("breves","id_breve=$id_breve&statut=publie&id_rubrique=$id_rubrique"), "breve-24.gif", "racine-24.gif");
	echo "</td>";
	
	echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_refuser_breve'), generer_url_ecrire("breves","id_breve=$id_breve&statut=refuse&id_rubrique=$id_rubrique"), "breve-24.gif", "supprimer.gif");
	echo "</td>";
	

	echo "</table>";	
	echo "</div>";
	
}	

echo "</TD></TR></TABLE>";

fin_cadre_relief();

//////////////////////////////////////////////////////
// Forums
//

echo "<BR><BR>";

echo "\n<div align='center'>";
 icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id_breve=$id_breve&titre_message=".urlencode($titre) . "&adresse_retour=".urlencode( generer_url_ecrire("breves_voir", "id_breve=$id_breve"))),
       "forum-interne-24.gif", "creer.gif");
echo "</div>";


echo "<P align='left'>";


afficher_forum(spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_breve='$id_breve' AND id_parent=0 ORDER BY date_heure DESC LIMIT 20"), generer_url_ecrire("breves_voir", "id_breve=$id_breve"));

fin_page();
}

function exec_breves_voir_dist()
{
global $id_breve, $id_parent, $texte, $titre, $statut,
  $annee, $mois, $jour, $lien_titre, $lien_url,$champs_extra,
  $new, $modifier_breve, $changer_lang, $cherche_mot, $nouv_mot,$supp_mot,
  $connect_statut, $invalider_caches;

$id_breve = intval($id_breve);

if (($id_breve == 0) AND ($new == "oui")) {
	$id_rubrique = intval($id_parent);
	$langue_new = '';
	if ($row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique")))
		$langue_new = $row["lang"];
	if (!$langue_new) $langue_new = $GLOBALS['meta']['langue_site'];

	$id_breve = spip_abstract_insert("spip_breves",
		"(titre, date_heure, id_rubrique, statut, lang, langue_choisie)", 
		"('"._T('item_nouvelle_breve')."', NOW(), '$id_rubrique', 'refuse', '$langue_new', 'non')");
}

if (strval($titre)!='' AND $modifier_breve) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$lien_titre = addslashes($lien_titre);
	$lien_url = addslashes($lien_url);
	$statut = addslashes($statut); // a ameliorer
	$id_rubrique = intval($id_parent);

	// recoller les champs du extra
	if ($champs_extra) {
		include_ecrire("inc_extra");
		$add_extra = ", extra = '".addslashes(extra_recup_saisie("breves"))."'";
	} else
		$add_extra = '';

	spip_query("UPDATE spip_breves SET titre='$titre', texte='$texte', lien_titre='$lien_titre', lien_url='$lien_url', statut='$statut', id_rubrique='$id_rubrique' $add_extra WHERE id_breve=$id_breve");

	// invalider et reindexer
	if ($invalider_caches) {
		include_ecrire ("inc_invalideur");
		suivre_invalideur("id='id_breve/$id_breve'");
	}
	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer('breve', $id_breve);
	}
	calculer_rubriques();
	
	
	// Changer la langue heritee
	if ($id_rubrique != id_rubrique_old) {
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_breves WHERE id_breve=$id_breve"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];
		
		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
	
			if ($langue_new != $langue_old) {
				spip_query("UPDATE spip_breves SET lang = '$langue_new' WHERE id_breve = $id_breve");
			}
		}
	}
	
}


if ($jour AND $connect_statut == '0minirezo') {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	spip_query("UPDATE spip_breves SET date_heure='$annee-$mois-$jour' WHERE id_breve=$id_breve");
	calculer_rubriques();
}

 afficher_breves_voir($id_breve, $changer_lang, $cherche_mot, $supp_mot, $nouv_mot);
}
?>
