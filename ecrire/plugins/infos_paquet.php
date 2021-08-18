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
 * lecture d'un texte conforme a la DTD paquet.dtd
 * et conversion en tableau PHP identique a celui fourni par plugin.xml
 * manque la description
 *
 * @param $desc
 * @param string $plug
 * @param string $dir_plugins
 * @return array
 */
function plugins_infos_paquet($desc, $plug = '', $dir_plugins = _DIR_PLUGINS) {
	static $process = array( // tableau constant
		'debut' => 'paquet_debutElement',
		'fin' => 'paquet_finElement',
		'text' => 'paquet_textElement'
	);

	$valider_xml = charger_fonction('valider', 'xml');
	$vxml = $valider_xml($desc, false, $process, 'paquet.dtd', 'utf-8');
	if (!$vxml->err) {
		// On veut toutes les variantes selon la version de SPIP
		if (!$plug) {
			return $vxml->versions;
		}

		// compatibilite avec l'existant:
		$tree = $vxml->versions['0'];

		// l'arbre renvoie parfois un tag vide... etrange. Pas la peine de garder ca.
		if (isset($tree['']) and !strlen($tree[''])) {
			unset($tree['']);
		}

		$tree['slogan'] = $tree['prefix'] . '_slogan';
		$tree['description'] = $tree['prefix'] . '_description';
		paquet_readable_files($tree, "$dir_plugins$plug/");
		if (!$tree['chemin']) {
			$tree['chemin'] = array();
		}

		// On verifie qu'il existe des balises spip qu'il faudrait rajouter dans
		// la structure d'infos du paquet en fonction de la version spip courante
		if (count($vxml->versions) > 1) {
			$vspip = $GLOBALS['spip_version_branche'];
			foreach ($vxml->versions as $_compatibilite => $_version) {
				if (($_version['balise'] == 'spip')
					and (plugin_version_compatible($_compatibilite, $vspip, 'spip'))
				) {
					// on merge les sous-balises de la balise spip compatible avec celles de la
					// balise paquet
					foreach ($_version as $_index => $_balise) {
						if ($_index and $_index != 'balise') {
							$tree[$_index] = array_merge($tree[$_index], $_balise);
						}
					}
				}
			}
		}

		return $tree;
	}

	// Prendre les messages d'erreur sans les numeros de lignes
	$msg = array_column($vxml->err, 0);
	$t = _T('plugins_erreur', array('plugins' => $plug));
	array_unshift($msg, $t . " <ul class='erreur_xml'><li>" . reset($msg) . '</li></ul>');

	return array('erreur' => $msg);
}

/**
 * Verifier le presence des fichiers remarquables
 * options/actions/administrations et peupler la description du plugin en consequence
 *
 * @param array $tree
 * @param string $dir
 * @return void
 */
function paquet_readable_files(&$tree, $dir) {
	$prefix = strtolower($tree['prefix']);

	$tree['options'] = (is_readable($dir . $f = ($prefix . '_options.php'))) ? array($f) : array();
	$tree['fonctions'] = (is_readable($dir . $f = ($prefix . '_fonctions.php'))) ? array($f) : array();
	$tree['install'] = (is_readable($dir . $f = ($prefix . '_administrations.php'))) ? array($f) : array();
}

/**
 * Appeler le validateur, qui memorise le texte dans le tableau "versions"
 * On  memorise en plus dans les index de numero de version de SPIP
 * les attributs de la balise rencontree
 * qu'on complete par des entrees nommees par les sous-balises de "paquet",
 * et initialisees par un tableau vide, rempli a leur rencontre.
 * La sous-balise "spip", qui ne peut apparaitre qu'apres les autres,
 * reprend les valeurs recuperees precedement (valeurs par defaut)
 *
 * @param object $phraseur
 * @param string $name
 * @param array $attrs
 */
function paquet_debutElement($phraseur, $name, $attrs) {
	xml_debutElement($phraseur, $name, $attrs);
	if ($phraseur->err) {
		return;
	}
	if (($name == 'paquet') or ($name == 'spip')) {
		if ($name == 'spip') {
			$n = $attrs['compatibilite'];
			$attrs = array();
		} else {
			$n = '0';
			$phraseur->contenu['paquet'] = $attrs;
			$attrs['menu'] = array();
			$attrs['chemin'] = array();
			$attrs['necessite'] = array();
			$attrs['lib'] = array();
			$attrs['onglet'] = array();
			$attrs['procure'] = array();
			$attrs['pipeline'] = array();
			$attrs['utilise'] = array();
			$attrs['style'] = array();
			$attrs['script'] = array();
			$attrs['genie'] = array();
		}
		$phraseur->contenu['compatible'] = $n;
		$phraseur->versions[$phraseur->contenu['compatible']] = $attrs;
	} else {
		$phraseur->versions[$phraseur->contenu['compatible']][$name][0] = $attrs;
	}
	$phraseur->versions[$phraseur->contenu['compatible']][''] = '';
}

/**
 * Appeler l'indenteur pour sa gestion de la profondeur,
 * et memoriser les attributs dans le tableau avec l'oppose de la profondeur
 * comme index, avec '' comme sous-index (les autres sont les attributs)
 *
 * @param object $phraseur
 * @param string $data
 */
function paquet_textElement($phraseur, $data) {
	xml_textElement($phraseur, $data);
	if ($phraseur->err or !(trim($data))) {
		return;
	}
	$phraseur->versions[$phraseur->contenu['compatible']][''] .= $data;
}

/**
 * Si on sait deja que le texte n'est pas valide on ne fait rien.
 * Pour une balise sans attribut, le traitement est forcement toujours le meme.
 * Pour une balise sans texte, idem mais parce que la DTD est bien fichue
 *
 * @param object $phraseur
 * @param string $name
 */
