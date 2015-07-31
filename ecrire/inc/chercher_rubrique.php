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

define('_SPIP_SELECT_RUBRIQUES', 20); /* mettre 100000 pour desactiver ajax */

//
// Selecteur de rubriques pour l'espace prive
// En entree :
// - l'id_rubrique courante (0 si NEW)
// - le type d'objet a placer (une rubrique peut aller a la racine
//    mais pas dans elle-meme, les articles et sites peuvent aller
//    n'importe ou (defaut), et les breves dans les secteurs.
// $idem : en mode rubrique = la rubrique soi-meme
// http://doc.spip.org/@inc_chercher_rubrique_dist
function inc_chercher_rubrique_dist ($id_rubrique, $type, $restreint, $idem=0) {
	// Mode sans Ajax :
	// - soit parce que le cookie ajax n'est pas la
	// - soit parce qu'il y a peu de rubriques
	if (_SPIP_AJAX < 1
	OR $type == 'breve'
	OR spip_num_rows(
	spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT ".intval(_SPIP_SELECT_RUBRIQUES+1))) < _SPIP_SELECT_RUBRIQUES)
		return selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem);

	else
		return selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem);

}

// compatibilite pour extensions qui utilisaient l'ancien nom
$GLOBALS['selecteur_rubrique'] = 'inc_chercher_rubrique_dist';

// http://doc.spip.org/@style_menu_rubriques
function style_menu_rubriques($i) {
	global $browser_name, $browser_version, $spip_lang_left;

	$espace = '';
	if (preg_match(",mozilla,i", $browser_name)) {
		$style = "padding-$spip_lang_left: 16px; "
		. "margin-$spip_lang_left: ".(($i-1)*16)."px;";
	} else {
		$style = '';
		for ($count = 0; $count <= $i; $count ++)
			$espace .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	}
	if ($i ==1)
		$espace= "";
	$class = "niveau_$i";
	return array($class,$style,$espace);
}

// http://doc.spip.org/@sous_menu_rubriques
function sous_menu_rubriques($id_rubrique, $root, $niv, &$data, &$enfants, $exclus, $restreint, $type) {
	global $browser_name, $browser_version;
	static $decalage_secteur;

	// Si on a demande l'exclusion ne pas descendre dans la rubrique courante
	if ($exclus > 0
	AND $root == $exclus) return '';

	// en fonction du niveau faire un affichage plus ou moins kikoo

	// selected ?
	$selected = ($root == $id_rubrique) ? ' selected="selected"' : '';

	// le style en fonction de la profondeur
	list($class, $style, $espace) = style_menu_rubriques($niv);

	// class='selec_rub' sauf pour contourner le bug MSIE / MacOs 9.0
	if (!($browser_name == "MSIE" AND floor($browser_version) == "5"))
		$class .= " selec_rub";

	// creer l'<option> pour la rubrique $root

	if (isset($data[$root])) # pas de racine sauf pour les rubriques
	{
		$r = "<option$selected value='$root' class='$class' style='$style'>$espace"
		.$data[$root]
		.'</option>'."\n";
	} else 	$r = '';
	
	// et le sous-menu pour ses enfants
	$sous = '';
	if (isset($enfants[$root]))
		foreach ($enfants[$root] as $sousrub)
			$sous .= sous_menu_rubriques($id_rubrique, $sousrub,
				$niv+1, $data, $enfants, $exclus, $restreint, $type);

	// si l'objet a deplacer est publie, verifier qu'on a acces aux rubriques
	if ($restreint AND !autoriser('publierdans','rubrique',$root))
		return $sous;

	// sauter un cran pour les secteurs (sauf premier)
	if ($niv == 1
	AND $decalage_secteur++
	AND $type != 'breve')
		$r = "<option value='$root'></option>\n".$r;

	// et voila le travail
	return $r.$sous;
}

