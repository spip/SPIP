<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@inc_instituer_article_dist
function inc_instituer_article_dist($id_article, $statut, $id_rubrique)
{
	// menu de date pour les articles post-dates (plugin)
	/* un branchement sauvage ?
	if ($statut <> 'publie'
	AND $GLOBALS['meta']['post_dates'] == 'non'
	AND function_exists('menu_postdates'))
		list($postdates,$postdates_js) = menu_postdates();
	else $postdates = $postdates_js = '';*/

	// cf autorisations dans action/editer_article
	if (!autoriser('modifier', 'article', $id_article)) return '';

	$res = '';

	$etats = $GLOBALS['liste_des_etats'];

	if (!autoriser('publierdans', 'rubrique', $id_rubrique)) {
		if ($statut == 'publie')
			return '';
		unset($etats[array_search('publie', $etats)]);
		unset($etats[array_search('refuse', $etats)]);
		if ($statut == 'prepa')
			$res = supprimer_tags(_T('texte_proposer_publication'));
	}
	
	$res .=
	  "<ul id='instituer_article-$id_article' class='instituer_article instituer'>" 
	  . "<li>" . _T('texte_article_statut')
		. aide("artstatut")
	  ."<ul>";
	
	$href = redirige_action_auteur('instituer_article',$id_article,'articles', "id_article=$id_article");
	$href = parametre_url($href,'statut_old',$statut);

	foreach($etats as $affiche => $s){
		$puce = puce_statut($s) . _T($affiche);
		if ($s==$statut)
			$class=' selected';
		else {
			$class=''; 
			$puce = "<a href='"
			. parametre_url($href,'statut_nouv',$s)
			. "' onclick='return confirm(confirm_changer_statut);'>$puce</a>";
		}
		$res .= "<li class='$s $class'>$puce</li>";
	}

	$res .= "</ul></li></ul>";
  
	return $res;
}
?>
