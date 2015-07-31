<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_CALCUL_HTML4")) return;
define("_CALCUL_HTML4", "1");

// Renvoie le code html pour afficher le logo, avec ou sans survol, avec ou sans lien, etc.
function affiche_logos($logo, $lien, $align, $flag_fichier) {
	global $num_survol;
	global $espace_logos;

	list($arton,$artoff) = $logo;

	// Pour les documents comme pour les logos, le filtre |fichier donne
	// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
	// remarquable, mais a conserver pour compatibilite ascendante.
	// -> http://www.spip.net/fr_article901.html
	if ($flag_fichier) {
		$on = ereg_replace("^IMG/","",$arton);
		$off = ereg_replace("^IMG/","",$artoff);
		return $on ? $on : $off;
	}

	$num_survol++;
	if ($arton) {
		//$imgsize = @getimagesize("$arton");
		//$taille_image = ereg_replace("\"","'",$imgsize[3]);
		if ($align) $align="align='$align' ";

		$milieu = "<img src='$arton' $align".
			" name='image$num_survol' ".$taille_image." border='0' alt=''".
			" hspace='$espace_logos' vspace='$espace_logos' class='spip_logos' />";

		if ($artoff) {
			if ($lien) {
				$afflien = "<a href='$lien'";
				$afflien2 = "a>";
			}
			else {
				$afflien = "<div";
				$afflien2 = "div>";
			}
			$milieu = "$afflien onMouseOver=\"image$num_survol.src=".
				"'$artoff'\" onMouseOut=\"image$num_survol.src=".
				"'$arton'\">$milieu</$afflien2";
		}
		else if ($lien) {
			$milieu = "<a href='$lien'>$milieu</a>";
		}
	} else {
		$milieu="";
	}
	return $milieu;
}



//
// Ajouter le &var_recherche=toto dans les boucles de recherche
//
function url_var_recherche($url) {
	if ($GLOBALS['HTTP_GET_VARS']['recherche'] && !ereg("var_recherche", $url)) {
		$url .= strpos($url, '?') ? '&' : '?';
		$url .= "var_recherche=".urlencode($GLOBALS['recherche']);
	}
	return $url;
}

//
// fonction standard de calcul de la balise #INTRODUCTION
// on peut la surcharger en definissant dans mes_fonctions.php3 :
// function introduction($type,$texte,$descriptif) {...}
//
function calcul_introduction ($type, $texte, $chapo='', $descriptif='') {
	if (function_exists("introduction"))
		return introduction ($type, $texte, $chapo, $descriptif);

	switch ($type) {
		case 'articles':
			if ($descriptif)
				return propre($descriptif);
			else if (substr($chapo, 0, 1) == '=')	// article virtuel
				return '';
			else
				return PtoBR(propre(supprimer_tags(couper_intro($chapo."\n\n\n".$texte, 500))));
			break;
		case 'breves':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 300))));
			break;
		case 'forums':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
		case 'rubriques':
			if ($descriptif)
				return propre($descriptif);
			else
				return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
	}
}

function calcul_form_rech($lien)
{
  return 
    "<form action='$lien' method='get' class='formrecherche'><input type='text' id='formulaire_recherche' size='20' class='formrecherche' name='recherche' value='" . _T('info_rechercher') . "' /></form>";
}

?>
