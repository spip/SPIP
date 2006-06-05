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

	if ($virtuel) {
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
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml'
		style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'>";
		echo "<input type='hidden' name='changer_virtuel' value='oui'>";
		echo "</td></tr></table>\n";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo _T('texte_article_virtuel_reference');
		echo "</font>";
		echo "</div><p>\n";
	}

	else {
		echo "<hr />";

		if (($articles_chapeau) OR strlen($chapo)) {
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

function formulaire_articles_edit($row, $lier_trad, $new, $champs_article) {

  global   $champs_extra, $spip_lang, $options , $spip_ecran, $spip_display;

	$articles_surtitre = $champs_article['articles_surtitre'] != 'non';
	$articles_soustitre = $champs_article['articles_soustitre'] != "non";
	$articles_descriptif = $champs_article['articles_descriptif'] != "non";
	$articles_urlref = $champs_article['articles_urlref'] != "non";
	$articles_chapeau = $champs_article['articles_chapeau'] != "non";
	$articles_ps = $champs_article['articles_ps']  != "non";

	$id_article = $row['id_article'];
	$titre = $row['titre'];

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

	$titre = entites_html($row['titre']);
	$soustitre = entites_html($row['soustitre']);
	$surtitre = entites_html($row['surtitre']);
	$descriptif = entites_html($row['descriptif']);
	$nom_site = entites_html($row['nom_site']);
	$url_site = entites_html($row['url_site']);
	$chapo = entites_html($row['chapo']);
	$texte = entites_html($row['texte']);
	$ps = entites_html($row['ps']);

	$id_rubrique = $row['id_rubrique'];
	$id_secteur = $row['id_secteur'];
	$date = $row['date'];
	$extra = $row['extra'];
	$onfocus = $row['onfocus'];
	
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	if ($lier_trad) $id_article = "";
	
	echo generer_url_post_ecrire("articles", ($id_article ? "id_article=$id_article" : ""),'formulaire','',' onchange="disable_other_forms(this);"');

	if ($new == 'oui')
		echo "<INPUT TYPE='Hidden' NAME='new' VALUE='oui'>";

	if ($lier_trad) {
		echo "<INPUT TYPE='Hidden' NAME='lier_trad' VALUE='$lier_trad'>";
		echo "<INPUT TYPE='Hidden' NAME='changer_lang' VALUE='$spip_lang'>";
	}

	if (($options == "avancees" AND $articles_surtitre) OR $surtitre) {
		echo "<B>"._T('texte_sur_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='surtitre' CLASS='forml' VALUE=\"$surtitre\" SIZE='40'"
// Pour faire fonctionner le onchange sur Safari il faudrait modifier
// chaque input. Conclusion : c'est la mauvaise methode.
// .' onchange="disable_other_forms(this.parentNode);"'.
."><P>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='surtitre' VALUE=\"$surtitre\" >";
	}

	echo _T('texte_titre_obligatoire');
	echo aide ("arttitre");
	echo "\n<br /><INPUT TYPE='text' NAME='titre' style='font-weight: bold; font-size: 13px;' CLASS='formo' VALUE=\"$titre\" SIZE='40' $onfocus>\n<P>";

	if (($articles_soustitre) OR $soustitre) {
		echo "<B>"._T('texte_sous_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='soustitre' CLASS='forml' VALUE=\"$soustitre\" SIZE='40'><br><br>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='soustitre' VALUE=\"$soustitre\">";
	}

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	debut_cadre_couleur($logo, false, "", _T('titre_cadre_interieur_rubrique'). aide("artrub"));

	 echo selecteur_rubrique($id_rubrique, 'article', ($GLOBALS['statut'] == 'publie'));

	fin_cadre_couleur();
	
	if ($new != 'oui') echo "<INPUT TYPE='hidden' NAME='id_rubrique_old' VALUE='$id_rubrique'>";

	if (($options == "avancees" AND $articles_descriptif) OR $descriptif) {
		echo "<P><B>"._T('texte_descriptif_rapide')."</B>";
		echo aide ("artdesc");
		echo "</p><br />",_T('texte_contenu_article'),"<br />";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
	}

	if (($options == "avancees" AND $articles_urlref) OR $nom_site OR $url_site) {
		echo _T('entree_liens_sites'),"<br />\n";
		echo _T('info_titre')," ";
		echo "<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n";
		echo _T('info_url')," ";
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
	echo "<b>"._T('info_texte')."</b>";
	echo aide ("arttexte");
	echo "<br />"._T('texte_enrichir_mise_a_jour');
	echo aide("raccourcis");

	echo $textes_supplement;

	
	if ($spip_display!=4) echo afficher_barre('document.formulaire.texte');
	echo "<TEXTAREA id='text_area' NAME='texte' ".$GLOBALS['browser_caret']." CLASS='formo' ROWS='$rows' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA>\n";

	if (($articles_ps AND $options == "avancees") OR $ps) {
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

function exec_articles_edit_dist()
{
	$id_article =_request('id_article');
	$id_rubrique = _request('id_rubrique');
	$lier_trad = _request('lier_trad');
	$new = _request('new');

	pipeline('exec_init',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	
	$row = article_select($id_article, $id_rubrique, $lier_trad, $new);
	if (!$row) die ("<h3>"._T('info_acces_interdit')."</h3>");

	$id_article = $row['id_article'];
	$id_rubrique = $row['id_rubrique'];
	$titre = $row['titre'];

	debut_page(_T('titre_page_articles_edit', array('titre' => $titre)),
		   "documents", "articles", "hauteurTextarea();", 
		   "",
		   $id_rubrique);

	debut_grand_cadre();
	afficher_hierarchie($id_rubrique);
	fin_grand_cadre();

	debut_gauche();

	// Pave "documents associes a l'article"

	if ($new != 'oui'){
	# modifs de la description d'un des docs joints
		maj_documents($id_article, 'article');

	# affichage sur le cote des pieces jointes, en reperant les inserees
		if (isset($row['descriptif'])) document_a_voir($row['descriptif']);
		if (isset($row['chapo'])) document_a_voir($row['chapo']);
		if (isset($row['texte'])) document_a_voir($row['texte']);
		afficher_documents_colonne($id_article, 'article', true);
	}
	$GLOBALS['id_article_bloque'] = $id_article;	// globale dans debut_droite
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	debut_droite();
	
	debut_cadre_formulaire();
	formulaire_articles_edit($row, $lier_trad, $new, $GLOBALS['meta']);
	fin_cadre_formulaire();

	fin_page();
}
?>
