<?php

namespace Spip\Compilateur\Iterateur;

use EmptyIterator;
use Exception;

/**
 * Fabrique d'iterateur
 * permet de charger n'importe quel iterateur IterateurXXX
 * fourni dans le fichier iterateurs/xxx.php.
 */
class Factory
{
	public static function create($iterateur, $command, $info = null) {
		$iter = null;
		// cas des SI {si expression} analises tres tot
		// pour eviter le chargement de tout iterateur
		if (isset($command['si'])) {
			foreach ($command['si'] as $si) {
				if (!$si) {
					// $command pour boucle SQL peut generer des erreurs de compilation
					// s'il est transmis alors qu'on est dans un iterateur vide
					return new Decorator(new EmptyIterator(), [], $info);
				}
			}
		}

		// chercher un iterateur PHP existant (par exemple dans SPL)
		// (il faudrait passer l'argument ->sql_serveur
		// pour etre certain qu'on est sur un "php:")
		if (class_exists($iterateur)) {
			$a = $command['args'] ?? [];

			// permettre de passer un Iterateur directement {args #ITERATEUR} :
			// si on recoit deja un iterateur en argument, on l'utilise
			if ((is_countable($a) ? count($a) : 0) == 1 and is_object($a[0]) and is_subclass_of($a[0], \Iterator::class)) {
				$iter = $a[0];
			} else {
				// sinon, on cree un iterateur du type donne
				// arguments de creation de l'iterateur...
				try {
					$iter = new $iterateur(...$a);
				} catch (Exception $e) {
					spip_log("Erreur de chargement de l'iterateur {$iterateur}");
					spip_log($e->getMessage());
					$iter = new EmptyIterator();
				}
			}
		} else {
			// chercher la classe d'iterateur Iterateur/XXX
			// definie dans le fichier src/Compilateur/Iterateur/xxx.php
			// FIXME: déclarer quelque part les iterateurs supplémentaires
			$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($iterateur));
			if (!class_exists($class)) {
				// historique
				// Chercher IterateurXXX
				include_spip('iterateur/' . $iterateur);
				$class = 'Iterateur' . $iterateur;
				if (!class_exists($class)) {
					exit("Iterateur {$iterateur} non trouv&#233;");
					// si l'iterateur n'existe pas, on se rabat sur le generique
					// $iter = new EmptyIterator();
				}
			}
			$iter = new $class($command, $info);
		}

		return new Decorator($iter, $command, $info);
	}
}
