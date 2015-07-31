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
include_spip('inc/editer');
include_spip('inc/editer_article');

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_editer_article_charger_dist($id_article='new', $id_rubrique=0, $lier_trad=0, $retour='', $config_fonc='articles_edit_config', $row=array(), $hidden=''){

	$new = $id_article;
	// Appel direct dans un squelette
	if (!$row) {
		include_spip('inc/article_select');
		$row = article_select($id_article, $id_rubrique, $lier_trad);
		if (!$row) return '';
		if (is_numeric($id_article)) $new = '';
		else $new = $id_article;
	}
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id_article = ($new OR $lier_trad) ? 'oui' : $row['id_article'];

	$contexte = $row;
	$contexte['config'] = $config = $config_fonc($row);

	// on veut conserver la langue de l'interface ;
	// on passe cette donnee sous un autre nom, au cas ou le squelette
	// voudrait l'exploiter
	if (isset($contexte['lang'])) {
		$contexte['langue'] = $contexte['lang'];
		unset($contexte['lang']);
	}

	$contexte['browser_caret']=$GLOBALS['browser_caret'];

	$contexte['_hidden'] = "<input type='hidden' name='editer_article' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $config['langue'] .
		  "' />")) . $hidden;

	// Ajouter le controles md5
	if (intval($id_article)) {
		include_spip('inc/editer');
		$contexte['_hidden'] .= controles_md5($row);
	}
	if (isset($contexte['extra']))
		$contexte['extra'] = unserialize($contexte['extra']);

	// preciser que le formulaire doit passer dans un pipeline
	$contexte['_pipeline'] = array('editer_contenu_objet','args'=>array('type'=>'article','id'=>$id_article,'contexte'=>$contexte));
	// preciser que le formulaire doit etre securise auteur/action
	$contexte['_action'] = array('editer_article',$id_article);

	return $contexte;
}

?>