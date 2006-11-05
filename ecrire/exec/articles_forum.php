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
include_spip('inc/forum'); // pour boutons_controle_forum 
include_spip('inc/autoriser');

// http://doc.spip.org/@exec_articles_forum_dist
function exec_articles_forum_dist()
{
	$id_article = intval(_request('id_article'));

	if (!autoriser('moderer_forum', 'article', $id_article))
		return;

	$debut = intval(_request('debut'));
	$pack = intval(_request('pack'));
	$enplus = intval(_request('enplus'));

	if (!$pack) $pack = 5; // nb de forums affiches par page
	if (!$enplus) $enplus = 200;	// intervalle affiche autour du debut

	$result = spip_query("SELECT titre, id_rubrique FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
		$titre = $row["titre"];
		$id_rubrique = $row["id_rubrique"];
	}
	
	$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
	$limitnb = $debut + $enplus - $limitdeb;

	$ancre = 'navigation-forum';
	$result = spip_query("SELECT id_forum FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND statut IN ('publie', 'off', 'prop')" . 	" LIMIT $limitdeb, $limitnb");
#	" LIMIT  $limitnb OFFSET $limitdeb" # PG

	$res = spip_query("SELECT pied.*, max(thread.date_heure) AS date FROM spip_forum AS pied, spip_forum AS thread WHERE pied.id_article='$id_article' AND pied.id_parent=0 AND pied.statut IN ('publie', 'off', 'prop') AND thread.id_thread=pied.id_forum	GROUP BY id_thread ORDER BY date DESC 	LIMIT $debut, $pack");

	$mess = affiche_navigation_forum("articles_forum", "id_article=$id_article", $debut, $limitdeb, $pack, $ancre, $result)
	. '<br />'
	. afficher_forum($res,"", '', $id_article);

	if (_request('var_ajaxcharset')) ajax_retour($mess);

 	pipeline('exec_init',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, "naviguer", "articles", $id_rubrique);

	articles_forum_cadres($id_rubrique, $id_article, $titre, 'articles', "id_article=$id_article");


	echo "<div class='serif2' id='$ancre'>";
	echo $mess;
	echo '</div>';

	echo fin_page();
}

// http://doc.spip.org/@articles_forum_cadres
function articles_forum_cadres($id_rubrique, $id_article, $titre, $script, $args)
{
	debut_grand_cadre();

	echo afficher_hierarchie($id_rubrique);

	fin_grand_cadre();

	debut_gauche();

	debut_boite_info();

	echo "<p align=left>",
	  "<font FACE='Verdana,Arial,Sans,sans-serif' SIZE='2'>",
	  _T('info_gauche_suivi_forum'),
	  aide ("suiviforum"),
	  "</font></p>";

	echo "<div style='text-align: "
	  . $GLOBALS['spip_lang_right']
	  . ";'>"
	  . bouton_spip_rss('forum', array('id_article' => $id_article))
	  . "</div>";

	fin_boite_info();

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	debut_droite();

	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'>";
	echo "<td>";
	icone(_T('icone_retour'),
		generer_url_ecrire($script, $args),
		"article-24.gif", "rien.gif");
	echo "</td>";
	echo "<td>" . http_img_pack('rien.gif', " ", "width='10'") ."</td>\n";
	echo "<td width='100%'>";
	echo _T('texte_messages_publics');
	gros_titre($titre);
	echo "</td></tr></table>";
	echo "<p>";
}
?>
