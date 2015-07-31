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
function demarrer_site($site, $options = array()) {
	$options = array_merge(
		array(
			'creer_site' => false,
			'cookie_prefix' => true,
			'table_prefix' => true,
			'repertoire' => 'sites'
		),
		$options
	);

	// Le prefixe = max 10 caracteres a-z0-9, qui ressemblent au domaine
	if ($options['cookie_prefix'] OR $options['table_prefix']) {
		$prefix = preg_replace(',^www\.|[^a-z0-9],', '', strtolower($site));
		$prefix = substr($prefix, 0, 10);
		if ($options['cookie_prefix'])
			$GLOBALS['cookie_prefix'] = $prefix;
		if ($options['table_prefix'])
			$GLOBALS['table_prefix'] = $prefix;
	}

	if (!is_dir($e = _DIR_RACINE . $options['repertoire'].'/' . $site . '/')) {
		spip_initialisation();
		echec_init_mutualisation($e, $options);
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


function echec_init_mutualisation($e, $options) {
	include_spip('inc/minipres');

	if ($options['creer_site']) {
		$ok = mkdir($e, _SPIP_CHMOD)
		AND chmod($e, _SPIP_CHMOD)
		AND mkdir($e._NOM_PERMANENTS_INACCESSIBLES, _SPIP_CHMOD)
		AND mkdir($e._NOM_PERMANENTS_ACCESSIBLES, _SPIP_CHMOD)
		AND mkdir($e._NOM_TEMPORAIRES_INACCESSIBLES, _SPIP_CHMOD)
		AND mkdir($e._NOM_TEMPORAIRES_ACCESSIBLES, _SPIP_CHMOD)
		AND chmod($e._NOM_PERMANENTS_INACCESSIBLES, _SPIP_CHMOD)
		AND chmod($e._NOM_PERMANENTS_ACCESSIBLES, _SPIP_CHMOD)
		AND chmod($e._NOM_TEMPORAIRES_INACCESSIBLES, _SPIP_CHMOD)
		AND chmod($e._NOM_TEMPORAIRES_ACCESSIBLES, _SPIP_CHMOD);

		echo minipres(
			_L('Creation du r&eacute;pertoire du site (<tt>'.$e.'</tt>)'),

				"<div><img alt='SPIP' src='" . _DIR_IMG_PACK . "logo-spip.gif' /></div>\n"
				.'<h3>'.($ok
					? _L('OK, vous pouvez <a href="'.generer_url_ecrire('install').'">installer votre site</a>.')
					: _L('erreur')
				).'</h3>'
		);
	} else {
		echo minipres(
			_L('Le r&eacute;pertoire du site (<tt>'.$e.'</tt>) n\'existe pas'),
			"<div><img alt='SPIP' src='" . _DIR_IMG_PACK . "logo-spip.gif' /></div>\n".
			'<h3>'
			._L('Veuillez créer le répertoire '.$e.' et ses sous répertoires:')
			.'</h3>'
			.'<ul>'
			.'<li>'.$e._NOM_PERMANENTS_INACCESSIBLES.'</li>'
			.'<li>'.$e._NOM_PERMANENTS_ACCESSIBLES.'</li>'
			.'<li>'.$e._NOM_TEMPORAIRES_INACCESSIBLES.'</li>'
			.'<li>'.$e._NOM_TEMPORAIRES_ACCESSIBLES.'</li>'
			.'</ul>'
		);
	}
}



?>
