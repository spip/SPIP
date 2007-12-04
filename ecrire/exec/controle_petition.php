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
	exec_controle_petition_args(intval(_request('id_article')),
				    _request('type'),
				    intval(_request('debut')));
}

function exec_controle_petition_args($id_article, $type, $debut)
{
	include_spip('inc/presentation');

	$titre =' ';
	$statut='new';
	if ($id_article) {
		if ($row = sql_fetsel("titre,statut", "spip_articles", "id_article=$id_article"));
		if (!$row)
			$id_article = 0;
		else {
			$titre = $row['titre'];
			$statut = $row['statut'];	
		}
	}

	if (!(
		autoriser('modererpetition')
		OR (
			$id_article > 0
			AND autoriser('modererpetition', 'article', $id_article)
			)
		)) {
		include_spip('inc/minipres'); 
		echo minipres();}
	else {
		$signatures = charger_fonction('signatures', 'inc');
		$r = $signatures('controle_petition',
			$id_article,
			$debut, 
			"(statut='publie' OR statut='poubelle')",
			"date_time DESC",
			 10,
			 $type);

		if (_request('var_ajaxcharset'))
			ajax_retour($r);
		else controle_petition_page($id_article, $debut, $type, $titre, $statut, $r);
	}
}

function controle_petition_page($id_article, $debut, $type, $titre, $statut, $r)
{
	$args = ($id_article ? "id_article=$id_article" :'')
		. ($debut ? "debut=$debut" : '')
		. '&type=';

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
	echo debut_gauche('', true);

	echo debut_droite('', true);
  
	echo gros_titre(_T('titre_suivi_petition'),'', false);

	echo debut_onglet();
	echo onglet(_L('Signatures confirm&eacute;es'), generer_url_ecrire('controle_petition', $args . "public"), "public", $type=='public', "forum-public-24.gif");
	echo onglet(_L('Signatures en attente de validation'), generer_url_ecrire('controle_petition', $args . "interne"), "interne", $type=='interne', "forum-interne-24.gif");
	echo fin_onglet(), '<br /><br />';

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
?>
