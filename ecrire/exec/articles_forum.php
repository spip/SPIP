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
include_spip('inc/forum'); // pour boutons_controle_forum 

// http://doc.spip.org/@exec_articles_forum_dist
function exec_articles_forum_dist()
{
	$id_article = intval(_request('id_article'));

	if (!autoriser('modererforum', 'article', $id_article))
		return;

	$debut = intval(_request('debut'));
	$pack = intval(_request('pack'));
	$enplus = intval(_request('enplus'));

	if (!$pack) $pack = 5; // nb de forums affiches par page
	if (!$enplus) $enplus = 200;	// intervalle affiche autour du debut

	$result = spip_query("SELECT titre, id_rubrique FROM spip_articles WHERE id_article=$id_article");

	if ($row = sql_fetch($result)) {
		$titre = $row["titre"];
		$id_rubrique = $row["id_rubrique"];
	}
	
	$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
	$limitnb = $debut + $enplus - $limitdeb;

	$ancre = 'navigation-forum';
	$result = sql_select("id_forum", "spip_forum",  "id_article=$id_article AND id_parent=0 AND statut IN ('publie', 'off', 'prop')", '', '', "$limitdeb, $limitnb");

	$res = sql_select("pied.id_forum,pied.id_parent,pied.id_rubrique,pied.id_article,pied.id_breve,pied.id_message,pied.id_syndic,pied.date_heure,pied.titre,pied.texte,pied.auteur,pied.email_auteur,pied.nom_site,pied.url_site,pied.statut,pied.ip,pied.id_auteur, max(thread.date_heure) AS date", "spip_forum AS pied, spip_forum AS thread", "pied.id_article=$id_article AND pied.id_parent=0 AND pied.statut IN ('publie', 'off', 'prop') AND thread.id_thread=pied.id_forum", "thread.id_thread",  "date DESC",  "$debut, $pack");

	$mess = affiche_navigation_forum("articles_forum", "id_article=$id_article", $debut, $limitdeb, $pack, $ancre, $result)
	. '<br />'
	. afficher_forum($res,"", '', $id_article);

	if (_request('var_ajaxcharset'))
		ajax_retour($mess);
	else {

	 	pipeline('exec_init',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page($titre, "naviguer", "articles", $id_rubrique);

		articles_forum_cadres($id_rubrique, $id_article, $titre, 'articles', "id_article=$id_article");

		echo "<div class='serif2' id='$ancre'>";
		echo $mess;
		echo '</div>';
		
		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@articles_forum_cadres
function articles_forum_cadres($id_rubrique, $id_article, $titre, $script, $args)
{
	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	echo debut_boite_info(true);

	echo "<p style='text-align: left; ' class='verdana1 spip_x-small'>",
	  _T('info_gauche_suivi_forum'),
	  aide ("suiviforum"),
	  "</p>";

	echo "<div style='text-align: "
	  . $GLOBALS['spip_lang_right']
	  . ";'>"
	  . bouton_spip_rss('forum', array('id_article' => $id_article))
	  . "</div>";

	echo fin_boite_info(true);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_forum','id_article'=>$id_article),'data'=>''));
	echo debut_droite('', true);

	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr>";
	echo "<td>";
	echo icone(_T('icone_retour'),
		generer_url_ecrire($script, $args),
		"article-24.gif", "rien.gif");
	echo "</td>";
	echo "<td>" . http_img_pack('rien.gif', " ", "width='10'") ."</td>\n";
	echo "<td style='width: 100%'>";
	echo _T('texte_messages_publics');
	echo gros_titre($titre,'', false);
	echo "</td></tr></table>";
}
?>
