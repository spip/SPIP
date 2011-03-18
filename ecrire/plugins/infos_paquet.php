<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// lecture d'un texte conforme a la DTD paquet.dtd
// et conversion en tableau PHP identique a celui fourni par plugin.xml
// manque la description

function plugins_infos_paquet($desc, $plug='', $dir_plugins = _DIR_PLUGINS)
{
	include_spip('xml/valider');
	$vxml = new ValidateurXML();
	$vxml->process = array(
			'debut' => 'paquet_debutElement',
			'fin' => 'paquet_finElement',
			'text' => 'paquet_textElement'
			    );
	$dir = "$dir_plugins$plug/";
	$sax = charger_fonction('sax', 'xml');
	$sax($desc, false, $vxml, 'paquet.dtd');
	if (!$vxml->err) {
		// compatibilite avec l'existant:
		$vxml->tree['icon'] = $vxml->tree['logo']; 
		$vxml->tree['path'] = $vxml->tree['chemin'] ? $vxml->tree['chemin'] : array(array('dir'=>'')); // initialiser par defaut
		if ($plug) paquet_readable_files($vxml, "$dir_plugins$plug/");
		return $vxml->tree;
	}
	// Prendre les messages d'erreur sans les numeros de lignes
	$msg = array_map('array_shift', $vxml->err);
	// Construire le lien renvoyant sur l'application du validateur XML 
	$h = $GLOBALS['meta']['adresse_site'] . '/'. substr($dir, strlen(_DIR_RACINE)) . 'paquet.xml';
	$h = generer_url_ecrire('valider_xml', "var_url=$h");
	$t =_T('plugins_erreur', array('plugins' =>$plug));
	array_unshift($msg, "<a href='$h'>$t</a>");
	return array('erreur' => $msg);
}

function paquet_readable_files(&$vxml, $dir)
{
	$prefix = $vxml->tree['prefix'];
	if (is_readable($dir . $f = ($prefix . '_options.php')))
		$vxml->tree['options'] = array($f);
	if (is_readable($dir . $f = ($prefix . '_fonctions.php')))
		$vxml->tree['fonctions'] = array($f);
	if (is_readable($dir . $f = ($prefix . '_installation.php')))
		$vxml->tree['install'] = array($f);
}

// Appeler l'indenteur pour sa gestion de la profondeur, 
// et memoriser les attributs dans le tableau avec l'oppose de la profondeur
// comme index (la valeur positive a son developpe en chaine, on n'en veut pas)
// sauf pour la balise racine qui permet d'initialiser.
// Cette initialisation consiste a memoriser les attributs de la balise "paquet"
// qu'on complete par des entrees nommees par les sous-balises de "paquet",
// et initialisees par un tableau vide, rempli a leur rencontre.

function paquet_debutElement($phraseur, $name, $attrs)
{
	xml_debutElement($phraseur, $name, $attrs);
	$depth = $phraseur->depth;
	if (!$phraseur->err) {
		if ($name == 'paquet') {
		  $attrs['lib']=array();
		  $attrs['onglet']=array();
		  $attrs['bouton']=array();
		  $attrs['chemin']=array();
		  $attrs['utilise']=array();
		  $attrs['pipeline']=array();
		  $attrs['necessite']=array();
		  $phraseur->tree = $attrs;
		} else {
		  $phraseur->contenu[(0-$depth)] = $attrs;
		}
	}
	$phraseur->contenu[(0-$depth)][''] = '';
}

// Appeler l'indenteur pour sa gestion de la profondeur,
// et memoriser les attributs dans le tableau avec l'oppose de la profondeur
// comme index, avec '' comme sous-index (les autres sont les attributs)
function paquet_textElement($phraseur, $data)
{
	xml_textElement($phraseur, $data);
	if (!$phraseur->err) {
		$depth = $phraseur->depth;
		$phraseur->contenu[(0-$depth)][''] .= trim($data);
	}
}

// Si on sait deja que le texte n'est pas valide on ne fait rien.
// Pour une balise sans attribut, le traitement est forcement toujours le meme.
// Pour une balise sans texte, idem mais parce que la DTD est bien fichue

function paquet_finElement($phraseur, $name)
{
	if (!$phraseur->err) {
		$depth = 0-$phraseur->depth;
		$attrs = $phraseur->contenu[$depth];
		$texte = $attrs[''];
		unset($attrs['']);
		$f = 'info_paquet_' . $name;
		if (function_exists($f))
		  $f($phraseur, $attrs, $texte);
		elseif (!$attrs)
		  $phraseur->tree[$name] = $texte;
		else $phraseur->tree[$name][$attrs['nom']] = $attrs;
	}
	xml_finElement($phraseur, $name, $attrs);
}

function info_paquet_licence($phraseur, $attrs, $texte)
{ 
  if (isset($attrs['lien'])) 
    $texte = "<a href='" . $attrs['lien'] . "'>" . $texte . "</a>";
  $phraseur->tree['licence'] = $texte;
}

function info_paquet_auteur($phraseur, $attrs, $texte)
{
  if (isset($attrs['mail'])) {
    if (strpos($attrs['mail'], '@') )
	$attrs['mail'] = str_replace('@', ' AT ', $attrs['mail']);
    $texte .= ' (' . $attrs['mail'] . ')';
  }
  if (isset($attrs['lien'])) 
    $texte = "<a href='" . $attrs['lien'] . "'>" . $texte . "</a>";

  $phraseur->tree['auteur'] = isset($phraseur->tree['auteur']) ? 
    ($phraseur->tree['auteur'] . ' ' . $texte) : $texte;
}

// Sous-cas de compatibilite; A traiter.
function info_paquet_spip($phraseur, $attrs, $texte)
{ $phraseur->tree['spip'] = $texte; }


?>
