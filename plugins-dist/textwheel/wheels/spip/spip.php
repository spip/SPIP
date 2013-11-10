<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte');

/**
 * callback pour la puce qui est definissable/surchargeable
 * @return string
 */
function replace_puce(){
	static $puce;
	if (!isset($puce))
		$puce = "\n<br />".definir_puce()."&nbsp;";
	return $puce;
}

/**
 * callback pour les Abbr :
 * [ABBR|abbrevation]
 * [ABBR|abbrevation{lang}]
 * @param array $m
 * @return string
 */
function inserer_abbr($m){
	$title = attribut_html($m[2]);
	$lang = (isset($m[3])?" lang=\"".$m[3]."\"":"");
	return "<abbr title=\"$title\"$lang>".$m[1]."</abbr>";
}