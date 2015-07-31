<?php


////////////////////////////////////////////////////////////////////////////////////
// Pour utiliser les champs "extra", il faut installer dans le fichier
// ecrire/mes_options.php3 une fonction definissant les champs en question,
// pour chaque type d'objet (article, rubrique, breve, auteur ou mot) que
// l'on veut ainsi etendre ; utiliser dans l'espace public avec
// [(#EXTRA{nom_du_champ})]

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
function champs_extra($type, $id_objet, $ensemble) {
	if ($type == "auteur") {
		return Array (
			"sexe" => "ligne|brut",
			"age" => "ligne|propre|&Acirc;ge du capitaine",
			"biblio" => "bloc|propre|Bibliographie"
		);
	}

	if ($type == "article" && $ensemble == 7) {
		return Array (
			"isbn" => "ligne|typo"
		);
	}

	// par defaut, aucun champ extraaire.
	return Array ();
}
*/

////////////////////////////////////////////////////////////////////////////////////

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_EXTRA")) return;
define("_ECRIRE_INC_EXTRA", "1");

// a partir de la liste des champs, generer la liste des input
function extra_saisie($extra, $champs) {
	if (! sizeof($champs)) return;

	debut_cadre_enfonce();
	while(list($champ,$desc)=each($champs)) {
		list($type, $filtre, $prettyname)=explode("|", $desc);

		if (!$prettyname)
			$prettyname = ucfirst($champ);
		echo "<div><b>$prettyname&nbsp;:</b><br />";

		switch($type) {
			case "bloc":
			case "block":
				echo "<TEXTAREA NAME='suppl_$champ' CLASS='forml' style='font-size:9px;' ROWS='5' COLS='40'>".entites_html($extra[$champ])."</TEXTAREA>\n";
				break;
			case "ligne":
			case "line":
			default:
				echo "<INPUT TYPE='text' NAME='suppl_$champ' CLASS='forml' style='font-size:9px;'\n";
				echo " VALUE=\"".entites_html($extra[$champ])."\" SIZE='40'>\n";
				break;
		}

		echo "</div>\n";
	}
	fin_cadre_enfonce();
}

// recupere les valeurs postees pour reconstituer le extra
function extra_recup_saisie($champs) {
	$extra=array();
	while(list($champ,)=each($champs)) {
		$extra[$champ]=$GLOBALS["suppl_$champ"];
	}
	return $extra;
}

// a partir de la liste des champs, generer l'affichage
function extra_affichage($extra, $champs) {
	while (list($champ,$desc) = each($champs)) {
		list($type, $filtre, $prettyname) = explode("|", $desc);
		$contenu = $extra[$champ];
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
