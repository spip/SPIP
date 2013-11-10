<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte');

/**
 * Callback de traitement de chaque tableau
 *
 * @param array $m
 * @return string
 */
function replace_tableaux($m){
	return $m[1] . traiter_tableau($m[2]) . $m[3];
}
