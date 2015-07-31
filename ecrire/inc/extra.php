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



////////////////////////////////////////////////////////////////////////////////////
// Pour utiliser les champs "extra", il faut installer dans le fichier
// ecrire/mes_options un tableau definissant les champs pour chaque
// type d'objet que l'on veut Žtendre (article, rubrique, breve, auteur,
// site ou mot). Pour acceder aux valeurs des champs extra dans les
// squelettes du site public, utiliser la notation :
//                     [(#EXTRA|extra{nom_du_champ})]
// Exemples :

/*

//
// Definition de tous les extras possibles
//

$GLOBALS['champs_extra'] = Array (
	'auteurs' => Array (
			"alim" => "radio|brut|Pr&eacute;f&eacute;rences alimentaires|Veggie,Viande",
			"habitation" => "liste|brut|Lieu|Kuala Lumpur,Cape Town,Uppsala",
			"ml" => "case|propre|Je souhaite m'abonner &agrave; la mailinglist",
			"age" => "ligne|propre|&Acirc;ge du capitaine",
			"biblio" => "bloc|propre|Bibliographie"
		),

	'articles' => Array (
			"isbn" => "ligne|typo|ISBN",
			 "options" => "multiple|brut|Options de cet article|1,2,3,plus"

			 
		)
	);

// Note : pour les listes et les radios on peut preciser les valeurs des labels 
//  Exemples
//  "habitation" => "liste|brut|Lieu|San Diego,Suresnes|diego,suresnes",


*/


/*

// On peut optionnellement vouloir restreindre la portee des extras :
// - pour les articles/rubriques/breves en fonction du secteur ;
// - pour les auteurs en fonction du statut
// - pour les mots-cles en fonction du groupe de mots
// Exemples :

$GLOBALS['champs_extra_proposes'] = Array (
	'auteurs' => Array (
		// tous : par defaut
		'tous' =>  'age|alim|ml',
		// les admins (statut='0minirezo') ont plus de champs que les auteurs 
		'0minirezo' => 'age|alim|ml|biblio|habitation'
		),

	'articles' => Array (
		// tous : par defaut aucun champs extra sur les articles
		'tous' => '',
		// seul le champs extra "isbn" est proposé dans le secteur 1)
		'1' => 'isbn',
		// Dans le secteur 2 le champs "options" est proposé)
		'2' => 'options'
		)
	);


*/

////////////////////////////////////////////////////////////////////////////////////

//
if (!defined("_ECRIRE_INC_VERSION")) return;

// a partir de la liste des champs, generer la liste des input
function extra_saisie($extra, $type, $ensemble='', $aff=true) {
	if ($affiche = extra_form($extra, $type, $ensemble)) {
	  if ($aff) {
		debut_cadre_enfonce();
		echo $affiche;
		fin_cadre_enfonce();
	  } else {
	    return debut_cadre_enfonce('',true) . $affiche . fin_cadre_enfonce(true);
	  }
	}
}

