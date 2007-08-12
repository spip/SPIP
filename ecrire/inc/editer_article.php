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

include_spip('inc/actions');
include_spip('inc/extra');

// http://doc.spip.org/@inc_editer_article_dist
function inc_editer_article_dist($new, $id_rubrique=0, $lier_trad=0, $retour='', $config_fonc='articles_edit_config', $row=array(), $hidden='')
{
	// Appel en tant que filtre d'un squelette 
	if (!$row) {
		include_spip('inc/presentation');
		include_spip('inc/article_select');
		$row = article_select($new, $id_rubrique, $lier_trad);
		if (!$row) return '';
		if (is_numeric($new)) $new = '';
	}

	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id_article = ($new OR $lier_trad) ? 'oui' : $row['id_article'];

	$aider = charger_fonction('aider', 'inc');
	$config = $config_fonc($row);

	$form = "<input type='hidden' name='editer_article' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $config['langue'] .
		  "' />"))

	. editer_article_surtitre($row['surtitre'], $config, $aider)
	. editer_article_titre($row['titre'], $row['onfocus'], $config, $aider)
	. editer_article_soustitre($row['soustitre'], $config, $aider)
	. editer_article_rubrique($row['id_rubrique'], $row['id_secteur'], $config, $aider)
	. editer_article_descriptif($row['descriptif'], $config, $aider)
	. editer_article_url($row['url_site'], $row['nom_site'], $config, $aider)
	. editer_article_chapo($row['chapo'], $config, $aider)
	. editer_article_texte($row['texte'], $config, $aider,$row['lang'])
	. editer_article_ps($row['ps'], $config, $aider)
	. editer_article_extra($row['extra'], $row['id_secteur'], $config, $aider)
	. $hidden
	. ("<div style='text-align: right'><input class='fondo' type='submit' value='"
	. _T('bouton_enregistrer')
	. "' /></div>");

	return generer_action_auteur("editer_article", $id_article, $retour, $form, " method='post' name='formulaire'");
}

// http://doc.spip.org/@editer_article_texte
function editer_article_texte($texte, $config, $aider, $lang='')
{
	// cette meta n'est pas activable par l'interface, mais elle peut venir
	// d'ailleurs : http://www.spip-contrib.net/Personnaliser-les-champs-de-l
	if (($config['articles_texte'] == 'non') AND !strlen($texte))
		return '';

	$att_text = " class='formo' "
	. $GLOBALS['browser_caret']
	. " rows='"
	. ($config['lignes'] +15)
	. "' cols='40'";

	if ($config['afficher_barre']) {
		include_spip('inc/barre');
		$afficher_barre = '<div>' 
		.  afficher_barre('document.formulaire.texte',false,$lang)
		. '</div>';
	} else $afficher_barre = '';

	$texte = entites_html($texte);
	 // texte > 32 ko -> decouper en morceaux
	if (strlen($texte)>29*1024) {
	  list($texte, $sup) = editer_article_recolle($texte, $att_text);
	} else $sup='';

	return	"\n<p><b>" ._T('info_texte') ."</b>"
	. $aider ("arttexte") . "<br />\n" 
	. _T('texte_enrichir_mise_a_jour')
	. $aider("raccourcis")
	. "</p>"
	. $sup
	. "<br />"
	. $afficher_barre
	.  "<textarea id='text_area' name='texte'$att_text>"
	.  $texte
	. "</textarea>\n"
	. (test_espace_prive()
		? "<script type='text/javascript'><!--\njQuery(hauteurTextarea);\n//--></script>\n"
		: ''
	);
}

// http://doc.spip.org/@editer_article_titre
function editer_article_titre($titre, $onfocus, $config, $aider)
{
	return	"\n<p>" .
		_T('texte_titre_obligatoire') .
		$aider("arttitre") .
		"\n<br /><input type='text' name='titre' style='font-weight: bold; ' class='formo spip_small' value=\"" .
	  	entites_html($titre) .
		"\" size='40' " .
	  	$onfocus. // effacer le titre lorsque nouvel article
		  " />\n</p>";
}

// http://doc.spip.org/@editer_article_rubrique
function editer_article_rubrique($id_rubrique, $id_secteur, $config, $aider)
{
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$opt = $chercher_rubrique($id_rubrique, 'article', $config['restreint']);

	$msg = _T('titre_cadre_interieur_rubrique') .
	  ((preg_match('/^<input[^>]*hidden[^<]*$/', $opt)) ? '' : $aider("artrub"));

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	return debut_cadre_couleur($logo, true, "", $msg) . $opt .fin_cadre_couleur(true);
}

// http://doc.spip.org/@editer_article_surtitre
function editer_article_surtitre($surtitre, $config, $aider)
{
	if (($config['articles_surtitre'] == 'non') AND !$surtitre)
		return '';

	return ( "\n<p><b>" .
		 _T('texte_sur_titre') .
		"</b>" .
		$aider ("arttitre") .
		"<br />\n<input type='text' name='surtitre' class='forml' value=\"" .
		 entites_html($surtitre) .
		 "\" size='40' /></p>");
}

