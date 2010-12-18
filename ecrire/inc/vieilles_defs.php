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

if (!defined('_ECRIRE_INC_VERSION')) return;

/* Ce fichier contient des fonctions, globales ou constantes	*/
/* qui ont fait partie des fichiers de configurations de Spip	*/
/* mais en ont ete retires ensuite.				*/
/* Ce fichier n'est donc jamais charge par la presente version	*/
/* mais est present pour que les contributions a Spip puissent	*/
/* fonctionner en chargeant ce fichier, en attendant d'etre	*/
/* reecrites conformement a la nouvelle interface.		*/

define('_SIGNALER_ECHOS', false);


// Log des appels aux vieilles_defs
// http://doc.spip.org/@vieilles_log
function vieilles_log($quoi) {
	static $vus = array();
	$c = crc32($quoi);
	if (!isset($vus[$c])) {
		spip_log($quoi.' '.$GLOBALS['REQUEST_URI'].' - '.$_SERVER['SCRIPT_NAME'], 'vieilles_defs');
		$vus[$c] = 1;
	} else if (++$vus[$c] > 100) spip_log("$quoi doit imperativement etre remplacee");
}

foreach (
array(
'debut_raccourcis' => '() {
	vieilles_log(\'debut_raccourcis()\');

	global $spip_display;
	echo "<div>&nbsp;</div>";
	echo creer_colonne_droite(\'\', true);

	echo debut_cadre_enfonce(\'\',true);
	if ($spip_display != 4) {
		echo "<font face=\'Verdana, Geneva, Sans, sans-serif\' size=1>";
		echo "<b>"._T(\'titre_cadre_raccourcis\')."</b><p />";
	} else {
		echo "<h3>"._T(\'titre_cadre_raccourcis\')."</h3>";
		echo "<ul>";
	}
}
',

'fin_raccourcis' => '() {
	vieilles_log(\'fin_raccourcis()\');

	global $spip_display;
	
	if ($spip_display != 4) echo "</font>";
	else echo "</ul>";
	
	echo fin_cadre_enfonce(true);
}

',

'include_ecrire' => '($file, $silence=false) {
	vieilles_log(\'include_ecrire()\');

	preg_match(\'/^((inc_)?([^.]*))(\.php[3]?)?$/\', $file, $r);

	// Version new style, surchargeable
	# cas speciaux
	if ($r[3] == \'index\') return false; // inc/indexation
	if ($r[3] == \'db_mysql\') return include_spip(\'base/db_mysql\');
	if ($r[3] == \'connect\') { spip_connect(); return; }

	# cas general
	if ($f=include_spip(\'inc/\'.$r[3]))
		return $f;

	// fichiers old-style, ecrire/inc_truc.php
	if (is_readable($f = _DIR_RESTREINT . $r[1] . \'.php\'))
		return include_once($f);
}

',

'afficher_script_layer' => '(){
	vieilles_log(\'afficher_script_layer()\');

echo $GLOBALS[\'browser_layer\'];}

',

'test_layer' => '(){
	vieilles_log(\'test_layer()\');

return $GLOBALS[\'browser_layer\'];}


',

'affiche_auteur_boucle' => '($row, &$tous_id){
	vieilles_log(\'affiche_auteur_boucle()\');

	$vals = \'\';

	$id_auteur = $row[\'id_auteur\'];
	
	$nom = $row[\'nom\'];

	$s = bonhomme_statut($row);
	$s .= "<a href=\'" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur") . "\'>";
	$s .= typo($nom);
	$s .= "</a>";
	$vals[] = $s;

	return $vals;
}

',

'spip_abstract_quote' => '($arg_sql) {
	vieilles_log(\'spip_abstract_quote()\');

	return sql_quote($arg_sql);
}

',

'creer_repertoire' => '($base, $subdir) {
	vieilles_log(\'creer_repertoire()\');

	return sous_repertoire($base, $subdir, true);
}

',

'parse_plugin_xml' => '($texte, $clean=true){
	vieilles_log(\'parse_plugin_xml()\');

	include_spip(\'inc/xml\');
	return spip_xml_parse($texte,$clean);
}

',

'applatit_arbre' => '($arbre,$separateur = " "){
	vieilles_log(\'applatit_arbre()\');

	include_spip(\'inc/xml\');
	return spip_xml_aplatit($arbre,$separateur);
}


//
// une autre boite
//
',

'bandeau_titre_boite' => '($titre, $afficher_auteurs, $boite_importante = true) {
	vieilles_log(\'bandeau_titre_boite()\');

	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = \'#FFFFFF\';
	}
	else {
		$couleur_fond = \'#EEEECC\';
		$couleur_texte = \'#000000\';
	}
	echo "<tr bgcolor=\'$couleur_fond\'><td width=\"100%\"><font face=\'Verdana, Geneva, Sans, sans-serif\' size=\'3\' color=\'$couleur_texte\'>";
	echo "<b>$titre</b></font></td>";
	if ($afficher_auteurs){
		echo "<td width=\'100\'>";
		echo http_img_pack("rien.gif", "", "width=\'100\' height=\'12\'");
		echo "</td>";
	}
	echo "<td width=\'90\'>";
	echo http_img_pack("rien.gif", "", "width=\'90\' height=\'12\'");
	echo "</td>";
	echo "</tr>";
}

',

'debut_page' => '($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $onLoad = "" /* ignore */, $id_rubrique = "") {
	vieilles_log(\'debut_page()\');

	$commencer_page = charger_fonction(\'commencer_page\', \'inc\');
	echo $commencer_page($titre, $rubrique, $sous_rubrique, $id_rubrique);
	if ($onLoad) vieilles_log("parametre obsolete onLoad=$onLoad");
}

// obsolete, utiliser calculer_url
',

'extraire_lien' => '($regs) {
	vieilles_log(\'extraire_lien()\');

	list($lien, $class, $texte) = calculer_url($regs[3], $regs[1],\'tout\');
	// Preparer le texte du lien ; attention s\'il contient un <div>
	// (ex: [<docXX|right>->lien]), il faut etre smart
	$ref = "<a href=\"$lien\" class=\"$class\">$texte</a>";
	return array($ref, $lien, $texte);
}

// Prendre la fonction inc_dater_dist, qui fait du Ajax.
',

'afficher_formulaire_date' => '($script, $args, $texte, $jour, $mois, $annee){
	vieilles_log(\'afficher_formulaire_date()\');

  global $couleur_foncee;
  return generer_url_post_ecrire($script, $args)
	. "<table cellpadding=\'5\' cellspacing=\'0\' border=\'0\' width=\'100%\' background=\'"
	.  chemin_image(\'rien.gif\') 
	. "\'>"
	. "<tr><td bgcolor=\'$couleur_foncee\' colspan=\'2\'><font size=\'2\' color=\'#ffffff\'><b>"
	._T(\'texte_date_publication_article\')
	. "</b></font></tr>"
	. "<tr><td align=\'center\'>"
	. afficher_jour($jour, "name=\'jour\' size=\'1\'", true)
	. afficher_mois($mois, "name=\'mois\' size=\'1\'", true)
	. afficher_annee($annee, "name=\'annee\' size=\'1\'",1996)
	. "</td><td align=\'right\'>"
	. "<input type=\'submit\' name=\'Changer\'  value=\'"
	. _T(\'bouton_changer\')
	. "\'>"
	. "</td></tr></table>"
	. "</form>";
}

',

'ratio_image' => '($logo, $nom, $format, $taille, $taille_y, $attributs) {
	vieilles_log(\'ratio_image()\');

	// $logo est le nom complet du logo ($logo = "chemin/$nom.$format)
	// $nom et $format ne servent plus du fait du passage par le filtre image_reduire
	include_spip(\'inc/filtres_images_mini\');
	$res = image_reduire("<img src=\'$logo\' $attributs />", $taille, $taille_y);
	return $res;
}

',

'entites_unicode' => '($texte) {
	vieilles_log(\'entites_unicode()\');

	return charset2unicode($texte);
}


// utiliser directement le corps a present.

',

'afficher_claret' => '() {
	vieilles_log(\'afficher_claret()\');

	include_spip(\'inc/layer\');
	return $GLOBALS[\'browser_caret\'];
}


',

'spip_insert_id' => '() {
	vieilles_log(\'spip_insert_id()\');

	return mysql_insert_id();
}


// revenir a la langue precedente
',

'lang_dselect' => '() {
	vieilles_log(\'lang_dselect()\');

	lang_select();
}
// toujours disponible pour PHP > 4.0.1
$GLOBALS[\'flag_revisions\'] = function_exists("gzcompress");

// toujours a cette valeur a present
$GLOBALS[\'options\'] = \'avancees\';

// synonyme plus jamais utile
$GLOBALS[\'langue_site\'] = $GLOBALS[\'meta\'][\'langue_site\'];
$GLOBALS[\'all_langs\'] = @$GLOBALS[\'meta\'][\'langues_proposees\'];
',

'generer_url_post_ecrire' => '($script, $args=\'\', $name=\'\', $ancre=\'\', $onchange=\'\') {
	vieilles_log(\'generer_url_post_ecrire()\');

	include_spip(\'inc/filtres\');
	$action = generer_url_ecrire($script, $args);
	if ($name) $name = " name=\'$name\'";
	return "\n<form action=\'$action$ancre\'$name method=\'post\'$onchange>"
	.form_hidden($action);
}

',

'afficher_articles' => '($titre, $requete, $formater=\'\') {
	vieilles_log(\'afficher_articles()\');

	afficher_objets(\'article\',$titre,$requete,$formater);
}
',

'afficher_auteurs' => '($titre_table, $requete) {
	vieilles_log(\'afficher_auteurs()\');

	afficher_objets(\'auteur\',$titre_table,$requete,\'\');
}
',

'afficher_sites' => '($titre_table, $requete){
	vieilles_log(\'afficher_sites()\');

	afficher_objets(\'site\',$titre_table,$requete,\'\');	
}
',

'afficher_syndic_articles' => '($titre_table, $requete, $id = 0) {
	vieilles_log(\'afficher_syndic_articles()\');

	afficher_objets(\'syndic_article\',$titre_table,$requete,$id);	
}

// Retourne les droits de publication d\'un auteur selon le codage suivant:
// - le tableau de ses rubriques si c\'est un admin restreint
// - 0 si c\'est un admin de plein droit
// - la chaine indiquant son statut s\'il n\'est pas admin

',

'auth_rubrique' => '($id_auteur, $statut)
{


	if ($statut != \'0minirezo\') return $statut;

	$result = sql_select("id_rubrique", "spip_auteurs_rubriques", "id_auteur=$id_auteur AND id_rubrique!=\'0\'");
	if (!sql_count($result)) {
		return 0;
	}
	$rubriques = array();
	for (;;) {
		$r = array();
		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row[\'id_rubrique\'];
			$r[]= $rubriques[$id_rubrique] = $id_rubrique;
		}
		if (!$r) return $rubriques;
		$r = join(\',\', $r);

		$result = sql_select("id_rubrique", "spip_rubriques", "id_parent IN ($r) AND id_rubrique NOT IN ($r)");
	}
}

',

'bouton_block_invisible' => '($nom_block, $icone=\'\') {
	vieilles_log(\'bouton_block_invisible()\');
	include_spip(\'inc/layer\');
	return bouton_block_depliable(_T("info_sans_titre"),false,$nom_block);
}

',

'bouton_block_visible' => '($nom_block){
	vieilles_log(\'bouton_block_visible()\');
	include_spip(\'inc/layer\');
	return bouton_block_depliable(_T("info_sans_titre"),true,$nom_block);
}

',

'debut_block_visible' => '($id=""){
	vieilles_log(\'debut_block_visible()\');
	include_spip(\'inc/layer\');
	return debut_block_depliable(true,$id);
}

',

'debut_block_invisible' => '($id=""){
	vieilles_log(\'debut_block_invisible()\');
	include_spip(\'inc/layer\');
	return debut_block_depliable(false,$id);
}

',

'init_config' => '(){
	vieilles_log(\'init_config()\');
	
	include_spip(\'inc/config\');
	inc_config_dist();
}

',

/*
'extraire_tags' => '($texte) {
	vieilles_log(\'extraire_tags()\');
	
	return extraire_balises($texte, \'a\');
}
',

// synonyme de extraire_balise
'extraire_tag' => '($texte, $tag=\'a\') {
	vieilles_log(\'extraire_tag()\');
	
	return extraire_balise($texte, $tag);
}

',
*/

//
// une autre boite
//

'bandeau_titre_boite2' => '($titre, $logo="", $fond="toile_blanche", $texte="ligne_noire") {
	vieilles_log(\'bandeau_titre_boite2()\');
	global $spip_lang_left, $spip_display, $browser_name;
	
	if (strlen($logo) > 0 AND $spip_display != 1 AND $spip_display != 4) {
		$ie_style = ($browser_name == "MSIE") ? "height:1%" : \'\';

		return "\n<div style=\'position: relative;$ie_style\'>"
		. "\n<div style=\'position: absolute; top: -12px; $spip_lang_left: 3px;\'>"
		. http_img_pack($logo, "", "")
		. "</div>"
		. "\n<div style=\'padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;\' class=\'verdana2 $fond $texte\'>$titre</div>"
		. "</div>";
	} else {
		return "<h3 style=\'padding: 3px; border-bottom: 1px solid #444444; margin: 0px;\' class=\'verdana2 $fond $texte\'>$titre</h3>";
	}
}

',

'spip_free_result' => '($r) {
	vieilles_log(\'spip_free_result()\');

	sql_free($r);
}

',

'creer_objet_multi' => '($r, $l) {
	vieilles_log(\'creer_objet_multi()\');

 sql_multi($r, $l);
}

',

'envoyer_mail' => '($email, $sujet, $texte, $from = "", $headers = "") {
	vieilles_log(\'envoyer_mail()\');
	define(\'_FUNCTION_ENVOYER_MAIL\', charger_fonction(\'envoyer_mail\', \'inc\'));
	$args = func_get_args();
	if (_FUNCTION_ENVOYER_MAIL){
		return call_user_func_array(_FUNCTION_ENVOYER_MAIL, $args);
	}
}


',

'spip_num_rows' => '($r) {
	vieilles_log(\'spip_num_rows()\');
	
	return sql_count($r);
}


  ',

'spip_abstract_serveur' => '($ins_sql, $serveur) {
	vieilles_log(\'spip_abstract_serveur()\');
   	 return sql_serveur($ins_sql, $serveur);
  }
  
  ',

'spip_abstract_select' => '(
    $select = array(), $from = array(), $where = array(),
  	$groupby = \'\', $orderby = array(), $limit = \'\',
  	$sousrequete = \'\', $having = array(),
  	$table = \'\', $id = \'\', $serveur=\'\') {
    return sql_select(
      $select, $from, $where,
      $groupby, $orderby, $limit,
      $sousrequete, $having,
      $table, $id, $serveur);
  }
  
  ',

'spip_abstract_fetch' => '($res, $serveur=\'\') {
	vieilles_log(\'spip_abstract_fetch()\');
    return sql_fetch($res, $serveur);
  }
  
  ',

'spip_abstract_count' => '($res, $serveur=\'\') {
	vieilles_log(\'spip_abstract_count()\');
    return sql_count($res, $serveur);
  }
  
  ',

'spip_abstract_free' => '($res, $serveur=\'\') {
	vieilles_log(\'spip_abstract_free()\');
    return sql_free($res, $serveur);
  }

  ',

'spip_abstract_insert' => '($table, $noms, $valeurs, $serveur=\'\') {
	vieilles_log(\'spip_abstract_insert()\');
    return sql_insert($table, $noms, $valeurs, $serveur);
  }

  ',

'spip_abstract_update' => '($table, $exp, $where, $serveur=\'\') {
	vieilles_log(\'spip_abstract_update()\');
    return sql_update($table, $exp, $where, $serveur);
  }

  ',

'spip_abstract_delete' => '($table, $where, $serveur=\'\') {
	vieilles_log(\'spip_abstract_delete()\');
    return sql_delete($table, $where, $serveur);
  }

  ',

'spip_abstract_replace' => '($table, $values, $keys, $serveur=\'\'){
	vieilles_log(\'spip_abstract_replace()\');
    return sql_replace($table, $values, $keys, $serveur);
  }

  ',

'spip_abstract_showtable' => '($table, $serveur=\'\', $table_spip = false) {
	vieilles_log(\'spip_abstract_showtable()\');
    return sql_showtable($table, $table_spip, $serveur);
  }

  ',

'spip_abstract_create' => '($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur=\'\') {
	vieilles_log(\'spip_abstract_create()\');
    return sql_create($nom, $champs, $cles, $autoinc, $temporary, $serveur);
  }

  ',

'spip_create_table' => '($nom, $champs, $cles, $autoinc=false, $temporary=false) {
	vieilles_log(\'spip_create_table()\');
    return sql_create($nom, $champs, $cles, $autoinc, $temporary);
  }

  ',

'spip_abstract_multi' => '($sel, $lang, $serveur=\'\') {
	vieilles_log(\'spip_abstract_multi()\');
    return sql_multi($sel, $lang, $serveur);
  }

  ',

'spip_abstract_fetsel' => '(
  	$select = array(), $from = array(), $where = array(),
  	$groupby = \'\', $orderby = array(), $limit = \'\',
  	$sousrequete = \'\', $having = array(),
  	$table = \'\', $id = \'\', $serveur=\'\') {
    return sql_fetsel(
  	$select, $from, $where,
  	$groupby, $orderby, $limit,
  	$sousrequete, $having,
  	$table, $id, $serveur);
  }
  
  ',

'spip_abstract_countsel($from = array(), $where = array' => '(),
  	$groupby = \'\', $limit = \'\', $sousrequete = \'\', $having = array(),
  	$serveur=\'\') {
    return sql_countsel($from, $where,
    	$groupby, $limit, $sousrequete, $having,
    	$serveur);
  }

  ',

'spip_sql_error' => '($query, $serveur=\'\') {
	vieilles_log(\'spip_sql_error()\');
    return sql_error($query, $serveur);
  }

  ',

'spip_mysql_version' => '($serveur=\'\', $option=true) {
	vieilles_log(\'spip_mysql_version()\');
    return sql_version($query, $serveur);
  }

  ',

'spip_sql_errno' => '($serveur=\'\') {
	vieilles_log(\'spip_sql_errno()\');
    return sql_errno($serveur);
  }
  
  // r9916
  ',

'sql_calendrier_mois' => '($annee,$mois,$jour) {
	vieilles_log(\'sql_calendrier_mois()\');
	return quete_calendrier_mois($annee,$mois,$jour);
  }
  
  ',

'sql_calendrier_semaine' => '($annee,$mois,$jour) {
	vieilles_log(\'sql_calendrier_semaine()\');
	return quete_calendrier_semaine($annee,$mois,$jour);
  }
  
  ',

'sql_calendrier_jour' => '($annee,$mois,$jour) {
	vieilles_log(\'sql_calendrier_jour()\');
	return quete_calendrier_jour($annee,$mois,$jour);
  }
  
  ',

'sql_calendrier_interval' => '($limites) {
	vieilles_log(\'sql_calendrier_interval()\');
	return quete_calendrier_interval($limites);
  }
  
  ',

' sql_calendrier_interval_forums' => '($limites, &$evenements) {
	vieilles_log(\' sql_calendrier_interval_forums()\');
	return quete_calendrier_interval_forums($limites, $evenements);
  }
  
  ',

'sql_calendrier_interval_articles' => '($avant, $apres, &$evenements) {
	vieilles_log(\'sql_calendrier_interval_articles()\');
	return quete_calendrier_interval_articles($avant, $apres, $evenements);
  }
  
  ',

'sql_calendrier_interval_rubriques' => '($avant, $apres, &$evenements) {
	vieilles_log(\'sql_calendrier_interval_rubriques()\');
	return quete_calendrier_interval_rubriques($avant, $apres, $evenements);
  }
  
  ',

'sql_calendrier_interval_breves' => '($avant, $apres, &$evenements) {
	vieilles_log(\'sql_calendrier_interval_breves()\');
	return quete_calendrier_interval_breves($avant, $apres, $evenements);
  }
  
  ',

'sql_calendrier_interval_rv' => '($avant, $apres) {
	vieilles_log(\'sql_calendrier_interval_rv()\');
	return quete_calendrier_interval_rv($avant, $apres);
  }
  
  ',

'sql_calendrier_taches_annonces' => '() {
	vieilles_log(\'sql_calendrier_taches_annonces()\');
	return quete_calendrier_taches_annonces ();
  }
  
  ',

'sql_calendrier_taches_pb' => '() {
	vieilles_log(\'sql_calendrier_taches_pb()\');
	return quete_calendrier_taches_pb ();
  }
  
  ',

'sql_calendrier_taches_rv' => '() {
	vieilles_log(\'sql_calendrier_taches_rv()\');
	return quete_calendrier_taches_rv ();
  }
  
  ',

'sql_calendrier_agenda' => '($annee, $mois) {
	vieilles_log(\'sql_calendrier_agenda()\');
	return quete_calendrier_agenda ($annee, $mois);
  }
  
  //r9918
  ',

'sql_rubrique_fond' => '($contexte) {
	vieilles_log(\'sql_rubrique_fond()\');
	return quete_rubrique_fond($contexte);
  }
  
  ',

'sql_chapo' => '($id_article) {
	vieilles_log(\'sql_chapo()\');
	return quete_chapo($id_article);
  }
  
  ',

'sql_parent' => '($id_rubrique) {
	vieilles_log(\'sql_parent()\');
	return quete_parent($id_rubrique);
  }
  
  ',

'sql_profondeur' => '($id) {
	vieilles_log(\'sql_profondeur()\');
	return quete_profondeur($id);
  }
  
  ',

'sql_rubrique' => '($id_article) {
	vieilles_log(\'sql_rubrique()\');
	return quete_rubrique($id_article);
  }
  
  ',

'sql_petitions' => '($id_article, $table, $id_boucle, $serveur, &$cache) {
	vieilles_log(\'sql_petitions()\');
	return quete_petitions($id_article, $table, $id_boucle, $serveur, $cache);
  }
  
  ',

'sql_accepter_forum' => '($id_article) {
	vieilles_log(\'sql_accepter_forum()\');
	return quete_accepter_forum($id_article);
  }
  
  ',

'trouver_def_table' => '($nom, &$boucle) {
	vieilles_log(\'trouver_def_table()\');
	global $tables_principales, $tables_auxiliaires, $table_des_tables, $tables_des_serveurs_sql; 
 
	$nom_table = $nom; 
	$s = $boucle->sql_serveur; 
	if (!$s) { 
		$s = \'localhost\'; 
		if (in_array($nom, $table_des_tables)) 
			$nom_table = \'spip_\' . $nom; 
		} 
	$desc = $tables_des_serveurs_sql[$s]; 

	if (isset($desc[$nom_table])) 
		return array($nom_table, $desc[$nom_table]); 
	include_spip(\'base/auxiliaires\'); 
	$nom_table = \'spip_\' . $nom; 
	if ($desc = $tables_auxiliaires[$nom_table]) 
		return array($nom_table, $desc); 
	if ($desc = sql_showtable($nom, $boucle->sql_serveur)) 
		if (isset($desc[\'field\'])) { 
			// faudrait aussi prevoir le cas du serveur externe 
			$tables_principales[$nom] = $desc; 
			return array($nom, $desc); 
		} 
	erreur_squelette(_T(\'zbug_table_inconnue\', array(\'table\' => $nom)), 
	$boucle->id_boucle); 
	return false;
  }
',
'meme_rubrique' => '($id_rubrique, $id, $type, $order="date", $limit=NULL, $ajax=false) {
	$meme_rubrique = charger_fonction("meme_rubrique", "inc");
	return $meme_rubrique($id_rubrique, $id, $type, $order, $limit, $ajax);
  }
',
'afficher_liste' => '($largeurs, $table, $styles = \'\') { 
	global $spip_display; 

	if (!$table OR !is_array($table)) return ""; 

	if ($spip_display != 4) { 
			$res = \'\'; 
			foreach ($table as $t) { 
					$res .= afficher_liste_display_neq4($largeurs, $t, $styles); 
			} 
	} else { 
			$res = "\n<ul style=\'text-align: $spip_lang_left; background-color: white;\'>"; 
			foreach ($table as $t) { 
					$res .= afficher_liste_display_eq4($largeurs, $t, $styles); 
			} 
			$res .= "\n</ul>"; 
	} 

	return $res; 
  }
',
'afficher_liste_display_neq4' => '($largeurs, $t, $styles = \'\') {

	global $browser_name;

	$evt = (preg_match(",msie,i", $browser_name) ? " onmouseover=\"changeclass(this,\'tr_liste_over\');\" onmouseout=\"changeclass(this,\'tr_liste\');\"" :\'\');

	reset($largeurs);
	if ($styles) reset($styles);
	$res =\'\';
	while (list(, $texte) = each($t)) {
		$style = $largeur = "";
		list(, $largeur) = each($largeurs);
		if ($styles) list(, $style) = each($styles);
		if (!trim($texte)) $texte .= "&nbsp;";
		$res .= "\n<td" .
			($largeur ? (" style=\'width: $largeur" ."px;\'") : \'\') .
			($style ? " class=\"$style\"" : \'\') .
			">" . lignes_longues($texte) . "\n</td>";
	}

	return "\n<tr class=\'tr_liste\'$evt>$res</tr>"; 
  }
',
'afficher_liste_display_eq4' => '($largeurs, $t, $styles = \'\') {

	reset($largeurs);
	while (list(, $texte) = each($t)) {
		$largeur = "";
		list(, $largeur) = each($largeurs);
		if (!$largeur) $res .= $texte." ";
	}

	return "\n<li>$res</li>\n";
}',

'barre_textarea' =>'($texte, $rows, $cols, $lang=\'\') {
	static $num_textarea = 0;
	include_spip("inc/layer"); // definit browser_barre

	$texte = entites_html($texte);
	return "<textarea name=\'texte\' rows=\'$rows\' class=\'forml\' cols=\'$cols\'>$texte</textarea>";

}',

 'generer_url_article' => '($id, $args="", $ancre="")
	{ vieilles_log(\'generer_url_article\'); return generer_url_entite($id, "article", $args, $ancre);}',

 'generer_url_rubrique' => '($id, $args="", $ancre="") {
	vieilles_log(\'generer_url_rubrique\'); return generer_url_entite($id, "rubrique", $args, $ancre);}',

 'generer_url_breve' => '($id, $args="", $ancre="") {
	vieilles_log(\'generer_url_breve\');  return generer_url_entite($id, "breve", $args, $ancre);}',

 'generer_url_mot' => '($id, $args="", $ancre="") {
	vieilles_log(\'generer_url_mot\');  return generer_url_entite($id, "mot", $args, $ancre);}',

 'generer_url_site' => '($id, $args="", $ancre="") {
	vieilles_log(\'generer_url_site\');  return generer_url_entite($id, "site", $args, $ancre);}',

 'generer_url_auteur' => '($id, $args="", $ancre="") {
	vieilles_log(\'generer_url_auteur\');  return generer_url_entite($id,"auteur", $args, $ancre);}',

 'charger_generer_url' => '($prive=NULL) {
	vieilles_log(\'charger_generer_url\'); generer_url_entite("", "", "", "", !$prive);}',

 'tester_variable' => '($n, $v) {
	if (!isset($GLOBALS[$n])) $GLOBALS[$n] = $v;
	return $GLOBALS[$n];}',
	
	// SPIP < 2.1
 'barre_typo' => '($id,$lang=\'\',$forum=false){
	return \'\';}',

 'afficher_barre' => '(){
	 return \'\';}',

) as $f => $def) {
	if (!function_exists($f)) {
		eval("function $f$def");
	}
}


define('_DIR_DOC', _DIR_IMG);
//constantes spip pour mysql_fetch_array()
define('SPIP_BOTH', MYSQL_BOTH);
define('SPIP_ASSOC', MYSQL_ASSOC);
define('SPIP_NUM', MYSQL_NUM);

// http://doc.spip.org/@article_select
function article_select($id_article, $id_rubrique=0, $lier_trad=0, $id_version=0) {
	$article_select = charger_fonction('article_select','inc');
	return $article_select($id_article,$id_rubrique,$lier_trad,$id_version);
}


?>
