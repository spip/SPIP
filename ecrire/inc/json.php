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

// Les fonctions de toggg pour faire du JSON

function json_export($var) {
	$var = json_encode($var, JSON_THROW_ON_ERROR);

	// flag indiquant qu'on est en iframe et qu'il faut proteger nos
	// donnees dans un <textarea> ; attention $_FILES a ete vide par array_pop
	if (defined('FILE_UPLOAD')) {
		return '<textarea>' . spip_htmlspecialchars($var) . '</textarea>';
	} else {
		return $var;
	}
}