function extra_form($extra, $type, $ensemble='') {
	$extra = unserialize($extra);

	// quels sont les extras de ce type d'objet
	if (!$champs = $GLOBALS['champs_extra'][$type])
		$champs = Array();

	// prendre en compte, eventuellement, les champs presents dans la base
	// mais oublies dans mes_options.
	if (is_array($extra))
		while (list($key,) = each($extra))
			if (!$champs[$key])
				$champs[$key] = "masque||($key?)";

	// quels sont les extras proposes...
	// ... si l'ensemble est connu
	if ($ensemble && isset($GLOBALS['champs_extra_proposes'][$type][$ensemble]))
		$champs_proposes = explode('|', $GLOBALS['champs_extra_proposes'][$type][$ensemble]);
	// ... sinon, les champs proposes par defaut
	else if (isset($GLOBALS['champs_extra_proposes'][$type]['tous'])) {
		$champs_proposes = explode('|', $GLOBALS['champs_extra_proposes'][$type]['tous']);
	}

	// sinon tous les champs extra du type
	else {
		$champs_proposes =  Array();
		reset($champs);
		while (list($ch, ) = each($champs)) $champs_proposes[] = $ch;
	}

	// bug explode
	if($champs_proposes == explode('|', '')) $champs_proposes = Array();

	// maintenant, on affiche les formulaires pour les champs renseignes dans $extra
	// et pour les champs proposes
	reset($champs_proposes);
	while (list(, $champ) = each($champs_proposes)) {
		$desc = $champs[$champ];
		list($form, $filtre, $prettyname, $choix, $valeurs) = explode("|", $desc);

		if (!$prettyname) $prettyname = ucfirst($champ);
		$affiche .= "<b>$prettyname&nbsp;:</b><br />";

		switch($form) {

			case "case":
			case "checkbox":
				$affiche = ereg_replace("<br />$", "&nbsp;", $affiche);
				$affiche .= "<INPUT TYPE='checkbox' NAME='suppl_$champ'";
				if ($extra[$champ] == 'true')
					$affiche .= " CHECKED ";
				break;

			case "list":
			case "liste":
			case "select":
				$choix = explode(",",$choix);
				if (!is_array($choix)) {
					$affiche .= "Pas de choix d&eacute;finis.\n";
					break;
				}

				// prendre en compte les valeurs des champs
				// si elles sont renseignees
				$valeurs = explode(",",$valeurs);
				if($valeurs == explode(",",""))
					$valeurs = $choix ;

				$affiche .= "<SELECT NAME='suppl_$champ' ";
				$affiche .= "CLASS='forml'>\n";
				$i = 0 ;
				while (list(, $choix_) = each($choix)) {
					$val = $valeurs[$i] ;
					$affiche .= "<OPTION VALUE=\"$val\"";
					if ($val == entites_html($extra[$champ]))
						$affiche .= " SELECTED";
					$affiche .= ">$choix_</OPTION>\n";
					$i++;
				}
				$affiche .= "</SELECT>";
				break;

			case "radio":
				$choix = explode(",",$choix);
				if (!is_array($choix)) {
					$affiche .= "Pas de choix d&eacute;finis.\n";
					break;
				}
				$valeurs = explode(",",$valeurs);
				if($valeurs == explode(",",""))
					$valeurs = $choix ;

				$i=0;
				while (list(, $choix_) = each($choix)) {
					$affiche .= "<INPUT TYPE='radio' NAME='suppl_$champ' ";
					$val = $valeurs[$i] ;
					if (entites_html($extra["$champ"])== $val)
						$affiche .= " CHECKED";

					// premiere valeur par defaut
					if (!$extra["$champ"] AND $i == 0)
						$affiche .= " CHECKED";

					$affiche .= " VALUE='$val'>$choix_</INPUT>\n";
					$i++;
				}
				break;

			// A refaire car on a pas besoin de renvoyer comme pour checkbox
			// les cases non cochees
			case "multiple":
				$choix = explode(",",$choix);
				if (!is_array($choix)) {
					$affiche .= "Pas de choix d&eacute;finis.\n";
					break; }
				for ($i=0; $i < count($choix); $i++) {
					$affiche .= "<INPUT TYPE='checkbox' NAME='suppl_$champ$i'";
					if (entites_html($extra["$champ"][$i])=="on")
						$affiche .= " CHECKED";
					$affiche .= ">\n";
					$affiche .= $choix[$i];
					$affiche .= "</INPUT>\n";
				}
				break;

			case "bloc":
			case "block":
				$affiche .= "<TEXTAREA NAME='suppl_$champ' CLASS='forml' ROWS='5' COLS='40'>".entites_html($extra[$champ])."</TEXTAREA>\n";
				break;

			case "masque":
				$affiche .= "<font color='#555555'>".interdire_scripts($extra[$champ])."</font>\n";
				break;

			case "ligne":
			case "line":
			default:
				$affiche .= "<INPUT TYPE='text' NAME='suppl_$champ' CLASS='forml'\n";
				$affiche .= " VALUE=\"".entites_html($extra[$champ])."\" SIZE='40'>\n";
				break;
		}

		$affiche .= "<p>\n";
	}

	return $affiche;
}

