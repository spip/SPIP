<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * Ce fichier ne sert plus
 * Il est maintenu pour assurer la compatibilite des anciens scripts
 *  
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

// prise en charge des renomage de fonction
include_spip('inc/filtres_images_compat');

// prise en charge des fonctions de transformation d'image
include_spip('filtres/images_transforme');

// prise en charge des fonctions d'image typo
include_spip('filtres/images_typo');

// prise en charge des fonctions de transformation de couleur
include_spip('filtres/couleurs');

?>