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

include_spip('inc/presentation');
include_spip('inc/article_select');
include_spip('inc/documents');

// http://doc.spip.org/@exec_articles_edit_dist
function exec_articles_edit_dist()
{
	exec_articles_edit_args(_request('id_article'), // intval plus tard
		intval(_request('id_rubrique')),
		intval(_request('lier_trad')),
		intval(_request('id_version')),
		((_request('new') == 'oui') ? 'new' : ''));
}


// http://doc.spip.org/@exec_articles_edit_args
function exec_articles_edit_args($id_article, $id_rubrique, $lier_trad, $id_version, $new)
{
	if (!$new AND (!autoriser('voir', 'article', $id_article) OR !autoriser('modifier','article', $id_article))) {
		include_spip('inc/minipres');
		echo minipres(_T('info_acces_interdit'));
	} else {
		$article_select = charger_fonction('article_select','inc');
		$row = $article_select($id_article ? $id_article : $new, $id_rubrique,  $lier_trad, $id_version);
		$id_rubrique = $row ? $row['id_rubrique'] : false;

		if (!$id_rubrique OR ($new AND !autoriser('creerarticledans','rubrique',$id_rubrique))) {
			include_spip('inc/minipres');
			echo minipres(_T('public:aucun_article'));
		} else articles_edit($id_article, $id_rubrique, $lier_trad, $id_version, $new, 'articles_edit_config', $row);
	}
}

// http://doc.spip.org/@articles_edit
function articles_edit($id_article, $id_rubrique, $lier_trad, $id_version, $new, $config_fonc, $row)
{
	$id_article = $row['id_article'];
	$id_rubrique = $row['id_rubrique'];
	$titre = sinon($row["titre"],_T('info_sans_titre'));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	pipeline('exec_init',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	
	if ($id_version) $titre.= ' ('._T('version')." $id_version)";

	echo $commencer_page(_T('titre_page_articles_edit', array('titre' => $titre)), "naviguer", "articles", $id_rubrique);

	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_rubrique,'',$id_article,'article');
	echo fin_grand_cadre(true);

	echo debut_gauche("",true);

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
		# le moment venu, c'est-a-dire lors de la creation de l'article
		# dans editer_article.
		echo afficher_documents_colonne(
			0-$GLOBALS['visiteur_session']['id_auteur'], 'article');
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	echo creer_colonne_droite("",true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	echo debut_droite("",true);
	
	$oups = ($lier_trad ?
	     generer_url_ecrire("articles","id_article=$lier_trad")
	     : ($new
		? generer_url_ecrire("naviguer","id_rubrique=".$row['id_rubrique'])
		: generer_url_ecrire("articles","id_article=".$row['id_article'])
		));

	$contexte = array(
	'icone_retour'=>icone_inline(_T('icone_retour'), $oups, "article-24.gif", "rien.gif",$GLOBALS['spip_lang_left']),
	'redirect'=>generer_url_ecrire("articles"),
	'titre'=>$titre,
	'new'=>$new?$new:$row['id_article'],
	'id_rubrique'=>$row['id_rubrique'],
	'id_secteur'=>$row['id_secteur'],
	'lier_trad'=>$lier_trad,
	'config_fonc'=>$config_fonc,
	// passer row si c'est le retablissement d'une version anterieure
	'row'=> $id_version
		? $row
		: null
	);

	$milieu = recuperer_fond("prive/editer/article", $contexte);
	
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>$milieu));

	echo fin_gauche(), fin_page();
}

?>
