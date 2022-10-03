<?php

namespace Spip\Compilateur\Contexte;

/**
 * Description d'un contexte de compilation
 *
 * Objet simple pour stocker le nom du fichier, la ligne, la boucle
 * permettant entre autre de localiser le lieu d'une erreur de compilation.
 * Cette structure est nécessaire au traitement d'erreur à l'exécution.
 *
 * Le champ code est inutilisé dans cette classe seule, mais harmonise
 * le traitement d'erreurs.
 */
class Contexte
{
	/**
	 * Description du squelette
	 *
	 * Sert pour la gestion d'erreur et la production de code dependant du contexte
	 *
	 * Peut contenir les index :
	 *
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - documents : Pour embed et img dans les textes
	 * - session : Pour un cache sessionné par auteur
	 * - niv : Niveau de tabulation
	 */
	public array $descr = [];

	/** Identifiant de la boucle */
	public string $id_boucle = '';

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;

	/** Langue d'exécution */
	public string $lang = '';

	/** Résultat de la compilation: toujours une expression PHP */
	public string $code = '';
}
