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

/**
 * Fonctions génériques pour les balises `#LOGO_XXXX`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Compile la balise dynamique `#LOGO_xx` qui retourne le code HTML
 * pour afficher l'image de logo d'un objet éditorial de SPIP.
 *
 * Le type d'objet est récupéré dans le nom de la balise, tel que
 * `LOGO_ARTICLE` ou `LOGO_SITE`.
 *
 * Ces balises ont quelques options :
 *
 * - La balise peut aussi demander explicitement le logo normal ou de survol,
 *   avec `LOGO_ARTICLE_NORMAL` ou `LOGO_ARTICLE_SURVOL`.
 * - On peut demander un logo de rubrique en absence de logo sur l'objet éditorial
 *   demandé avec `LOGO_ARTICLE_RUBRIQUE`
 * - `LOGO_ARTICLE*` ajoute un lien sur l'image du logo vers l'objet éditorial
 * - `LOGO_ARTICLE**` retourne le nom du fichier de logo.
 * - `LOGO_ARTICLE{right}`. Valeurs possibles : top left right center bottom
 * - `LOGO_DOCUMENT{icone}`. Valeurs possibles : auto icone apercu vignette
 * - `LOGO_ARTICLE{200, 0}`. Redimensionnement indiqué
 *
 * Pour récupérer l’identifiant du document sous-jacent voir la balise `ID_LOGO_...`
 *
 * @balise
 * @uses generer_code_logo()
 * @example
 *     ```
 *     #LOGO_ARTICLE
 *     ```
 *
 * @param Spip\Compilateur\Noeud\Champ $p
 *     Pile au niveau de la balise
 * @return Spip\Compilateur\Noeud\Champ
 *     Pile complétée par le code à générer
 */
function balise_LOGO__dist($p) {

	preg_match(',^LOGO_([A-Z_]+?)(|_NORMAL|_SURVOL|_RUBRIQUE)$,i', $p->nom_champ, $regs);
	$type = strtolower($regs[1]);
	$suite_logo = $regs[2];

	// cas de #LOGO_SITE_SPIP
	if ($type == 'site_spip') {
		$type = 'site';
		$_id_objet = "\"'0'\"";
	}

	$id_objet = id_table_objet($type);
	if (!isset($_id_objet)) {
		$_id_objet = champ_sql($id_objet, $p);
	}

	$fichier = ($p->etoile === '**') ? -1 : 0;
	$coord = [];
	$align = $lien = '';
	$mode_logo = '';

	if ($p->param and !$p->param[0][0]) {
		$params = $p->param[0];
		array_shift($params);
		foreach ($params as $a) {
			if ($a[0]->type === 'texte') {
				$n = $a[0]->texte;
				if (is_numeric($n)) {
					$coord[] = $n;
				} elseif (in_array($n, ['top', 'left', 'right', 'center', 'bottom'])) {
					$align = $n;
				} elseif (in_array($n, ['auto', 'icone', 'apercu', 'vignette'])) {
					$mode_logo = $n;
				}
			} else {
				$lien = calculer_liste($a, $p->descr, $p->boucles, $p->id_boucle);
			}
		}
	}

	$coord_x = !$coord ? 0 : intval(array_shift($coord));
	$coord_y = !$coord ? 0 : intval(array_shift($coord));

	if ($p->etoile === '*') {
		include_spip('balise/url_');
		$lien = generer_generer_url_arg($type, $p, $_id_objet);
	}

	$connect = $p->id_boucle ? $p->boucles[$p->id_boucle]->sql_serveur : '';
	if ($type == 'document') {
		$qconnect = _q($connect);
		$doc = "quete_document($_id_objet, $qconnect)";
		if ($fichier) {
			$code = "quete_logo_file($doc, $qconnect)";
		} else {
			$code = "quete_logo_document($doc, " . ($lien ?: "''") . ", '$align', '$mode_logo', $coord_x, $coord_y, $qconnect)";
		}
		// (x=non-faux ? y : '') pour affecter x en retournant y
		if ($p->descr['documents']) {
			$code = '(($doublons["documents"] .= ",". '
				. $_id_objet
				. ") ? $code : '')";
		}
	} elseif ($connect) {
		$code = "''";
		spip_log('Les logos distants ne sont pas prevus');
	} else {
		// pour generer_code_logo
		include_spip('balise/id_logo_');
		$champ_logo = '';
		if ($fichier) {
			$champ_logo = 'fichier';
		}
		$code = generer_code_logo($id_objet, $_id_objet, $type, $align, $lien, $p, $suite_logo, $champ_logo);
	}

	// demande de reduction sur logo avec ecriture spip 2.1 : #LOGO_xxx{200, 0}
	if ($coord_x or $coord_y) {
		$code = "filtrer('image_graver',filtrer('image_reduire'," . $code . ", '$coord_x', '$coord_y'))";
	}

	$p->code = $code;
	$p->interdire_scripts = false;

	return $p;
}
