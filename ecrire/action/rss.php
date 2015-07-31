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

include_spip('inc/acces');
include_spip('inc/texte'); // utile pour l'espace public, deja fait sinon

// On recoit un op (operation) + args (arguments)
// + id (id_auteur) + cle (low_sec(id_auteur, "op args"))
// On verifie que la cle correspond
// On cree ensuite le RSS correspondant a l'operation

// Pour memoire, la forme des URLs : 
// 1.8: spip_rss.php?op=forums&args=page-public&id=4&cle=047b4183&lang=fr
// 1.9: spip.php?action=rss&op=forums&args=page-public&id=4&cle=047b4183&lang=fr
// ou encore spip.php?action=rss&op=a-suivre&id=5&cle=5731e121&lang=fr

// http://doc.spip.org/@action_rss_dist
function action_rss_dist()
{
	$op  = _request('op');
	$args = _request('args');
	$cle = _request('cle');
	$id = _request('id');
	$lang = _request('lang');

	spip_timer('rss');

	$f = charger_fonction(str_replace('-', '_', $op), 'rss');
	if (!$f OR !verifier_low_sec($id, $cle, "rss $op $args")) {
		$f = 'rss_erreur';
	} else {
		charger_generer_url();
		lang_select($lang);
	}
	xml_rss($f($args));
	spip_log("spip_rss s'applique sur '$op $args pour $id' en " . spip_timer('rss'));
}

// http://doc.spip.org/@rss_split_args
function rss_split_args($args)
{
	$a = array();
	foreach (split(':', $args) as $bout) {
		list($var, $val) = split('-', $bout, 2);
		$a[$var] = $val;
	}
	return $a;
}

//
// Fonctions de remplissage du RSS
//

// Suivi des revisions d'articles
// http://doc.spip.org/@rss_suivi_versions
function rss_suivi_versions($a) {
	include_spip('inc/suivi_versions');
	return  afficher_suivi_versions (0, $a['id_secteur'], $a['id_auteur'], $a['lang_choisie'], true, true);

}

// Suivi des forums
// http://doc.spip.org/@rss_suivi_forums
function rss_suivi_forums($page, $from, $where, $lien_moderation=false) {

	$rss = sql_allfetsel('*', $from, $where,'', "date_heure DESC", 20);

	foreach ($rss as $k => $t) {
		$item = array();
		$item['titre'] = $t['titre'];
		if ($page == 'public' AND $t['statut']<>'publie')
			$item['titre'] .= ' ('.$t['statut'].')';
		$item['date'] = $t['date_heure'];
		$item['nom'] = $t['auteur'];
		$item['email_auteur'] = $t['email_auteur'];

		if ($lien_moderation)
			$item['url'] = generer_url_ecrire('controle_forum', 'type='.$page .'&debut_id_forum='.$t['id_forum']);
		else
			$item['url'] = generer_url_forum($t['id_forum']);

		$item['in_reply_to_url'] = generer_url_forum_parent($t['id_forum']);
		$item['texte'] = $t['texte'];
		if ($t['nom_site'] OR vider_url($t['url_site']))
			$item['texte'] .= ("<br />\n- [".$t['nom_site']."->".$t['url_site']."]<br />");

		$rss[$k] = $item;
	}
	return $rss;
}

// Suivi de la messagerie privee
// http://doc.spip.org/@rss_messagerie
function  rss_messagerie($a)
{
	$a = rss_split_args($a);
	$rss = $messages_vus = array();

	// 1. les messages
	$s = sql_select("*", "spip_messages AS messages, spip_auteurs_messages AS lien", "lien.id_auteur=".$a['id_auteur']." AND lien.id_message=messages.id_message ", " messages.id_message ", " messages.date_heure DESC");
	while ($t = sql_fetch($s)) {
		if ($compte++<10) {
			$auteur = sql_fetsel("auteurs.nom AS nom, auteurs.email AS email_auteur", "spip_auteurs AS auteurs, spip_auteurs_messages AS lien", "lien.id_message=".$t['id_message']." AND lien.id_auteur!=".$t['id_auteur']." AND lien.id_auteur = auteurs.id_auteur");
			$item = array(
				'titre' => $t['titre'],
				'date' => $t['date_heure'],
				'nom' => $auteur['nom'],
				'email_auteur' => $auteur['email_auteur'],
				'texte' => $t['texte'],
				'url' => generer_url_ecrire('message', 'id_message='.$t['id_message'] ));
			$rss[] = $item;
		}
		$messages_vus[] = $t['id_message'];
	}

	// 2. les reponses aux messages
	if ($messages_vus) {
		$s = sql_select("*", "spip_forum", sql_in('id_message', $messages_vus), "", "date_heure DESC", "10");

		while ($t = sql_fetch($s)) {
			$item = array(
				'titre' => $t['titre'],
				'date' => $t['date_heure'],
				'texte' => $t['texte'],
				'nom' => $t['auteur'],
				'email_auteur' => $t['email_auteur'],
				'url' => generer_url_ecrire('message', 'id_message='.$t['id_message']	.'#'.$t['id_forum']  ));
			$rss[] = $item;
		}
	}
	usort($rss, 'trier_par_date');
	$title = _T("icone_messagerie_personnelle");
	$url = generer_url_ecrire('messagerie');
	return array($title, $rss, $url);
}


