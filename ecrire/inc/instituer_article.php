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

// http://doc.spip.org/@inc_instituer_article_dist
function inc_instituer_article_dist($id_article, $statut=-1)
{
	if ($statut == -1) return demande_publication($id_article);

	$res =
	"\n<div id='instituer_article-$id_article'>" .
	"\n<center>" . 
	"<b>" .
	_T('texte_article_statut') .
	"</b>" .
	"\n<select name='statut_nouv' size='1' class='fondl'\n" .
	"onchange=\"document.statut.src='" .
	_DIR_IMG_PACK .
	"' + puce_statut(options[selectedIndex].value);" .
	" setvisibility('valider_statut', 'visible');\">\n" .
	"<option"  . mySel("prepa", $statut)  ." style='background-color: white'>" ._T('texte_statut_en_cours_redaction') ."</option>\n" .
	"<option"  . mySel("prop", $statut)  . " style='background-color: #FFF1C6'>" ._T('texte_statut_propose_evaluation') ."</option>\n" .
	"<option"  . mySel("publie", $statut)  . " style='background-color: #B4E8C5'>" ._T('texte_statut_publie') ."</option>\n" .
	"<option"  . mySel("poubelle", $statut) .
	http_style_background('rayures-sup.gif')  . '>'  ._T('texte_statut_poubelle') ."</option>\n" .
	"<option"  . mySel("refuse", $statut)  . " style='background-color: #FFA4A4'>" ._T('texte_statut_refuse') ."</option>\n" .
	"</select>" .
	" &nbsp; " .
	http_img_pack("puce-".puce_statut($statut).'.gif', "", "border='0'") .
	"  &nbsp;\n" .
	"<span class='visible_au_chargement' id='valider_statut'>" .
	"<input type='submit' value='"._T('bouton_valider')."' class='fondo' />" .
	"</span>" .
	aide("artstatut") .
	 "</center>"
	. '</div>';
  
	return redirige_action_auteur('instituer_article',$id_article,'articles', "id_article=$id_article", $res, " method='post'");
}


// http://doc.spip.org/@demande_publication
function demande_publication($id_article)
{
	return debut_cadre_relief('',true) .
		"<center>" .
		"<b>" ._T('texte_proposer_publication') . "</b>" .
		aide ("artprop") .
			redirige_action_auteur('instituer_article', "$id_article-prop",
			'articles',
			"id_article=$id_article",
			("<input type='submit' class='fondo' value=\"" . 
			    _T('bouton_demande_publication') .
			    "\" />\n"),
			"method='post'") .
		"</center>" .
		fin_cadre_relief(true);
}

?>
