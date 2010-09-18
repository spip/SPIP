<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Fonctions utilises au calcul des squelette du prive.
 */

/**
 * Bloquer l'acces a une page en renvoyant vers 403
 * @param bool $ok
 */
function interdire_acces($ok=false) {
	if ($ok) return '';
	ob_end_clean(); // vider tous les tampons
	$echec = charger_fonction('403','exec');
	$echec();

	#include_spip('inc/headers');
	#redirige_formulaire(generer_url_ecrire('403','acces='._request('exec')));
	exit;
}

/**
 * Filtre d'ajout automatique du title dans les pages du prive en squelette
 * appelle dans le pipeline affichage_final_prive
 *
 * @param string $texte
 * @return string
 */
function f_title_auto($texte){
	if (strpos($texte,'<title>')===false
	  AND
			(preg_match(",<h1[^>]*>(.+)</h1>,Uims", $texte, $match)
		   OR preg_match(",<h[23][^>]*>(.+)</h[23]>,Uims", $texte, $match))
		AND $match = trim($match[1])
		AND ($p = strpos($texte,'<head>'))!==FALSE) {
		if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
			$nom_site_spip=  _T('info_mon_site_spip');

		$titre = "<title>["
			. $nom_site_spip
			. "] ". $match
		  ."</title>";

		$texte = substr_replace($texte, $titre, $p+6,0);
	}
	return $texte;
}

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

	$p->code = "boite_ouvrir($_titre$_class$_head_class)";
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

	$p->code = "boite_pied($_class)";
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
	$p->code = "boite_fermer()";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Ouvrir une boite
 *
 * @param string $titre
 * @param string $class
 * @return <type>
 */
function boite_ouvrir($titre, $class='', $head_class=''){
	$class = "box $class";
	$head_class = "hd $head_class";
	if (strlen($titre) AND strpos($titre,'<h')===false)
		$titre = "<h3>$titre</h3>";
	return '<div class="'.$class.'">'
	.'<b class="top"><b class="tl"></b><b class="tr"></b></b>'
	.'<div class="inner">'
	.($titre?'<div class="'.$head_class.'">'.$titre.'</div>':'')
	.'<div class="bd">';
}

/**
 * Passer au pied d'une boite
 * @param <type> $class
 * @return <type>
 */
function boite_pied($class='act'){
	$class = "ft $class";
	return 	'</div>'
	.'<div class="'.$class.'">';
}

/**
 * Fermer une boite
 * @return <type>
 */
function boite_fermer(){
	return '</div></div>'
	.'<b class="bottom"><b class="bl"></b><b class="br"></b></b>'
	.'</div>';
}

?>