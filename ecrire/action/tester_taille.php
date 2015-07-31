<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/headers');

function action_tester_taille_error_handler($output)
{
	// on est ici, donc echec lors de la creation de l'image
	if ($GLOBALS['redirect']){
		return redirige_formulaire($GLOBALS['redirect']);
	}
	return $output;
}


// Tester nos capacites a creer des images avec GD2 (taille memoire)
// http://doc.spip.org/@action_tester_taille_dist
function action_tester_taille_dist() {
	
	if (!autoriser('configurer'))
		return;
	
	$taille = _request('arg');
	$taille = explode('-',$taille);

	$GLOBALS['taille_max'] = end($taille);
	$GLOBALS['taille_min'] = 0;
	if (count($taille)>1)
		$GLOBALS['taille_min'] = reset($taille);

	// si l'intervalle est assez petit, on garde la valeur min
	if ($GLOBALS['taille_max']*$GLOBALS['taille_max']-$GLOBALS['taille_min']*$GLOBALS['taille_min']<50000){
		ecrire_meta('max_taille_vignettes',$t=($GLOBALS['taille_min']*$GLOBALS['taille_min'])*0.9,'non');
		echo round($t/1000000,3).' Mpx';
		die();
	}

	$taille = $GLOBALS['taille_test'] = round(($GLOBALS['taille_max']+$GLOBALS['taille_min'])/2);

	include_spip('inc/filtres');
	// des inclusions representatives d'un hit prive et/ou public pour la conso memoire
	include_spip('public/assembler');
	include_spip('public/balises');
	include_spip('public/boucles');
	include_spip('public/cacher');
	include_spip('public/compiler');
	include_spip('public/composer');
	include_spip('public/criteres');
	include_spip('public/interfaces');
	include_spip('public/parametrer');
	include_spip('public/phraser_html');
	include_spip('public/references');

	include_spip('inc/presentation');
	include_spip('inc/charsets');
	include_spip('inc/documents');
	include_spip('inc/header');
	propre("<doc1>"); // charger propre avec le trairement d'un modele

	$i = _request('i')+1;
	$image_source = chemin_image("test.png");
	$GLOBALS['redirect'] = generer_url_action("tester_taille", "i=$i&arg=".$GLOBALS['taille_min']."-".$GLOBALS['taille_test']);

	ob_start('action_tester_taille_error_handler');
	filtrer('image_recadre',$image_source,$taille,$taille);
	$GLOBALS['redirect'] = generer_url_action("tester_taille", "i=$i&arg=$taille-".$GLOBALS['taille_max']);
	// si la valeur intermediaire a reussi, on teste la valeur maxi qui est peut etre sous estimee
	$taille = $GLOBALS['taille_max'];
	filtrer('image_recadre',$image_source,$taille,$taille);
	$GLOBALS['redirect'] = generer_url_action("tester_taille", "i=$i&arg=$taille-".$GLOBALS['taille_max']);
	ob_end_clean();

	// on est ici, donc pas de plantage
	echo redirige_formulaire($GLOBALS['redirect']);
}

?>
