<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LAYER")) return;
define("_INC_LAYER", "1");

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

verif_butineur();

?>
