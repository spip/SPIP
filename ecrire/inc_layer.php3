<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LAYER")) return;
define("_INC_LAYER", "1");




function test_layer(){
	global $browser_name, $browser_version, $browser_description;

	if (
	(eregi("msie", $browser_name) AND $browser_version >= 5)
	|| (eregi("mozilla", $browser_name) AND $browser_version >= 5)
	|| (eregi("opera", $browser_name) AND $browser_version >= 7)
		|| (eregi("safari", $browser_name))
	)
		return true;
}


function afficher_script_layer(){
	global $dir_ecrire;

	if (test_layer()){
		echo '<script type="text/javascript" src="'.$dir_ecrire.'layer.js">';
		echo "</script>\n";
	}
}


function debut_block_visible($nom_block){
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}
		$retour .= "<div id='Layer".$numero_block["$nom_block"]."' style='display: block'>";
	}
	return $retour;
}

function debut_block_invisible($nom_block){
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		$retour = "\n<script type='text/javascript'><!--\n";
		$retour .= "vis['".$numero_block["$nom_block"]."'] = 'hide';\n";
		$retour .= "document.write('<div id=\"Layer".$numero_block["$nom_block"]."\" style=\"display: none; margin-top: 1;\">');\n";
		$retour .= "//-->\n";
		$retour .= "</script>\n";

		$retour .= "<noscript><div id='Layer".$numero_block["$nom_block"]."' style='display: block;'></noscript>\n";
	}
	return $retour;
}

function fin_block() {
	if (test_layer()) {
		return "<div style='clear: both;'></div></div>";
	}
}

function bouton_block_invisible($nom_block) {
	global $numero_block;
	global $compteur_block;
	global $spip_lang_rtl;
	global $dir_ecrire;

	$num_triangle = $compteur_block + 1;

	if (test_layer()) {
		$blocks = explode(",", $nom_block);

		for ($index=0; $index < count($blocks); $index ++){
			$nom_block = $blocks[$index];

			if (!$numero_block["$nom_block"] > 0){
				$compteur_block++;
				$numero_block["$nom_block"] = $compteur_block;
			}

			$javasc .= "swap_couche(\\'".$numero_block[$nom_block]."\\', \\'$spip_lang_rtl\\');";
		}
		$retour = "\n<script type='text/javascript'><!--\n";
		$retour .= "document.write('<a class=\"triangle_block\" href=\"javascript:$javasc\"><img name=\"triangle".$numero_block["$nom_block"]."\" src=\"".$dir_ecrire."img_pack/deplierhaut$spip_lang_rtl.gif\" alt=\"\" title=\"".addslashes(_T('info_deplier'))."\" width=\"10\" height=\"10\" border=\"0\"></a>');\n";
		$retour .= "//-->\n";
		$retour .= "</script>\n";

		return $retour;
	}
}


function bouton_block_visible($nom_block){
	global $dir_ecrire;
	global $spip_lang_rtl;
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		return "<a class=\"triangle_block\" href=\"javascript:swap_couche('".$numero_block["$nom_block"]."', '$spip_lang_rtl')\"><IMG name='triangle".$numero_block["$nom_block"]."' src='".$dir_ecrire."img_pack/deplierbas.gif' alt='' title='".addslashes(_T('info_deplier'))."' width='10' height='10' border='0'></a>";
	}
}

verif_butineur();

?>
