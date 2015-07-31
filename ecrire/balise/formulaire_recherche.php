<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Pas d'arg a compiler ==> pas besoin d'appeler calculer_balise_dynamique

// http://doc.spip.org/@balise_FORMULAIRE_RECHERCHE_stat
function balise_FORMULAIRE_RECHERCHE_stat($args, $context_compil) {
	// le premier element du tableau etait auparavant un script.
	// Voir si on ne pourrait pas simplifier maintenant
	return array('', $args ? $args[0] : '');
}

?>
