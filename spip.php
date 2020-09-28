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

# ou est l'espace prive ?
if (!defined('_DIR_RESTREINT_ABS')) {
	define('_DIR_RESTREINT_ABS', 'ecrire/');
}
include_once _DIR_RESTREINT_ABS.'inc_version.php';

# au travail...
include _DIR_RESTREINT_ABS.'public.php';
