<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Trouver le fond pour embarquer un document avec un mime_type donne
 * text_html
 * => modeles/text_html.html si il existe,
 * => modeles/text.html sinon
 * 
 * @param  $mime_type
 * @return mixed
 */
function trouver_modele_mime($mime_type){
	$fond = preg_replace(',\W,','_',$mime_type);
	if (trouve_modele($fond))
		return $fond;
	else
		return preg_replace(',\W.*$,','',$mime_type);
}