// http://doc.spip.org/@rss_articles
function rss_articles($critere) {
	$rss = sql_allfetsel("*", "spip_articles", $critere, "", "date DESC", "10");
	foreach ($rss as $k => $t) {
		$auteur = sql_fetsel("auteurs.nom AS nom, auteurs.email AS email_auteur", "spip_auteurs AS auteurs LEFT JOIN spip_auteurs_articles AS lien ON lien.id_auteur = auteurs.id_auteur", "lien.id_article=".$t['id_article']);
		$item = array(
			'titre' => $t['titre'],
			'date' => $t['date'],
			'nom' => $auteur['nom'],
			'email_auteur' => $auteur['email_auteur'],
			'texte' => couper("{{".$t['chapo']."}}\n\n".$t['texte'],300),
			'url' => generer_url_ecrire('articles', 'id_article='.$t['id_article']   ));
		if ($t['statut'] == 'prop')
		  $item['titre'] = _T('info_article_propose').' : '.$item['titre'];

		$rss[$k] = $item;
	}
	return $rss;
}


// http://doc.spip.org/@rss_breves
function rss_breves($critere) {
	$rss = sql_allfetsel("*", "spip_breves", $critere, "", "date_heure DESC", "10");
	foreach ($rss as $k =>$t) {
		$item = array(
			'titre' => $t['titre'],
			'date' => $t['date_heure'],
			'texte' => couper($t['texte'],300),
			'url' => generer_url_ecrire('breves_voir', 'id_breve='.$t['id_breve']   ));
		if ($t['statut'] == 'prop')
			$item['titre'] = _T('titre_breve_proposee').' : '.$item['titre'];

		$rss[$k] = $item;
	}
	return $rss;
}


// http://doc.spip.org/@rss_sites
function rss_sites($critere) {
	$rss = sql_allfetsel("*", "spip_syndic", $critere, "", "date DESC", "10");
	foreach ($rss  as $k => $t) {
		$item = array(
			'titre' => $t['titre']." ".$t['url_site'],
			'date' => $t['date'],
			'texte' => couper($t['texte'],300),
			'url' => generer_url_ecrire('sites', 'id_syndic='.$t['id_syndic']   ));
		if ($t['statut'] == 'prop')
			$item['titre'] = _T('info_site_attente').' : '.$item['titre'];

		$rss[$k] = $item;
	}
	return $rss;
}


// http://doc.spip.org/@rss_signatures
function rss_signatures($a) {
	$rss = sql_allfetsel("S.id_article AS id_article, A.titre AS titre, S.date_time AS date, S.nom_email AS nom, S.ad_email AS email_auteur, S.message AS texte, S.url_site AS chapo", "spip_signatures AS S LEFT JOIN spip_articles AS A ON S.id_article=A.id_article", "S.statut='publie'", "", "date DESC", "50");
	foreach ($rss as $k => $t) {
		$item = array(
			'titre' => $t['titre'],
			'date' => $t['date'],
			'nom' => $t['nom'],
			'email_auteur' => $t['email_auteur'],
			'texte' => couper("{{".$t['chapo']."}}\n\n".$t['texte'],300),
			'url' => generer_url_article($t['id_article']));

		$rss[$k] = $item;
	}
	return array('(' . _T('titre_suivi_petition') . ')',
		     $rss,
		     generer_url_ecrire('controle_petition'));
}

# forum public
// http://doc.spip.org/@rss_forum
function rss_forum($a)
{
	$a = rss_split_args($a);
	$page = $a['page'];
	include_spip('inc/forum');
	if ($id = intval($a['id_article'])) {
		$rss = rss_suivi_forums($page, "spip_forum", "statut='publie' AND id_article=$id", false);
		$title = sql_getfetsel('titre', "spip_articles", "id_article=$id");
		$url = generer_url_article($id);
	}
	else if ($id = intval($a['id_syndic'])) {
		$rss = rss_suivi_forums($page, "spip_forum", "statut='publie' AND id_syndic=$id", false);
		$title = sql_getfetsel("nom_site", "spip_syndic", "id_syndic=$id");
		$url = generer_url_site($id);
	}
	else if ($id = intval($a['id_breve'])) {
		$rss = rss_suivi_forums($page, "spip_forum", "statut='publie' AND id_breve=$id", false);
		$title = sql_getfetsel('titre', "spip_breves", "id_breve=$id");
		$url = generer_url_breve($id);
	}
	else if ($id = intval($a['id_rubrique'])) {
		$rss = rss_suivi_forums($page, "spip_forum", "statut='publie' AND id_rubrique=$id", false);
		$title = sql_getfetsel('titre', "spip_rubrique", "id_rubrique=$id");
		$url = generer_url_rubrique($id);
	}
	else if ($id = intval($a['id_thread'])) {
		$rss = rss_suivi_forums($page, "spip_forum", "statut='publie' AND id_thread=$id", false);
		$title = sql_getfetsel('titre', "spip_forum", "id_forum=$id");
		$url = generer_url_forum($id);
	} else { $rss = array(); $url = $titre = '';}
	$title .=  ' (' . _T("ecrire:titre_page_forum_suivi") .')';
	return array($title, $rss, $url);
}