// recupere les valeurs postees pour reconstituer l'extra
function extra_recup_saisie($type) {
	$champs = $GLOBALS['champs_extra'][$type];
	if (is_array($champs)) {
		$extra = Array();
		while(list($champ,)=each($champs)) {
			list($style, $filtre, , $choix,) = explode("|", $GLOBALS['champs_extra'][$type][$champ]);
			list(, $filtre) = explode(",", $filtre);
			switch ($style) {
			case "multiple":
				$choix =  explode(",", $choix);
				$extra["$champ"] = array();
				for ($i=0; $i < count($choix); $i++) {
					if ($filtre && function_exists($filtre))
						 $extra["$champ"][$i] =
						 	$filtre($GLOBALS["suppl_$champ$i"]);
					else
						$extra["$champ"][$i] = $GLOBALS["suppl_$champ$i"];
				}
				break;

			case 'case':
			case 'checkbox':
				if ($GLOBALS["suppl_$champ"] == 'on')
					$GLOBALS["suppl_$champ"] = 'true';
				else
					$GLOBALS["suppl_$champ"] = 'false';

			default:
				if ($filtre && function_exists($filtre))
				$extra["$champ"]=$filtre($GLOBALS["suppl_$champ"]);
				else $extra["$champ"]=$GLOBALS["suppl_$champ"];
				break;
			}
		}
		return serialize($extra);
	} else
		return '';
}

// Retourne la liste des filtres a appliquer pour un champ extra particulier
function extra_filtres($type, $nom_champ) {
	$champ = $GLOBALS['champs_extra'][$type][$nom_champ];
	if (!$champ) return array();
	list(, $filtre, ) = explode("|", $champ);
	list($filtre, ) = explode(",", $filtre);
	if ($filtre && $filtre != 'brut' && function_exists($filtre))
		return array($filtre);
	return array();
}

// Retourne la liste des filtres a appliquer a la recuperation
// d'un champ extra particulier
function extra_filtres_recup($type, $nom_champ) {
	$champ = $GLOBALS['champs_extra'][$type][$nom_champ];
	if (!$champ) return array();
	list(, $filtre, ) = explode("|", $champ);
	list(,$filtre) = explode(",", $filtre);
	if ($filtre && $filtre != 'brut' && function_exists($filtre))
		return array($filtre);
	return array();
}

function extra_champ_valide($type, $nom_champ) {
	return isset($GLOBALS['champs_extra'][$type][$nom_champ]);
}

// a partir de la liste des champs, generer l'affichage
function extra_affichage($extra, $type) {
	$extra = unserialize ($extra);
	if (!is_array($extra)) return;
	$champs = $GLOBALS['champs_extra'][$type];

	while (list($nom,$contenu) = each($extra)) {
		list ($style, $filtre, $prettyname, $choix, $valeurs) =
			explode("|", $champs[$nom]);
		list($filtre, ) = explode(",", $filtre);
		switch ($style) {
			case "checkbox":
			case "case":
				if ($contenu=="true") $contenu = _T('item_oui');
				elseif ($contenu=="false") $contenu = _T('item_non');
				break;

			case "multiple":
				$contenu_ = "";
				$choix = explode (",", $choix);
				if (is_array($contenu) AND is_array($choix)
				AND count($choix)==count($contenu))
					for ($i=0; $i < count($contenu); $i++)
						if ($contenu[$i] == "on")
							$contenu_ .= "$choix[$i], ";
						else if ($contenu[$i] <> '')
							$contenu_ = "Choix incoh&eacute;rents, "
							."v&eacute;rifiez la configuration... ";
				$contenu = ereg_replace(", $", "", $contenu_);
				break;
		}
		if ($filtre != 'brut' AND function_exists($filtre))
			$contenu = $filtre($contenu);
		if (!$prettyname)
			$prettyname = ucfirst($nom);
		if ($contenu)
			$affiche .= "<div><b>$prettyname&nbsp;:</b> "
			.interdire_scripts($contenu)."<br /></div>\n";
	}

	if ($affiche) {
		debut_cadre_enfonce();
		echo $affiche;
		fin_cadre_enfonce();
	}
}

?>
