<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Déclare la liste des tables principales
 *
 * @todo
 *     Nettoyages à faire dans le core : on ne devrait plus appeler
 *     Ce fichier mais directement base/objets si nécessaire
 *
 * @package SPIP\Core\SQL\Tables
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/objets');
lister_tables_objets_sql();
