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

//
// Selecteur de rubriques pour l'espace prive
// En entree :
// - l'id_rubrique courante (0 si NEW)
// - le type d'objet a placer (une rubrique peut aller a la racine
//    mais pas dans elle-meme, les articles et sites peuvent aller
//    n'importe ou (defaut), et les breves dans les secteurs.
// $idem : en mode rubrique = la rubrique soi-meme
function inc_chercher_rubrique_dist ($id_rubrique, $type, $restreint, $idem=0) {
	global $_COOKIE;
	global $browser_name, $browser_version;

	// Mode sans Ajax :
	// - soit parce que le cookie ajax n'est pas la
	// - soit parce qu'il y a peu de rubriques
	// - soit parce qu'avec IE ca plante et c'est penible
	if (false /* mettre true pour desactiver ajax */
	OR (($browser_name == "MSIE" AND floor($browser_version) < "7"))
	OR $_COOKIE['spip_accepte_ajax'] < 1
	OR spip_num_rows(
	spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 21")) < 20)
		return selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem);

	else
		return selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem);

}

// compatibilite pour extensions qui utilisaient l'ancien nom
$GLOBALS['selecteur_rubrique'] = 'inc_chercher_rubrique_dist';

function style_menu_rubriques($i) {
	global $browser_name, $browser_version;
	global $couleur_claire, $spip_lang_left;

	if (eregi("mozilla", $browser_name)) {
		$style = "padding-$spip_lang_left: 16px; "
		. "margin-$spip_lang_left: ".(($i-1)*16)."px;";
		$espace = '';
	} else {
		$style = '';
		for ($count = 0; $count <= $i; $count ++)
			$espace .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	}
	switch ($i) {
		case 1:
			$espace= "";
			$style .= "font-weight: bold;";
			break;
		case 2:
			$style .= "color: #202020;";
			break;
		case 3:
			$style .= "color: #404040;";
			break;
		case 4:
			$style .= "color: #606060;";
			break;
		case 5:
			$style .= "color: #808080;";
			break;
		default:
			$style .= "color: #A0A0A0;";
			break;
	}

	if ($i==1) {
		$style .= "background-image: url(" . _DIR_IMG_PACK. "secteur-12.gif);";
		$style .= "background-color: $couleur_claire;";
		$style .= "font-weight: bold;";
	}
	else if ($i==2) {
		$style .= "border-bottom: 1px solid $couleur_claire;";
		$style .= "font-weight: bold;";
	}

	if ($style) $style = " style='$style'";

	return array($style,$espace);
}

function sous_menu_rubriques($id_rubrique, $root, $niv, &$data, &$enfants, $exclus, $restreint, $type) {
	global $browser_name, $browser_version;
	static $decalage_secteur;

	// Si on a demande l'exclusion ne pas descendre dans la rubrique courante
	if ($exclus > 0
	AND $root == $exclus) return '';

	// en fonction du niveau faire un affichage plus ou moins kikoo

	// selected ?
	$selected = ($root == $id_rubrique) ? ' selected' : '';

	// class='selec_rub' sauf pour contourner le bug MSIE / MacOs 9.0
	if (!($browser_name == "MSIE" AND floor($browser_version) == "5"))
		$class = " class='selec_rub'";

	// le style en fonction de la profondeur
	list($style,$espace) = style_menu_rubriques($niv);

	// creer l'<option> pour la rubrique $root
	if (isset($data[$root])) # pas de racine sauf pour les rubriques
	{
		$r .= "<option$selected value='$root'$class$style>$espace"
		.$data[$root]
		.'</option>'."\n";
	}
	
	// et le sous-menu pour ses enfants
	$sous = '';
	if ($enfants[$root])
		foreach ($enfants[$root] as $sousrub)
			$sous .= sous_menu_rubriques($id_rubrique, $sousrub,
				$niv+1, $data, $enfants, $exclus, $restreint, $type);

	// si l'objet a deplacer est publie, verifier qu'on a acces aux rubriques
	if ($restreint AND !acces_rubrique($root))
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
function selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem=0) {
	$data = array();
	if ($type == 'rubrique')
		$data[0] = _T('info_racine_site');
	if ($type == 'auteur')
		$data[0] = '&nbsp;'; # premier choix = neant (rubriques restreintes)

	//
	// creer une structure contenant toute l'arborescence
	//

	$q = spip_query("SELECT id_rubrique, id_parent, titre, statut, lang, langue_choisie FROM spip_rubriques " . ($type == 'breve' ?  'WHERE id_parent=0 ' : '') . "ORDER BY 0+titre,titre");
	while ($r = spip_fetch_array($q)) {
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


	$opt = sous_menu_rubriques($id_rubrique,0, 0,$data,$enfants,$idem, $restreint, $type);
	$att = " name='id_parent'\nstyle='font-size: 90%; width: 99%; font-face: verdana,arial,helvetica,sans-serif; max-height: 24px;'";

	if (preg_match(',^<option[^<>]*value=.(\d*).[^<>]*>([^<]*)</option>$,',$opt,$r))
	  $r = "<input$att type='hidden' value='" . $r[1] . "' />" . $r[2] ;
	else 
	  $r = "<select$att size='1'>\n$opt</select>\n";

	# message pour neuneus (a supprimer ?)
#	if ($type != 'auteur' AND $type != 'breve')
#		$r .= "\n<br />"._T('texte_rappel_selection_champs');

	return $r;
}

//
// Le selecteur de rubriques en mode Ajax
//

function selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem=0) {

	## $restreint indique qu'il faut limiter les rubriques affichees
	## aux rubriques editables par l'admin restreint... or, ca ne marche pas.
	## Pour la version HTML c'est bon (cf. ci-dessus), mais pour l'ajax...
	## je laisse ca aux specialistes de l'ajax & des admins restreints
	## note : toutefois c'est juste un pb d'interface, car question securite
	## la verification est faite a l'arrivee des donnees (Fil)

	if ($idem)
		$exclus = "&exclus=$idem&rac=oui";

	if ($id_rubrique)
		list($titre_parent) = spip_fetch_array(spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	else if ($type == 'auteur')
		$titre_parent = '&nbsp;';
	else
		$titre_parent = _T('info_racine_site');

	return 	"<table width='100%'><tr width='100%'><td width='45'>
	<a	href=\"#\"
		onClick=\"if(findObj('selection_rubrique').style.display=='none')
			{charger_id_url_si_vide('" .
	  generer_url_ecrire('selectionner',"var_ajax=1&id=$id_rubrique$exclus", true) ."',
				'selection_rubrique');}
			else {findObj('selection_rubrique').style.display='none';}\"
	><img src='". _DIR_IMG_PACK . "loupe.png' style='vertical-align: middle;'
	/></a><img src='". _DIR_IMG_PACK . "searching.gif'
		id='img_selection_rubrique' style='visibility: hidden;'>
	</td><td>
	<input type='text' id='titreparent' name='titreparent'
		disabled='disabled' class='forml' value=\""
	  . str_replace('&amp;', '&', entites_html(textebrut(typo($titre_parent))))
	  ."\" />
	<input type='hidden' id='id_parent' name='id_parent' value='$id_rubrique' />
	</td></tr></table>
	<div id='selection_rubrique' style='display: none;'></div>";
}
?>
