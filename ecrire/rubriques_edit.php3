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


function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $connect_toutes_rubriques;
	global $i;
	global $couleur_claire, $spip_lang_left;
	global $browser_name, $browser_version;

	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY 0+titre,titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		$statut_rubrique = $row['statut'];
		$lang_rub = $row['lang'];
		$langue_choisie_rub = $row['langue_choisie'];
		$style = "";
		$espace = "";

		if ($my_rubrique != $id_rubrique){

			if (eregi("mozilla", $browser_name)) {
				//$style .= "padding-$spip_lang_left: 16px; ";
				$style .= "margin-$spip_lang_left: ".($i*16)."px;";
			} else {
				for ($count = 0; $count <= $i; $count ++) $espace .= "&nbsp;&nbsp;&nbsp;&nbsp;";
			}

			if ($i > 3) $style .= "color: #666666;";
			if ($i > 4) $style .= "font-style: italic;";
			if ($i < 3) $style .= "font-weight:bold; ";
			if ($i==1) {
				$style .= "background-image: url(" . _DIR_IMG_PACK . 'secteur-12.gif);';
				$style .= "background-color: $couleur_claire;";
				$style .= "font-weight: bold;";
			}
			else if ($i==2) {
				$style .= "border-bottom: 1px solid $couleur_claire;";
				$style .= "font-weight: bold;";
			}
			else {
			}

			$titre = couper(supprimer_tags(typo(extraire_multi($titre)))." ", 50);
			if ($statut_rubrique!='publie') $titre = "($titre)";
			if (lire_meta('multi_rubriques') == 'oui' AND $langue_choisie_rub == "oui") $titre = $titre." [".traduire_nom_langue($lang_rub)."]";

			$selec_rub = "selec_rub";
			if ($browser_name == "MSIE" AND floor($browser_version) == "5") $selec_rub = ""; // Bug de MSIE MacOs 9.0

			if (acces_rubrique($my_rubrique)) {
				echo "<option".mySel($my_rubrique,$id_parent)." class='$selec_rub' style=\"$style\">$espace".supprimer_tags($titre)."\n";
			}
			enfant($my_rubrique);
		}

	}
	$i=$i-1;
}


if ($new == "oui") {
	if (($connect_statut=='0minirezo') AND acces_rubrique($id_parent)) {
		$id_parent = intval($id_parent);
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$descriptif = "";
		$texte = "";
	}
	else {
		echo _T('avis_acces_interdit');
		exit;
	}
}
else {
	$query = "SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$id_secteur = $row['id_secteur'];
		$extra = $row["extra"];
	}
}

debut_page(_T('info_modifier_titre', array('titre' => $titre)), "documents", "rubriques");

if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";

if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
else {
	$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_parent'";
 	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$parent_parent=$row['id_parent'];
	}
	if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
	else $logo_parent = "rubrique-24.gif";
}



debut_grand_cadre();

afficher_hierarchie($id_parent);

fin_grand_cadre();

debut_gauche();
//////// parents



debut_droite();

debut_cadre_formulaire();

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";

if ($id_rubrique) icone(_T('icone_retour'), "naviguer.php3?id_rubrique=$id_rubrique", $ze_logo, "rien.gif");
else icone(_T('icone_retour'), "naviguer.php3?id_rubrique=$id_parent", $ze_logo, "rien.gif");

echo "</td>";
echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
echo "<td width='100%'>";
echo _T('info_modifier_rubrique');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

if ($id_rubrique>0)
	echo "<FORM ACTION='naviguer.php3?id_rubrique=$id_rubrique' METHOD='post'>";
 else
   {
	echo "<FORM ACTION='naviguer.php3' METHOD='post'>";
   }

$titre = entites_html($titre);

echo _T('entree_titre_obligatoire');
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40' $onfocus><P>";


debut_cadre_couleur("$logo_parent", false, '', _T('entree_interieur_rubrique').aide ("rubrub"));

