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
 * Ce fichier regroupe la gestion des filtres et balises gérant des
 * boîtes de contenu
 *
 * @package SPIP\Core\Compilateur\Filtres
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Compile la balise `#BOITE_OUVRIR` ouvrant une boîte de contenu
 *
 * Racourci pour ouvrir une boîte (info, simple, pour noisette ...)
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @see balise_BOITE_PIED_dist() Pour passer au pied de boîte
 * @see balise_BOITE_FERMER_dist() Pour fermer une boîte
 * @example
 *   ```
 *   #BOITE_OUVRIR{titre[,type]}
 *   [(#BOITE_OUVRIR{<:titre_cadre_interieur_rubrique:>,simple})]
 *   #BOITE_OUVRIR{'',raccourcis}
 *   ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_BOITE_OUVRIR_dist($p) {
	$_titre = interprete_argument_balise(1, $p);
	$_class = interprete_argument_balise(2, $p);
	$_head_class = interprete_argument_balise(3, $p);
	$_titre = ($_titre ? $_titre : "''");
	$_class = ($_class ? ", $_class" : ", 'simple'");
	$_head_class = ($_head_class ? ", $_head_class" : '');

	$f = chercher_filtre('boite_ouvrir');
	$p->code = "$f($_titre$_class$_head_class)";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Compile la balise `#BOITE_PIED` cloturant une boîte de contenu
 *
 * Racourci pour passer au pied de la boite, avant sa fermeture. On peut
 * lui transmettre une classe CSS avec `#BOITE_PIED{class}`
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @see balise_BOITE_OUVRIR_dist() Pour ouvrir une boîte
 * @see balise_BOITE_FERMER_dist() Pour fermer une boîte
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_BOITE_PIED_dist($p) {
	$_class = interprete_argument_balise(1, $p);
	$_class = ($_class ? "$_class" : '');

	$f = chercher_filtre('boite_pied');
	$p->code = "$f($_class)";
	$p->interdire_scripts = false;

	return $p;
}


/**
 * Compile la balise `#BOITE_FERMER` clôturant une boîte de contenu
 *
 * Racourci pour fermer une boîte ouverte
 *
 * @package SPIP\Core\Compilateur\Balises
 * @balise
 * @see balise_BOITE_OUVRIR_dist() Pour ouvrir une boîte
 * @see balise_BOITE_PIED_dist() Pour passer au pied de boîte
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_BOITE_FERMER_dist($p) {
	$f = chercher_filtre('boite_fermer');
	$p->code = "$f()";
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Ouvrir une boîte
 *
 * Peut-être surchargé par `filtre_boite_ouvrir_dist` ou `filtre_boite_ouvrir`
 *
 * @filtre
 * @see balise_BOITE_OUVRIR_dist() qui utilise ce filtre
 * @param string $titre
 *     Titre de la boîte
 * @param string $class
 *     Classes CSS de la boîte
 * @param string $head_class
 *     Classes CSS sur l'entête
 * @param string $id
 *     Identifiant HTML de la boîte
 * @return string
 *     HTML du début de la boîte
 */
function boite_ouvrir($titre, $class = '', $head_class = '', $id = '') {
	$class = "box $class";
	$head_class = "box__header $head_class clearfix";
	// dans l'espace prive, titrer en h3 si pas de balise <hn>
	if (test_espace_prive() and strlen($titre) and strpos($titre, '<h') === false) {
		$titre = "<h3>$titre</h3>";
	}

	return '<div class="' . $class . ($id ? "\" id=\"$id" : '') . '">'
	. ($titre ? "<div class=\"$head_class\">$titre<!--/hd--></div>" : '')
	. '<div class="box__body clearfix">';
}


/**
 * Passer au pied d'une boîte
 *
 * Peut-être surchargé par `filtre_boite_pied_dist` ou `filtre_boite_pied`
 *
 * @filtre
 * @see balise_BOITE_PIED_dist() qui utilise ce filtre
 * @param string $class
 *     Classes CSS de la boîte
 * @return string
 *     HTML de transition vers le pied de la boîte
 */
function boite_pied($class = 'act') {
	$class = "box__footer $class";

	return '</div>'
	. "<div class=\"$class clearfix\">";
}


/**
 * Fermer une boîte
 *
 * Peut-être surchargé par `filtre_boite_fermer_dist` ou `filtre_boite_fermer`
 *
 * @filtre
 * @see balise_BOITE_FERMER_dist() qui utilise ce filtre
 * @return string
 *     HTML de fin de la boîte
 */
function boite_fermer() {
	return '</div>'
	. '</div>';
}
