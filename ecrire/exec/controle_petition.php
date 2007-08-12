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

// http://doc.spip.org/@exec_controle_petition_dist
function exec_controle_petition_dist()
{
	include_spip('inc/presentation');

	$id_article = intval(_request('id_article'));
	if ($id_article) {
		$titre = sql_fetch(spip_query("SELECT titre FROM spip_articles WHERE id_article=$id_article"));
		if (!$titre) $id_article = 0; else $titre = $titre['titre'];
	} else $titre =' ';

	if (!(
		autoriser('modererpetition')
		OR (
			$id_article > 0
			AND autoriser('modererpetition', 'article', $id_article)
			)
		))
	  {include_spip('inc/minipres'); echo minipres(); exit;}

	$debut = intval(_request('debut'));

	$signatures = charger_fonction('signatures', 'inc');

	$r = $signatures('controle_petition',
			$id_article,
			$debut, 
			"(statut='publie' OR statut='poubelle')",
			"date_time DESC",
			10);

	if (_request('var_ajaxcharset'))
		ajax_retour($r);
	else {

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
		debut_gauche();

		debut_droite();
  
		gros_titre(_T('titre_suivi_petition'));

		if (!$titre)
		  echo _T('trad_article_inexistant');
		else {
		  if ($id_article) {
			  echo  "<a href='",
			  (($statut == 'publie') ? 
			   generer_url_action('redirect', "id_article=$id_article") :
			   generer_url_ecrire('articles', "id_article=$id_article")),
			  "'>",
			  typo($titre),
			  "</a>",
			  " <span class='arial1'>(",
			  _T('info_numero_abbreviation'),
			  $id_article,
			  ")</span>";
		  }
		  $a = "editer_signature-" . $id_article;

		  echo  "<div id='", $a, "' class='serif2'>", $r, "</div>";
		}
		echo fin_gauche(), fin_page();
	}
}

?>
