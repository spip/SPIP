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

//
// Verifier la securite du lien et decoder les arguments
//
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
	case 'forums':
		include_ecrire("inc_forum.php3");
		$rss = rss_suivi_forums($a);
		$title = _T("ecrire:titre_page_forum_suivi")." (".$a['page'].")";
		$url = _DIR_RESTREINT_ABS .'controle_forum.php3?page='.$a['page'];
		break;
	case 'revisions':
		include_ecrire("inc_suivi_revisions.php");
		$rss = rss_suivi_versions($a);
		$title = _T("icone_suivi_revisions");
		$url = _DIR_RESTREINT_ABS .'suivi_revisions.php3?';
		foreach (array('id_secteur', 'id_auteur', 'lang_choisie') as $var)
			if ($a[$var]) $url.= '&'.$var.'='.$a[$var];
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
	'url' => lire_meta('adresse_site').'/'.$url
);

echo affiche_rss($rss, $intro);
exit;

?>
