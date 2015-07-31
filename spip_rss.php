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


//
// Verifier la securite du lien et decoder les arguments
//
include_ecrire("inc_acces.php3");
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
}

//
// Choisir la fonction de calcul du RSS
//
switch($op) {
	case 'revisions':
		$rss = rss_suivi_versions($a);
		$title = _T("icone_suivi_revisions");
		$url = _DIR_RESTREINT_ABS .'suivi_revisions.php3';
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
include_ecrire('inc_sites.php3');
@header('Content-Type: text/xml; charset='.lire_meta('charset'));

$intro = array(
	'title' => "[".lire_meta('nom_site')."] RSS ".$title,
	'url' => lire_meta('adresse_site').'/'.$url
);

echo affiche_rss($rss, $intro);
exit;

//
// Fonctions de calcul (a dispatcher dans les librairies)
//
function rss_suivi_versions($a) {
	include_ecrire("inc_suivi_revisions.php");
	include_ecrire("lab_revisions.php");
	include_ecrire("lab_diff.php");
	include_ecrire("inc_presentation.php3");
	$rss = afficher_suivi_versions (0, $a['id_secteur'], $a['id_auteur'], $a['lang_choisie'], true, true);
	return $rss;
}

?>