<?php


////////////////////////////////////////////////////////////////////////////////////
// Pour utiliser les champs "supplementaires", il faut installer dans le fichier
// ecrire/mes_options.php3 une fonction definissant les champs en question,
// pour chaque type d'objet (article, rubrique, breve, auteur ou mot) que l'on
// veut ainsi etendre

/*	Arguments :
 *		$type = "article", "rubrique", "auteur" ...
 *		$id_objet = id de l'article, de la rubrique ...
 *		$ensemble = id de la rubrique de l'article, le parent de la rubrique,
 *		le statut de l'auteur, type du mot...
 *	Retour : un tableau au format (champ => description, champ => desc ...)
 * 	ou la description est au format "type|filtre[|pretty name]"
 *		type = ligne, bloc, texte
 *		filtre = brut, typo, propre (a appliquer dans l'espace prive)
 *		prettyname (optionnel) = nom du champ tel qu'on l'affiche dans l'esp. prive
 */

/*
function champs_supplement($type, $id_objet, $ensemble) {
	if ($type == "auteur") {
		return Array (
			"sexe" => "ligne|brut",
			"age" => "ligne|propre|&Acirc;ge du capitaine"
			"biblio" => "bloc|propre|Bibliographie",
		);
	}

	if ($type == "article" && $ensemble == 7) {
		return array(
			"isbn" => "ligne|typo",
		);
	}

	// par defaut, aucun champ supplementaire.
	return array();
}
*/

////////////////////////////////////////////////////////////////////////////////////

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_SUPPLEMENT")) return;
define("_ECRIRE_INC_SUPPLEMENT", "1");

// a partir de la liste des champs, generer la liste des input
function supplement_saisie($supplement, $champs) {
	if (! sizeof($champs)) return;

	debut_cadre_enfonce();
	while(list($champ,$desc)=each($champs)) {
		list($type, $filtre, $prettyname)=explode("|", $desc);

		if (!$prettyname)
			$prettyname = $champ;
		echo "<div><b>$prettyname&nbsp;:</b><br />";

		switch($type) {
			case "bloc":
			case "block":
				echo "<TEXTAREA NAME='suppl_$champ' CLASS='forml' style='font-size:9px;' ROWS='5' COLS='40'>".entites_html($supplement[$champ])."</TEXTAREA>\n";
				break;
			case "ligne":
			case "line":
			default:
				echo "<INPUT TYPE='text' NAME='suppl_$champ' CLASS='forml' style='font-size:9px;'\n";
				echo " VALUE=\"".entites_html($supplement[$champ])."\" SIZE='40'>\n";
				break;
		}

		echo "</div>\n";
	}
	fin_cadre_enfonce();
}

// recupere les valeurs postees pour reconstituer le supplement
function supplement_recup_saisie($champs) {
	$supplement=array();
	while(list($champ,)=each($champs)) {
		$supplement[$champ]=$GLOBALS["suppl_$champ"];
	}
	return $supplement;
}

// a partir de la liste des champs, generer l'affichage
function supplement_affichage($supplement, $champs) {
	while (list($champ,$desc) = each($champs)) {
		list($type, $filtre, $prettyname) = explode("|", $desc);
		$contenu = $supplement[$champ];
		switch($filtre) {
			case "typo":
				$contenu = typo($contenu);
				break;
			case "propre":
				$contenu = propre($contenu);
				break;
			case "brut":
			default:
				break;
		}
		if (!$prettyname)
			$prettyname = $champ;
		if ($contenu)
			$affiche .= "<div><b>$prettyname&nbsp;:</b> ".interdire_scripts($contenu)."<br /></div>\n";
	}

	if ($affiche) {
		debut_cadre_enfonce();
		echo $affiche;
		fin_cadre_enfonce();
	}
}

?>
