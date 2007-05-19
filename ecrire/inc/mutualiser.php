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


// Demarrer un site dans le sous-repertoire sites/$f/
// Options :
// creer_site => on va creer les repertoires qui vont bien (defaut: false)
// cookie_prefix, table_prefix => regler les prefixes (defaut: true)
// http://doc.spip.org/@demarrer_site
function demarrer_site($site = '', $options = array()) {
	if (!$site) return;

	$options = array_merge(
		array(
			'creer_site' => false,
			'creer_base' => false,
			'mail' => '',
			'table_prefix' => false,
			'cookie_prefix' => false,
			'repertoire' => 'sites'
		),
		$options
	);

	// Le prefixe = max 10 caracteres a-z0-9, qui ressemblent au domaine
	// et ne commencent pas par un chiffre
	if ($options['cookie_prefix'])
		$GLOBALS['cookie_prefix'] = prefixe_mutualisation($site);
	if ($options['table_prefix'])
		$GLOBALS['table_prefix'] = prefixe_mutualisation($site);

	if (!is_dir($e = _DIR_RACINE . $options['repertoire'].'/' . $site . '/')) {
		spip_initialisation();
		include_spip('inc/mutualiser_creer');
		mutualiser_creer($e, $options);
		exit;
	}

	define('_SPIP_PATH',
		$e . ':' .
		_DIR_RACINE .':' .
		_DIR_RACINE .'dist/:' .
		_DIR_RESTREINT
	);

	spip_initialisation(
		($e . _NOM_PERMANENTS_INACCESSIBLES),
		($e . _NOM_PERMANENTS_ACCESSIBLES),
		($e . _NOM_TEMPORAIRES_INACCESSIBLES),
		($e . _NOM_TEMPORAIRES_ACCESSIBLES)
	);

	if (is_dir($e.'squelettes'))
		$GLOBALS['dossier_squelettes'] = $e.'squelettes';

	if (is_readable($f = $e._NOM_PERMANENTS_INACCESSIBLES._NOM_CONFIG.'.php')) 
		include($f); // attention cet include n'est pas en globals

}

// Cette fonction cree un prefixe acceptable par MySQL a partir du nom
// du site ; a utiliser comme prefixe des tables, comme suffixe du nom
// de la base de donnees ou comme prefixe des cookies...
// http://doc.spip.org/@prefixe_mutualisation
function prefixe_mutualisation($site) {
	static $prefix;

	if (!isset($prefix)) {
		$prefix = preg_replace(',^www\.|[^a-z0-9],', '', strtolower($site));
		$prefix = substr($prefix, 0, 10);
		if (!preg_match(',^[a-z],', $prefix))
			$prefix = 'a'.$prefix;
	}
	return $prefix;

}

?>
