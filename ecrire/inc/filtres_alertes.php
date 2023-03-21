<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Ce fichier regroupe la gestion des filtres et balises générant
 * le HTML des messages d'alerte.
 *
 * @package SPIP\Core\Compilateur\Filtres
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise `#ALERTE_MESSAGE` produisant le HTML d'un message d'alerte complet.
 *
 * @note
 * Produit par défaut une alerte avec la classe `notice` et le role `alert`,
 * sauf si on passe des chaînes vides en param.
 * Cela permet par exemple de retirer le rôle dans certains cas précis.
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @example
 *   ```
 *   #ALERTE_MESSAGE{message[,titre][,classes][,role][,id]}
 *   [(#ALERTE_MESSAGE{<:chaine_langue:>, <:chaine_langue:>, notice, status, mon_alerte})]
 *   ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_ALERTE_MESSAGE_dist($p) {
	$_texte = interprete_argument_balise(1, $p);
	$_titre = interprete_argument_balise(2, $p);
	$_class = interprete_argument_balise(3, $p);
	$_role  = interprete_argument_balise(4, $p);
	$_id    = interprete_argument_balise(5, $p);
	$_texte = ($_texte ?: "''");
	$_titre = ($_titre ? ", $_titre" : ', null');
	$_class = ($_class ? ", $_class" : ', null');
	$_role  = ($_role  ? ", $_role"  : ', null');
	$_id    = ($_id    ? ", $_id"    : ', null');

	$f = chercher_filtre('message_alerte');
	$p->code = "$f($_texte$_titre$_class$_role$_id)";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#ALERTE_OUVRIR` produisant le HTML ouvrant d'un message d’alerte
 *
 * Doit être suivie du texte de l'alerte, puis de la balise `#ALERTE_FERMER`.
 *
 * @note
 * Produit par défaut une alerte avec la classe `notice` et le role `alert`,
 * sauf si on passe des chaînes vides en param.
 * Cela permet par exemple de ne pas mettre de rôle dans certains cas précis.
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @see balise_ALERTE_FERMER_dist() Pour clôturer une alerte
 * @example
 *   ```
 *   #ALERTE_OUVRIR{titre[,classes][,role][,id]}
 *   [(#ALERTE_OUVRIR{<:chaine_langue:>, notice, status, mon_alerte})]
 *   ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_ALERTE_OUVRIR_dist($p) {
	$_titre = interprete_argument_balise(1, $p);
	$_class = interprete_argument_balise(2, $p);
	$_role  = interprete_argument_balise(3, $p);
	$_id    = interprete_argument_balise(4, $p);
	$_titre = ($_titre ? "$_titre"   : 'null');
	$_class = ($_class ? ", $_class" : ', null');
	$_role  = ($_role  ? ", $_role"  : ', null');
	$_id    = ($_id    ? ", $_id"    : ', null');

	$f = chercher_filtre('message_alerte_ouvrir');
	$p->code = "$f($_titre$_class$_role$_id)";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#ALERTE_FERMER` produisant le HTML clôturant un message d’alerte
 *
 * Doit être précédée du texte de l'alerte et de la balise `#ALERTE_OUVRIR`.
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @see balise_ALERTE_OUVRIR_dist() Pour ouvrir une alerte
 * @example
 *   ```
 *   #ALERTE_FERMER
 *   ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_ALERTE_FERMER_dist($p) {
	$f = chercher_filtre('message_alerte_fermer');
	$p->code = "$f()";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Générer un message d’alerte
 *
 * Peut-être surchargé par `filtre_message_alerte_dist` ou `filtre_message_alerte`
 *
 * @note
 * Produit par défaut une alerte avec la classe `notice` et le rôle `alert`,
 * sauf si on passe des chaînes vides en param (compat balises).
 * Cela permet par exemple de ne pas mettre de rôle dans certains cas précis.
 *
 * @filtre
 * @see balise_ALERTE_dist() qui utilise ce filtre
 * @see message_alerte_ouvrir()
 * @see message_alerte_fermer()
 * @param string $texte
 *     Contenu de l'alerte
 * @param string $titre
 *     Titre de l'alerte : texte simple, <hn> ou autre.
 * @param string $class
 *     Classes CSS ajoutées au conteneur
 *     Doit contenir le type : `notice`, `error`, `success`, `info`
 *     Défaut = `notice` (sauf en cas de chaîne vide)
 * @param string $role
 *     Attribut role ajouté au conteneur : `alert` ou `status`, selon l'importance
 *     Défaut = `alert` (sauf en cas de chaîne vide)
 * @param string $id
 *     Identifiant HTML du conteneur
 * @return string
 *     HTML de l'alerte
 */
function message_alerte(string $texte, ?string $titre = null, ?string $class = null, ?string $role = null, ?string $id = null): string {

	$message_alerte_ouvrir = chercher_filtre('message_alerte_ouvrir');
	$message_alerte_fermer = chercher_filtre('message_alerte_fermer');

	return $message_alerte_ouvrir($titre, $class, $role, $id) .
		$texte .
		$message_alerte_fermer();
}

/**
 * Ouvrir un message d’alerte
 *
 * Peut-être surchargé par `filtre_message_alerte_ouvrir_dist` ou `filtre_message_alerte_ouvrir`
 *
 * @note
 * Produit par défaut une alerte avec la classe `notice` et le role `alert`,
 * sauf si on passe des chaînes vides en param (compat balises).
 * Cela permet par exemple de ne pas mettre de rôle dans certains cas précis.
 *
 * @filtre
 * @see balise_ALERTE_OUVRIR_dist() qui utilise ce filtre
 * @param string $titre
 *     Titre de l'alerte : texte simple, <hn> ou autre.
 * @param string $class
 *     Classes CSS ajoutées au conteneur
 *     Doit contenir le type : `notice`, `error`, `success`, `info`
 *     Défaut = `notice` (sauf en cas de chaîne vide)
 * @param string $role
 *     Attribut role ajouté au conteneur : `alert` ou `status`, selon l'importance
 *     Défaut = `alert` (sauf en cas de chaîne vide)
 * @param string $id
 *     Identifiant HTML du conteneur
 * @return string
 *     HTML d'ouverture de l'alerte
 */
function message_alerte_ouvrir(?string $titre = null, ?string $class = null, ?string $role = null, ?string $id = null): string {

	$prive = test_espace_prive();

	// Valeurs par défaut
	$titre = trim($titre ?? '');
	$role ??= 'alert'; // fallback uniquement si null
	$class ??= 'notice'; // fallback uniquement si null

	// Type d'alerte : le chercher dans les classes, nettoyer celles-ci, puis le réinjecter
	$types = [
		'notice',
		'error',
		'success',
		'info',
	];
	$type  = array_intersect(explode(' ', $class), $types);
	$type  = reset($type);
	$class = trim(str_replace($types, '', $class) . " $type");

	// Classes
	$class_racine = 'msg-alert';
	$clearfix     = ($prive ? 'clearfix' : '');
	$class_alerte = "$class_racine $class";
	$class_texte  = "{$class_racine}__text $clearfix";
	$class_titre  = "{$class_racine}__heading";

	// Titre : markup
	$titre = trim($titre);
	if (strlen($titre)) {
		include_spip('inc/filtres');
		// Si besoin on encapsule le titre : un h3 dans le privé, un simple div sinon.
		$cherche_tag = ($prive ? '<h' : '<');
		$wrap_tag    = ($prive ? '<h3>' : '<div>');
		if (!str_starts_with($titre, $cherche_tag)) {
			$titre = wrap($titre, $wrap_tag);
		}
		// puis on ajoute la classe
		$titre = ajouter_class($titre, $class_titre);
	}

	// Attributs
	$attr_role = ($role ? "role=\"$role\"" : '');
	$attr_id   = ($id   ? "id=\"$id\"" : '');
	$attr_data = ($type ? "data-alert=\"$type\"" : '');

	return "<div class=\"$class_alerte\" $attr_role $attr_id $attr_data>"
			. $titre
			. "<div class=\"$class_texte\">";
}

/**
 * Fermer un message d’alerte
 *
 * Peut-être surchargé par `filtre_message_alerte_fermer_dist` ou `filtre_message_alerte_fermer`
 *
 * @filtre
 * @see balise_ALERTE_FERMER_dist() qui utilise ce filtre
 * @return string
 *     HTML de fin de l'alerte
 */
function message_alerte_fermer(): string {
	return '</div></div>';
}
