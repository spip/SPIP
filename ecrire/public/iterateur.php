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

/**
 * Fabrique d'iterateur
 * permet de charger n'importe quel iterateur IterateurXXX
 * fourni dans le fichier iterateurs/iterateur_xxx.php
 * 
 */
class IterFactory{
	public static function create($iterateur,$command, $info=null){

		// chercher la classe d'iterateur
		// IterateurXXX
		// definie dans le fichier iterateurs/iterateur_xxx.php
		$class = "Iterateur".$iterateur;
		if (!include_spip("iterateurs/iterateur_" . strtolower($iterateur))
		  OR !class_exists($class)) {

			die("Iterateur $iterateur non trouve");
			// si l'iterateur n'existe pas, on se rabat sur le generique
			$class = "IterateurSPIP";
		  include_spip("iterateurs/iterateur");
		}

		return new $class($command, $info);
	}
}

?>
