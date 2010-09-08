<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * Un test sur les articles syndiques
 * pour "depublier les items qui ne figurent plsu dans le flux"
 * @global <type> $my_sites
 * @param <type> $id
 * @return <type>
 */
function filtre_test_syndic_article_miroir_dist($id){
	if (isset($GLOBALS['my_sites'][$id]['miroir']) AND $GLOBALS['my_sites'][$id]['miroir'] == 'oui')
		return ' ';
	return '';
}

?>