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

// Les balises URL_$type sont generiques, sauf qq cas particuliers:
// elles produisent un appel a generer_url_$type(id-courant)
// grace a la fonction ci-dessous
// Si ces balises sont utilisees pour la base locale,
// producttion des appels aux fonctions generer_url parametrees par $type_urls
// Si la base est externe et non geree par SPIP
// on retourne NULL pour provoquer leur interpretation comme champ SQL normal.
// Si la base est externe et sous SPIP,
// on produit l'URL de l'objet si c'est une piece jointe
// ou sinon l'URL du site local applique sur l'objet externe
// ce qui permet de le voir a travers les squelettes du site local

// http://doc.spip.org/@generer_generer_url
function generer_generer_url($type, $p)
{
	$_id = interprete_argument_balise(1,$p);

	if (!$_id) $_id = champ_sql('id_' . $type, $p);

	if ($s = $p->id_boucle) $s = $p->boucles[$s]->sql_serveur;

	if (!$s)
		return "generer_url_$type($_id)";
	elseif (!$GLOBALS['connexions'][$s]['spip_connect_version']) {
		return NULL;
	} else {
		$s = addslashes($s);
		if ($type != 'document')
			return "'./?page=$type&amp;id_$type=' . $_id . '&amp;connect=$s'";
		else {
			$u = "quete_meta('adresse_site', '$s')";
			$d = "quete_meta('dir_img', '$s')";
			$f = "quete_fichier($_id,'$s')";
			return "$u . '/' .\n\t$d . $f";
		}
	}
}


// http://doc.spip.org/@balise_URL__dist
function balise_URL__dist($p) {

	if ($f = charger_fonction($p->nom_champ, 'balise', true))
		return $f($p);
	else {
		$code = champ_sql($p->nom_champ, $p);
		if (strpos($code, '@$Pile[0]') !== false) {
			$nom = strtolower(substr($p->nom_champ,4));
			$code = generer_generer_url($nom, $p);
			if ($code === NULL) return NULL;
		}
		$p->code = $code;
		$p->interdire_scripts = false;
		return $p;
	}
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

// Autres balises URL_*, qui ne concernent pas une table
// (historique)

// http://doc.spip.org/@balise_URL_SITE_SPIP_dist
function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "sinon(\$GLOBALS['meta']['adresse_site'],'.')";
	$p->code = "htmlspecialchars(".$p->code.")";
	$p->interdire_scripts = false;
	return $p;
}

//
// #URL_PAGE{backend} -> backend.php3 ou ?page=backend selon les cas
// Pour les pages qui commencent par "spip_", il faut eventuellement
// aller chercher spip_action.php?action=xxxx
//
// http://doc.spip.org/@balise_URL_PAGE_dist
function balise_URL_PAGE_dist($p) {

	$p->code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args != "''" && $args!==NULL)
		$p->code .= ','.$args;

	// autres filtres (???)
	array_shift($p->param);

	if ($p->id_boucle
	AND $s = $p->boucles[$p->id_boucle]->sql_serveur) {

		if (!$GLOBALS['connexions'][$s]['spip_connect_version']) {
			$p->code = "404";
		} else {
			$p->code .=  ", 'connect=" .  addslashes($s) . "'";
		}
	}

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
