<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Les balises URL_$type sont generiques, sauf qq cas particuliers.
// Si ces balises sont utilisees pour la base locale,
// production des appels a generer_url_entite(id-courant, entite)
// Si la base est externe et non geree par SPIP
// on retourne NULL pour provoquer leur interpretation comme champ SQL normal.
// Si la base est externe et sous SPIP,
// on produit l'URL de l'objet si c'est une piece jointe
// ou sinon l'URL du site local applique sur l'objet externe
// ce qui permet de le voir a travers les squelettes du site local
// On communique le type-url distant a generer_url_entite mais il ne sert pas
// car rien ne garantit que le .htaccess soit identique. A approfondir

// http://doc.spip.org/@generer_generer_url
function generer_generer_url($type, $p){
	$_id = interprete_argument_balise(1,$p);

	if (!$_id) {
		$primary = id_table_objet($type);
		$_id = champ_sql($primary, $p);
	}

	return generer_generer_url_arg($type, $p, $_id);
}
	
function generer_generer_url_arg($type, $p, $_id)
{
	if ($s = trouver_nom_serveur_distant($p)) {

// si une fonction de generation des url a ete definie pour ce connect l'utiliser
		if (function_exists($f = 'generer_generer_url_'.$s)){
			return $f($type, $_id, $s);
		}
		if (!$GLOBALS['connexions'][strtolower($s)]['spip_connect_version']) {
			return NULL;
		}
		$s = _q($s);
		# exception des urls de documents sur un serveur distant...
		if ($type == 'document') {
			return 
			"quete_meta('adresse_site', $s) . '/' .\n\t" .
			"quete_meta('dir_img', $s) . \n\t" .
			"quete_fichier($_id,$s)";
		}
		$s = ", '', '', $s, quete_meta('type_urls', $s)";
	}
	else 
		$s = ", '', '', true";
	return "urlencode_1738(generer_url_entite($_id, '$type'$s))";
}


// http://doc.spip.org/@balise_URL__dist
function balise_URL__dist($p) {

	$nom = $p->nom_champ;
	if ($nom === 'URL_') {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' URL_'));
		erreur_squelette($msg, $p);
		$p->interdire_scripts = false;
		return $p;
	} elseif ($f = charger_fonction($nom, 'balise', true)) {
		return $f($p);
	}else {
		$nom = strtolower($nom);
		$code = generer_generer_url(substr($nom,4), $p);
		$code = champ_sql($nom, $p, $code);
		$p->code = $code;
		if (!$p->etoile)
			$p->code = "vider_url($code)";
		$p->interdire_scripts = false;
		return $p;
	}
}

// http://doc.spip.org/@balise_URL_ARTICLE_dist
function balise_URL_ARTICLE_dist($p) {

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($p->type_requete == 'syndic_articles') {
		$code = champ_sql('url', $p);
	} else  $code = generer_generer_url('article', $p);

	$p->code = $code;
	if (!$p->etoile)
		$p->code = "vider_url($code)";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_SITE_dist
function balise_URL_SITE_dist($p)
{
	$code = champ_sql('url_site', $p);
	if (strpos($code, '@$Pile[0]') !== false) {
		$code = generer_generer_url('site', $p);
		if ($code === NULL) return NULL;
	} else {
		if (!$p->etoile)
			$code = "calculer_url($code,'','url', \$connect)";
	}
	$p->code = $code;
	$p->interdire_scripts = false;
	return $p;
}

// Autres balises URL_*, qui ne concernent pas une table
// (historique)

// http://doc.spip.org/@balise_URL_SITE_SPIP_dist
function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "sinon(\$GLOBALS['meta']['adresse_site'],'.')";
	$p->code = "spip_htmlspecialchars(".$p->code.")";
	$p->interdire_scripts = false;
	return $p;
}

//
// #URL_PAGE{backend} -> backend.php3 ou ?page=backend selon les cas
// Pour les pages qui commencent par "spip_", il faut eventuellement
// aller chercher spip_action.php?action=xxxx
// Sans argument, #URL_PAGE retourne l'URL courante.
// #URL_PAGE* retourne l'URL sans convertir les & en &amp;
// http://doc.spip.org/@balise_URL_PAGE_dist
function balise_URL_PAGE_dist($p) {

	$code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args == NULL)
		$args = "''";

	if ($s = trouver_nom_serveur_distant($p)) {
		// si une fonction de generation des url a ete definie pour ce connect l'utiliser
		// elle devra aussi traiter le cas derogatoire type=page
		if (function_exists($f = 'generer_generer_url_'.$s)){
			if ($args AND $args!=="''") $code .= ", $args";
			$code = $f('page', $code, $s);
			return $p;
		}
		$s = 'connect=' .  addslashes($s);
		$args = (($args AND $args!=="''") ? "$args . '&$s'" : "'$s'");
	}

	if (!$code) {
		$noentities = $p->etoile ? "'&'" : '';
		$code = "url_de_base() . preg_replace(',^./,', '', self($noentities))";
	} else{
	  	if (!$args) $args = "''";
		$noentities = $p->etoile ? ", true" : '';
		$code = "generer_url_public($code, $args$noentities)";
	}
	$p->code = $code;
	spip_log("connect vaut $s ca donne " .  $p->code . " args $args");

	#$p->interdire_scripts = true;
	return $p;
}

//
// #URL_ECRIRE{rubriques} -> ecrire/?exec=rubriques
// #URL_ECRIRE*  meme chose, mais sans convertir les & en &amp;
// http://doc.spip.org/@balise_URL_ECRIRE_dist
function balise_URL_ECRIRE_dist($p) {

	$code = interprete_argument_balise(1,$p);
	if (!$code) {
		$fonc = "''";
	}
	else {
		$fonc = $code;
		$args = interprete_argument_balise(2,$p);
		if ($args === NULL) {
			$args = "''";
		}
		$noentities = $p->etoile ? ", true" : '';
		if (($args != "''")  OR $noentities) {
			$fonc .= ",$args$noentities";
		}
	}
	$p->code = 'generer_url_ecrire(' . $fonc .')';
	$p->interdire_scripts = false;
	return $p;
}

//
// #URL_ACTION_AUTEUR{converser,arg,redirect} -> ecrire/?action=converser&arg=arg&hash=xxx&redirect=redirect
//
// http://doc.spip.org/@balise_URL_ACTION_AUTEUR_dist
function balise_URL_ACTION_AUTEUR_dist($p) {
	$p->descr['session'] = true;

	$p->code = interprete_argument_balise(1,$p);
	$args = interprete_argument_balise(2,$p);
	if ($args != "''" && $args!==NULL)
		$p->code .= ",".$args;
	$redirect = interprete_argument_balise(3,$p);
	if ($redirect != "''" && $redirect!==NULL) {
		if ($args == "''" || $args===NULL)
			$p->code .= ",''";
		$p->code .= ",".$redirect;
	}

	$p->code = "generer_action_auteur(" . $p->code . ")";
	$p->interdire_scripts = false;
	return $p;
}
?>
