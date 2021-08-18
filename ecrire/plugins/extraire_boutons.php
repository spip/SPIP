<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Analyser un arbre xml et extraire les infos concernant les boutons et onglets
 *
 * @param <type> $arbre
 * @return <type>
 */
function plugins_extraire_boutons_dist($arbre) {
	$ret = array('bouton' => array(), 'onglet' => array());
	// recuperer les boutons et onglets si necessaire
	spip_xml_match_nodes(',^(bouton|onglet)\s,', $arbre, $les_boutons);
	if (is_array($les_boutons) && count($les_boutons)) {
		$ret['bouton'] = array();
		$ret['onglet'] = array();
		foreach ($les_boutons as $bouton => $val) {
			$bouton = spip_xml_decompose_tag($bouton);
			$type = reset($bouton);
			$bouton = end($bouton);
			if (isset($bouton['id'])) {
				$id = $bouton['id'];
				$val = reset($val);
				if (is_array($val)) {
					$ret[$type][$id]['parent'] = isset($bouton['parent']) ? $bouton['parent'] : '';
					$ret[$type][$id]['position'] = isset($bouton['position']) ? $bouton['position'] : '';
					$ret[$type][$id]['titre'] = isset($val['titre']) ? trim(spip_xml_aplatit($val['titre'])) : '';
					$ret[$type][$id]['icone'] = isset($val['icone']) ? trim(end($val['icone'])) : '';
					$ret[$type][$id]['action'] = isset($val['url']) ? trim(end($val['url'])) : '';
					$ret[$type][$id]['parametres'] = isset($val['args']) ? trim(end($val['args'])) : '';
				}
			}
		}
	}

	return $ret;
}
