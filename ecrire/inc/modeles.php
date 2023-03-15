<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Traiter les modeles d'un texte
 * @param string $texte
 * @param bool|array $doublons
 * @param string $echap
 * @param string $connect
 * @param ?Spip\Texte\Collecteur\Liens $collecteurLiens
 * @param array $env
 * @return string
 */
function traiter_modeles($texte, $doublons = false, $echap = '', string $connect = '', ?Spip\Texte\Collecteur\Liens $collecteurLiens = null, $env = []) {

	include_spip('src/Texte/Collecteur/AbstractCollecteur');
	include_spip('src/Texte/Collecteur/Modeles');
	$collecteurModeles = new Spip\Texte\Collecteur\Modeles();

	$options = [
		'doublons' => $doublons,
		'echap' => $echap,
		'connect' => $connect,
		'collecteurLiens' => $collecteurLiens,
		'env' => $env
	];
	return $collecteurModeles->traiter($texte ?? '', $options);
}
