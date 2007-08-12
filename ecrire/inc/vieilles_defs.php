<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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
/* mais est present pour que les contributions a Spip puissent	*/
/* fonctionner en chargeant ce fichier, en attendant d'etre	*/
/* reecrites conformement a la nouvelle interface.		*/

define('_DIR_DOC', _DIR_IMG);

// http://doc.spip.org/@debut_raccourcis
function debut_raccourcis() {
spip_log('debut_raccourcis() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
        global $spip_display;
        echo "<div>&nbsp;</div>";
        creer_colonne_droite();

        debut_cadre_enfonce();
        if ($spip_display != 4) {
                echo "<font face='Verdana, Geneva, Sans, sans-serif' size=1>";
                echo "<b>"._T('titre_cadre_raccourcis')."</b><p />";
        } else {
                echo "<h3>"._T('titre_cadre_raccourcis')."</h3>";
                echo "<ul>";
        }
}

// http://doc.spip.org/@fin_raccourcis
function fin_raccourcis() {
spip_log('fin_raccourcis() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
        global $spip_display;
        
        if ($spip_display != 4) echo "</font>";
        else echo "</ul>";
        
        fin_cadre_enfonce();
}

// http://doc.spip.org/@include_ecrire
function include_ecrire($file, $silence=false) {
spip_log('include_ecrire() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
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
function lire_meta($nom) {
spip_log('lire_meta() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
 global $meta; return $meta[$nom];}

// http://doc.spip.org/@afficher_script_layer
function afficher_script_layer(){
spip_log('afficher_script_layer() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
echo $GLOBALS['browser_layer'];}

// http://doc.spip.org/@test_layer
function test_layer(){
spip_log('test_layer() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
return $GLOBALS['browser_layer'];}


// http://doc.spip.org/@affiche_auteur_boucle
function affiche_auteur_boucle($row, &$tous_id){
spip_log('affiche_auteur_boucle() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	$vals = '';

	$id_auteur = $row['id_auteur'];
	
	$nom = $row['nom'];

	$s = bonhomme_statut($row);
	$s .= "<a href='" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur") . "'>";
	$s .= typo($nom);
	$s .= "</a>";
	$vals[] = $s;

	return $vals;
}

// http://doc.spip.org/@spip_abstract_quote
function spip_abstract_quote($arg_sql) {
spip_log('spip_abstract_quote() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return _q($arg_sql);
}

// http://doc.spip.org/@creer_repertoire
function creer_repertoire($base, $subdir) {
spip_log('creer_repertoire() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return sous_repertoire($base, $subdir, true);
}

// http://doc.spip.org/@parse_plugin_xml
function parse_plugin_xml($texte, $clean=true){
spip_log('parse_plugin_xml() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	include_spip('inc/xml');
	return spip_xml_parse($texte,$clean);
}

// http://doc.spip.org/@applatit_arbre
function applatit_arbre($arbre,$separateur = " "){
spip_log('applatit_arbre() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	include_spip('inc/xml');
	return spip_xml_aplatit($arbre,$separateur);
}


//
// une autre boite
//
// http://doc.spip.org/@bandeau_titre_boite
function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
spip_log('bandeau_titre_boite() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<tr bgcolor='$couleur_fond'><td width=\"100%\"><font face='Verdana, Geneva, Sans, sans-serif' size='3' color='$couleur_texte'>";
	echo "<b>$titre</b></font></td>";
	if ($afficher_auteurs){
		echo "<td width='100'>";
		echo http_img_pack("rien.gif", "", "width='100' height='12'");
		echo "</td>";
	}
	echo "<td width='90'>";
	echo http_img_pack("rien.gif", "", "width='90' height='12'");
	echo "</td>";
	echo "</tr>";
}

// http://doc.spip.org/@debut_page
function debut_page($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $onLoad = "" /* ignore */, $id_rubrique = "") {
spip_log('debut_page() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, $rubrique, $sous_rubrique, $id_rubrique);
	if ($onLoad) spip_log("parametre obsolete onLoad=$onLoad");
}

// obsolete, utiliser calculer_url
// http://doc.spip.org/@extraire_lien
function extraire_lien ($regs) {
spip_log('extraire_lien() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	list($lien, $class, $texte) = calculer_url($regs[3], $regs[1],'tout');
	// Preparer le texte du lien ; attention s'il contient un <div>
	// (ex: [<docXX|right>->lien]), il faut etre smart
	$ref = "<a href=\"$lien\" class=\"$class\">$texte</a>";
	return array($ref, $lien, $texte);
}

// Prendre la fonction inc_dater_dist, qui fait du Ajax.
// http://doc.spip.org/@afficher_formulaire_date
function afficher_formulaire_date($script, $args, $texte, $jour, $mois, $annee){
spip_log('afficher_formulaire_date() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
  global $couleur_foncee;
  return generer_url_post_ecrire($script, $args)
	. "<table cellpadding='5' cellspacing='0' border='0' width='100%' background='"
	.  _DIR_IMG_PACK
	. "rien.gif'>"
	. "<tr><td bgcolor='$couleur_foncee' colspan='2'><font size='2' color='#ffffff'><b>"
	._T('texte_date_publication_article')
	. "</b></font></tr>"
	. "<tr><td align='center'>"
	. afficher_jour($jour, "name='jour' size='1' class='fondl'", true)
	. afficher_mois($mois, "name='mois' size='1' class='fondl'", true)
	. afficher_annee($annee, "name='annee' size='1' class='fondl'",1996)
	. "</td><td align='right'>"
	. "<input type='submit' name='Changer' class='fondo' value='"
	. _T('bouton_changer')
	. "'>"
	. "</td></tr></table>"
	. "</form>";
}

// http://doc.spip.org/@ratio_image
function ratio_image($logo, $nom, $format, $taille, $taille_y, $attributs) {
spip_log('ratio_image() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	// $logo est le nom complet du logo ($logo = "chemin/$nom.$format)
	// $nom et $format ne servent plus du fait du passage par le filtre image_reduire
	include_spip('inc/filtres_images');
	$res = image_reduire("<img src='$logo' $attributs />", $taille, $taille_y);
	return $res;
}

// http://doc.spip.org/@entites_unicode
function entites_unicode($texte) {
spip_log('entites_unicode() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return charset2unicode($texte);
}


// utiliser directement le corps a present.

// http://doc.spip.org/@afficher_claret
function afficher_claret() {
spip_log('afficher_claret() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	include_spip('inc/layer');
	return $GLOBALS['browser_caret'];
}


// http://doc.spip.org/@spip_insert_id
function spip_insert_id() {
spip_log('spip_insert_id() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return mysql_insert_id();
}


// revenir a la langue precedente
// http://doc.spip.org/@lang_dselect
function lang_dselect () {
spip_log('lang_dselect() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	lang_select();
}
// toujours disponible pour PHP > 4.0.1
$GLOBALS['flag_revisions'] = function_exists("gzcompress");

// toujours a cette valeur a present
$GLOBALS['options'] = 'avancees';

// synonyme plus jamais utile
$GLOBALS['langue_site'] = $GLOBALS['meta']['langue_site'];
$GLOBALS['all_langs'] = @$GLOBALS['meta']['langues_proposees'];
// http://doc.spip.org/@generer_url_post_ecrire
function generer_url_post_ecrire($script, $args='', $name='', $ancre='', $onchange='') {
spip_log('generer_url_post_ecrire() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	include_spip('inc/filtres');
	$action = generer_url_ecrire($script, $args);
	if ($name) $name = " name='$name'";
	return "\n<form action='$action$ancre'$name method='post'$onchange>"
	.form_hidden($action);
}

// http://doc.spip.org/@afficher_articles
function afficher_articles($titre, $requete, $formater='') {
spip_log('afficher_articles() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	afficher_objets('article',$titre,$requete,$formater);
}
// http://doc.spip.org/@afficher_auteurs
function afficher_auteurs ($titre_table, $requete) {
spip_log('afficher_auteurs() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	afficher_objets('auteur',$titre_table,$requete,'');
}
// http://doc.spip.org/@afficher_sites
function afficher_sites($titre_table, $requete){
spip_log('afficher_sites() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	afficher_objets('site',$titre_table,$requete,'');	
}
// http://doc.spip.org/@afficher_syndic_articles
function afficher_syndic_articles($titre_table, $requete, $id = 0) {
spip_log('afficher_syndic_articles() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	afficher_objets('syndic_article',$titre_table,$requete,$id);	
}

// Retourne les droits de publication d'un auteur selon le codage suivant:
// - le tableau de ses rubriques si c'est un admin restreint
// - 0 si c'est un admin de plein droit
// - la chaine indiquant son statut s'il n'est pas admin

// http://doc.spip.org/@auth_rubrique
function auth_rubrique($id_auteur, $statut)
{
spip_log('auth_rubrique() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');

	if ($statut != '0minirezo') return $statut;

	$result = spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur AND id_rubrique!='0'");
	if (!spip_num_rows($result)) {
		return 0;
	}
	$rubriques = array();
	for (;;) {
		$r = array();
		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row['id_rubrique'];
			$r[]= $rubriques[$id_rubrique] = $id_rubrique;
		}
		if (!$r) return $rubriques;
		$r = join(',', $r);

		$result = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)");
	}
}

// http://doc.spip.org/@bouton_block_invisible
function bouton_block_invisible($nom_block, $icone='') {
	include_spip('inc/layer');
	return bouton_block_depliable(_T("info_sans_titre"),false,$nom_block);
}

// http://doc.spip.org/@bouton_block_visible
function bouton_block_visible($nom_block){
	include_spip('inc/layer');
	return bouton_block_depliable(_T("info_sans_titre"),true,$nom_block);
}

// http://doc.spip.org/@debut_block_visible
function debut_block_visible($id=""){
	include_spip('inc/layer');
	return debut_block_depliable(true,$id);
}

// http://doc.spip.org/@debut_block_invisible
function debut_block_invisible($id=""){
	include_spip('inc/layer');
	return debut_block_depliable(false,$id);
}

// http://doc.spip.org/@init_config
function init_config(){
	spip_log('init_config() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	include_spip('inc/config');
	inc_config_dist();
}

/*
// synonyme de extraire_balises (et utilises nulle part sur la zone)
// http://doc.spip.org/@extraire_tags
function extraire_tags($texte) {
	spip_log('extraire_tags() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return extraire_balises($texte, 'a');
}
// synonyme de extraire_balise
// http://doc.spip.org/@extraire_tag
function extraire_tag($texte, $tag='a') {
	spip_log('extraire_tag() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	return extraire_balise($texte, $tag);
}
*/

//
// une autre boite
//
// http://doc.spip.org/@bandeau_titre_boite2
function bandeau_titre_boite2($titre, $logo="", $fond="toile_blanche", $texte="ligne_noire") {
	global $spip_lang_left, $spip_display, $browser_name;
	
	if (strlen($logo) > 0 AND $spip_display != 1 AND $spip_display != 4) {
		$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

		return "\n<div style='position: relative;$ie_style'>"
		. "\n<div style='position: absolute; top: -12px; $spip_lang_left: 3px;'>"
		. http_img_pack($logo, "", "")
		. "</div>"
		. "\n<div style='padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;' class='verdana2 $fond $texte'>$titre</div>"
		. "</div>";
	} else {
		return "<h3 style='padding: 3px; border-bottom: 1px solid #444444; margin: 0px;' class='verdana2 $fond $texte'>$titre</h3>";
	}
}

// http://doc.spip.org/@spip_free_result
function spip_free_result($r) {
spip_log('spip_free_result() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	sql_free($r);
}

// http://doc.spip.org/@creer_objet_multi
function creer_objet_multi($r, $l) {
spip_log('creer_objet_multi() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
 sql_multi($r, $l);
}

// Pas de message pour cette fonction, si utilisee auparavant
// http://doc.spip.org/@spip_fetch_array
function spip_fetch_array($r, $t=MYSQL_ASSOC) {
	if ($r) return mysql_fetch_array($r, $t);
}


// http://doc.spip.org/@envoyer_mail
function envoyer_mail($email, $sujet, $texte, $from = "", $headers = "") {
spip_log('creer_objet_multi() '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	return $envoyer_mail($email,$sujet,$texte,$from,$headers);
}

?>
