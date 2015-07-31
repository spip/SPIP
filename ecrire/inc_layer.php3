<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LAYER")) return;
define("_INC_LAYER", "1");

include_ecrire("inc_filtres.php3"); # pour http_script (normalement déjà fait)

function debut_block_visible($nom_block){
	global $numero_block, $compteur_block, $browser_layer;
	if (!$browser_layer) return '';
	if (!$numero_block["$nom_block"] > 0){
		$compteur_block++;
		$numero_block["$nom_block"] = $compteur_block;
	}
	return "<div id='Layer".$numero_block["$nom_block"]."' style='display: block'>";

}

function debut_block_invisible($nom_block){
	global $numero_block, $compteur_block, $browser_layer;
	if (!$browser_layer) return '';
	if (!$numero_block["$nom_block"] > 0){
		$compteur_block++;
		$numero_block["$nom_block"] = $compteur_block;
	}

	return http_script("
vis['".$numero_block["$nom_block"]."'] = 'hide';
document.write('<div id=\"Layer".$numero_block["$nom_block"]."\" style=\"display: none; margin-top: 1;\">');",
			      '',
			   "<div id='Layer".$numero_block["$nom_block"]."' style='display: block;'>");

}

function fin_block() {
  return (!$GLOBALS['browser_layer'] ? '' : "<div style='clear: both;'></div></div>");
}

function bouton_block_invisible($nom_block) {
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;

	$num_triangle = $compteur_block + 1;

	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);

	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		$javasc .= "swap_couche(\\'".$numero_block[$nom_block]."\\', \\'$spip_lang_rtl\\',\\'" . _DIR_IMG_PACK . "\\');";
		}
	return http_script("
document.write('<a class=\"triangle_block\" href=\"javascript:$javasc\"><img name=\"triangle".$numero_block["$nom_block"]."\" src=\"". _DIR_IMG_PACK . "deplierhaut$spip_lang_rtl.gif\" alt=\"\" title=\"".addslashes(_T('info_deplier'))."\" width=\"10\" height=\"10\" border=\"0\"></a>');\n");
}


function bouton_block_visible($nom_block){
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;

	$num_triangle = $compteur_block + 1;

	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);

	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		$javasc .= "swap_couche(\\'".$numero_block[$nom_block]."\\', \\'$spip_lang_rtl\\',\\'" . _DIR_IMG_PACK . "\\');";
		}

	return http_script("
document.write('<a class=\"triangle_block\" href=\"javascript:$javasc\"><img name=\"triangle".$numero_block["$nom_block"]."\" src=\"". _DIR_IMG_PACK . "deplierbas.gif\" alt=\"\" title=\"".addslashes(_T('info_deplier'))."\" width=\"10\" height=\"10\" border=\"0\"></a>');\n");
}

//
// Tests sur le nom du butineur
//
function verif_butineur() {
	global $HTTP_USER_AGENT, $browser_name, $browser_version;
	global $browser_description, $browser_rev, $browser_layer;
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];
	$browser_layer = '';

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_INCLUDE . 'layer.js',''));
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('', _DIR_INCLUDE . 'layer.js',''));
	}
	else if (eregi("KHTML", $browser_description) &&
		eregi("Safari/([^;]*)", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('', _DIR_INCLUDE . 'layer.js','');
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
		$browser_layer = http_script('', _DIR_INCLUDE . 'layer.js','');
		// Numero de version pour Mozilla "authentique"
		if (ereg("rv:([0-9]+\.[0-9]+)", $browser_description, $match))
			$browser_rev = doubleval($match[1]);
		// Autres Gecko => equivalents 1.4 par defaut (Galeon, etc.)
		else if (strpos($browser_description, "Gecko") and !strpos($browser_description, "KHTML"))
			$browser_rev = 1.4;
		// Machins quelconques => equivalents 1.0 par defaut (Konqueror, etc.)
		else $browser_rev = 1.0;
	}

	if (!$browser_name) $browser_name = "Mozilla";
}

verif_butineur();

?>
