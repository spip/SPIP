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

include_spip('inc/xml');
include_spip('inc/plugin');

// https://code.spip.net/@plugin_verifie_conformite
function plugins_verifie_conformite_dist($plug, &$arbre, $dir_plugins = _DIR_PLUGINS) {
	static $etats = ['dev', 'experimental', 'test', 'stable'];

	$matches = [];
	$silence = false;
	$p = null;
	// chercher la declaration <plugin spip='...'> a prendre pour cette version de SPIP
	if ($n = spip_xml_match_nodes(',^plugin(\s|$),', $arbre, $matches)) {
		// version de SPIP
		$vspip = $GLOBALS['spip_version_branche'];
		foreach ($matches as $tag => $sous) {
			list($tagname, $atts) = spip_xml_decompose_tag($tag);
			if ($tagname == 'plugin' and is_array($sous)) {
				// On rajoute la condition sur $n :
				// -- en effet si $n==1 on a pas plus a choisir la balise que l'on ait
				//    un attribut spip ou pas. Cela permet de traiter tous les cas mono-balise
				//    de la meme facon.
				if (
					!isset($atts['spip'])
					or $n == 1
					or plugin_version_compatible($atts['spip'], $vspip, 'spip')
				) {
					// on prend la derniere declaration avec ce nom
					$p = end($sous);
					$compat_spip = isset($atts['spip']) ? $atts['spip'] : '';
				}
			}
		}
	}
	if (is_null($p)) {
		$arbre = ['erreur' => [_T('erreur_plugin_tag_plugin_absent') . " : $plug"]];
		$silence = true;
	} else {
		$arbre = $p;
	}
	if (!is_array($arbre)) {
		$arbre = [];
	}
	// verification de la conformite du plugin avec quelques
	// precautions elementaires
	if (!isset($arbre['nom'])) {
		if (!$silence) {
			$arbre['erreur'][] = _T('erreur_plugin_nom_manquant');
		}
		$arbre['nom'] = [''];
	}
	if (!isset($arbre['version'])) {
		if (!$silence) {
			$arbre['erreur'][] = _T('erreur_plugin_version_manquant');
		}
		$arbre['version'] = [''];
	}
	if (!isset($arbre['prefix'])) {
		if (!$silence) {
			$arbre['erreur'][] = _T('erreur_plugin_prefix_manquant');
		}
		$arbre['prefix'] = [''];
	} else {
		$prefix = trim(end($arbre['prefix']));
		if (strtoupper($prefix) == 'SPIP' and $plug != './') {
			$arbre['erreur'][] = _T('erreur_plugin_prefix_interdit');
		}
		if (isset($arbre['etat'])) {
			$etat = trim(end($arbre['etat']));
			if (!in_array($etat, $etats)) {
				$arbre['erreur'][] = _T('erreur_plugin_etat_inconnu') . " : '$etat'";
			}
		}
		if (isset($arbre['options'])) {
			foreach ($arbre['options'] as $optfile) {
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins . "$plug/$optfile")) {
					if (!$silence) {
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent') . " : $optfile";
					}
				}
			}
		}
		if (isset($arbre['fonctions'])) {
			foreach ($arbre['fonctions'] as $optfile) {
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins . "$plug/$optfile")) {
					if (!$silence) {
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent') . " : $optfile";
					}
				}
			}
		}
		$fonctions = [];
		if (isset($arbre['fonctions'])) {
			$fonctions = $arbre['fonctions'];
		}
		$liste_methodes_reservees = [
			'__construct',
			'__destruct',
			'plugin',
			'install',
			'uninstall',
			strtolower($prefix)
		];

		$extraire_pipelines = charger_fonction('extraire_pipelines', 'plugins');
		$arbre['pipeline'] = $extraire_pipelines($arbre);
		foreach ($arbre['pipeline'] as $pipe) {
			if (!isset($pipe['nom'])) {
				if (!$silence) {
					$arbre['erreur'][] = _T('erreur_plugin_nom_pipeline_non_defini');
				}
			}
			if (isset($pipe['action'])) {
				$action = $pipe['action'];
			} else {
				$action = $pipe['nom'];
			}
			// verif que la methode a un nom autorise
			if (in_array(strtolower($action), $liste_methodes_reservees)) {
				if (!$silence) {
					$arbre['erreur'][] = _T('erreur_plugin_nom_fonction_interdit') . " : $action";
				}
			}
			if (isset($pipe['inclure'])) {
				$inclure = $dir_plugins . "$plug/" . $pipe['inclure'];
				if (!@is_readable($inclure)) {
					if (!$silence) {
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent') . " : $inclure";
					}
				}
			}
		}
		$necessite = [];
		$spip_trouve = false;
		if (spip_xml_match_nodes(',^necessite,', $arbre, $needs)) {
			foreach (array_keys($needs) as $tag) {
				list($tag, $att) = spip_xml_decompose_tag($tag);
				if (!isset($att['id'])) {
					if (!$silence) {
						$arbre['erreur'][] = _T(
							'erreur_plugin_attribut_balise_manquant',
							['attribut' => 'id', 'balise' => $att]
						);
					}
				} else {
					$necessite[] = $att;
				}
				if (strtolower($att['id']) == 'spip') {
					$spip_trouve = true;
				}
			}
		}
		if ($compat_spip and !$spip_trouve) {
			$necessite[] = ['id' => 'spip', 'version' => $compat_spip];
		}
		$arbre['necessite'] = $necessite;
		$utilise = [];
		if (spip_xml_match_nodes(',^utilise,', $arbre, $uses)) {
			foreach (array_keys($uses) as $tag) {
				list($tag, $att) = spip_xml_decompose_tag($tag);
				if (!isset($att['id'])) {
					if (!$silence) {
						$arbre['erreur'][] = _T(
							'erreur_plugin_attribut_balise_manquant',
							['attribut' => 'id', 'balise' => $att]
						);
					}
				} else {
					$utilise[] = $att;
				}
			}
		}
		$arbre['utilise'] = $utilise;
		$procure = [];
		if (spip_xml_match_nodes(',^procure,', $arbre, $uses)) {
			foreach (array_keys($uses) as $tag) {
				list($tag, $att) = spip_xml_decompose_tag($tag);
				$procure[] = $att;
			}
		}
		$arbre['procure'] = $procure;
		$path = [];
		if (spip_xml_match_nodes(',^chemin,', $arbre, $paths)) {
			foreach (array_keys($paths) as $tag) {
				list($tag, $att) = spip_xml_decompose_tag($tag);
				$att['path'] = $att['dir']; // ancienne syntaxe
				$path[] = $att;
			}
		} else {
			$path = [['dir' => '']];
		} // initialiser par defaut
		$arbre['path'] = $path;
		// exposer les noisettes
		if (isset($arbre['noisette'])) {
			foreach ($arbre['noisette'] as $k => $nut) {
				$nut = preg_replace(',[.]html$,uims', '', trim($nut));
				$arbre['noisette'][$k] = $nut;
				if (!@is_readable($dir_plugins . "$plug/$nut.html")) {
					if (!$silence) {
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent') . " : $nut";
					}
				}
			}
		}
		$traduire = [];
		if (spip_xml_match_nodes(',^traduire,', $arbre, $trads)) {
			foreach (array_keys($trads) as $tag) {
				list($tag, $att) = spip_xml_decompose_tag($tag);
				$traduire[] = $att;
			}
		}
		$arbre['traduire'] = $traduire;
	}
}