# suivi prive des forums
// http://doc.spip.org/@rss_forums
function  rss_forums($a)
{
	$a = rss_split_args($a);
	$page = $a['page'];
	include_spip('inc/forum');
	list($f,$w) = critere_statut_controle_forum($page);
	$rss = rss_suivi_forums($page, $f, $w, true);
	$title = _T("ecrire:titre_page_forum_suivi")." (".$page.")";
	$url = generer_url_ecrire('controle_forum', 'type='.$page);
	return array($title, $rss, $url);
}

# revisions des articles
// http://doc.spip.org/@rss_revisions
function  rss_revisions($a)
{
	$a = rss_split_args($a);
	$rss = rss_suivi_versions($a);
	$title = _T("icone_suivi_revisions");
	$url = "";
	foreach (array('id_secteur', 'id_auteur', 'lang_choisie') as $var)
		if ($a[$var]) $url.= $var.'='.$a[$var] . '&';
	$url = generer_url_ecrire('suivi_revisions', $url);
	return array($title, $rss, $url);
}


// Suivi de la page "a suivre" : articles, breves, sites proposes et publies
// http://doc.spip.org/@rss_a_suivre
function rss_a_suivre($a) {
	$rss_articles = rss_articles("statut = 'prop'");
	$rss_breves = rss_breves("statut = 'prop'");
	$rss_sites = rss_sites("statut = 'prop'");

	$rss = array_merge($rss_articles, $rss_breves, $rss_sites);
	usort($rss, 'trier_par_date');
	$title = _T("icone_a_suivre");
	$url = _DIR_RESTREINT_ABS;
	return array($title, $rss, $url);
}

// http://doc.spip.org/@rss_erreur
function  rss_erreur($a)
{
	$rss = array(array('titre' => _T('login_erreur_pass')));
	$title = _T('login_erreur_pass');
	$url = '';
	return array($title, $rss, $url);
}

// d'abord un tri par date (inverse)
// http://doc.spip.org/@trier_par_date
function trier_par_date($a, $b) {
	return ($a['date'] < $b['date']);
}

// http://doc.spip.org/@xml_rss
function xml_rss($trio) {
	list($title, $rss, $url) = $trio;
	$title = "[".$GLOBALS['meta']['nom_site']."] RSS ". $title;

	$u = '';
	if (is_array($rss)) {
		foreach ($rss as $row) {
			$lang = texte_backend($row['lang']);
			$url = texte_backend(url_absolue($row['url']));
			$u .= '
	<item';
			if ($lang) $u .= 'xml:lang="'.$lang.'"';
			$u .= '>
		<title>'.texte_backend(typo($row['titre'])).'</title>
		<link>'.$url.'</link>
		<guid isPermaLink="true">'.$url.'</guid>
		<dc:date>'.date_iso($row['date']).'</dc:date>
		<dc:format>text/html</dc:format>';
			if ($lang) $u .= '
		<dc:language>'.$lang.'</dc:language>';
			if ($row['in_reply_to_url']) $u .= ' 
		<thr:in-reply-to ref="'.texte_backend(url_absolue($row['in_reply_to_url'])).
				'" href="'.texte_backend(url_absolue($row['in_reply_to_url'])).'" type="text/html" />';
			if ($row['nom']) {
				$nom = typo($row['nom']) .
				  (!$row['email_auteur'] ? '' :
				   (' <'.$row['email_auteur'].'>'));

				$u .= '
		<dc:creator>'.texte_backend($nom).'</dc:creator>';
			}
			$description = propre($row['texte']);
			if ($GLOBALS['les_notes']) {
				$description .= '<hr />'.$GLOBALS['les_notes'];
				$GLOBALS['les_notes'] = '';
			}
			$u .= '
		<description>'.texte_backend(liens_absolus($description)).'</description>
	</item>
';
		}
	}
	header('Content-Type: text/xml; charset='.$GLOBALS['meta']['charset']);
#	header('Content-Type: text/plain; charset='.$GLOBALS['meta']['charset']);
	echo '<',
	  '?xml version="1.0" encoding="'.
	  $GLOBALS['meta']['charset'],
	  '" ?',
	  '>

<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:thr="http://purl.org/syndication/thread/1.0">
<channel xml:lang="'.texte_backend($GLOBALS['spip_lang']).'">
	<title>'.texte_backend($title).'</title>
	<link>'.texte_backend(url_absolue($url)).'</link>
	<description></description>
	<language>'.texte_backend($GLOBALS['spip_lang']).'</language>
	'. $u . '</channel>
</rss>
';
}
?>
