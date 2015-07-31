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

/**
 * Déclare la liste des tables auxiliaires
 *
 * @todo
 *     Nettoyages à faire dans le core : on ne devrait plus appeler
 *     Ce fichier mais directement base/objets si nécessaire
 *
 * @package SPIP\SQL\Tables
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/objets');
lister_tables_objets_sql();

?>
