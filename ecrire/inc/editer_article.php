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

include_spip('inc/actions');
include_spip('inc/barre');

// http://doc.spip.org/@inc_editer_article
function inc_editer_article($row, $lier_trad, $new, $champs_article) {

	global $champs_extra, $spip_lang, $options, $spip_ecran, $spip_display;

	$articles_surtitre = $champs_article['articles_surtitre'] != 'non';
	$articles_soustitre = $champs_article['articles_soustitre'] != "non";
	$articles_descriptif = $champs_article['articles_descriptif'] != "non";
	$articles_urlref = $champs_article['articles_urlref'] != "non";
	$articles_chapeau = $champs_article['articles_chapeau'] != "non";
	$articles_ps = $champs_article['articles_ps']  != "non";

	$id_trad = $row['id_article'];
	$gros_titre = $row['titre'];
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id_article = $lier_trad ? '' : $id_trad;

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
	$statut = $row['statut'];
	$onfocus = $row['onfocus']; // effacer le titre lorsque nouvel article
	
	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	if ($spip_ecran == "large") $rows = 28;	else $rows = 20;
	$att_text = " class='formo' ".$GLOBALS['browser_caret']." rows='$rows' cols='40'";
	if (strlen($texte)>29*1024) { // texte > 32 ko -> decouper en morceaux
	  list($texte, $sup) = articles_edit_recolle($texte, $att_text);
	} else $sup='';

	if ($champs_extra) include_spip('inc/extra');

	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$redirect = generer_url_ecrire("articles");

	$form = "<input type='hidden' name='editer_article' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $spip_lang .
		  "' />")) .

		(!(($options == "avancees" AND $articles_surtitre) OR $surtitre)?
			("\n<input type='hidden' name='surtitre' value=\"$surtitre\" />") :
			( "<b>" .
			  _T('texte_sur_titre') .
			  "</b>" .
			  aide ("arttitre") .
			  "<br />\n<input type='text' name='surtitre' class='forml' value=\"" .
			  $surtitre .
			  "\" size='40' /><p>")) .
		_T('texte_titre_obligatoire') .
		aide ("arttitre") .
		"\n<br /><input type='text' name='titre' style='font-weight: bold; font-size: 13px;' class='formo' value=\"" .
		$titre .
		"\" size='40' " .
		$onfocus .
		" />\n</p><p>" .

		(($articles_soustitre OR strlen($soustitre)) ?
		 ("<b>" .
		  _T('texte_sous_titre') .
		  "</b>" .
		  aide ("arttitre") .
		  "\n<br /><input type='text' name='soustitre' class='forml' value=\"" .
		  $soustitre .
		  "\" size='40' /><br /><br /></p>\n") :
		 '') .

		debut_cadre_couleur($logo, true, "", _T('titre_cadre_interieur_rubrique'). aide("artrub")) .

		$chercher_rubrique($id_rubrique, 'article', ($statut == 'publie')) .

		fin_cadre_couleur(true) .
	
		((($options == "avancees" AND $articles_descriptif) OR strlen($descriptif))?
		 ("\n<p><b>" ._T('texte_descriptif_rapide') ."</b>" .
		  aide ("artdesc") .
		  "<br />" ._T('texte_contenu_article') ."<br />\n" .
		  "<textarea name='descriptif' class='forml' rows='2' cols='40'>" .
		  $descriptif .
		  "</textarea>\n") :
		 '') .

		((($options == "avancees" AND $articles_urlref) OR $nom_site OR $url_site) ?
		 (_T('entree_liens_sites') ."<br />\n" .
		  _T('info_titre') ." " .
		  "\n<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n" .
		  _T('info_url') .
		  "\n<input type='text' name='url_site' class='forml' width='40' value=\"$url_site\"/>\n") : '') .

		chapo_articles_edit($chapo, $articles_chapeau) .

		"</p><p><b>" ._T('info_texte') ."</b>" . 
		aide ("arttexte") . "<br />\n" .
		_T('texte_enrichir_mise_a_jour') .
		aide("raccourcis") .
		$sup .
		($spip_display==4 ? '' : afficher_barre('document.formulaire.texte')) .
		"<textarea id='text_area' name='texte'$att_text>$texte</textarea>\n"
		."<script type='text/javascript'><!--\njQuery(hauteurTextarea);\n//--></script>\n"

		.

		((($articles_ps AND $options == "avancees") OR strlen($ps)) ?
		 ("\n</p><p><b>" . _T('info_post_scriptum') ."</b><br />" . "<textarea name='ps' class='forml' rows='5' cols='40'>" . $ps . "</textarea>\n") :
		 '') .

		(!$champs_extra ? '': extra_saisie($extra, 'articles', $id_secteur)) .

		"<div align='right'><input class='fondo' type='submit' value='" . _T('bouton_enregistrer') . "' /></div></p>";

	return
		"\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>" .
		"<tr>" .
		"\n<td>" .
		($lier_trad ?
		 icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$lier_trad"), "article-24.gif", "rien.gif", '',false) :
		 icone(_T('icone_retour'),
			$new=='oui'
				? generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
				: generer_url_ecrire("articles","id_article=$id_trad"),
			"article-24.gif", "rien.gif",'',false)) .
		"</td>\n<td>" .
		http_img_pack('rien.gif', " ", "width='10'") .
		"</td>\n" .
		"<td width='100%'>" .
	 	_T('texte_modifier_article') .
		gros_titre($gros_titre,'',false) . 
		"</td></tr></table><hr />\n<p>" .
	  generer_action_auteur("editer_article", $new ? $new : $id_article, $redirect, $form, " method='post' name='formulaire'");

}