// Le selecteur de rubriques en mode classique (menu)
// http://doc.spip.org/@selecteur_rubrique_html
function selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem=0) {
	$data = array();
	if ($type == 'rubrique')
		$data[0] = _T('info_racine_site');
	if ($type == 'auteur')
		$data[0] = '&nbsp;'; # premier choix = neant (rubriques restreintes)

	//
	// creer une structure contenant toute l'arborescence
	//

	include_spip('base/abstract_sql');
	$q = sql_select("id_rubrique, id_parent, titre, statut, lang, langue_choisie", "spip_rubriques", ($type == 'breve' ?  ' id_parent=0 ' : ''), '', "0+titre,titre");
	while ($r = sql_fetch($q)) {
		if (autoriser('voir','rubrique',$r['id_rubrique'])){
			// titre largeur maxi a 50
			$titre = couper(supprimer_tags(typo(extraire_multi($r['titre']
			)))." ", 50);
			if ($GLOBALS['meta']['multi_rubriques'] == 'oui'
			AND ($r['langue_choisie'] == "oui" OR $r['id_parent'] == 0))
				$titre .= ' ['.traduire_nom_langue($r['lang']).']';
			$data[$r['id_rubrique']] = $titre;
			$enfants[$r['id_parent']][] = $r['id_rubrique'];
			if ($id_rubrique == $r['id_rubrique']) $id_parent = $r['id_parent'];
		}
	}


	$opt = sous_menu_rubriques($id_rubrique,0, 0,$data,$enfants,$idem, $restreint, $type);
	$att = " id='id_parent' name='id_parent'\nstyle='font-size: 90%; width: 99%; max-height: 24px;' class='verdana1'";

	if (preg_match(',^<option[^<>]*value=.(\d*).[^<>]*>([^<]*)</option>$,',$opt,$r))
	  $r = "<input$att type='hidden' value='" . $r[1] . "' />" . $r[2] ;
	else 
	  $r = "<select$att size='1'>\n$opt</select>\n";

	# message pour neuneus (a supprimer ?)
#	if ($type != 'auteur' AND $type != 'breve')
#		$r .= "\n<br />"._T('texte_rappel_selection_champs');

	return $r;
}

// http://doc.spip.org/@selecteur_rubrique_ajax
function selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem=0) {

       ## $restreint indique qu'il faut limiter les rubriques affichees
       ## aux rubriques editables par l'admin restreint... or, ca ne marche pas.
       ## Pour la version HTML c'est bon (cf. ci-dessus), mais pour l'ajax...
       ## je laisse ca aux specialistes de l'ajax & des admins restreints
       ## note : toutefois c'est juste un pb d'interface, car question securite
       ## la verification est faite a l'arrivee des donnees (Fil)

	if ($id_rubrique) {
		$titre = sql_fetch(spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$titre = $titre['titre'];
	} else if ($type == 'auteur')
		$titre = '&nbsp;';
	else
		$titre = _T('info_racine_site');

	$titre = str_replace('&amp;', '&', entites_html(textebrut(typo($titre))));
	$init = " disabled='disabled' type='text' value=\"" . $titre . '" style=\'width:300px;\'';

	$url = generer_url_ecrire('selectionner',"id=$id_rubrique&type=$type" . (!$idem ? '' : ("&exclus=$idem&racine=" . ($restreint ? 'non' : 'oui')))
	. (isset($GLOBALS['var_profile']) ? '&var_profile=1' : ''));

	return construire_selecteur($url, '', 'selection_rubrique', 'id_parent', $init, $id_rubrique);
}

// construit un bloc comportant une icone clicable avec image animee a cote
// pour charger en Ajax du code a mettre sous cette icone.
// Attention: changer le onclick si on change le code Html.
// (la fonction JS charger_node ignore l'attribut id qui ne sert en fait pas;
// getElement en mode Ajax est trop couteux).

// http://doc.spip.org/@construire_selecteur
function construire_selecteur($url, $js, $idom, $name, $init='', $id=0)
{
	$icone = ($idom == 'selection_auteur') ? 'message.gif' : 'loupe.png';
	return 	"<table width='100%'><tr><td style='width: 45px'><a onclick=\""
	.  $js
	. "return charger_node_url_si_vide('"
	. $url
	. "', this.parentNode.parentNode.parentNode.parentNode.nextSibling, this.nextSibling,'',event)\"><img src='"
	. _DIR_IMG_PACK
	. $icone
	. "' style='vertical-align: middle;' alt=' ' /></a><img src='"
	. _DIR_IMG_PACK
	. "searching.gif' id='img_"
	.  $idom
	. "' style='visibility: hidden;' alt='*' /></td><td>"
	. "<input id='titreparent' name='titreparent'"
	. $init
	. " />" 
	. "<input type='hidden' id='$name' name='$name' value='"
	. $id
	. "' /></td></tr></table><div id='"
	. $idom
	. "' style='display: none;'></div>";
}
?>
