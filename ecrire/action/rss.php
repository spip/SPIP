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
	if (verifier_low_sec($id, $cle, "rss $op $args")) {
		charger_generer_url();
		lang_select($lang);
		$op = str_replace('-', '_', $op);
		$contexte = array('fond' => 'prive/rss/' . $op);
		foreach (split(':', $args) as $bout) {
			list($var, $val) = split('-', $bout, 2);
			$contexte[$var] = $val;
		}
		$f = charger_fonction($op, 'rss', true);
		if ($f) $contexte = $f($contexte);
	} else $contexte = '';
	if ($contexte) {
		$f = evaluer_fond ('', $contexte);
		echo $f['texte'];
		$message ="spip_rss s'applique sur " . $contexte['fond'] . " et $args pour $id";
	} else 	$message = ("spip_rss sur '$op $args pour $id' incorrect");
	spip_log("$message (" . spip_timer('rss') .')');
	exit;
}

// Dans quelques cas le contexte doit etre revu
// Il faudrait les eliminer, et gerer la compatibilite autrement

# revisions des articles
// http://doc.spip.org/@rss_revisions
function  rss_revisions($a)
{
	if (isset($a['langue_choisie'])) {
		$a['lang'] = $a['langue_choisie'];
		unset($a['langue_choisie']);
	}
	if (isset($a['id_auteur'])) {
		$a['statut'] = array('prepa','prop','publie'); 
	} else {
		$a['statut'] = array('prop','publie');
	}
	include_spip('inc/suivi_versions');
	return $a;
}
// suivi public des forums publics 
// Ne sert plus qu'a la compatibilite, c'est du squelette public a present
// http://doc.spip.org/@rss_forum
function rss_forum($a)
{
	if ($id = intval($a['id_article'])) {
		$a['fond'] = 'rss_forum_article';
	}
	else if ($id = intval($a['id_syndic'])) {
		$a['fond'] = 'rss_forum_syndic';
	}
	else if ($id = intval($a['id_breve'])) {
		$a['fond'] = 'rss_forum_breve';
	}
	else if ($id = intval($a['id_rubrique'])) {
		$a['fond'] = 'rss_forum_rubrique';
	}
	else if ($id = intval($a['id_thread'])) {
		$a['fond'] = 'rss_forum_thread';
	} else { $a ='';}

	return $a;
}

# suivi prive de tous les forums
// Ne sert plus qu'a la compatibilite, cf les squeletes forums_$page a present
// http://doc.spip.org/@rss_forums
function  rss_forums($a)
{
	switch ($a['page']) {
	case 'public':
		$a['statut'] = array('publie','prop','off','spam');
		$a['texte'] = '.';
		return $a;
	case 'prop':
		$a['statut'] = array('prop');
		$a['texte'] = '.*';
		return $a;
	case 'spam':
		$a['statut'] = array('spam');
		$a['texte'] = '.*';
		return $a;
	case 'interne':
		$a['statut'] = array('prive','privac','privoff','privadm');
		$a['texte'] = '.';
		return $a;
	case 'vide':
		$a['statut'] = array('publie','off','prive','privac','privoff','privadm');
		$a['texte'] = '^$';
		return $a;
	default:
		return '';
	}
}


?>
