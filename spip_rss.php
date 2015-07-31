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
include_ecrire('inc_rss.php3');
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
)) {
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
		$title = _T("icone_messagerie_personnelle");
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
$intro = array(
	'title' => "[".lire_meta('nom_site')."] RSS ".$title,
	'url' => $url
);

list($content,$header) = affiche_rss($rss, $intro, $fmt);
if ($header) @header($header);
echo $content;

spip_log("spip_rss: ".spip_timer('rss'));
exit;


?>
