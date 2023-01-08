<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Extraire les infos de pipeline
 *
 * @param array $arbre
 */
function plugins_extraire_pipelines_dist(&$arbre) {
	$pipes = null;
	$tag = null;
	$pipeline = [];
	if (spip_xml_match_nodes(',^pipeline,', $arbre, $pipes)) {
		foreach ($pipes as $tag => $p) {
			if (!is_array($p[0])) {
				[$tag, $att] = spip_xml_decompose_tag($tag);
				$pipeline[] = $att;
			} else {
				foreach ($p as $pipe) {
					$att = [];
					if (is_array($pipe)) {
						foreach ($pipe as $k => $t) {
							$att[$k] = trim(end($t));
						}
					}
					$pipeline[] = $att;
				}
			}
		}
		unset($arbre[$tag]);
	}

	return $pipeline;
}
