<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
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
 * @param ?Spip\Texte\CollecteurLiens $collecteurLiens
 * @param array $env
 * @return string
 */
function traiter_modeles($texte, $doublons = false, $echap = '', string $connect = '', ?Spip\Texte\CollecteurLiens $collecteurLiens = null, $env = []) {

	include_spip("src/Texte/Utils/Collecteur");
	include_spip("src/Texte/CollecteurModeles");
	$collecteurModeles = new Spip\Texte\CollecteurModeles();

	$options = [
		'doublons' => $doublons,
		'echap' => $echap,
		'connect' => $connect,
		'collecteurLiens' => $collecteurLiens,
		'env' => $env
	];
	return $collecteurModeles->traiter($texte ?? '', $options);
}
