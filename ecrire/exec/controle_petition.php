<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
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
		$r = sql_fetsel("id_article, statut", "spip_signatures", "id_signature=$id_signature");
		$id_article = $r['id_article'];
		$s = $r['statut'];
		$type = ($s=='publie' OR $s=='poubelle') ? 'public' : 'interne';
		$where = '(id_signature=' . sql_quote($id_signature) . ') AND ';
	} else 	$where = '';
	if ($id_article AND !($titre = sql_getfetsel("titre", "spip_articles", "id_article=$id_article"))) {
		include_spip('inc/minipres');
                echo minipres(_T('public:aucun_article'));
	}	else controle_petition_args($id_article, $type, $date, $debut, $titre, $where, $pas, $id_signature);
}

function controle_petition_args($id_article, $type, $date, $debut, $titre, $where, $pas, $id_signature=0)
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
		$count = ($type != 'interne') ? 0 : sql_countsel("spip_signatures", $where);
		controle_petition_page($id_article, $titre, $id_signature ? '' : $type, $res, $count);
	}
}

// http://doc.spip.org/@controle_petition_page
function controle_petition_page($id_article, $titre, $type,  $corps, $count)
{
	$args = array();
	$rac = '';

	if (!(autoriser('modererpetition')
	OR (
		$id_article > 0
		AND autoriser('modererpetition', 'article', $id_article)
	    ))) {
		$ong = '';
	} else {
		$ong = controle_petition_onglet($id_article, $debut, $type, '');
		if ($id_article) {
			$h = generer_url_ecrire("statistiques_visites","id_article=$id_article");
			$rac = icone_horizontale(_T('icone_statistiques_visites'), $h, "statistiques-24.gif","rien.gif", false);
			if ($type !== 'public') {

				$h = redirige_action_auteur('editer_signatures', $id_article . 'A', 'controle_petition', "id_article=$id_article&type=interne");
				$rac .= icone_horizontale(_T('icone_relancer_signataire') . " ($count)", $h, "envoi-message-24.gif","rien.gif", false);
			}
			$rac = bloc_des_raccourcis($rac);
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
		} else {

		  $q = sql_select('A.titre, A.date, A.id_article, count(*) AS n', 
			     'spip_signatures AS S LEFT JOIN spip_articles AS A ON A.id_article=S.id_article',
			     '',
			     'A.id_article',
				  'n desc',
				  "0,10");
		  while ($r = sql_fetch($q)) {
		    $id = $r['id_article'];
		    $h = generer_url_entite($id, 'article');
		    $title = affdate_jourcourt($r['date']) . "\n" .$r['titre'];
		    $rac .= "<li><a href='$h' title=\"" .  attribut_html($title).  '">' . _T('info_numero_abbreviation') . " $id" . '</a>&nbsp;: '. $r['n'] . ' ' . _T('signatures') . "</li>";
		  }
		  if ($rac) $rac = "<ul>$rac</ul>";
		  $rac = debut_cadre_enfonce('',true)
		    . "\n<div style='font-size: x-small' class='verdana1'><b>"
		    ._T('public:articles_populaires')
		    ."</b>"
		    . $rac
		    . "</div>"
		    . fin_cadre_enfonce(true);

		}
		$rac = "<br /><br /><br /><br /><br />" . $rac;
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
