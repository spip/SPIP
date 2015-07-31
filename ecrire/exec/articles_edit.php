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
include_spip('inc/article_select');
include_spip('inc/rubriques');
include_spip('inc/documents');
include_spip('inc/barre');

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
function formulaire_articles_edit($id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, $champs_article)
{
 global   $champs_extra, $spip_lang, $options , $spip_ecran;

 $articles_surtitre = $champs_article['articles_surtitre'];
 $articles_soustitre = $champs_article['articles_soustitre'];
 $articles_descriptif = $champs_article['articles_descriptif'];
 $articles_urlref = $champs_article['articles_urlref'];
 $articles_chapeau = $champs_article['articles_chapeau'];
 $articles_ps = $champs_article['articles_ps'];
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
			$textes_supplement .= "<TEXTAREA id='texte$nombre_textes' NAME='texte_plus[$nombre_textes]'".
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
		include_spip('inc/extra');
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


function exec_affiche_articles_edit_dist($row, $lier_trad, $new, $champs_article) {
	global $champs_extra;
	$id_article = $row['id_article'];
	$id_rubrique = $row['id_rubrique'];
	$titre = $row['titre'];
	$soustitre = $row['soustitre'];
	$surtitre = $row['surtitre'];
	$descriptif = $row['descriptif'];
	$chapo = $row['chapo'];
	$texte = $row['texte'];
	$ps = $row['ps'];
	$nom_site = $row['nom_site'];
	$url_site = $row['url_site'];
	$extra = $row['extra'];
	$id_secteur = $row['id_secteur'];
	$date = $row['date'];
	$onfocus = $row['onfocus'];

	debut_page(_T('titre_page_articles_edit', array('titre' => $titre)), "documents", "articles", "hauteurTextarea();", "", $id_rubrique);

	debut_grand_cadre();

	afficher_hierarchie($id_rubrique);

	fin_grand_cadre();

	debut_gauche();

//
// Pave "documents associes a l'article"
//

	if ($new != 'oui'){
	# modifs de la description d'un des docs joints
		maj_documents($id_article, 'article');

	# affichage
		afficher_documents_colonne($id_article, 'article', true);
	}
	$GLOBALS['id_article_bloque'] = $id_article;	// globale dans debut_droite
	debut_droite();
	debut_cadre_formulaire();

	formulaire_articles_edit($id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $chapo, $texte, $ps, $new, $nom_site, $url_site, $extra, $id_secteur, $date, $onfocus, $lier_trad, $champs_article);
	fin_cadre_formulaire();

	fin_page();
}


function exec_articles_edit_dist()
{
	$row = article_select(_request('id_article'), _request('id_rubrique'), _request('lier_trad'), _request('new'));

	if (!$row) die ("<h3>"._T('info_acces_interdit')."</h3>");

	exec_affiche_articles_edit_dist($row, $lier_trad, $new,$GLOBALS['meta']);
}

?>
