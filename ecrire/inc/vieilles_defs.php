<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/* Ce fichier contient des fonctions, globales ou constantes	*/
/* qui ont fait partie des fichiers de configurations de Spip	*/
/* mais en ont ete retires ensuite.				*/
/* Ce fichier n'est donc jamais charge par la presente version	*/
/* mais est present pour que les contributions à Spip puissent	*/
/* fonctionner en chargeant ce fichier, en attendant d'etre	*/
/* reecrites conformement a la nouvelle interface.		*/


// http://doc.spip.org/@debut_raccourcis
function debut_raccourcis() {
        global $spip_display;
        echo "<div>&nbsp;</div>";
        creer_colonne_droite();

        debut_cadre_enfonce();
        if ($spip_display != 4) {
                echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
                echo "<b>"._T('titre_cadre_raccourcis')."</b><p />";
        } else {
                echo "<h3>"._T('titre_cadre_raccourcis')."</h3>";
                echo "<ul>";
        }
}

// http://doc.spip.org/@fin_raccourcis
function fin_raccourcis() {
        global $spip_display;
        
        if ($spip_display != 4) echo "</font>";
        else echo "</ul>";
        
        fin_cadre_enfonce();
}

// http://doc.spip.org/@include_ecrire
function include_ecrire($file, $silence=false) {
	preg_match('/^((inc_)?([^.]*))(\.php[3]?)?$/', $file, $r);

	// Version new style, surchargeable
	# cas speciaux
	if ($r[3] == 'index') return include_spip('inc/indexation');
	if ($r[3] == 'db_mysql') return include_spip('base/db_mysql');
	if ($r[3] == 'connect') { spip_connect(); return; }

	# cas general
	if ($f=include_spip('inc/'.$r[3]))
		return $f;

	// fichiers old-style, ecrire/inc_truc.php
	if (is_readable($f = _DIR_RESTREINT . $r[1] . '.php'))
		return include_once($f);
}

// http://doc.spip.org/@lire_meta
function lire_meta($nom) { global $meta; return $meta[$nom];}

// http://doc.spip.org/@afficher_script_layer
function afficher_script_layer(){echo $GLOBALS['browser_layer'];}

// http://doc.spip.org/@test_layer
function test_layer(){return $GLOBALS['browser_layer'];}


// http://doc.spip.org/@affiche_auteur_boucle
function affiche_auteur_boucle($row, &$tous_id)
{
	$vals = '';

	$id_auteur = $row['id_auteur'];
	
	$nom = $row['nom'];

	$s = bonhomme_statut($row);
	$s .= "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>";
	$s .= typo($nom);
	$s .= "</a>";
	$vals[] = $s;

	return $vals;
}

function spip_abstract_quote($arg_sql) {
	return _q($arg_sql);
}

function creer_repertoire($base, $subdir) {
	return sous_repertoire($base, $subdir, true);
}

// http://doc.spip.org/@parse_plugin_xml
function parse_plugin_xml($texte, $clean=true){
	include_spip('inc/xml');
	return spip_xml_parse($texte,$clean);
}

// http://doc.spip.org/@applatit_arbre
function applatit_arbre($arbre,$separateur = " "){
	include_spip('inc/xml');
	return spip_xml_aplatit($arbre,$separateur);
}

?>