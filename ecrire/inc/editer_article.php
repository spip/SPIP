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

function inc_editer_article_dist($new, $id_rubrique=0, $lier_trad=0, $retour='', $config=array(), $row=array()) {


	if ($afficher_barre = $config['afficher_barre'])
		include_spip('inc/barre');

	// Appel en tant que filtre d'un squelette 
	if (!$row) {
		include_spip('inc/presentation');
		include_spip('inc/article_select');
		$row = article_select($new, $id_rubrique, $lier_trad);
	}
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id_trad = $row['id_article'];
	$id_article = $lier_trad ? '' : $id_trad;
	$titre = entites_html($row['titre']);
	$texte = entites_html($row['texte']);

	$id_rubrique = $row['id_rubrique'];
	$id_secteur = $row['id_secteur'];
	$date = $row['date'];
	$extra = $row['extra'];
	$statut = $row['statut'];
	$onfocus = $row['onfocus']; // effacer le titre lorsque nouvel article
	
	$rows = $config['lignes'] +15;
	$att_text = " class='formo' ".$GLOBALS['browser_caret']." rows='$rows' cols='40'";
	if (strlen($texte)>29*1024) { // texte > 32 ko -> decouper en morceaux
	  list($texte, $sup) = editer_article_recolle($texte, $att_text);
	} else $sup='';

	$aider = charger_fonction('aider', 'inc');

	$form = "<input type='hidden' name='editer_article' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $config['langue'] .
		  "' />")) .
		editer_article_surtitre($row, $config, $aider) .
		_T('texte_titre_obligatoire') .
		$aider("arttitre") .
		"\n<br /><input type='text' name='titre' style='font-weight: bold; font-size: 13px;' class='formo' value=\"" .
		$titre .
		"\" size='40' " .
		$onfocus .
		" />\n</p>" .

		editer_article_soustitre($row, $config, $aider) .
		editer_article_rubrique($row, $config, $aider) .
		editer_article_descriptif($row, $config, $aider) .
		editer_article_url($row, $config, $aider) .
		editer_article_chapo($row, $config, $aider) .

		"<p><b>" ._T('info_texte') ."</b>" . 
		$aider ("arttexte") . "<br />\n" .
		_T('texte_enrichir_mise_a_jour') .
		$aider("raccourcis") .
		'<br />' .
		$sup .
		(!$afficher_barre ? '' : afficher_barre('document.formulaire.texte')) .
		"<textarea id='text_area' name='texte'$att_text>$texte</textarea>\n"
		."<script type='text/javascript'><!--\njQuery(hauteurTextarea);\n//--></script>\n" .

		editer_article_ps($row, $config, $aider) .

		(!$config['extra'] ? '': extra_saisie($extra, 'articles', $id_secteur)) .
		"<div align='right'><input class='fondo' type='submit' value='"
		. _T('bouton_enregistrer')
		. "' /></div></p>";

	$oups = _DIR_RESTREINT ? ''
	  : ($lier_trad ?
	     generer_url_ecrire("articles","id_article=$lier_trad")
	     : ($new
		? generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
		: generer_url_ecrire("articles","id_article=$id_trad")
		));
	return
		"\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>" .
		"<tr>" .
		"\n<td>" .
		(!$oups ? '' : icone(_T('icone_retour'), $oups, "article-24.gif", "rien.gif", '',false)) .
		"</td>\n<td>" .
		"<img src='" .
	  	_DIR_IMG_PACK .	"rien.gif' width='10' />" .
		"</td>\n" .
		"<td width='100%'>" .
	 	_T('texte_modifier_article') .
		gros_titre($row['titre'],'',false) . 
		"</td></tr></table><hr />\n<p>" .
	  generer_action_auteur("editer_article", $new ? 'oui' : $id_article, $retour, $form, " method='post' name='formulaire'");

}

function editer_article_rubrique($row, $config, $aider)
{
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$id_rubrique = $row['id_rubrique'];
	$id_secteur = $row['id_secteur'];
	$statut = $row['statut'];

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	return debut_cadre_couleur($logo, true, "", _T('titre_cadre_interieur_rubrique'). $aider("artrub")) .

	  $chercher_rubrique($id_rubrique, 'article', ($statut == 'publie')) .

	  fin_cadre_couleur(true);
}

function editer_article_surtitre($row, $config, $aider)
{
	if (($config['articles_surtitre'] == 'non') AND !$row['surtitre'])
		return '';

	return ( "<p><b>" .
		 _T('texte_sur_titre') .
		"</b>" .
		$aider ("arttitre") .
		"<br />\n<input type='text' name='surtitre' class='forml' value=\"" .
		 entites_html($row['surtitre']) .
		 "\" size='40' /></p>");
}

function editer_article_soustitre($row, $config, $aider)
{
	if (($config['articles_soustitre'] == "non") AND !$row['soustitre'])
		return '';

	return ("<p><b>" .
		  _T('texte_sous_titre') .
		  "</b>" .
		  $aider ("arttitre") .
		  "\n<br /><input type='text' name='soustitre' class='forml' value=\"" .
		  entites_html($row['soustitre']) .
		"\" size='40' /><br /><br /></p>\n");
}

function editer_article_descriptif($row, $config, $aider)
{
	if (($config['articles_descriptif'] == "non") AND !$row['descriptif'])
		return '';

	return ("\n<p><b>" ._T('texte_descriptif_rapide') ."</b>" .
		  $aider("artdesc") .
		  "<br />" ._T('texte_contenu_article') ."<br />\n" .
		  "<textarea name='descriptif' class='forml' rows='2' cols='40'>" .
		entites_html($row['descriptif']) .
		"</textarea></p>\n");
}

function editer_article_url($row, $config, $aider)
{
	if (($config['articles_urlref'] == "non") AND !$row['url_site'] AND $row['nom_site'])
		return '';

	$url_site = entites_html($row['url_site']);
	$nom_site = entites_html($row['nom_site']);

	return '<br />' . _T('entree_liens_sites') ."<br />\n" .
	  _T('info_titre') ." " .
	  "\n<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n" .
	  _T('info_url') .
	  "\n<input type='text' name='url_site' class='forml' width='40' value=\"$url_site\"/>\n";
}

function editer_article_ps($row, $config, $aider)
{
	if (($config['articles_ps'] == "non") AND !$row['ps'])
		 return '';

	return ("\n<p><b>"
		. _T('info_post_scriptum')
		."</b><br />"
		. "<textarea name='ps' class='forml' rows='5' cols='40'>"
		. entites_html($row['ps'])
		. "</textarea></p>\n");
}

//
// Gestion des textes trop longs (limitation brouteurs)
// utile pour les textes > 32ko

function coupe_trop_long($texte){
	$aider = charger_fonction('aider', 'inc');
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

function editer_article_recolle($texte, $att_text)
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


function editer_article_chapo($row, $config, $aider)
{
	$chapo = entites_html($row['chapo']);

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);

		return "<div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>" .
			"<table width=100% cellspacing=0 cellpadding=0 border=0>" .
			"<tr><td valign='top'>" .
			"<font face='Verdana,Arial,Sans,sans-serif' size=2>" .
			"<b><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></b>" .
			$aider ("artvirt") .
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

		if (($config['articles_chapeau'] == "non") AND !$chapo)
			return '';

		$rows = $config['lignes'];
		return "<p><br /><b>"._T('info_chapeau')."</b>" .
			$aider ("artchap") .
		  	"\n<br />"._T('texte_introductif_article')."<br />\n" .
			"<textarea name='chapo' class='forml' rows='$rows' cols='40'>" .
			$chapo .
			"</textarea></p>\n";
	}
}
?>
