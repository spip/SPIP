<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// SPIP RSS
//
// On recoit un op (operation) + args (arguments)
// + id (id_auteur) + cle (low_sec(id_auteur, "op args"))
// On verifie que la cle correspond
// On cree ensuite le RSS correspondant a l'operation

include("ecrire/inc_version.php3");
include_ecrire("inc_texte.php3");
include_ecrire('inc_sites.php3');
include_ecrire("inc_acces.php3");
// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3"))
	include_local("inc-urls.php3");
else
	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");


//
// Verifier la securite du lien et decoder les arguments
//
spip_timer('rss');
if (!verifier_low_sec ($id, $cle,
"rss $op $args"
)
OR ($a['id_auteur']>0 AND $id<>$a['id_auteur'])) {
	$op = 'erreur securite';
	unset($a);
} else {
	$a = array();
	foreach (split(':', $args) as $bout) {
		list($var, $val) = split('-', $bout, 2);
		$a[$var] = $val;
	}
	include_ecrire('inc_lang.php3');
	lang_select($lang);
}

//
// Choisir la fonction de calcul du RSS
//
switch($op) {
	# forum public
	case 'forum':
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
		if ($id) $rss = rss_suivi_forums($a, $critere, false);
		$title = _T("ecrire:titre_page_forum_suivi");
		break;
	# suivi prive des forums
	case 'forums':
		include_ecrire("inc_forum.php3");
		$critere = critere_statut_controle_forum($a['page']);
		$rss = rss_suivi_forums($a, $critere, true);
		$title = _T("ecrire:titre_page_forum_suivi")." (".$a['page'].")";
		$url = _DIR_RESTREINT_ABS .'controle_forum.php3?page='.$a['page'];
		break;
	# revisions des articles
	case 'revisions':
		$rss = rss_suivi_versions($a);
		$title = _T("icone_suivi_revisions");
		$url = _DIR_RESTREINT_ABS .'suivi_revisions.php3?';
		foreach (array('id_secteur', 'id_auteur', 'lang_choisie') as $var)
			if ($a[$var]) $url.= '&'.$var.'='.$a[$var];
		break;
	# messagerie privee
	case 'messagerie':
		$rss = rss_suivi_messagerie($a);
		$title = _T("icone_suivi_revisions");
		$url = _DIR_RESTREINT_ABS .'messagerie.php3';
		break;
	# a suivre
	case 'a-suivre':
		$rss = rss_a_suivre($a);
		$title = _T("icone_a_suivre");
		$url = _DIR_RESTREINT_ABS .'';
		break;
	case 'erreur securite':
		$rss = array(array('title' => _L('Erreur de s&eacute;curit&eacute;')));
		$title = _L('Erreur de s&eacute;curit&eacute;');
		$url = '';
		break;
	default:
		$rss = array(array('title' => _L('Erreur')));
		$title = _L('Erreur');
		$url = '';
		break;
}

//
// Envoyer le RSS
//
@header('Content-Type: text/xml; charset='.lire_meta('charset'));

$intro = array(
	'title' => "[".lire_meta('nom_site')."] RSS ".$title,
	'url' => $url
);

echo affiche_rss($rss, $intro);
spip_log("spip_rss: ".spip_timer('rss'));
exit;




//
// Fonctions de remplissage du RSS
//


// Suivi des revisions d'articles
function rss_suivi_versions($a) {
	include_ecrire("inc_suivi_revisions.php");
	include_ecrire("lab_revisions.php");
	include_ecrire("lab_diff.php");
	include_ecrire("inc_presentation.php3");
	$rss = afficher_suivi_versions (0, $a['id_secteur'], $a['id_auteur'], $a['lang_choisie'], true, true);
	return $rss;
}

// Suivi des forums
function rss_suivi_forums($a, $query_forum='', $lien_moderation=false) {
	include_ecrire("inc_forum.php3");

	$result_forum = spip_query("
	SELECT	*
	FROM	spip_forum
	WHERE " . $query_forum . "
	ORDER BY date_heure DESC LIMIT 0,20"
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
			$item['url'] = _DIR_RESTREINT_ABS
			.'controle_forum.php3?page='.$a['page']
			.'&debut_id_forum='.$t['id_forum'];
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
				'url' => _DIR_RESTREINT_ABS
					.'message.php3?id_message='.$t['id_message']
			);
			$rss[] = $item;
		}
		$messages_vus[] = $t['id_message'];
	}

	// 2. les reponses aux messages
	if ($messages_vus) {
		$s = spip_query("SELECT * FROM spip_forum WHERE id_message
		IN (".join(',', $messages_vus).")
		ORDER BY date_heure DESC LIMIT 0,10");

		while ($t = spip_fetch_array($s)) {
			$item = array(
				'title' => typo($t['titre']),
				'date' => $t['date_heure'],
				'description' => propre($t['texte']),
				'author' => typo($t['auteur']),
				'email' => $t['email_auteur'],
				'url' => _DIR_RESTREINT_ABS
					.'message.php3?id_message='.$t['id_message']
					.'#'.$t['id_forum']
			);
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
	$s = spip_query("SELECT * FROM spip_articles WHERE $critere
	ORDER BY date DESC LIMIT 0,10");
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
			'url' => _DIR_RESTREINT_ABS
				.'articles.php3?id_article='.$t['id_article']
		);
		if ($t['statut'] == 'prop')
			$item['title'] = _T('info_article_propose').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}


function rss_breves($critere) {
	$s = spip_query("SELECT * FROM spip_breves WHERE $critere
	ORDER BY date_heure DESC LIMIT 0,10");
	while ($t = spip_fetch_array($s)) {
		$item = array(
			'title' => typo($t['titre']),
			'date' => $t['date_heure'],
			'description' => propre(couper($t['texte'],300)),
			'url' => _DIR_RESTREINT_ABS
				.'breves.php3?id_breve='.$t['id_breve']
		);
		if ($t['statut'] == 'prop')
			$item['title'] = _T('titre_breve_proposee').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}


function rss_sites($critere) {
	$s = spip_query("SELECT * FROM spip_syndic WHERE $critere
	ORDER BY date DESC LIMIT 0,10");
	while ($t = spip_fetch_array($s)) {
		$item = array(
			'title' => typo($t['titre']." ".$t['url_site']),
			'date' => $t['date'],
			'description' => propre(couper($t['texte'],300)),
			'url' => _DIR_RESTREINT_ABS
				.'sites.php3?id_syndic='.$t['id_syndic']
		);
		if ($t['statut'] == 'prop')
			$item['title'] = _T('info_site_attente').' : '.$item['title'];

		$rss[] = $item;
	}
	return $rss;
}


?>
