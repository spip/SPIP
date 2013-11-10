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

include_spip('inc/revisions');
include_spip('inc/diff');

/**
 * Afficher un diff correspondant a une revision d'un objet
 * 
 * @param int $id_objet
 * @param string $objet
 * @param int $id_version
 * @param bool $court
 * @return string
 */
function revisions_diff ($id_objet,$objet, $id_version, $court=false){
	$textes = revision_comparee($id_objet,$objet, $id_version, 'diff');
	if (!is_array($textes)) return $textes;
	$rev = '';
	$nb = 0;
	foreach ($textes as $var => $t) {
		if ($n=strlen($t)) {
			if ($court)
				$nb += $n;
			else {
				$aff = propre_diff($t);
				if ($GLOBALS['les_notes']) {
					$aff .= '<p>'.$GLOBALS['les_notes'].'</p>';
					$GLOBALS['les_notes'] = '';
				}
				$rev .= "<blockquote>$aff</blockquote>";
			}
		}
	}
	return $court ? _T('taille_octets', array('taille' => $nb)) : $rev;
}

/**
 * Retrouver le champ d'un objet, pour une version demandee
 * @param string $objet
 * @param int $id_objet
 * @param int $id_version
 * @param string $champ
 * @param array $champs
 */
function retrouver_champ_version_objet($objet,$id_objet,$id_version,$champ,&$champs){
	if (isset($champs[$champ]))
		return;

	// Remonter dans le temps pour trouver le champ en question
	// pour la version demandee
	$id_ref = $id_version-1;
	$prev = array();
	while (!isset($prev[$champ]) AND $id_ref>0) {
		$prev = recuperer_version($id_objet,$objet, $id_ref--);
	}
	if (isset($prev[$champ]))
		$champs[$champ] = $prev[$champ];
	else {
		// le champ n'a jamais ete versionne :
		// il etait initialement vide
		if (strncmp($champ,'jointure_',9)==0)
			$champs[$champ] = '';
		else
			$champs[$champ] = '';
	}
}

/**
 * retourne un array() des champs modifies a la version id_version
 * le format =
 *    - diff => seulement les modifs (page revisions)
 *    - apercu => idem, mais en plus tres cout s'il y en a bcp
 *    - complet => tout, avec surlignage des modifications (page revision)
 *
 * http://doc.spip.org/@revision_comparee
 *
 * @param int $id_objet
 * @param string $objet
 * @param int $id_version
 * @param string $format
 * @param null $id_diff
 * @return array
 */
function revision_comparee($id_objet, $objet, $id_version, $format='diff', $id_diff=NULL) {
	include_spip('inc/diff');

	// chercher le numero de la version precedente
	if (!$id_diff) {
		$id_diff = Sql::getfetsel("id_version", "spip_versions", "id_objet=" . intval($id_objet) . " AND id_version < " . intval($id_version)." AND objet=".Sql::quote($objet), "", "id_version DESC", "1");
	}

	if ($id_version && $id_diff) {

		// si l'ordre est inverse, on remet a l'endroit
		if ($id_diff > $id_version) {
			$t = $id_version;
			$id_version = $id_diff;
			$id_diff = $t;
		}

		$old = recuperer_version($id_objet,$objet, $id_diff);
		$new = recuperer_version($id_objet,$objet, $id_version);

		$textes = array();

		// Mode "diff": on ne s'interesse qu'aux champs presents dans $new
		// Mode "complet": on veut afficher tous les champs
		switch ($format) {
			case 'complet':
				$champs = liste_champs_versionnes(table_objet_sql($objet));
				break;
			case 'diff':
			case 'apercu':
			default:
				$champs = array_keys($new);
				break;
		}

		// memoriser les cas les plus courant
		$afficher_diff_champ = charger_fonction('champ','afficher_diff');
		$afficher_diff_jointure = charger_fonction('jointure','afficher_diff');
		foreach ($champs as $champ) {
			// Remonter dans le temps pour trouver le champ en question
			// pour chaque version
			retrouver_champ_version_objet($objet,$id_objet,$id_version,$champ,$new);
			retrouver_champ_version_objet($objet,$id_objet,$id_diff,$champ,$old);

			if (!strlen($new[$champ]) && !strlen($old[$champ])) continue;

			// si on n'a que le vieux, ou que le nouveau, on ne
			// l'affiche qu'en mode "complet"
			if ($format == 'complet')
				$textes[$champ] = strlen($new[$champ])
					? $new[$champ] : $old[$champ];

			// si on a les deux, le diff nous interesse, plus ou moins court
			if (isset($new[$champ]) AND isset($old[$champ])) {
				if (!$afficher_diff = charger_fonction($objet."_".$champ,'afficher_diff',true)
				  AND !$afficher_diff = charger_fonction($champ,'afficher_diff',true))
					$afficher_diff = (strncmp($champ,'jointure_',9)==0?$afficher_diff_jointure:$afficher_diff_champ);

				$textes[$champ] = $afficher_diff($champ,$old[$champ],$new[$champ],$format);
			}
		}
	}

	// que donner par defaut ? (par exemple si id_version=1)
	if (!$textes)
		$textes = recuperer_version($id_objet,$objet, $id_version);

	return $textes;
}

?>
