<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@balise_URL__dist
function balise_URL__dist($p) {

	if ($f = charger_fonction($p->nom_champ, 'balise', true))
		return $f($p);
	else return NULL;
}

// http://doc.spip.org/@balise_URL_SITE_SPIP_dist
function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "sinon(\$GLOBALS['meta']['adresse_site'],'.')";
	$p->code = "htmlspecialchars(".$p->code.")";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_ARTICLE_dist
function balise_URL_ARTICLE_dist($p) {

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($p->type_requete == 'syndic_articles') {
		$p->code = champ_sql('url', $p);
	} else  $p->code = generer_generer_url('article', $p);

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_AUTEUR_dist
function balise_URL_AUTEUR_dist($p) {

	$p->code = generer_generer_url('auteur', $p);
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_RUBRIQUE_dist
function balise_URL_RUBRIQUE_dist($p) {

	$p->code = generer_generer_url('rubrique', $p);
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_BREVE_dist
function balise_URL_BREVE_dist($p) {

	$p->code = generer_generer_url('breve', $p);
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_MOT_dist
function balise_URL_MOT_dist($p) {

	$p->code = generer_generer_url('mot', $p);
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_FORUM_dist
function balise_URL_FORUM_dist($p) {

	$p->code = generer_generer_url('forum', $p);
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_DOCUMENT_dist
function balise_URL_DOCUMENT_dist($p) {

	$p->code = generer_generer_url('document', $p);
	$p->interdire_scripts = false;
	return $p;
}

# URL_SITE est une donnee "brute" tiree de la base de donnees
# URL_SYNDIC correspond a l'adresse de son backend.
# Il n'existe pas de balise pour afficher generer_url_site($id_syndic),
# a part [(#ID_SYNDIC|generer_url_site)]


//
// #URL_PAGE{backend} -> backend.php3 ou ?page=backend selon les cas
// Pour les pages qui commencent par "spip_", il faut eventuellement
// aller chercher spip_action.php?action=xxxx
//
// http://doc.spip.org/@balise_URL_PAGE_dist
function balise_URL_PAGE_dist($p) {

	if ($p->boucles[$p->id_boucle]->sql_serveur) {
		$p->code = 'generer_url_public("404")';
		return $p;
	}

	$p->code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args != "''" && $args!==NULL)
		$p->code .= ','.$args;

	// autres filtres (???)
	array_shift($p->param);

	$p->code = 'generer_url_public(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #URL_ECRIRE{naviguer} -> ecrire/?exec=naviguer
//
// http://doc.spip.org/@balise_URL_ECRIRE_dist
function balise_URL_ECRIRE_dist($p) {

	if ($p->boucles[$p->id_boucle]->sql_serveur) {
		$p->code = 'generer_url_public("404")';
		return $p;
	}

	$p->code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args != "''" && $args!==NULL)
		$p->code .= ','.$args;

	// autres filtres (???)
	array_shift($p->param);

	$p->code = 'generer_url_ecrire(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #URL_ACTION_AUTEUR{converser,arg,redirect} -> ecrire/?action=converser&arg=arg&hash=xxx&redirect=redirect
//
// http://doc.spip.org/@balise_URL_ACTION_AUTEUR_dist
function balise_URL_ACTION_AUTEUR_dist($p) {

	if ($p->boucles[$p->id_boucle]->sql_serveur) {
		$p->code = 'generer_url_public("404")';
		return $p;
	}

	$p->code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args != "''" && $args!==NULL)
		$p->code .= ".'\",\"'.".$args;
	$redirect = interprete_argument_balise(3,$p);
	if ($redirect != "''" && $redirect!==NULL)
		$p->code .= ".'\",\"'.".$redirect;

	$p->code = "'<"."?php echo generer_action_auteur(\"'." . $p->code .".'\"); ?>'";

	$p->interdire_scripts = false;
	return $p;
}
?>
