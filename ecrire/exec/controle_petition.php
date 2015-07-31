<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
				    intval(_request('debut')),
				    intval(_request('id_signature')));
}

function exec_controle_petition_args($id_article, $type, $debut, $id_signature)
{
	include_spip('inc/presentation');

	if (!preg_match('/^\w+$/',$type)) $type = 'public';
	$statut='new';
	$where = '';
	if ($id_signature) {
		$id_article = sql_getfetsel("id_article", "spip_signatures", "id_signature=$id_signature");
		if ($id_article)
			$where = '(id_signature=' . sql_quote($id_signature) . ') AND ';
	}
	if ($id_article) {
		$row = sql_fetsel("titre,statut", "spip_articles", "id_article=$id_article");
		if (!$row) {
			$id_article = 0;
			$titre = '';
		} else {
			$titre = $row['titre'];
			$statut = $row['statut'];	
		}
	} else 	$titre = '    '; // ok.

	if (!$titre) {
		include_spip('inc/minipres');
                echo minipres(_T('public:aucun_article'));
	} elseif (!(
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
			$where . "(statut='publie' OR statut='poubelle')",
			"date_time DESC",
			 15,
			 $type);

		if (_request('var_ajaxcharset'))
			ajax_retour($r);
		else {
		  $ong = controle_petition_onglet($id_article, $debut, $type);
		  controle_petition_page($id_article, $titre, $ong, $statut, $r);
		}
	}
}

function controle_petition_page($id_article, $titre,  $ong, $statut, $corps)
{
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
	echo debut_gauche('', true);

	echo debut_droite('', true);
  
	echo gros_titre(_T('titre_suivi_petition'),'', false);

	echo $ong; 

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
		if (!sql_countsel('spip_petitions', "id_article=$id_article"))
			echo '<br >', _L('petition close');
	}
	$a = "editer_signature-" . $id_article;

	echo  "<br /><br /><div id='", $a, "' class='serif2'>", $corps, "</div>";

	echo fin_gauche(), fin_page();
}

function controle_petition_onglet($id_article, $debut, $type)
{
	$arg = ($id_article ? "id_article=$id_article&" :'');
	$arg2 = ($debut ? "debut=$debut&" : '');
	if ($type=='public') {
	  $argp = $arg2;
	  $argi = '';
	} else {
	  $argi = $arg2;
	  $argp = '';
	}

	return debut_onglet()
	  . onglet(_L('Signatures confirm&eacute;es'), generer_url_ecrire('controle_petition', $argp . $arg . "type=public"), "public", $type=='public', "forum-public-24.gif")
	.  onglet(_L('Signatures en attente de validation'), generer_url_ecrire('controle_petition', $argi . $arg .  "type=interne"), "interne", $type=='interne', "forum-interne-24.gif")
	. fin_onglet()
	. '<br />';
}
?>