// Integrer la recherche de rubrique au clavier
//echo "<script language='JavaScript' type='text/javascript' src='filtery.js'></script>\n";
//echo "<input type='text' size='10' style='font-size: 90%; width: 15%;' onkeyup=\"filtery(this.value,this.form.id_parent);\" onChange=\"filtery(this.value,this.form.id_parent);\"> ";


if ($spip_display == 4) {
	echo "<SELECT NAME='id_parent' style='font-size: 90%; width:80%; font-face:verdana,arial,helvetica,sans-serif; max-height: 24px;' SIZE=1>\n";
	
	if ($connect_toutes_rubriques) {
	  echo "<OPTION".mySel("0",$id_parent). http_style_background('racine-site-12.gif',  "$spip_lang_left no-repeat; background-color:$couleur_foncee; padding-$spip_lang_left: 16px; font-weight:bold; color:white") .'>'._T('info_racine_site')."\n";
	} else {
		echo "<OPTION".mySel("0",$id_parent).">"._T('info_non_deplacer')."\n";
	}
	
	if (lire_meta('multi_rubriques') == 'oui') echo " [".traduire_nom_langue(lire_meta('langue_site'))."]";
	
	// si le parent ne fait pas partie des rubriques restreintes, modif impossible
	if (acces_rubrique($id_parent)) {
		enfant(0);
	}
	echo "</SELECT>\n";
} else {

	$query = spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_parent");
	if ($row = spip_fetch_array($query)) {
		$titre_parent = entites_html(typo($row["titre"])); 
	} else {
		$titre_parent = entites_html(_T("info_racine_site"));
	}
	
	
	echo "<table width='100%'><tr width='100%'><td width='45'>";
	echo "<a href='#' onClick=\"javascript:if(findObj('selection_rubrique').style.display=='none') {charger_id_url_si_vide('ajax_page.php?fonction=aff_parent&id_rubrique=$id_parent&exclus=$id_rubrique','selection_rubrique');} else {findObj('selection_rubrique').style.display='none';} return true;\"><img src='img_pack/loupe.png' style='border: 0px; vertical-align: middle;' /></a> ";
	echo "<img src='img_pack/searching.gif' id='img_selection_rubrique' style='visibility: hidden;'>";
	echo "</td><td>";
	echo "<input type='text' id='titreparent' name='titreparent' disabled='disabled' class='forml' value=\"$titre_parent\" />";
	echo "<input type='hidden' id='id_parent' name='id_parent' value='$id_parent' />";
	echo "</td></tr></table>";
	
	echo "<div id='selection_rubrique' style='display: none;'></div>";

}

// si c'est une rubrique-secteur contenant des breves, ne pas proposer de deplacer
$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique='$id_rubrique'";
$row = spip_fetch_array(spip_query($query));
$contient_breves = $row['cnt'];
if ($contient_breves > 0) {
        $scb = ($contient_breves>1? 's':'');
	echo "<div><font size='2'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace'><label for='confirme-deplace'>&nbsp;"._T('avis_deplacement_rubrique', array('contient_breves' => $contient_breves, 'scb' => $scb))."</font></label></div>\n";
}
fin_cadre_couleur();

echo "<P>";


if ($options == "avancees" OR $descriptif) {
	echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
	echo _T('entree_contenu_rubrique')."<BR>";
	echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo $descriptif;
	echo "</TEXTAREA><P>\n";
}
else {
	echo "<INPUT TYPE='Hidden' NAME='descriptif' VALUE=\"".entites_html($descriptif)."\" />";
}

echo "<B>"._T('info_texte_explicatif')."</B>";
echo aide ("raccourcis");
echo "<BR><TEXTAREA NAME='texte' ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
echo $texte;
echo "</TEXTAREA>\n";

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($extra, 'rubriques', $id_secteur);
	}

echo "<input type='hidden' name='action' value='",
	  (($new == "oui") ? 'creer' : 'modifier'),
	  "' />";

echo "\n<p align='right'><input type='submit' value='"._T('bouton_enregistrer')."' CLASS='fondo' />\n</p></form>";

fin_cadre_formulaire();

fin_page();

?>
