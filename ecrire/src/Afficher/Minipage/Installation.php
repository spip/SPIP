<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 * \***************************************************************************/

namespace Spip\Afficher\Minipage;

/**
 * Présentation des pages simplifiées pour installer SPIP
 **/
class Installation extends Admin {

	public const TYPE = 'installation';

	protected function setOptions(array $options) {
		$options['titre'] ??= '';
		if (!$options['titre'] || $options['titre'] === 'AUTO') {
			$options['titre'] = _T('info_installation_systeme_publication');
		}
		$options = parent::setOptions($options);
		$options['couleur_fond'] = '#a1124d';
		$options['css_files'][] = find_in_theme('installation.css');
		$options['footer'] = '';
		return $options;
	}

	public function page($corps = '', $options = []) {
		$options['titre'] ??= 'AUTO';
		return parent::page($corps, $options);
	}

}
