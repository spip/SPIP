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

include_spip('inc/presentation');
include_spip('inc/article_select');
include_spip('inc/documents');

// http://doc.spip.org/@exec_articles_edit_dist
function exec_articles_edit_dist()
{
	$id_article = _request('id_article');
	$id_rubrique = _request('id_rubrique');
	$lier_trad = intval(_request('lier_trad'));
	$new = _request('new');

	pipeline('exec_init',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	
	$row = article_select($id_article, $id_rubrique, $lier_trad, $new);
	if (!$row) die ("<h3>"._T('info_acces_interdit')."</h3>");

	$id_article = $row['id_article'];

	// si une ancienne revision est demandee, la charger
	// en lieu et place de l'actuelle ; attention les champs
	// qui etaient vides ne sont pas vide's. Ca permet de conserver
	// des complements ajoutes "orthogonalement", et ca fait un code
	// plus generique.
	if ($id_version = intval(_request('id_version'))) {
		include_spip('inc/revisions');
		if ($textes = recuperer_version($id_article, $id_version)) {
			foreach ($textes as $champ => $contenu)
				$row[$champ] = $contenu;
		}
	}

	$id_rubrique = $row['id_rubrique'];
	$titre = $row['titre'];

	if ($id_version) $titre.= ' ('._T('version')." $id_version)";

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_articles_edit', array('titre' => $titre)),
			"naviguer", "articles", $id_rubrique);

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
		echo afficher_documents_colonne($id_article, 'article', true);
	} else {
		# ICI GROS HACK
		# -------------
		# on est en new ; si on veut ajouter un document, on ne pourra
		# pas l'accrocher a l'article (puisqu'il n'a pas d'id_article)...
		# on indique donc un id_article farfelu (0-id_auteur) qu'on ramassera
		# le moment venu, c'est-ˆ-dire lors de la creation de l'article
		# dans editer_article.
		echo afficher_documents_colonne(
			0-$GLOBALS['auteur_session']['id_auteur'], 'article', true);
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	debut_droite();
	
	debut_cadre_formulaire();
	$editer_article = charger_fonction('editer_article', 'inc');
	echo $editer_article($row, $lier_trad, $new, $GLOBALS['meta']);
	fin_cadre_formulaire();

	echo fin_page();
}
?>
