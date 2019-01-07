<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier gère les favoris par défaut dans le menu du bandeau
 *
 * @package SPIP\Core\Bandeau
 **/

/**
 * Retourne la liste des menus favoris par défaut ainsi que leur rang
 */
function inc_definir_menus_favoris_dist() {
	$liste = array(

		// Menu Édition,
		'auteurs' => 1,
		'rubriques' => 2,
		'articles' => 3,

		// Menu Maintenance
		'admin_vider' => 1,

		// Menu Configurations
		'configurer_identite' => 1,
		'admin_plugin' => 2,

	);

	return $liste;
}