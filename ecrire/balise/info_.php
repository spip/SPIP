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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Generer n'importe quel info pour un objet : #INFO_TITRE{article, #ENV{id_article}}
 * Utilise la fonction generer_info_entite() de inc/filtres
 * se reporter a sa documentation
 * 
 */
function balise_INFO__dist($p){
	$info = $p->nom_champ;
	$type_objet = interprete_argument_balise(1,$p);
	$id_objet = interprete_argument_balise(2,$p);
	if ($info === 'INFO_' or !$type_objet or !$id_objet) {
		$msg = _T('zbug_balise_sans_argument', array('balise' => ' INFO_'));
		erreur_squelette($msg, $p);
		$p->interdire_scripts = true;
		return $p;
	}
	else {
		$info_sql = strtolower(substr($info,5));
		$code = "generer_info_entite($id_objet, $type_objet, '$info_sql'".($p->etoile?","._q($p->etoile):"").")";
		$p->code = champ_sql($info, $p, $code);
		$p->interdire_scripts = true;
		return $p;
	}
}

?>
