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

include_spip('inc/minipres');
include_spip('inc/acces');
include_spip('inc/texte'); // utile pour l'espace public, deja fait sinon

// mais d'abord un tri par date (inverse)
function trier_par_date($a, $b) {
	return ($a['date'] < $b['date']);
}


//
// Prend un tableau et l'affiche au format rss
// (fonction inverse de analyser_backend)
// A completer (il manque des tests, des valeurs par defaut, les enclosures,
// differents formats de sortie, etc.)
//
function affiche_rss($rss, $intro = '', $fmt='') {
	if (!$fmt) $fmt = 'rss';
	if (function_exists($f = 'affiche_rss_'.$fmt)) {
		return $f($rss, $intro);
	}
	else
		spip_log("Format $fmt inconnu");
}

function affiche_rss_rss($rss, $intro = '') {
	// entetes
	$u = '<'.'?xml version="1.0" encoding="'.$GLOBALS['meta']['charset'].'"?'.">\n";

	$u .= '
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
	<title>'.texte_backend($intro['title']).'</title>
	<link>'.texte_backend(url_absolue($intro['url'])).'</link>
	<description>'.texte_backend($intro['description']).'</description>
	<language>'.texte_backend($intro['language']).'</language>
	';

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {
			if ($article['email'])
				$article['author'].=' <'.$article['email'].'>';
			$u .= '
	<item>
		<title>'.texte_backend($article['title']).'</title>
		<link>'.texte_backend(url_absolue($article['url'])).'</link>
		<dc:date>'.date_iso($article['date']).'</dc:date>
		<dc:format>text/html</dc:format>
		<dc:language>'.texte_backend($article['lang']).'</dc:language>
		<dc:creator>'.texte_backend($article['author']).'</dc:creator>
		<description>'.texte_backend(liens_absolus($article['description'])).'</description>
	</item>
';
		}
	}

	// pied
	$u .= '
	</channel>
</rss>
';

	return array($u, 'Content-Type: text/xml; charset='.$GLOBALS['meta']['charset']);
}

function affiche_rss_atom($rss, $intro = '') {
	// entetes
	$u = '<'.'?xml version="1.0" encoding="'.$GLOBALS['meta']['charset']
	.'"?'.">\n";
	$u .= '<feed xmlns="http://www.w3.org/2005/Atom"';
	if ($intro['language'])
		$u .= ' xml:lang="'.$intro['language'].'"';
	$u .= '>
	<title>'.texte_backend($intro['title']).'</title>
	<id>'.texte_backend(url_absolue($intro['url'])).'</id>
	<link href="'.texte_backend(url_absolue($intro['url'])).'"/>';
	if ($intro['description']) $u .= '<subtitle>'.texte_backend($intro['description']).'</subtitle>';
	$u .= '<link rel="self" type="application/atom+xml" href="'.texte_backend(url_absolue($_SERVER['REQUEST_URI'])).'"/>
	<updated>'.gmdate("Y-m-d\TH:i:s\Z").'</updated>'; // probleme, <updated> pourrait etre plus precis

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {
			$u .= "\n\t<entry";
			if ($article['lang'])
				$u .= ' xml:lang="'.texte_backend($article['lang']).'"';
			$u .= '>
		<title>'.texte_backend($article['title']).'</title>
		<id>'.texte_backend(url_absolue($article['url'])).'</id>
		<link rel="alternate" type="text/html" href="'.texte_backend(url_absolue($article['url'])).'"/>
		<published>'.date_iso($article['date']).'</published>
		<updated>'.date_iso($article['date']).'</updated>
		<author><name>'.texte_backend($article['author']).'</name>';
			if ($article['email'])
				$u .= '<email>'.texte_backend($article['email']).'</email>';
			$u .= '</author>
		<summary type="html">'.texte_backend(liens_absolus($article['description'])).'</summary>
	</entry>
';
		}
	}

	// pied
	$u .= '
