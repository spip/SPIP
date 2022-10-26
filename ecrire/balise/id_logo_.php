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
 * Compile la balise dynamique `#ID_LOGO_xx` qui retourne l'identifiant du document utilisé comme logo
 * pour un objet éditorial de SPIP.
 *
 * Le type d'objet est récupéré dans le nom de la balise, tel que
 * `ID_LOGO_ARTICLE` ou `ID_LOGO_SITE`.
 *
 * Ces balises ont quelques options :
 *
 * - La balise peut aussi demander explicitement le logo normal ou de survol,
 *   avec `ID_LOGO_ARTICLE_NORMAL` ou `ID_LOGO_ARTICLE_SURVOL`.
 * - On peut demander un logo de rubrique en absence de logo sur l'objet éditorial
 *   demandé avec `ID_LOGO_ARTICLE_RUBRIQUE`
 *
 * @balise
 * @uses generer_code_logo()
 * @example
 *     ```
 *     #ID_LOGO_ARTICLE
 *     ```
 *
 * @param Spip\Compilateur\Noeud\Champ $p
 *     Pile au niveau de la balise
 * @return Spip\Compilateur\Noeud\Champ
 *     Pile complétée par le code à générer
 */
function balise_ID_LOGO__dist($p) {

	preg_match(',^ID_LOGO_([A-Z_]+?)(|_NORMAL|_SURVOL|_RUBRIQUE)$,i', $p->nom_champ, $regs);
	$type = strtolower($regs[1]);
	$suite_logo = $regs[2];

	// cas de #ID_LOGO_SITE_SPIP
	if ($type == 'site_spip') {
		$type = 'site';
		$_id_objet = "\"'0'\"";
	}

	$id_objet = id_table_objet($type);
	if (!isset($_id_objet)) {
		$_id_objet = champ_sql($id_objet, $p);
	}

	$connect = $p->id_boucle ? $p->boucles[$p->id_boucle]->sql_serveur : '';
	if ($type == 'document') {
		$qconnect = _q($connect);
		$doc = "quete_document($_id_objet, $qconnect)";
		$code = "table_valeur($doc, 'id_vignette')";
	} elseif ($connect) {
		$code = "''";
		spip_log('Les logos distants ne sont pas prevus');
	} else {
		$champ_logo = 'id';
		$code = generer_code_logo($id_objet, $_id_objet, $type, '', "''", $p, $suite_logo, $champ_logo);
	}

	$p->code = $code;
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Calcule le code HTML pour l'image ou l'information sur un logo
 *
 * @uses quete_logo()
 * @uses quete_html_logo()
 *
 * @param string $id_objet
 *     Nom de la clé primaire de l'objet (id_article, ...)
 * @param string $_id_objet
 *     Code pour la compilation permettant de récupérer la valeur de l'identifiant
 * @param string $type
 *     Type d'objet
 * @param string $align
 *     Alignement demandé du logo
 * @param string $lien
 *     Lien pour encadrer l'image avec si présent
 * @param Spip\Compilateur\Noeud\Champ $p
 *     Pile au niveau de la balise
 * @param string $suite
 *     Suite éventuelle de la balise logo, telle que `_SURVOL`, `_NORMAL` ou `_RUBRIQUE`.
 * @param string $champ
 *     Indique un type de champ à retourner (fichier, src, titre, descriptif, credits, id, alt)
 * @return string
 *     Code compilé retournant le chemin du logo ou le code HTML du logo.
 **/
function generer_code_logo($id_objet, $_id_objet, $type, $align, $_lien, $p, $suite, string $champ = ''): string {
	$onoff = 'ON';
	$_id_rubrique = "''";

	if ($type === 'rubrique') {
		$_id_rubrique = "quete_parent($_id_objet)";
	}

	if ($suite === '_SURVOL') {
		$onoff = 'off';
	} elseif ($suite === '_NORMAL') {
		$onoff = 'on';
	} elseif ($suite === '_RUBRIQUE') {
		$_id_rubrique = champ_sql('id_rubrique', $p);
	}

	$code = "quete_logo('$id_objet', '$onoff', $_id_objet, $_id_rubrique)";

	if ($champ) {
		return "table_valeur($code, '".addslashes($champ)."')";
	}

	$align = preg_replace(',\W,', '', $align);

	return "quete_html_logo($code, '$align', " . ($_lien ?: "''") . ')';
}
