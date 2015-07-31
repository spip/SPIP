<?php

function image_bg ($img, $couleur, $pos="") {
	if (function_exists("imagecreatetruecolor")) return "background: url(".extraire_attribut(image_sepia($img, $couleur), "src").") $pos;";
	else return "background-color: #$couleur;";
}

?>