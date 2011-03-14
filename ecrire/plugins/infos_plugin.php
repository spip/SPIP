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

// lecture d'un texte ecrit en pseudo-xml issu d'un fichier plugin.xml
// et conversion approximative en tableau PHP.

function plugins_infos_plugin($desc, $plug='', $dir_plugins = _DIR_PLUGINS)
{
	include_spip('inc/xml');
	$arbre = spip_xml_parse($desc);
	$verifie_conformite = charger_fonction('verifie_conformite','plugins');
	$verifie_conformite($plug, $arbre, $dir_plugins);
	$extraire_boutons = charger_fonction('extraire_boutons','plugins');
	$les_boutons = $extraire_boutons($arbre);
	if (isset($arbre['erreur'])) 
	  spip_log("get_infos $plug " . @join(' ', $arbre['erreur']));

	include_spip('inc/charsets');
	$ret = info_plugin_normalise_necessite($arbre['necessite']);
	$ret['utilise'] = info_plugin_normalise_utilise($arbre['utilise']);
	$ret['nom'] = charset2unicode(spip_xml_aplatit($arbre['nom']));
	$ret['version'] = trim(end($arbre['version']));

	if (isset($arbre['auteur']))
		$ret['auteur'] = spip_xml_aplatit($arbre['auteur']);
	if (isset($arbre['icon']))
		$ret['icon'] = trim(spip_xml_aplatit($arbre['icon']));
	if (isset($arbre['description']))
		$ret['description'] = spip_xml_aplatit($arbre['description']);
	if (isset($arbre['lien']))
		$ret['lien'] = join(' ',$arbre['lien']);
	if (isset($arbre['etat']))
		$ret['etat'] = trim(end($arbre['etat']));
	if (isset($arbre['options']))
		$ret['options'] = $arbre['options'];
	if (isset($arbre['licence']))
		$ret['licence'] = spip_xml_aplatit($arbre['licence']);
	if (isset($arbre['install']))
		$ret['install'] = $arbre['install'];
	if (isset($arbre['config']))
		$ret['config'] = spip_xml_aplatit($arbre['config']);
	if (isset($arbre['meta']))
		$ret['meta'] = spip_xml_aplatit($arbre['meta']);
	if (isset($arbre['fonctions']))
		$ret['fonctions'] = $arbre['fonctions'];
	$ret['prefix'] = trim(array_pop($arbre['prefix']));
	if (isset($arbre['pipeline']))
		$ret['pipeline'] = $arbre['pipeline'];
	if (isset($arbre['erreur']))
		$ret['erreur'] = $arbre['erreur'];
	if (isset($arbre['version_base']))
		$ret['version_base'] = trim(end($arbre['version_base']));

	$ret['path'] = $arbre['path'];
	if (isset($arbre['noisette']))
		$ret['noisette'] = $arbre['noisette'];

	$ret['bouton'] = $les_boutons['bouton'];
	$ret['onglet'] = $les_boutons['onglet'];

	return $ret;
}

function info_plugin_normalise_necessite($necessite)
{
	$res = array('necessite' => array(), 'lib' => array());
	foreach($necessite as $need){
		$id = $need['id'];

		// Necessite SPIP version x ?
		if (strtoupper($id)=='SPIP') {
			$res['compatible'] = $need['version'];
		} else if (preg_match(',^lib:\s*([^\s]*),i', $id, $r)) {
		  $res['lib'][] = array('nom' => $r[1], 'lien' => $need['src']);
		} else $res['necessite'][] = array('nom' => $id, 'version' => $need['version']);
	}
	return $res;
}

function info_plugin_normalise_utilise($utilise)
{
	$res = array();
	foreach($utilise as $need){
		$id = $need['id'];
		$res[]= array('nom' => $id, 'version' => $need['version']);
	}
	return $res;
}

?>
