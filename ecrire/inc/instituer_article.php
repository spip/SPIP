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

// http://doc.spip.org/@inc_instituer_article_dist
function inc_instituer_article_dist($id_article, $statut=-1)
{
	if ($statut == -1) return demande_publication($id_article);

	// menu de date pour les articles post-dates (plugin)
	/* un branchement sauvage ?
	if ($statut <> 'publie'
	AND $GLOBALS['meta']['post_dates'] == 'non'
	AND function_exists('menu_postdates'))
		list($postdates,$postdates_js) = menu_postdates();
	else $postdates = $postdates_js = '';*/

	$liste_statuts = array(
	  // statut => array(titre,image)
		'prepa' => array(_T('texte_statut_en_cours_redaction'),''),
		'prop' => array(_T('texte_statut_propose_evaluation'),''),	
		'publie' => array(_T('texte_statut_publie'),''),	
		'poubelle' => array(_T('texte_statut_poubelle'),''),	
		'refuse' => array(_T('texte_statut_refuse'),'')	
	);

	$res =
	  "<ul id='instituer_article-$id_article' class='instituer_article instituer'>" 
	  . "<li>" . _T('texte_article_statut') 
		. aide("artstatut")
	  ."<ul>";
	
	$href = redirige_action_auteur('instituer_article',$id_article,'articles', "id_article=$id_article");
	foreach($liste_statuts as $s=>$affiche){
		$href = parametre_url($href,'statut_nouv',$s);
		$sel = ($s==$statut) ? " selected":"";
		$res .= "<li class='$s$sel'><a href='$href'>" . puce_statut($s) . $affiche[0] . '</a></li>';
	}

	$res .= "</ul></li></ul>";
  
	return $res;
}

// http://doc.spip.org/@demande_publication
function demande_publication($id_article)
{
	return "<br style='clear:both;' />" .
		debut_cadre_relief('',true) .
		"<div style='text-align: center'>" .
		"<b>" ._T('texte_proposer_publication') . "</b>" .
		aide ("artprop") .
			redirige_action_auteur('instituer_article', "$id_article-prop",
			'articles',
			"id_article=$id_article",
			("<input type='submit' class='fondo' value=\"" . 
			    _T('bouton_demande_publication') .
			    "\" />\n"),
			"method='post'") .
		"</div>" .
		fin_cadre_relief(true);
}

?>