function paquet_finElement($phraseur, $name) {
	if ($phraseur->err) {
		return;
	}
	$n = $phraseur->contenu['compatible'];

	if (isset($phraseur->versions[$n][$name][0]) and is_array($phraseur->versions[$n][$name][0])) {
		$attrs = $phraseur->versions[$n][$name][0];
		unset($phraseur->versions[$n][$name][0]);
	} else {
		$attrs = array();
	}

	$texte = trim($phraseur->versions[$n]['']);
	$phraseur->versions[$n][''] = '';

	$f = 'info_paquet_' . $name;
	if (function_exists($f)) {
		$f($phraseur, $attrs, $texte);
	} elseif (!$attrs) {
		$phraseur->versions[$n][$name] = $texte;
	} else {
		// Traitement generique. Si $attrs['nom'] n'existe pas, ce n'est pas normal ici
		$phraseur->versions[$n][$name][$attrs['nom']] = $attrs;
		#	  echo("<br>pour $name $n " . $attrs['nom']); var_dump($phraseur->versions[$n]);
	}
	xml_finElement($phraseur, $name, $attrs);
}

/**
 * Cas particulier de la balise licence :
 * transformer en lien sur url fournie dans l'attribut lien
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_licence($phraseur, $attrs, $texte) {
	if (isset($attrs['lien'])) {
		$lien = $attrs['lien'];
	} else {
		$lien = '';
	}
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['licence'][] = array('nom' => $texte, 'url' => $lien);
}

/**
 * Cas particulier de la balise chemin :
 * stocker un tableau
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_chemin($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	if (isset($attrs['path'])) {
		if (isset($attrs['type'])) {
			$phraseur->versions[$n]['chemin'][] = array('path' => $attrs['path'], 'type' => $attrs['type']);
		} else {
			$phraseur->versions[$n]['chemin'][] = array('path' => $attrs['path']);
		}
	}
}


/**
 * Cas particulier de la balise auteur
 * peupler le mail si besoin (en le protegeant, mais est-ce bien la place pour cela ?)
 * et le lien vers le site de l'auteur si fournit
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_auteur($phraseur, $attrs, $texte) {
	#  echo 'auteur ', $texte;  var_dump($attrs);
	if (isset($attrs['mail'])) {
		if (strpos($attrs['mail'], '@')) {
			$attrs['mail'] = str_replace('@', ' AT ', $attrs['mail']);
		}
		$mail = $attrs['mail'];
	} else {
		$mail = '';
	}

	if (isset($attrs['lien'])) {
		$lien = $attrs['lien'];
	} else {
		$lien = '';
	}

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['auteur'][] = array('nom' => $texte, 'url' => $lien, 'mail' => $mail);
}

/**
 * Cas particulier de la balise credit
 * peupler le lien vers le site externe si necessaire
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_credit($phraseur, $attrs, $texte) {

	if (isset($attrs['lien'])) {
		$lien = $attrs['lien'];
	} else {
		$lien = '';
	}

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['credit'][] = array('nom' => $texte, 'url' => $lien);
}

/**
 * Cas particulier de la balise copyright :
 * transformer en lien sur url fournie dans l'attribut lien
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_copyright($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['copyright'][] = $texte;
}

/**
 * Cas particulier de la balise paquet :
 * Remplacer cet index qui ne sert a rien par un index balise=paquet et ajouter la reference a la dtd
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_paquet($phraseur, $attrs, $texte) {
	$n = 0;
	$phraseur->versions[$n]['dtd'] = 'paquet';
	$phraseur->versions[$n]['balise'] = 'paquet';
}

/**
 * Cas particulier sur la balise traduire :
 * Elle n'a pas de 'nom'
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 **/
function info_paquet_traduire($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['traduire'][] = $attrs;
}

/**
 * Cas particulier de la balise spip :
 * Remplacer cet index qui ne sert a rien par un index balise=spip et ajouter la reference a la dtd
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_spip($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['balise'] = 'spip';
}


/**
 * Pipelines : plusieurs declarations possibles pour un meme pipeline
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_pipeline($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['pipeline'][] = $attrs;
}


/**
 * Style : plusieurs declarations possibles.
 * Traitement de l'attribut source pour générer en remplacement les attributs url et path
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_style($phraseur, $attrs, $texte) {
	$lien = $chemin = $type = $media = '';

	include_spip('inc/utils');
	if (tester_url_absolue($attrs['source'])) {
		$lien = $attrs['source'];
	} else {
		$chemin = $attrs['source'];
	}
	if (isset($attrs['type'])) {
		$type = $attrs['type'];
	}
	if (isset($attrs['media'])) {
		$media = $attrs['media'];
	}

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['style'][] = array('url' => $lien, 'path' => $chemin, 'type' => $type, 'media' => $media);
}


/**
 * Script : plusieurs declarations possibles.
 * Traitement de l'attribut source pour générer en remplacement les attributs url et path
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_script($phraseur, $attrs, $texte) {
	$lien = $chemin = $type = $media = '';

	include_spip('inc/utils');
	if (tester_url_absolue($attrs['source'])) {
		$lien = $attrs['source'];
	} else {
		$chemin = $attrs['source'];
	}
	if (isset($attrs['type'])) {
		$type = $attrs['type'];
	}

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['script'][] = array('url' => $lien, 'path' => $chemin, 'type' => $type);
}

/**
 * Genie : plusieurs declarations possibles pour les crons
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_genie($phraseur, $attrs, $texte) {
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['genie'][] = $attrs;
}
