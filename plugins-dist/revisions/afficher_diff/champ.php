<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/diff');

/**
 * Afficher le diff d'un champ texte generique
 * @param string $champ
 * @param string $old
 * @param string $new
 * @param string $format
 *   apercu, diff ou complet
 * @return string
 */
function afficher_diff_champ_dist($champ,$old,$new,$format='diff'){
	// ne pas se compliquer la vie !
	if ($old==$new)
		$out = ($format!='complet'?'':$new);
	else {
		if($f = charger_fonction($champ,'afficher_diff'))
			return $f($champ,$old,$new,$format='diff');
		$diff = new Diff(new DiffTexte);
		$n = preparer_diff($new);
		$o = preparer_diff($old);

		$out = afficher_diff($diff->comparer($n,$o));
		if ($format == 'diff' OR $format == 'apercu')
			$out = afficher_para_modifies($out, ($format == 'apercu'));
	}
	return $out;
}


/**
 * http://doc.spip.org/@afficher_para_modifies
 *
 * @param string $texte
 * @param bool $court
 * @return string
 */
function afficher_para_modifies ($texte, $court = false) {
	// Limiter la taille de l'affichage
	if ($court) $max = 200;
	else $max = 2000;

	$texte_ret = "";
	$paras = explode ("\n",$texte);
	for ($i = 0; $i < count($paras) AND strlen($texte_ret) < $max; $i++) {
		if (strpos($paras[$i], '"diff-')) $texte_ret .= $paras[$i]."\n\n";
#		if (strlen($texte_ret) > $max) $texte_ret .= '(...)';
	}
	$texte = $texte_ret;
	return $texte;
}
