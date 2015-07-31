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
include_ecrire ("inc_documents");
include_spip ('inc_barre');

//
// Gestion des textes trop longs (limitation brouteurs)
//

function coupe_trop_long($texte){	// utile pour les textes > 32ko
	if (strlen($texte) > 28*1024) {
		$texte = str_replace("\r\n","\n",$texte);
		$pos = strpos($texte, "\n\n", 28*1024);	// coupe para > 28 ko
		if ($pos > 0 and $pos < 32 * 1024) {
			$debut = substr($texte, 0, $pos)."\n\n<!--SPIP-->\n";
			$suite = substr($texte, $pos + 2);
		} else {
			$pos = strpos($texte, " ", 28*1024);	// sinon coupe espace
			if (!($pos > 0 and $pos < 32 * 1024)) {
				$pos = 28*1024;	// au pire (pas d'espace trouv'e)
				$decalage = 0; // si y'a pas d'espace, il ne faut pas perdre le caract`ere
			} else {
				$decalage = 1;
			}
			$debut = substr($texte,0,$pos + $decalage); // Il faut conserver l'espace s'il y en a un
			$suite = substr($texte,$pos + $decalage);
		}
		return (array($debut,$suite));
	}
	else
		return (array($texte,''));
}



function chapo_articles_edit($chapo, $articles_chapeau)
{
	global $connect_statut, $spip_ecran;

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
		$chapo = "";
	}

	if ($connect_statut=="0minirezo" AND $virtuel){
		echo "<p><div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>";
		echo "<table width=100% cellspacing=0 cellpadding=0 border=0>";
		echo "<tr><td valign='top'>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo "<B><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></B>";
		echo aide ("artvirt");
		echo "</font>";
		echo "</td>";
		echo "<td width=10>&nbsp;</td>";
		echo "<td valign='top' width='50%'>";
		if (!$virtuel) $virtuel = "http://";
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml' style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'>";
		echo "<input type='hidden' name='changer_virtuel' value='oui'>";
		echo "</td></tr></table>\n";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo _T('texte_article_virtuel_reference');
		echo "</font>";
		echo "</div><p>\n";
	}

	else {
		echo "<HR>";

		if (($articles_chapeau != "non") OR $chapo) {
			if ($spip_ecran == "large") $rows = 8;
			else $rows = 5;
			echo "<B>"._T('info_chapeau')."</B>";
			echo aide ("artchap");
			echo "<BR>"._T('texte_introductif_article')."<BR>";
			echo "<TEXTAREA NAME='chapo' CLASS='forml' ROWS='$rows' COLS='40' wrap=soft>";
			echo $chapo;
			echo "</TEXTAREA><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='chapo' VALUE=\"$chapo\">";
		}
	}
}
//// a TESTER
function formulaire_articles_edit($id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $url, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, $champs_article)
{
 global   $champs_extra, $spip_lang, $options , $spip_ecran;

 $articles_surtitre = $champs_article['articles_surtitre'];
 $articles_soustitre = $champs_article['articles_soustitre'];
 $articles_descriptif = $champs_article['articles_descriptif'];
 $articles_urlref = $champs_article['articles_urlref'];
 $articles_chapeau = $champs_article['articles_chapeau'];
 $articles_ps = $champs_article['articles_ps'];
 $articles_redac = $champs_article['articles_redac'];
 $articles_mots = $champs_article['articles_mots'];
 $articles_modif = $champs_article['articles_modif'];

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	if ($lier_trad) icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$lier_trad"), "article-24.gif", "rien.gif");
	else icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif", "rien.gif");

echo "</td>";
echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
echo "<td width='100%'>";
echo _T('texte_modifier_article');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

echo "<P><HR><P>";

	$titre = entites_html($titre);
	$soustitre = entites_html($soustitre);
	$surtitre = entites_html($surtitre);

	$descriptif = entites_html($descriptif);
	$nom_site = entites_html($nom_site);
	$url_site = entites_html($url_site);
	$chapo = entites_html($chapo);
	$texte = entites_html($texte);
	$ps = entites_html($ps);

	echo generer_url_post_ecrire("articles", ($id_article ? "id_article=$id_article" : ""),'formulaire');

	if ($new == 'oui')
		echo "<INPUT TYPE='Hidden' NAME='new' VALUE='oui'>";

	if ($lier_trad) {
		echo "<INPUT TYPE='Hidden' NAME='lier_trad' VALUE='$lier_trad'>";
		echo "<INPUT TYPE='Hidden' NAME='changer_lang' VALUE='$spip_lang'>";
	}

	if (($options == "avancees" AND $articles_surtitre != "non") OR $surtitre) {
		echo "<B>"._T('texte_sur_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='surtitre' CLASS='forml' VALUE=\"$surtitre\" SIZE='40'><P>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='surtitre' VALUE=\"$surtitre\" >";
	}

	echo _T('texte_titre_obligatoire');
	echo aide ("arttitre");
	echo "<BR><INPUT TYPE='text' NAME='titre' style='font-weight: bold; font-size: 13px;' CLASS='formo' VALUE=\"$titre\" SIZE='40' $onfocus><P>";

	if (($articles_soustitre != "non") OR $soustitre) {
		echo "<B>"._T('texte_sous_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='soustitre' CLASS='forml' VALUE=\"$soustitre\" SIZE='40'><br><br>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='soustitre' VALUE=\"$soustitre\">";
	}


	/// Dans la rubrique....
	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent, titre FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
			$titre_parent = $row["titre"];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}
	debut_cadre_couleur("$logo_parent", false, "", _T('titre_cadre_interieur_rubrique').aide ("artrub"));

	// appel du selecteur de rubrique
	$restreint = ($GLOBALS['statut'] == 'publie');
	echo selecteur_rubrique($id_rubrique, 'article', $restreint);

	fin_cadre_couleur();
	
	if ($new != 'oui') echo "<INPUT TYPE='hidden' NAME='id_rubrique_old' VALUE=\"$id_rubrique\" >";

	if (($options == "avancees" AND $articles_descriptif != "non") OR $descriptif) {
		echo "<P><B>"._T('texte_descriptif_rapide')."</B>";
		echo aide ("artdesc");
		echo "<BR>"._T('texte_contenu_article')."<BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
	}

	if (($options == "avancees" AND $articles_urlref != "non") OR $nom_site OR $url_site) {
		echo _T('entree_liens_sites')."<br />\n";
		echo _T('info_titre')." ";
		echo "<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n";
		echo _T('info_url')." ";
		echo "<input type='text' name='url_site' class='forml' width='40' value=\"$url_site\"/>";
	}

	chapo_articles_edit($chapo, $articles_chapeau);

	if ($spip_ecran == "large") $rows = 28;
	else $rows = 20;

	if (strlen($texte)>29*1024) // texte > 32 ko -> decouper en morceaux
	{
		$textes_supplement = "<br /><font color='red'>"._T('info_texte_long')."</font>\n";
		while (strlen($texte)>29*1024)
		{
			$nombre_textes ++;
			list($texte1,$texte) = coupe_trop_long($texte);

			$textes_supplement .= "<BR />";
			$textes_supplement .= afficher_barre('document.formulaire.texte'.$nombre_textes);
			$textes_supplement .= "<TEXTAREA NAME='texte_plus[$nombre_textes]'".
				" CLASS='formo' ".$GLOBALS['browser_caret']." ROWS='$rows' COLS='40' wrap=soft>" .
				$texte1 . "</TEXTAREA><P>\n";
		}
	}
	echo "<B>"._T('info_texte')."</B>";
	echo aide ("arttexte");
	echo "<br>"._T('texte_enrichir_mise_a_jour');
	echo aide("raccourcis");

	echo $textes_supplement;

	//echo "<BR>";
	echo afficher_barre('document.formulaire.texte');
	echo "<TEXTAREA id='text_area' NAME='texte' ".$GLOBALS['browser_caret']." CLASS='formo' ROWS='$rows' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA>\n";

	if (($articles_ps != "non" AND $options == "avancees") OR $ps) {
		echo "<P><B>"._T('info_post_scriptum')."</B><BR>";
		echo "<TEXTAREA NAME='ps' CLASS='forml' ROWS='5' COLS='40' wrap=soft>";
		echo $ps;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='ps' VALUE=\"$ps\">";
	}

	if ($champs_extra) {
		include_ecrire("inc_extra");
		extra_saisie($extra, 'articles', $id_secteur);
	}

	if ($date)
		echo "<INPUT TYPE='Hidden' NAME='date' VALUE=\"$date\" SIZE='40'><P>";

	if ($new == "oui")
		echo "<INPUT TYPE='Hidden' NAME='statut_nouv' VALUE=\"prepa\" SIZE='40'><P>";

	echo "<DIV ALIGN='right'>";
	echo "<INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='"._T('bouton_enregistrer')."'>";
	echo "</DIV></FORM>";
}


function exec_affiche_articles_edit_dist($flag_editable, $id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $url, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, $champs_article)
{
  global $champs_extra;
debut_page(_T('titre_page_articles_edit', array('titre' => $titre)), "documents", "articles", "hauteurTextarea();");

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();

debut_gauche();

//
// Pave "documents associes a l'article"
//

 if ($new != 'oui'){
	# modifs de la description d'un des docs joints
	if ($flag_editable) maj_documents($id_article, 'article');

	# affichage
	afficher_documents_colonne($id_article, 'article', $flag_editable);
}
 $GLOBALS['id_article_bloque'] = $id_article;	// globale dans debut_droite
 debut_droite();
 debut_cadre_formulaire();

 formulaire_articles_edit($id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $url, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, 
$champs_article);
fin_cadre_formulaire();

fin_page();
}


//
// Creation de l'objet article
//

function exec_articles_edit_dist()
{
  global $connect_id_auteur, $spip_lang, $id_article, $id_rubrique, $lier_trad, $new;
  $id_article = intval($id_article);
  $id_rubrique =  intval($id_rubrique);
  $lier_trad =  intval($lier_trad);

// ESSAI pour "Joindre un document" depuis l'espace prive (UPLOAD_DIRECT)
/*if ($GLOBALS['action'] AND $GLOBALS['doc']) {
	global $action, $doc;

	$var_nom = "spip_image";
	$var_f = find_in_path('inc_' . $var_nom . '.php');
	if ($var_f) 
		include($var_f);
	else include_ecrire('inc_' . $var_nom);

	$var_nom .= '_' . $action;

	if (function_exists($var_nom))
		$var_nom($doc);
	elseif (function_exists($var_f = $var_nom . '_dist'))
		$var_f($doc);
	else
		spip_log("fonction $var_nom indisponible");

#	return;
}*/


  if ($id_article) {
	// Recuperer les donnees de l'article
	$query = "SELECT * FROM spip_articles WHERE id_article=$id_article";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_article = $row["id_article"];
		$surtitre = $row["surtitre"];
		$titre = $row["titre"];
		$soustitre = $row["soustitre"];
		$id_rubrique = $row["id_rubrique"];
		$id_secteur = $row['id_secteur'];
		$descriptif = $row["descriptif"];
		$nom_site = $row["nom_site"];
		$url_site = $row["url_site"];
		$chapo = $row["chapo"];
		$texte = $row["texte"];
		$ps = $row["ps"];
		$date = $row["date"];
		$statut = $row['statut'];
		$date_redac = $row['date_redac'];
	    	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)){
		        $mois_redac = $regs[2];
		        $jour_redac = $regs[3];
		        $annee_redac = $regs[1];
		        if ($annee_redac > 4000) $annee_redac -= 9000;
		}
		$extra=$row["extra"];

		$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
		$result_auteur = spip_query($query);
		$flag_auteur = (spip_num_rows($result_auteur) > 0);

		$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
	}
}
else if ($new=='oui') {
	if ($lier_trad) {
		// Pas de langue choisie par defaut
		$changer_lang = '';

		// Recuperer les donnees de la traduction
		$query = "SELECT * FROM spip_articles WHERE id_article=$lier_trad";
		$result = spip_query($query);
	
		if ($row = spip_fetch_array($result)) {
			$surtitre = $row["surtitre"];
			$titre = filtrer_entites(_T('info_nouvelle_traduction')).' '.$row["titre"];
			$soustitre = $row["soustitre"];
			$id_rubrique_trad = $row["id_rubrique"];
			$descriptif = $row["descriptif"];
			$nom_site = $row["nom_site"];
			$url_site = $row["url_site"];
			$chapo = $row["chapo"];
			$texte = $row["texte"];
			$ps = $row["ps"];
			$date = $row["date"];
			$date_redac = $row['date_redac'];
			if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)) {
				$mois_redac = $regs[2];
				$jour_redac = $regs[3];
				$annee_redac = $regs[1];
				if ($annee_redac > 4000) $annee_redac -= 9000;
			}
			$extra = $row["extra"];
		}
		$langues_autorisees = $GLOBALS['meta']['langues_multilingue'];
		
		// Regler la langue, si possible
		if (ereg(",$spip_lang,", ",$langues_autorisees,")) {
			if ($GLOBALS['meta']['multi_articles'] == 'oui') {
				// Si le menu de langues est autorise sur les articles,
				// on peut changer la langue quelle que soit la rubrique
				$changer_lang = $spip_lang;
			}
			else if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
				// Chercher la rubrique la plus adaptee pour accueillir l'article
				if ($GLOBALS['meta']['multi_secteurs'] == 'oui') 
					$id_parent = 0;
				else {
					$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
					$row_rub = spip_fetch_array(spip_query($query));
					$id_parent = $row_rub['id_parent'];
				}
				$query = "SELECT id_rubrique FROM spip_rubriques WHERE lang='$spip_lang' AND id_parent=$id_parent";
				if ($row_rub = spip_fetch_array(spip_query($query))) {
					$id_rubrique = $id_secteur = $row_rub['id_rubrique'];
					$changer_lang = 'herit';
				}
			}
		}
	}
	else {
		// Nouvel article : titre par defaut
		$titre = filtrer_entites(_T('info_nouvel_article'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	}
	if (!$id_secteur) {
		$row_rub = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$id_secteur = $row_rub['id_secteur'];
	}
	$flag_editable = true;
}

if (!$flag_editable) {
	die ("<H3>"._T('info_acces_interdit')."</H3>");
}


spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");

 exec_affiche_articles_edit_dist($flag_editable, $id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $url, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, $GLOBALS['meta']);
}

?>
