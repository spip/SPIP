<?php


////////////////////////////////////////////////////////////////////////////////////
// Pour utiliser les champs "extra", il faut installer dans le fichier
// ecrire/mes_options.php3 un tableau definissant les champs en question,
// pour chaque type d'objet (article, rubrique, breve, auteur ou mot) que
// l'on veut ainsi etendre ; utiliser dans l'espace public avec
// [(#EXTRA|extra{"nom_du_champ"})]

/*

//
// Definition de tous les extras possibles
//

$GLOBALS['champs_extra'] = Array (
	'auteur' => Array (
			"sexe" => "ligne|brut",
			"age" => "ligne|propre|&Acirc;ge du capitaine",
			"biblio" => "bloc|propre|Bibliographie"
		),

	'article' => Array (
			"isbn" => "ligne|typo"
		)
	);

*/


/*

// On peut optionnellement vouloir affiner les extras :
// - pour les articles/rubriques/breves en fonction du secteur ;
// - pour les auteurs en fonction du statut
// - pour les mots-cles en fonction du groupe de mots

$GLOBALS['champs_extra_proposes'] = Array (
	'auteur' => Array (
		// 0 : par defaut
		0 =>  'age|sexe',
		// une biblio pour les admin (statut='0minirezo')
		'0minirezo' => 'age|sexe|biblio'
		),

	'article' => Array (
		// 0 : par defaut
		0 => '',
		// 1 : id_secteur=1; 
		1 => 'isbn'
		)
	);

*/

////////////////////////////////////////////////////////////////////////////////////

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_EXTRA")) return;
define("_ECRIRE_INC_EXTRA", "1");

// a partir de la liste des champs, generer la liste des input
function extra_saisie($extra, $type='article', $ensemble='') {
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
		$champs_proposes = $GLOBALS['champs_extra_proposes'][$type][$ensemble];
	else // ... sinon, les champs proposes par defaut
	if (isset($GLOBALS['champs_extra_proposes'][$type][0]))
		$champs_proposes = $GLOBALS['champs_extra_proposes'][$type][0];
	else // sinon tous les champs extra du type
	if (is_array($champs)) {
		$champs_proposes =  Array();
		reset ($champs);
		while (list($ch,) = each($champs))
			$champs_proposes[] = $ch;
		$champs_proposes = join('|', $champs_proposes);
	}

	// maintenant, on affiche les formulaires pour les champs renseignes dans $extra
	// et pour les champs proposes
	reset ($champs);
	while(list($champ,$desc)=each($champs)) {
		if ($extra[$champ] OR strpos(" |$champs_proposes|", "|$champ|")) {
			list($form, $filtre, $prettyname) = explode("|", $desc);

			if (!$prettyname)
				$prettyname = ucfirst($champ);
			$affiche .= "<div><b>$prettyname&nbsp;:</b><br />";

			switch($form) {
				case "bloc":
				case "block":
					$affiche .= "<TEXTAREA NAME='suppl_$champ' CLASS='forml' style='font-size:9px;' ROWS='5' COLS='40'>".entites_html($extra[$champ])."</TEXTAREA>\n";
					break;
				case "masque":
					$affiche .= "<font color='#555555'>".interdire_scripts($extra[$champ])."</font>\n";
					break;
				case "ligne":
				case "line":
				default:
					$affiche .= "<INPUT TYPE='text' NAME='suppl_$champ' CLASS='forml' style='font-size:9px;'\n";
					$affiche .= " VALUE=\"".entites_html($extra[$champ])."\" SIZE='40'>\n";
					break;
			}

			$affiche .= "</div>\n";
		}
	}

	if ($affiche) {
		debut_cadre_enfonce();
		echo $affiche;
		fin_cadre_enfonce();
	}
}

// recupere les valeurs postees pour reconstituer l'extra
function extra_recup_saisie($type='article') {
	$champs = $GLOBALS['champs_extra'][$type];
	if (is_array($champs)) {
		$extra = Array();
		while(list($champ,)=each($champs))
			$extra[$champ]=$GLOBALS["suppl_$champ"];
		return serialize($extra);
	} else
		return '';
}

// a partir de la liste des champs, generer l'affichage
function extra_affichage($extra, $type) {
	$extra = unserialize ($extra);
	$champs = $GLOBALS['champs_extra'][$type];
	while (list($nom,$contenu) = each($extra)) {
		list($type, $filtre, $prettyname) = explode("|", $champs[$nom]);
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
			$prettyname = ucfirst($nom);
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
