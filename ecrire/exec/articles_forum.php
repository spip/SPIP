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
include_spip('inc/forum'); // pour boutons_controle_forum 

// http://doc.spip.org/@exec_articles_forum_dist
function exec_articles_forum_dist()
{
	exec_articles_forum_args(intval(_request('id_article')),
				 _request('date'),
				 intval(_request('debut')),
				 intval(_request('pas')),
				 intval(_request('enplus')));
}

// http://doc.spip.org/@exec_articles_forum_args
function exec_articles_forum_args($id_article, $date, $debut, $pas, $enplus)
{
	if (!autoriser('modererforum', 'article', $id_article)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$query = array('SELECT' => "pied.id_forum,pied.id_parent,pied.id_rubrique,pied.id_article,pied.id_breve,pied.id_message,pied.id_syndic,pied.date_heure,pied.titre,pied.texte,pied.auteur,pied.email_auteur,pied.nom_site,pied.url_site,pied.statut,pied.ip,pied.id_auteur, max(thread.date_heure) AS date", 
		       'FROM' => "spip_forum AS pied LEFT JOIN spip_forum AS thread ON pied.id_forum=thread.id_thread",
		       'WHERE' => "pied.id_article=$id_article AND pied.id_parent=0 AND pied.statut IN ('publie', 'off', 'prop', 'spam')",
		       'GROUP BY' => "pied.id_forum",
		       'ORDER BY' => "date DESC");

	if (!$pas) $pas = 5;
	$nav = affiche_navigation_forum($query, "articles_forum", "id_article=$id_article", $debut, $pas, $enplus, $date);

	$res = afficher_forum($query, '', '', $id_article, 'articles_forum', "id_article=$id_article");
	$res =  "<br />$nav<br />$res<br />$nav";	

	if (_AJAX) {
		ajax_retour($res);
	} else {

		$ancre = 'articles_forum';
		$res =  "<div class='serif2' id='$ancre'>$res</div>";

	 	pipeline('exec_init',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));

		$row = sql_fetsel("titre, id_rubrique", "spip_articles", "id_article=$id_article");

		if ($row) {
			$titre = $row["titre"];
			$id_rubrique = $row["id_rubrique"];
		}

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page($titre, "naviguer", "articles", $id_rubrique);

		articles_forum_cadres($id_rubrique, $id_article, $titre, 'articles', "id_article=$id_article");

		echo $res;
		echo fin_gauche(), fin_page();
	}
	}
}

// http://doc.spip.org/@articles_forum_cadres
function articles_forum_cadres($id_rubrique, $id_article, $titre, $script, $args)
{
	global $spip_lang_right, $spip_lang_left;

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	echo debut_boite_info(true);

	echo "<p style='text-align: $spip_lang_left; ' class='verdana1 spip_x-small'>",
	  _T('info_gauche_suivi_forum'),
	  aide ("suiviforum"),
	  "</p>";

	$img = http_img_pack('feed.png', 'RSS', '', 'RSS');
	$url = bouton_spip_rss('forums_public', array("id_article" => $id_article));

	echo "<div style='text-align: $spip_lang_right;'>", $url, "</div>";

	echo fin_boite_info(true);

	$res = icone_horizontale(_T('icone_statistiques_visites'), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif", false);

	echo bloc_des_raccourcis($res);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	echo debut_droite('', true);

	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr>";
	echo "<td>";
	echo icone(_T('icone_retour'),
		$url = generer_url_ecrire($script, $args),
		"article-24.gif", "rien.gif");
	echo "</td>";
	echo "<td>" . http_img_pack('rien.gif', " ", "width='10'") ."</td>\n";
	echo "<td style='width: 100%'>";
	echo _T('texte_messages_publics');
	echo "<a href='$url'>".gros_titre($titre,'', false)."</a>";
	echo "</td></tr></table>";
}
?>