//
// Gestion des textes trop longs (limitation brouteurs)
//

// http://doc.spip.org/@coupe_trop_long
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

// http://doc.spip.org/@articles_edit_recolle
function articles_edit_recolle($texte, $att_text)
{
	$textes_supplement = "<br /><font color='red'>"._T('info_texte_long')."</font>\n";
	$nombre = 0;

	while (strlen($texte)>29*1024) {
		$nombre ++;
		list($texte1,$texte) = coupe_trop_long($texte);

		$textes_supplement .= "<br />" .
			afficher_barre('document.formulaire.texte'.$nombre)  .
			"<textarea id='texte$nombre' name='texte_plus[$nombre]'$att_text>$texte1</textarea></p><p>\n";
		}
	return array($texte,$textes_supplement);
}


// http://doc.spip.org/@chapo_articles_edit
function chapo_articles_edit($chapo, $articles_chapeau)
{
	global $spip_ecran;

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
		$chapo = "";
	}

	if ($virtuel) {
		return "<div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>" .
			"<table width=100% cellspacing=0 cellpadding=0 border=0>" .
			"<tr><td valign='top'>" .
			"<font face='Verdana,Arial,Sans,sans-serif' size=2>" .
			"<b><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></b>" .
			aide ("artvirt") .
			"</font>" .
			"</td>" .
			"<td width=10>&nbsp;</td>" .
			"<td valign='top' width='50%'>" .
			"<input type='text' name='virtuel' class='forml'
		style='font-size:9px;' value=\"$virtuel\" size='40' />" .
			"<input type='hidden' name='changer_virtuel' value='oui' />" .
			"</td></tr></table>\n" .
			"<font face='Verdana,Arial,Sans,sans-serif' size=2>" .
			_T('texte_article_virtuel_reference') .
			"</font>" .
			"</div>\n";
	} else {

		if (($articles_chapeau) OR strlen($chapo)) {
			if ($spip_ecran == "large") $rows = 8;
			else $rows = 5;
			return "<br /><b>"._T('info_chapeau')."</b>" .
				aide ("artchap") .
				"\n<br />"._T('texte_introductif_article')."<br />\n" .
				"<textarea name='chapo' class='forml' rows='$rows' cols='40'>" .
				$chapo .
				"</textarea>\n";
		}
	}
}

?>
