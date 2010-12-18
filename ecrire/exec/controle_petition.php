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

// http://doc.spip.org/@exec_controle_petition_dist
function exec_controle_petition_dist()
{
	exec_controle_petition_args(intval(_request('id_article')),
		_request('type'),
		_request('date'),
		intval(_request('debut')),
		intval(_request('id_signature')),
		intval(_request('pas'))); // a proposer grapiquement
}

// http://doc.spip.org/@exec_controle_petition_args
function exec_controle_petition_args($id_article, $type, $date, $debut, $id_signature, $pas=NULL)
{
	if ($id_signature) {
		$id_article = sql_getfetsel("id_article", "spip_signatures", "id_signature=$id_signature");
		$where = '(id_signature=' . sql_quote($id_signature) . ') AND ';
	} else 	$where = '';
	if ($id_article AND !($titre = sql_getfetsel("titre", "spip_articles", "id_article=$id_article"))) {
		include_spip('inc/minipres');
                echo minipres(_T('public:aucun_article'));
	}	else controle_petition_args($id_article, $type, $date, $debut, $titre, $where, $pas);
}

function controle_petition_args($id_article, $type, $date, $debut, $titre, $where, $pas)
{
	if (!preg_match('/^\w+$/',$type)) $type = 'public';
	if ($id_article) $where .= "id_article=$id_article AND ";
	$extrait = "(statut='publie' OR statut='poubelle')";
	if ($type == 'interne') $extrait = "NOT($extrait)";
	$where .= $extrait;
	$order = "date_time DESC";
	if (!$pas) $pas = 10;
	if ($date) {
		include_spip('inc/forum');
		$query = array('SELECT' => 'UNIX_TIMESTAMP(date_time) AS d',
				'FROM' => 'spip_signatures', 
				'WHERE' => $where,
				'ORDER BY' => $order);
		$debut = navigation_trouve_date($date, 'd', $pas, $query);
	}
	$signatures = charger_fonction('signatures', 'inc');

	$res = $signatures('controle_petition', $id_article, $debut, $pas, $where, $order, $type);

	if (_AJAX) {
			ajax_retour($res);
	} else {

		if (autoriser('modererpetition')
		OR (
			$id_article > 0
			AND autoriser('modererpetition', 'article', $id_article)
			))
			$ong = controle_petition_onglet($id_article, $debut, $type);
		else {
		  $type = 'public';
		  $ong = '';
		}
		controle_petition_page($id_article, $titre, $ong, $res);
	}
}

// http://doc.spip.org/@controle_petition_page
function controle_petition_page($id_article, $titre,  $ong, $corps)
{
	if ($id_article) {
		$a =  generer_url_ecrire("statistiques_visites","id_article=$id_article");
		$rac = "<br /><br /><br /><br /><br />" .
		bloc_des_raccourcis(icone_horizontale(_T('icone_statistiques_visites'),$a, "statistiques-24.gif","rien.gif", false));

		$titre = "<a href='" .
			generer_url_entite($id_article,'article') .
			"'>" .
			typo($titre) .
			"</a>" .
			" <span class='arial1'>(" .
			_T('info_numero_abbreviation') .
			$id_article .
			")</span>";

		if (!sql_countsel('spip_petitions', "id_article=$id_article"))
			$titre .= '<br >' . _T('info_petition_close');

		$args = array('id_article' => $id_article);
	} else  {
		$args = array();
		$rac = $titre = '';
	}

	$head = _T('titre_page_controle_petition');
	$idom = "editer_signature-" . $id_article;
	$commencer_page = charger_fonction('commencer_page', 'inc');

	echo $commencer_page($head, "forum", "suivi-petition");
	echo debut_gauche('', true);
	echo $rac;
	echo debut_droite('', true);
	echo gros_titre(_T('titre_suivi_petition'),'', false);
	echo $ong; 
	echo bouton_spip_rss('signatures', $args);
	echo $titre;
	echo  "<br /><br />";
	echo "<div id='", $idom, "' class='serif2'>", $corps, "</div>";
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@controle_petition_onglet
function controle_petition_onglet($id_article, $debut, $type, $arg='')
{
	$arg .= ($id_article ? "id_article=$id_article&" :'');
	$arg2 = ($debut ? "debut=$debut&" : '');
	if ($type=='public') {
	  $argp = $arg2;
	  $argi = '';
	} else {
	  $argi = $arg2;
	  $argp = '';
	}

	return debut_onglet()
	  . onglet(_T('titre_signatures_confirmees'), generer_url_ecrire('controle_petition', $argp . $arg . "type=public"), "public", $type=='public', "forum-public-24.gif")
	.  onglet(_T('titre_signatures_attente'), generer_url_ecrire('controle_petition', $argi . $arg .  "type=interne"), "interne", $type=='interne', "forum-interne-24.gif")
	. fin_onglet()
	. '<br />';
}
?>
