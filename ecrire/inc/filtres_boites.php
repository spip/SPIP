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
 * #BOITE_OUVRIR{titre[,type]}
 * Racourci pour ouvrir une boite (info, simple, pour noisette ...)
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_OUVRIR_dist($p) {
	$_titre = interprete_argument_balise(1,$p);
	$_class = interprete_argument_balise(2,$p);
	$_head_class = interprete_argument_balise(3,$p);
	$_titre = ($_titre?$_titre:"''");
	$_class = ($_class?", $_class":", 'simple'");
	$_head_class = ($_head_class?", $_head_class":"");

	$f = chercher_filtre('boite_ouvrir');
	$p->code = "$f($_titre$_class$_head_class)";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * #BOITE_PIED{class}
 * Racourci pour passer au pied de la boite, avant sa fermeture
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_PIED_dist($p) {
	$_class = interprete_argument_balise(1,$p);
	$_class = ($_class?"$_class":"");

	$f = chercher_filtre('boite_pied');
	$p->code = "$f($_class)";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * #BOITE_FERMER
 * Racourci pour fermer une boite ouverte
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_FERMER_dist($p) {
	$f = chercher_filtre('boite_fermer');
	$p->code = "$f()";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Ouvrir une boite
 * peut etre surcharge par filtre_boite_ouvrir_dist, filtre_boite_ouvrir
 *
 * @param string $titre
 * @param string $class
 * @return <type>
 */
function boite_ouvrir($titre, $class='', $head_class='', $id=""){
	$class = "box $class";
	$head_class = "hd $head_class";
	// dans l'espace prive, titrer en h3 si pas de balise <hn>
	if (test_espace_prive() AND strlen($titre) AND strpos($titre,'<h')===false)
		$titre = "<h3>$titre</h3>";
	return '<div class="'.$class.($id?"\" id=\"$id":"").'">'
	.'<b class="top"><b class="tl"></b><b class="tr"></b></b>'
	.'<div class="inner">'
	.($titre?'<div class="'.$head_class.'">'.$titre.'<div class="nettoyeur"></div><!--/hd--></div>':'')
	.'<div class="bd">';
}

/**
 * Passer au pied d'une boite
 * peut etre surcharge par filtre_boite_pied_dist, filtre_boite_pied
 *
 * @param <type> $class
 * @return <type>
 */
function boite_pied($class='act'){
	$class = "ft $class";
	return 	'<div class="nettoyeur"></div></div>'
	.'<div class="'.$class.'">';
}

/**
 * Fermer une boite
 * peut etre surcharge par filtre_boite_fermer_dist, filtre_boite_fermer
 *
 * @return <type>
 */
function boite_fermer(){
	return '<div class="nettoyeur"></div></div></div>'
	.'<b class="bottom"><b class="bl"></b><b class="br"></b></b>'
	.'</div>';
}




?>
