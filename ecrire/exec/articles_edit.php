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

include_spip('inc/presentation');
include_spip('inc/article_select');
include_spip('inc/documents');

// http://doc.spip.org/@exec_articles_edit_dist
function exec_articles_edit_dist()
{
  articles_edit(_request('id_article'), // intval plus tard
		intval(_request('id_rubrique')),
		intval(_request('lier_trad')),
		intval(_request('id_version')),
		((_request('new') == 'oui') ? 'new' : ''),
		'articles_edit_config');
}


// http://doc.spip.org/@articles_edit
function articles_edit($id_article, $id_rubrique,$lier_trad,  $id_version, $new, $config_fonc)
{
	$row = article_select($id_article ? $id_article : $new, $id_rubrique,  $lier_trad, $id_version);
	$id_article = $row['id_article'];
	$id_rubrique = $row['id_rubrique'];
	
	$commencer_page = charger_fonction('commencer_page', 'inc');
	if (!$row
	  OR ($new AND !autoriser('creerarticledans','rubrique',$id_rubrique)) 
	  OR (!$new AND (!autoriser('voir', 'article', $id_article)	OR !autoriser('modifier','article', $id_article))) 
	  ) {
		echo $commencer_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", $id_rubrique);
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
	}

	pipeline('exec_init',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	
	if ($id_version) $titre.= ' ('._T('version')." $id_version)";
	else $titre = $row['titre'];

	echo $commencer_page(_T('titre_page_articles_edit', array('titre' => $titre)), "naviguer", "articles", $id_rubrique);

	debut_grand_cadre();
	echo afficher_hierarchie($id_rubrique);
	fin_grand_cadre();

	debut_gauche();

	// Pave "documents associes a l'article"
	
	if (!$new){
		# affichage sur le cote des pieces jointes, en reperant les inserees
		# note : traiter_modeles($texte, true) repere les doublons
		# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles(join('',$row), true);
		echo afficher_documents_colonne($id_article, 'article');
	} else {
		# ICI GROS HACK
		# -------------
		# on est en new ; si on veut ajouter un document, on ne pourra
		# pas l'accrocher a l'article (puisqu'il n'a pas d'id_article)...
		# on indique donc un id_article farfelu (0-id_auteur) qu'on ramassera
		# le moment venu, c'est-ˆ-dire lors de la creation de l'article
		# dans editer_article.
		echo afficher_documents_colonne(
			0-$GLOBALS['auteur_session']['id_auteur'], 'article');
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	debut_droite();
	
	debut_cadre_formulaire();
	echo articles_edit_presentation($new, $row['id_rubrique'], $lier_trad, $row['id_article'], $row['titre']);
	$editer_article = charger_fonction('editer_article', 'inc');
	echo $editer_article($new, $id_rubrique, $lier_trad, generer_url_ecrire("articles"), $config_fonc, $row);
	fin_cadre_formulaire();

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));

	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@articles_edit_presentation
function articles_edit_presentation($new, $id_rubrique, $lier_trad, $id_article, $titre)
{
	$oups = ($lier_trad ?
	     generer_url_ecrire("articles","id_article=$lier_trad")
	     : ($new
		? generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
		: generer_url_ecrire("articles","id_article=$id_article")
		));

	return
		"\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>" .
		"<tr>" .
		"\n<td>" .
		icone(_T('icone_retour'), $oups, "article-24.gif", "rien.gif", '',false) .
		"</td>\n<td>" .
		"<img src='" .
	  	_DIR_IMG_PACK .	"rien.gif' width='10' alt='' />" .
		"</td>\n" .
		"<td style='width: 100%'>" .
	 	_T('texte_modifier_article') .
		gros_titre($titre,'',false) . 
		"</td></tr></table><hr />\n";
}
?>