// http://doc.spip.org/@editer_article_soustitre
function editer_article_soustitre($soustitre, $config, $aider)
{
	if (($config['articles_soustitre'] == "non") AND !$soustitre)
		return '';

	return ("\n<p><b>" .
		  _T('texte_sous_titre') .
		  "</b>" .
		  $aider ("arttitre") .
		  "\n<br /><input type='text' name='soustitre' class='forml' value=\"" .
		  entites_html($soustitre) .
		"\" size='40' /><br /><br /></p>\n");
}

// http://doc.spip.org/@editer_article_descriptif
function editer_article_descriptif($descriptif, $config, $aider)
{
	if (($config['articles_descriptif'] == "non") AND !strlen($descriptif))
		return '';

	$msg = _T('texte_contenu_article');
	return ("\n<p><b>" ._T('texte_descriptif_rapide') ."</b>" .
		  $aider("artdesc") .
		"<br />\n" . 
		(!trim($msg) ? '' : "$msg<br />\n") .
		"<textarea name='descriptif' class='forml' rows='2' cols='40'>" .
		entites_html($descriptif) .
		"</textarea></p>");
}

// http://doc.spip.org/@editer_article_url
function editer_article_url($url, $nom, $config, $aider)
{
	if (($config['articles_urlref'] == "non") AND !$url AND !$nom)
		return '';

	$url_site = entites_html($url);
	$nom_site = entites_html($nom);

	return '<br />' . _T('entree_liens_sites') ."<br />\n" .
	  _T('info_titre') ." " .
	  "\n<input type='text' name='nom_site' class='forml' size='40' value=\"$nom\"/><br />\n" .
	  _T('info_url') .
	  "\n<input type='text' name='url_site' class='forml' size='40' value=\"$url\"/>\n";
}

// http://doc.spip.org/@editer_article_ps
function editer_article_ps($ps, $config, $aider)
{
	if (($config['articles_ps'] == "non") AND !$ps)
		 return '';

	return  "\n<p><b>"
		. _T('info_post_scriptum')
		."</b><br />"
		. "<textarea name='ps' class='forml' rows='5' cols='40'>"
		. entites_html($ps)
		. "</textarea></p>\n";
}

//
// Gestion des textes trop longs (limitation brouteurs)
// utile pour les textes > 32ko

// http://doc.spip.org/@coupe_trop_long
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

// http://doc.spip.org/@editer_article_recolle
function editer_article_recolle($texte, $att_text)
{
	$textes_supplement = "<br /><span style='color: red'>"._T('info_texte_long')."</span>\n";
	$nombre = 0;

	while (strlen($texte)>29*1024) {
		$nombre ++;
		list($texte1,$texte) = coupe_trop_long($texte);

		$textes_supplement .= "<br />" .
			afficher_barre('document.formulaire.texte'.$nombre)  .
			"<textarea id='texte$nombre' name='texte_plus[$nombre]'$att_text>$texte1</textarea>\n";
		}
	return array($texte,$textes_supplement);
}


// http://doc.spip.org/@editer_article_chapo
function editer_article_chapo($chapo, $config, $aider)
{
	$chapo = entites_html($chapo);

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);

		return "<div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>" .
			"<table width='100%' cellspacing='0' cellpadding='0' border='0'>" .
			"<tr><td valign='top'>" .
			"<span class='verdana1 spip_small'><b><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></b>" .	$aider ("artvirt") . "</span>" .
			"</td>" .
			"<td style='width: 10px'>&nbsp;</td>" .
			"<td valign='top' style='width: 50%'>" .
			"<input type='text' name='virtuel' class='forml spip_xx-small' value=\"$virtuel\" size='40' />" .
			"<input type='hidden' name='changer_virtuel' value='oui' />" .
			"</td></tr></table>\n" .
			"<span class='verdana1 spip_small'>" . _T('texte_article_virtuel_reference') . "</span>" .
			"</div>\n";
	} else {

		if (($config['articles_chapeau'] == "non") AND !$chapo)
			return '';

		$rows = $config['lignes'];
		return "\n<p><br /><b>"._T('info_chapeau')."</b>" .
			$aider ("artchap") .
		  	"\n<br />"._T('texte_introductif_article')."<br />\n" .
			"<textarea name='chapo' class='forml' rows='$rows' cols='40'>" .
			$chapo .
			"</textarea></p>\n";
	}
}

// http://doc.spip.org/@editer_article_extra
function editer_article_extra($extra, $id_secteur, $config, $aider)
{
	if (!$config['extra'])
		return '';
	include_spip('inc/extra');
	return extra_saisie($extra, 'articles', $id_secteur);
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function articles_edit_config($row)
{
	global $champs_extra, $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['afficher_barre'] = $spip_display != 4;
	$config['langue'] = $spip_lang;

	if ($champs_extra) {
		include_spip('inc/extra');
		$config['extra'] = true;
	} else $config['extra'] = false;

	$config['restreint'] = ($row['statut'] == 'publie');
	return $config;
}
?>