</feed>
 ';

	return array($u, 'Content-Type: text/xml; charset='.$GLOBALS['meta']['charset']);
}

function affiche_rss_ical($rss, $intro = '') {

	// entetes
	$u =
'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
X-WR-CALNAME;VALUE=TEXT:'.filtrer_ical($intro['title']).'
X-WR-RELCALID:'.filtrer_ical(url_absolue($intro['url'])).'
';

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {

			// Regler la date de fin a h+60min
			if (!$article['enddate'])
				$article['enddate'] = date_ical($article['date'],60);
			else
				$article['enddate'] = date_ical($article['enddate']);

			// Type d'evenement
			if ($article['type'] == 'todo')
				$type = 'VTODO';
			else
				$type = 'VEVENT';

			$u .=
'BEGIN:'.$type.'
SUMMARY:'.filtrer_ical($article['title']).'
URL:'.filtrer_ical(url_absolue($article['url'])).'
DTSTAMP:'. date_ical($article['date']).'
DTSTART:'. date_ical($article['date']).'
DTEND:'. $article['enddate'].'
DESCRIPTION:'.filtrer_ical(liens_absolus($article['description'])).'
ORGANIZER:'.filtrer_ical($article['author']).'
CATEGORIES:--
END:'.$type.'
';
		}
	}

	// pied
	$u .= 'END:VCALENDAR';

	return array($u, 'Content-Type: text/calendar; charset=utf-8');
}

//
// Fonctions de remplissage du RSS
//


// Suivi des revisions d'articles
function rss_suivi_versions($a) {
	include_spip('inc/suivi_versions');
	return  afficher_suivi_versions (0, $a['id_secteur'], $a['id_auteur'], $a['lang_choisie'], true, true);

}

// Suivi des forums
function rss_suivi_forums($a, $query_forum='', $lien_moderation=false) {
	$rss = array();

	$result_forum = spip_query("
	SELECT	* " . $query_forum . "
	ORDER BY date_heure DESC LIMIT 20"
	);

	while ($t = spip_fetch_array($result_forum)) {
		$item = array();
		$item['title'] = typo($t['titre']);
		if ($a['page'] == 'public'
		AND $t['statut']<>'publie'
		)
			$item['title'] .= ' ('.$t['statut'].')';
		$item['date'] = $t['date_heure'];
		$item['author'] = $t['auteur'];
		$item['email'] = $t['email_auteur'];

		if ($lien_moderation)
		  $item['url'] = generer_url_ecrire('controle_forum', 'page='.$a['page'] .'&debut_id_forum='.$t['id_forum']);
		else
			$item['url'] = generer_url_forum($t['id_forum']);

		$item['description'] = propre($t['texte']);
		if ($GLOBALS['les_notes']) {
			$item['description'] .= '<hr />'.$GLOBALS['les_notes'];
			$GLOBALS['les_notes'] = '';
		}
		if ($t['nom_site'] OR vider_url($t['url_site']))
			$item['description'] .= propre("\n- [".$t['nom_site']."->".$t['url_site']."]<br />");

		$rss[] = $item;
	}

	return $rss;
}



