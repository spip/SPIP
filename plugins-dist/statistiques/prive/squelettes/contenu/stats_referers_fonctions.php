<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function vigneter_referer($url){

	if (!strlen($GLOBALS['source_vignettes']) OR $GLOBALS['meta']["activer_captures_referers"]=='non')
		return '';

	return $GLOBALS['source_vignettes'].rawurlencode(preg_replace(";^[a-z]{3,6}://;","",$url));
}
