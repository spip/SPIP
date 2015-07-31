<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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

	$contexte['config'] = $config = $config_fonc($row);

	$form = "<input type='hidden' name='editer_article' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $config['langue'] .
		  "' />"));

	$contexte = $row;

	// on veut conserver la langue de l'interface ;
	// on passe cette donnee sous un autre nom, au cas ou le squelette
	// voudrait l'exploiter
	if (isset($contexte['lang'])) {
		$contexte['langue'] = $contexte['lang'];
		unset($contexte['lang']);
	}

	$contexte['browser_caret']=$GLOBALS['browser_caret'];
	include_spip('public/assembler');
	$form .= recuperer_fond("prive/editer/article", $contexte);

	$form .= $hidden
	. ("<div style='text-align: right'><input class='fondo' type='submit' value='"
	. _T('bouton_enregistrer')
	. "' /></div>");

	$form = pipeline(
		'editer_contenu_objet',
		array(
			'data'=>$form,
			'args'=>array('type'=>'article','id'=>$id_article,'contexte'=>$contexte)
		)
	);

	return generer_action_auteur("editer_article", $id_article, $retour, $form, " method='post'");
}


/*
// code mort
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
		.  afficher_barre("document.getElementById('text_area')",false,$lang)
		. '</div>';
	} else $afficher_barre = '';

	$texte = entites_html($texte);
	 // texte > 32 ko -> decouper en morceaux
	if (strlen($texte)>29*1024) {
	  list($texte, $sup) = editer_article_recolle($texte, $att_text);
	} else $sup='';

	return	"\n<p><label for='text_area'><b>" ._T('info_texte') ."</b></label>"
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
		? "<script type='text/javascript'><!--
		jQuery(function(){
			jQuery('#text_area')
			.css('height',(jQuery(window).height()-80)+'px');
		});\n//--></script>\n"
		: ''
	);
}
*/

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
		$id = "document.getElementById('texte$nombre')";
		$textes_supplement .= "<br />" . afficher_barre($id) .
			"<textarea id='texte$nombre' name='texte_plus[$nombre]'$att_text>$texte1</textarea>\n";
		}
	return array($texte,$textes_supplement);
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