// Suivi de la messagerie privee
function rss_suivi_messagerie($a) {
	$rss = array();

	// 1. les messages
	$s = spip_query("SELECT * FROM spip_messages AS messages,
	spip_auteurs_messages AS lien WHERE lien.id_auteur=".$a['id_auteur']
	." AND lien.id_message=messages.id_message
	GROUP BY messages.id_message ORDER BY messages.date_heure DESC");
	while ($t = spip_fetch_array($s)) {
		if ($compte++<10) {
			$auteur = spip_fetch_array(spip_query("SELECT
			auteurs.nom AS nom, auteurs.email AS email
			FROM spip_auteurs AS auteurs,
			spip_auteurs_messages AS lien
			WHERE lien.id_message=".$t['id_message']."
			AND lien.id_auteur!=".$t['id_auteur']."
			AND lien.id_auteur = auteurs.id_auteur"));
			$item = array(
				'title' => typo($t['titre']),
				'date' => $t['date_heure'],
				'author' => typo($auteur['nom']),
				'email' => $auteur['email'],
				'description' => propre($t['texte']),
				'url' => generer_url_ecrire('message', 'id_message='.$t['id_message'] ));
			$rss[] = $item;
		}
		$messages_vus[] = $t['id_message'];
	}

	// 2. les reponses aux messages
	if ($messages_vus) {
		$s = spip_query("SELECT * FROM spip_forum WHERE id_message
		IN (".join(',', $messages_vus).")
		ORDER BY date_heure DESC LIMIT 10");

		while ($t = spip_fetch_array($s)) {
			$item = array(
				'title' => typo($t['titre']),
				'date' => $t['date_heure'],
				'description' => propre($t['texte']),
				'author' => typo($t['auteur']),
				'email' => $t['email_auteur'],
				'url' => generer_url_ecrire('message', 'id_message='.$t['id_message']	.'#'.$t['id_forum']  ));
			$rss[] = $item;
		}
	}

	return $rss;
}

// Suivi de la page "a suivre" : articles, breves, sites proposes et publies
function rss_a_suivre($a) {
	$rss_articles = rss_articles("statut = 'prop'");
	$rss_breves = rss_breves("statut = 'prop'");
	$rss_sites = rss_sites("statut = 'prop'");

	return array_merge($rss_articles, $rss_breves, $rss_sites);
}

function rss_articles($critere) {
	$rss = array();
	$s = spip_query("SELECT * FROM spip_articles WHERE $critere
	ORDER BY date DESC LIMIT 10");
	while ($t = spip_fetch_array($s)) {
		$auteur = spip_fetch_array(spip_query("SELECT
			auteurs.nom AS nom, auteurs.email AS email
			FROM spip_auteurs AS auteurs,
			spip_auteurs_articles AS lien
			WHERE lien.id_article=".$t['id_article']."
			AND lien.id_auteur = auteurs.id_auteur"));
		$item = array(
			'title' => typo($t['titre']),
			'date' => $t['date'],
			'author' => typo($auteur['nom']),
			'email' => $auteur['email'],
			'description' => propre(couper("{{".$t['chapo']."}}\n\n".$t['texte'],300)),
			'url' => generer_url_ecrire('articles', 'id_article='.$t['id_article']   ));
		if ($t['statut'] == 'prop')
		  $item['title'] = _T('info_article_propose').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}


function rss_breves($critere) {
	$rss = array();
	$s = spip_query("SELECT * FROM spip_breves WHERE $critere
	ORDER BY date_heure DESC LIMIT 10");
	while ($t = spip_fetch_array($s)) {
		$item = array(
			'title' => typo($t['titre']),
			'date' => $t['date_heure'],
			'description' => propre(couper($t['texte'],300)),
			'url' => generer_url_ecrire('breves_voir', 'id_breve='.$t['id_breve']   ));
		if ($t['statut'] == 'prop')
			$item['title'] = _T('titre_breve_proposee').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}


function rss_sites($critere) {
	$rss = array();
	$s = spip_query("SELECT * FROM spip_syndic WHERE $critere
	ORDER BY date DESC LIMIT 10");
	while ($t = spip_fetch_array($s)) {
		$item = array(
			'title' => typo($t['titre']." ".$t['url_site']),
			'date' => $t['date'],
			'description' => propre(couper($t['texte'],300)),
			'url' => generer_url_ecrire('sites', 'id_syndic='.$t['id_syndic']   ));
		if ($t['statut'] == 'prop')
			$item['title'] = _T('info_site_attente').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}

// On recoit un op (operation) + args (arguments)
// + id (id_auteur) + cle (low_sec(id_auteur, "op args"))
// On verifie que la cle correspond
// On cree ensuite le RSS correspondant a l'operation


function action_rss_dist()
{
  global $args, $cle, $fmt, $id, $lang, $op;

  charger_generer_url();

//
// Verifier la securite du lien et decoder les arguments
//

// Pour memoire, la forme des URLs : 
// 1.8: spip_rss.php?op=forums&args=page-public&id=4&cle=047b4183&lang=fr
// 1.9: spip.php?action=rss&op=forums&args=page-public&id=4&cle=047b4183&lang=fr
// ou encore spip.php?action=rss&op=a-suivre&id=5&cle=5731e121&lang=fr

spip_timer('rss');
if (!verifier_low_sec ($id, $cle,
"rss $op $args"
)) {
	$op = 'erreur securite';
	unset($a);
} else {
	$a = array();
	foreach (split(':', $args) as $bout) {
		list($var, $val) = split('-', $bout, 2);
		$a[$var] = $val;
	}
	include_spip('inc/lang');
	lang_select($lang);
}

//
// Choisir la fonction de calcul du RSS
//
switch($op) {
	# forum public
	case 'forum':
		include_spip('inc/forum');
		if ($id = intval($a['id_article'])) {
			$critere = "statut='publie' AND id_article=$id";
			$url = generer_url_article($id);
		}
		else if ($id = intval($a['id_syndic'])) {
			$critere = "statut='publie' AND id_syndic=$id";
			$url = generer_url_site($id);
		}
		else if ($id = intval($a['id_breve'])) {
			$critere = "statut='publie' AND id_breve=$id";
			$url = generer_url_breve($id);
		}
		else if ($id = intval($a['id_rubrique'])) {
			$critere = "statut='publie' AND id_rubrique=$id";
			$url = generer_url_rubrique($id);
		}
		else if ($id = intval($a['id_thread'])) {
			$critere = "statut='publie' AND id_thread=$id";
			$url = generer_url_forum($id);
		}
		if ($id) $rss = rss_suivi_forums($a, "FROM spip_forum WHERE $critere", false);
		$title = _T("ecrire:titre_page_forum_suivi");
		break;
	# suivi prive des forums
	case 'forums':
		include_spip('inc/forum');
		$critere = critere_statut_controle_forum($a['page']);
		$rss = rss_suivi_forums($a, $critere, true);
		$title = _T("ecrire:titre_page_forum_suivi")." (".$a['page'].")";
		$url = generer_url_ecrire('controle_forum', 'page='.$a['page']);
		break;
	# revisions des articles
	case 'revisions':
		$rss = rss_suivi_versions($a);
		$title = _T("icone_suivi_revisions");
		$url = "";
		foreach (array('id_secteur', 'id_auteur', 'lang_choisie') as $var)
			if ($a[$var]) $url.= $var.'='.$a[$var] . '&';
		$url = generer_url_ecrire('suivi_revisions', $url);
		break;
	# messagerie privee
	case 'messagerie':
		$rss = rss_suivi_messagerie($a);
		$title = _T("icone_messagerie_personnelle");
		$url = generer_url_ecrire('messagerie');
		break;
	# a suivre
	case 'a-suivre':
		$rss = rss_a_suivre($a);
		$title = _T("icone_a_suivre");
		$url = _DIR_RESTREINT_ABS;
		break;
	case 'erreur securite':
		$rss = array(array('title' => _T('login_erreur_pass')));
		$title = _T('login_erreur_pass');
		$url = '';
		break;
	default:
		$rss = array(array('title' => _T('forum_titre_erreur')));
		$title = _T('forum_titre_erreur');
		$url = '';
		break;
}

//
// Envoyer le RSS
//
$intro = array(
	'title' => "[".$GLOBALS['meta']['nom_site']."] RSS ".$title,
	'url' => $url,
	'language'=> $GLOBALS['spip_lang']
);

list($content,$header) = affiche_rss($rss, $intro, $fmt);
if ($header) @header($header);
echo $content;

spip_log("spip_rss: ".spip_timer('rss'));

}

?>
