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
 * Compile la balise `#ALERTE` produisant le HTML d'un message d'alerte complet.
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @example
 *   ```
 *   #ALERTE{message[,titre][,classes][,role][,id]}
 *   [(#ALERTE{<:chaine_langue:>, <:chaine_langue:>, notice, status, mon_alerte})]
 *   ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_ALERTE_dist($p) {
	$_texte = interprete_argument_balise(1, $p);
	$_titre = interprete_argument_balise(2, $p);
	$_class = interprete_argument_balise(3, $p);
	$_role  = interprete_argument_balise(4, $p);
	$_id    = interprete_argument_balise(5, $p);
	$_titre = ($_titre ? ", $_titre" : "''");
	$_class = ($_class ? ", $_class" : ", ''");
	$_role  = ($_role ? ", $_role" : ", ''");
	$_id    = ($_id ? ", $_id" : ", ''");

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
	$_titre = ($_titre ? "$_titre" : "''");
	$_class = ($_class ? ", $_class" : ", ''");
	$_role  = ($_role ? ", $_role" : ", ''");
	$_id    = ($_id ? ", $_id" : ", ''");

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
 * @param string $role
 *     Attribut rôle ajouté au conteneur : `alert` ou `status`, selon l'importance
 * @param string $id
 *     Identifiant HTML du conteneur
 * @return string
 *     HTML de l'alerte
 */
function message_alerte(string $texte, string $titre = '', string $class = '', string $role = '', string $id = '') : string {

	$message_alerte_ouvrir = chercher_filtre('message_alerte_ouvrir');
	$message_alerte_fermer = chercher_filtre('message_alerte_fermer');
	$message =
		$message_alerte_ouvrir($titre, $class, $role, $id) .
		$texte .
		$message_alerte_fermer();

	return $message;
}

/**
 * Ouvrir un message d’alerte
 *
 * Peut-être surchargé par `filtre_message_alerte_ouvrir_dist` ou `filtre_message_alerte_ouvrir`
 *
 * @filtre
 * @see balise_ALERTE_OUVRIR_dist() qui utilise ce filtre
 * @param string $titre
 *     Titre de l'alerte : texte simple, <hn> ou autre.
 * @param string $class
 *     Classes CSS ajoutées au conteneur
 *     Doit contenir le type : `notice`, `error`, `success`, `info`
 *     Défaut = `notice`
 * @param string $role
 *     Attribut role ajouté au conteneur : `alert` ou `status`, selon l'importance
 *     Défaut = `alert`
 * @param string $id
 *     Identifiant HTML du conteneur
 * @return string
 *     HTML d'ouverture de l'alerte
 */
function message_alerte_ouvrir(string $titre = '', string $class = '', string $role = '', string $id = '') : string {

	$prive = test_espace_prive();

	// Type d'alerte : le chercher dans les classes, nettoyer celles-ci, puis le réinjecter
	$types = [
		'notice',
		'error',
		'success',
		'info',
	];
	$type = array_intersect(explode(' ', $class), $types);
	$type  = reset($type) ?: 'notice';
	$class = trim(str_replace($types, '', $class) . " $type");

	// Role
	$role = $role ?: 'alert';

	// Classes
	$class_racine = 'msg-alert';
	$clearfix     = ($prive ? 'clearfix' : '');
	$class_alerte = "$class_racine $class";
	$class_texte  = "${class_racine}__text $clearfix";
	$class_titre  = "${class_racine}__heading";

	// Titre : markup
	$titre = trim($titre);
	if (strlen($titre)) {
		include_spip('inc/filtres');
		// Si besoin on encapsule le titre : un h3 dans le privé, un simple div sinon.
		$cherche_tag = ($prive ? '<h' : '<');
		$wrap_tag    = ($prive ? '<h3>' : '<div>');
		if (strpos($titre, $cherche_tag) !== 0) {
			$titre = wrap($titre, $wrap_tag);
		}
		// puis on ajoute la classe
		$titre = ajouter_class($titre, $class_titre);
	}

	// Autres attributs
	$attr_id = ($id ? "id=\"$id\"" : '');
	$attr_data = ($type ? "data-$role=\"$type\"" : '');

	$message =
		"<div class=\"$class_alerte\" role=\"$role\" $attr_id $attr_data>" .
			$titre .
			"<div class=\"$class_texte\">";

	return $message;
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
function message_alerte_fermer() : string {
	return '</div></div>';
}
