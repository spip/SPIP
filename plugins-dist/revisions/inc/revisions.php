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

/**
 * Fonctions utilitaires du plugin révisions
 *
 * @package Revisions
**/
if (!defined("_ECRIRE_INC_VERSION")) return;

$GLOBALS['agregation_versions'] = 10;

/** Intervalle de temps (en seconde) separant deux révisions par un même auteur */
define('_INTERVALLE_REVISIONS', 600); 

// http://doc.spip.org/@separer_paras
function separer_paras($texte, $paras = "") {
	if (!$paras) $paras = array();
	while (preg_match("/(\r\n?){2,}|\n{2,}/", $texte, $regs)) {
		$p = strpos($texte, $regs[0]) + strlen($regs[0]);
		$paras[] = substr($texte, 0, $p);
		$texte = substr($texte, $p);
	}
	if ($texte) $paras[] = $texte;
	return $paras;
}

// http://doc.spip.org/@replace_fragment
function replace_fragment($id_objet,$objet, $version_min, $version_max, $id_fragment, $fragment) {
	$fragment = serialize($fragment);
	$compress = 0;

	// pour le portage en PG il faut l'equivalente au mysql_escape_string
	// et deporter son appel dans les fonctions d'abstraction.
	if (function_exists('gzcompress')
	AND $GLOBALS['connexions'][0]['type'] == 'mysql') {
		$s = gzcompress($fragment);
		if (strlen($s) < strlen($fragment)) {
			# spip_log("gain gz: ".intval(100 - 100 * strlen($s) / strlen($fragment)));
			$compress = 1;
			$fragment = $s;
		}
	}

	// Attention a echapper $fragment, binaire potentiellement gz
	return array(
		     'id_objet' => intval($id_objet),
			 'objet' => $objet,
		     'id_fragment' => intval($id_fragment),
		     'version_min' => intval($version_min),
		     'version_max' => intval($version_max),
		     'compress' => $compress,
		     'fragment' => $fragment);
}

// http://doc.spip.org/@envoi_replace_fragments
function envoi_replace_fragments($replaces) {
	$desc = $GLOBALS['tables_auxiliaires']['spip_versions_fragments'];
	foreach($replaces as $r)
		Sql::replace('spip_versions_fragments', $r, $desc);
}


// http://doc.spip.org/@envoi_delete_fragments
function envoi_delete_fragments($id_objet,$objet, $deletes) {
	if (count($deletes)) {
		Sql::delete("spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND ((".	join(") OR (", $deletes)."))");
	}
}


//
// Ajouter les fragments de la derniere version (tableau associatif id_fragment => texte)
//
// http://doc.spip.org/@ajouter_fragments
function ajouter_fragments($id_objet,$objet, $id_version, $fragments) {
	global $agregation_versions;

	$replaces = array();
	foreach ($fragments as $id_fragment => $texte) {
		$nouveau = true;
		// Recuperer la version la plus recente
		$row = Sql::fetsel("compress, fragment, version_min, version_max", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_fragment=$id_fragment AND version_min<=$id_version", "", "version_min DESC", "1");

		if ($row) {
			$fragment = $row['fragment'];
			$version_min = $row['version_min'];
			if ($row['compress'] > 0) $fragment = @gzuncompress($fragment);
			$fragment = unserialize($fragment);
			if (is_array($fragment)) {
				unset($fragment[$id_version]);
				// Si le fragment n'est pas trop gros, prolonger celui-ci
				$nouveau = count($fragment) >= $agregation_versions
					&& strlen($row['fragment']) > 1000;
			}
		}
		if ($nouveau) {
			$fragment = array($id_version => $texte);
			$version_min = $id_version;
		}
		else {
			// Ne pas dupliquer les fragments non modifies
			$modif = true;
			for ($i = $id_version - 1; $i >= $version_min; $i--) {
				if (isset($fragment[$i])) {
					$modif = ($fragment[$i] != $texte);
					break;
				}
			}
			if ($modif) $fragment[$id_version] = $texte;
		}

		// Preparer l'enregistrement du fragment
		$replaces[] = replace_fragment($id_objet,$objet, $version_min, $id_version, $id_fragment, $fragment);
	}

	envoi_replace_fragments($replaces);
}

//
// Supprimer tous les fragments d'un objet lies a un intervalle de versions
// (essaie d'eviter une trop grande fragmentation)
//
// http://doc.spip.org/@supprimer_fragments
function supprimer_fragments($id_objet,$objet, $version_debut, $version_fin) {
	global $agregation_versions;

	$replaces = array();
	$deletes = array();

	// D'abord, vider les fragments inutiles
	Sql::delete("spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND version_min>=$version_debut AND version_max<=$version_fin");

	// Fragments chevauchant l'ensemble de l'intervalle, s'ils existent
	$result = Sql::select("id_fragment, compress, fragment, version_min, version_max", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND version_min<$version_debut AND version_max>$version_fin");

	while ($row = Sql::fetch($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_fin; $i >= $version_debut; $i--) {
			if (isset($fragment[$i])) {
				// Recopier le dernier fragment si implicite
				if (!isset($fragment[$version_fin + 1]))
					$fragment[$version_fin + 1] = $fragment[$i];
				unset($fragment[$i]);
			}
		}

		$replaces[] = replace_fragment($id_objet,$objet,
			$row['version_min'], $row['version_max'], $id_fragment, $fragment);
	}

	// Fragments chevauchant le debut de l'intervalle, s'ils existent
	$result = Sql::select("id_fragment, compress, fragment, version_min, version_max", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND version_min<$version_debut AND version_max>=$version_debut AND version_max<=$version_fin");

	$deb_fragment = array();
	while ($row = Sql::fetch($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		$version_min = $row['version_min'];
		$version_max = $row['version_max'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_debut; $i <= $version_max; $i++) {
			if (isset($fragment[$i])) unset($fragment[$i]);
		}

		// Stocker temporairement le fragment pour agregation
		$deb_fragment[$id_fragment] = $fragment;
		// Ajuster l'intervalle des versions
		$deb_version_min[$id_fragment] = $version_min;
		$deb_version_max[$id_fragment] = $version_debut - 1;
	}

	// Fragments chevauchant la fin de l'intervalle, s'ils existent
	$result = Sql::select("id_fragment, compress, fragment, version_min, version_max", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND version_max>$version_fin AND version_min>=$version_debut AND version_min<=$version_fin");

	while ($row = Sql::fetch($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		$version_min = $row['version_min'];
		$version_max = $row['version_max'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_fin; $i >= $version_min; $i--) {
			if (isset($fragment[$i])) {
				// Recopier le dernier fragment si implicite
				if (!isset($fragment[$version_fin + 1]))
					$fragment[$version_fin + 1] = $fragment[$i];
				unset($fragment[$i]);
			}
		}

		// Virer l'ancien enregistrement (la cle primaire va changer)
		$deletes[] = "id_fragment=$id_fragment AND version_min=$version_min";
		// Essayer l'agregation
		$agreger = false;
		if (isset($deb_fragment[$id_fragment])) {
			$agreger = (count($deb_fragment[$id_fragment]) + count($fragment) <= $agregation_versions);
			if ($agreger) {
				$fragment = $deb_fragment[$id_fragment] + $fragment;
				$version_min = $deb_version_min[$id_fragment];
			}
			else {
				$replaces[] = replace_fragment($id_objet,$objet,
					$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
					$id_fragment, $deb_fragment[$id_fragment]);
			}
			unset($deb_fragment[$id_fragment]);
		}
		if (!$agreger) {
			// Ajuster l'intervalle des versions
			$version_min = $version_fin + 1;
		}
		$replaces[] = replace_fragment($id_objet, $objet, $version_min, $version_max, $id_fragment, $fragment);
	}

	// Ajouter fragments restants
	if (is_array($deb_fragment) && count($deb_fragment) > 0) {
		foreach ($deb_fragment as $id_fragment => $fragment) {
			$replaces[] = replace_fragment($id_objet,$objet,
				$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
				$id_fragment, $deb_fragment[$id_fragment]);
		}
	}

	envoi_replace_fragments($replaces);
	envoi_delete_fragments($id_objet,$objet, $deletes);
}

//
// Recuperer les fragments d'une version donnee
// renvoie un tableau associatif (id_fragment => texte)
//
// http://doc.spip.org/@recuperer_fragments
function recuperer_fragments($id_objet,$objet, $id_version) {
	$fragments = array();

	if ($id_version == 0) return array();

	$result = Sql::select("id_fragment, version_min, version_max, compress, fragment", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND version_min<=$id_version AND version_max>=$id_version");

	while ($row = Sql::fetch($result)) {
		$id_fragment = $row['id_fragment'];
		$version_min = $row['version_min'];
		$fragment = $row['fragment'];
		if ($row['compress'] > 0){
			$fragment_ = @gzuncompress($fragment);
			if (strlen($fragment) && $fragment_===false)
				$fragment=serialize(array($row['version_max']=>"["._T('forum_titre_erreur').$id_fragment."]"));
			else
			 $fragment = $fragment_;
		}
		$fragment_ = unserialize($fragment);
		if (strlen($fragment) && $fragment_===false)
			$fragment=array($row['version_max']=>"["._T('forum_titre_erreur').$id_fragment."]");
		else
		 $fragment = $fragment_;
		for ($i = $id_version; $i >= $version_min; $i--) {
			if (isset($fragment[$i])) {

				## hack destine a sauver les archives des sites iso-8859-1
				## convertis en utf-8 (les archives ne sont pas converties
				## mais ce code va les nettoyer ; pour les autres charsets
				## la situation n'est pas meilleure ni pire qu'avant)
				if ($GLOBALS['meta']['charset'] == 'utf-8'
				AND include_spip('inc/charsets')
				AND !is_utf8($fragment[$i])) {
					$fragment[$i] = importer_charset($fragment[$i], 'iso-8859-1');
				}

				$fragments[$id_fragment] = $fragment[$i];
				break;
			}
		}
	}
	return $fragments;
}


//
// Apparier des paragraphes deux a deux entre une version originale
// et une version modifiee
//
// http://doc.spip.org/@apparier_paras
function apparier_paras($src, $dest, $flou = true) {
	$src_dest = array();
	$dest_src = array();

	$t1 = $t2 = array();

	$md1 = $md2 = array();
	$gz_min1 = $gz_min2 = array();
	$gz_trans1 = $gz_trans2 = array();
	$l1 = $l2 = array();

	// Nettoyage de la ponctuation pour faciliter l'appariement
	foreach($src as $key => $val) {
		$t1[$key] = strval(preg_replace("/[[:punct:][:space:]]+/", " ", $val));
	}
	foreach($dest as $key => $val) {
		$t2[$key] = strval(preg_replace("/[[:punct:][:space:]]+/", " ", $val));
	}

	// Premiere passe : chercher les correspondance exactes
	foreach($t1 as $key => $val) $md1[$key] = md5($val);
	foreach($t2 as $key => $val) $md2[md5($val)][$key] = $key;
	foreach($md1 as $key1 => $h) {
		if (isset($md2[$h])) {
			$key2 = reset($md2[$h]);
			if (isset($t1[$key1]) AND isset($t2[$key2]) AND $t1[$key1] == $t2[$key2]) {
				$src_dest[$key1] = $key2;
				$dest_src[$key2] = $key1;
				unset($t1[$key1]);
				unset($t2[$key2]);
				unset($md2[$h][$key2]);
			}
		}
	}

	if ($flou) {
		// Deuxieme passe : recherche de correlation par test de compressibilite
		foreach($t1 as $key => $val) {
			$l1[$key] = strlen(gzcompress($val));
		}
		foreach($t2 as $key => $val) {
			$l2[$key] = strlen(gzcompress($val));
		}
		foreach($t1 as $key1 => $s1) {
			foreach($t2 as $key2 => $s2) {
				$r = strlen(gzcompress($s1.$s2));
				$taux = 1.0 * $r / ($l1[$key1] + $l2[$key2]);
				if (!isset($gz_min1[$key1]) || !$gz_min1[$key1] || $gz_min1[$key1] > $taux) {
					$gz_min1[$key1] = $taux;
					$gz_trans1[$key1] = $key2;
				}
				if (!isset($gz_min2[$key2]) ||!$gz_min2[$key2] || $gz_min2[$key2] > $taux) {
					$gz_min2[$key2] = $taux;
					$gz_trans2[$key2] = $key1;
				}
			}
		}

		// Depouiller les resultats de la deuxieme passe :
		// ne retenir que les correlations reciproques
		foreach($gz_trans1 as $key1 => $key2) {
			if ($gz_trans2[$key2] == $key1 && $gz_min1[$key1] < 0.9) {
				$src_dest[$key1] = $key2;
				$dest_src[$key2] = $key1;
			}
		}
	}

	// Retourner les mappings
	return array($src_dest, $dest_src);
}

//
// Recuperer les champs d'une version donnee
//
// http://doc.spip.org/@recuperer_version
function recuperer_version($id_objet,$objet, $id_version) {

	$champs = Sql::getfetsel("champs", "spip_versions", "id_objet=" . intval($id_objet) . " AND objet=".Sql::quote($objet)." AND id_version=" . intval($id_version));
	if (!$champs OR !is_array($champs = unserialize($champs)))
		return array();
	else return reconstuire_version($champs,
			 recuperer_fragments($id_objet,$objet, $id_version));
}

// http://doc.spip.org/@reconstuire_version
function reconstuire_version($champs, $fragments, $res=array()) {

	static $msg;
	if (!$msg) $msg = _T('forum_titre_erreur');

	foreach ($champs as $nom => $code) {
		if (!isset($res[$nom])) {
			$t = '';
			foreach (array_filter(explode(' ', $code)) as $id) {
				$t .= isset($fragments[$id])
					? $fragments[$id]
					: "[$msg$id]";
			}
			$res[$nom] = $t;
		}
	}
	return $res;
}

// http://doc.spip.org/@supprimer_versions
function supprimer_versions($id_objet,$objet, $version_min, $version_max) {
	Sql::delete("spip_versions", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version>=$version_min AND id_version<=$version_max");

	supprimer_fragments($id_objet,$objet, $version_min, $version_max);
}

//
// Ajouter une version a un objet
//
// http://doc.spip.org/@ajouter_version
function ajouter_version($id_objet,$objet, $champs, $titre_version = "", $id_auteur) {
	$paras = $paras_old = $paras_champ = $fragments = array();

	// Attention a une edition anonyme (type wiki): id_auteur n'est pas
	// definie, on enregistre alors le numero IP
	$str_auteur = intval($id_auteur) ? intval($id_auteur) : $GLOBALS['ip'];
	// si pas de titre dans cette version, la marquer 'non' permanente,
	// et elle pourra etre fusionnee avec une revision ulterieure dans un delai < _INTERVALLE_REVISIONS
	// permet de fusionner plusieurs editions consecutives champs par champs avec les crayons
	$permanent = empty($titre_version) ? 'non' : '';

	// Detruire les tentatives d'archivages non abouties en 1 heure
	Sql::delete('spip_versions', "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version <= 0 AND date < DATE_SUB(".Sql::quote(date('Y-m-d H:i:s')).", INTERVAL "._INTERVALLE_REVISIONS." SECOND)");

	// Signaler qu'on opere en mettant un numero de version negatif
	// distinctif (pour eviter la violation d'unicite)
	// et un titre contenant en fait le moment de l'insertion
	list($ms, $sec) = explode(' ', microtime());
	$date = $sec . substr($ms,1,4); // SQL ne ramene que 4 chiffres significatifs apres la virgule pour 0.0+titre_version
	$datediff = ($sec - mktime(0,0,0,9,1,2007)) * 1000000 + substr($ms,2, strlen($ms)-4);

	$valeurs = array('id_objet' => $id_objet,
			 'objet' => $objet,
			 'id_version' => (0 - $datediff),
			 'date' => date('Y-m-d H:i:s'),
			 'id_auteur' => $str_auteur, //  varchar ici!
			 'titre_version' => $date);
	Sql::insertq('spip_versions',  $valeurs);

	// Eviter les validations entremelees en s'endormant s'il existe
	// une version <0 plus recente mais pas plus vieille que 10s
	// Une <0 encore plus vieille est une operation avortee,
	// on passe outre (vaut mieux archiver mal que pas du tout).
	// Pour tester:
	// 0. mettre le delai a 30
	// 1. decommenter le premier sleep(15)
	// 2. enregistrer une modif
	// 3. recommenter le premier sleep(15), decommenter le second.
	// 4. enregistrer une autre modif dans les 15 secondes
# 	  sleep(15);
	$delai = $sec-10;
	while (Sql::countsel('spip_versions', "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version < 0 AND 0.0+titre_version < $date AND 0.0+titre_version > $delai")) {
		spip_log("version $objet $id_objet :insertion en cours avant $date ($delai)");
		sleep(1);
		$delai++;
	}
#   sleep(15); 	spip_log("sortie $sec $delai");
	// Determiner le numero du prochain fragment
	$next = Sql::fetsel("id_fragment", "spip_versions_fragments", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet), "", "id_fragment DESC", "1");

	$onlylock = '';

	// Examiner la derniere version
	$row = Sql::fetsel("id_version, champs, id_auteur, date, permanent", "spip_versions", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version > 0", '', "id_version DESC", "1"); // le champ id_auteur est un varchar dans cette table

	if ($row) {
		$id_version = $row['id_version'];
		$paras_old = recuperer_fragments($id_objet,$objet, $id_version);
		$champs_old = $row['champs'];
		if ($row['id_auteur']!= $str_auteur
		  OR $row['permanent']!='non'
		  OR strtotime($row['date']) < (time()-_INTERVALLE_REVISIONS)) {
			spip_log(strtotime($row['date']), 'revisions');
			spip_log(time(), 'revisions');
			spip_log(_INTERVALLE_REVISIONS, 'revisions');
			$id_version++;
		}
		// version precedente recente, on va la mettre a jour
		// avec les nouveaux arrivants si presents
		else {
			$champs = reconstuire_version(unserialize($champs_old), $paras_old, $champs);
			$onlylock = 're';
		}
	} else
		$id_version = 1;

	spip_log($str_auteur, 'revisions');
	spip_log($row, 'revisions');
	spip_log($id_version, 'revisions');

	$next = !$next ? 1 : ($next['id_fragment'] + 1);

	// Generer les nouveaux fragments
	$codes = array();
	foreach ($champs as $nom => $texte) {
		$codes[$nom] = array();
		$paras = separer_paras($texte, $paras);
		$paras_champ[$nom] = count($paras);
	}

	// Apparier les fragments de maniere optimale
	$n = count($paras);
	if ($n) {
		// Tables d'appariement dans les deux sens
		list(,$trans) = apparier_paras($paras_old, $paras);
		reset($champs);
		$nom = '';

		// eviter une notice PHP au tout debut de la boucle
		// on ajoute ''=>0 en debut de tableau.
		$paras_champ = array($nom=>0) + $paras_champ;

		for ($i = 0; $i < $n; $i++) {
			while ($i >= $paras_champ[$nom]) list($nom, ) = each($champs);
			// Lier au fragment existant si possible, sinon creer un nouveau fragment
			$id_fragment = isset($trans[$i]) ? $trans[$i] : $next++;
			$codes[$nom][] = $id_fragment;
			$fragments[$id_fragment] = $paras[$i];
		}
	}
	foreach ($champs as $nom => $t) {
		$codes[$nom] = join(' ', $codes[$nom]);
		# avec la ligne qui suit, un champ qu'on vide ne s'enregistre pas
		# if (!strlen($codes[$nom])) unset($codes[$nom]);
	}

	// Enregistrer les modifications
	ajouter_fragments($id_objet,$objet, $id_version, $fragments);

	// Si l'insertion ne servait que de verrou,
	// la detruire apres mise a jour de l'ancienne entree,
	// sinon la mise a jour efface en fait le verrou.

	if (!$onlylock) {
		Sql::updateq('spip_versions', array('id_version'=>$id_version, 'date'=>date('Y-m-d H:i:s'), 'champs'=> serialize($codes), 'permanent'=>$permanent, 'titre_version'=> $titre_version), "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version < 0 AND titre_version='$date'");
	} else {
		Sql::updateq('spip_versions', array('date'=>date('Y-m-d H:i:s'), 'champs'=>serialize($codes), 'permanent'=>$permanent, 'titre_version'=> $titre_version), "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version=$id_version");

		Sql::delete("spip_versions", "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version < 0 AND titre_version ='$date'");
	}
	spip_log($onlylock . "memorise la version $id_version de l'objet $objet $id_objet $titre_version");

	return $id_version;
}

// les textes "diff" ne peuvent pas passer dans propre directement,
// car ils contiennent des <span> et <div> parfois mal places
// http://doc.spip.org/@propre_diff
function propre_diff($texte) {

	$span_diff = array();
	if (preg_match_all(',<(/)?(span|div) (class|rem)="diff-[^>]*>,', $texte, $regs, PREG_SET_ORDER)) {
		$regs = array_slice($regs,0,500); #limiter la casse s'il y en a trop
		foreach ($regs as $c => $reg) {
			$texte = str_replace($reg[0], '@@@SPIP_DIFF'.$c.'@@@', $texte);
		}
	}

	// [ ...<span diff> -> lien ]
	// < tag <span diff> >
	$texte = preg_replace(',<([^>]*?@@@SPIP_DIFF[0-9]+@@@),',
		'&lt;\1', $texte);

	# attention ici astuce seulement deux @@ finals car on doit eviter
	# deux patterns a suivre, afin de pouvoir prendre [ mais eviter [[
	$texte = preg_replace(',(^|[^[])[[]([^[\]]*@@@SPIP_DIFF[0-9]+@@),',
		'\1&#91;\2', $texte);

	// desactiver TeX & toujours-paragrapher
	$tex = $GLOBALS['traiter_math'];
	$GLOBALS['traiter_math'] = '';
	$mem = $GLOBALS['toujours_paragrapher'];
	$GLOBALS['toujours_paragrapher'] = false;

	$texte = propre($texte);

	// retablir
	$GLOBALS['traiter_math'] = $tex;
	$GLOBALS['toujours_paragrapher'] = $mem;

	// un blockquote mal ferme peut gener l'affichage, et title plante safari
	$texte = preg_replace(',<(/?(blockquote|title)[^>]*)>,i', '&lt;\1>', $texte);

	// Dans les <cadre> c'est un peu plus complique
	if (preg_match_all(',<textarea (.*)</textarea>,Uims', $texte, $area, PREG_SET_ORDER)) {
		foreach ($area as $reg) {
			$remplace = preg_replace(',@@@SPIP_DIFF[0-9]+@@@,', '**', $reg[0]);
			if ($remplace <> $reg[0])
				$texte = str_replace($reg[0], $remplace, $texte);
		}
	}

	// replacer les valeurs des <span> et <div> diff-
	if (is_array($regs))
	foreach ($regs as $c => $reg) {
		$bal = (!$reg[1]) ? $reg[0] : "</$reg[2]>";
		$texte = str_replace('@@@SPIP_DIFF'.$c.'@@@', $bal, $texte);
		$GLOBALS['les_notes'] = str_replace('@@@SPIP_DIFF'.$c.'@@@', $bal, $GLOBALS['les_notes']);
	}


	// quand le dernier tag est ouvrant le refermer ...
	$reg = end($regs);
	if (!$reg[1] AND $reg[2]) $texte.="</$reg[2]>";

	return $texte;
}


/**
 * Liste les champs versionnés d'une table objet.
 * 
 * @param string $table
 *     Nom complet de sa table sql. Exemple 'spip_articles'
 * @return array
 *     Liste des champs versionnés
 */
function liste_champs_versionnes($table) {
	$liste_objets_versionnees = is_array(unserialize($GLOBALS['meta']['objets_versions'])) ? unserialize($GLOBALS['meta']['objets_versions']) : array();

	if (!in_array($table,$liste_objets_versionnees))
		return array();

	include_spip('base/objets');
	if ($infos=lister_tables_objets_sql($table)
	  AND isset($infos['champs_versionnes']))
		return $infos['champs_versionnes'];

	return array();
}

/**
 * Lorsqu'un champ versionée est une jointure, récuperer tous les liens
 * et les mettre sous forme de liste énumérée
 * 
 * @param string $objet
 * @param string $id_objet
 * @param string $jointure
 * @return string
 */
function recuperer_valeur_champ_jointure($objet,$id_objet,$jointure){
	$objet_joint = objet_type($jointure);
	include_spip('action/editer_liens');
	$v = array();
	if (objet_associable($objet_joint)) {
		$liens = objet_trouver_liens(array($objet_joint=>'*'),array($objet=>$id_objet));
		foreach($liens as $l)
			$v[] = $l[$objet_joint];
	}
	elseif(objet_associable($objet)) {
		$liens = objet_trouver_liens(array($objet=>$id_objet),array($objet_joint=>'*'));
		foreach($liens as $l)
			$v[] = $l[$objet];
	}
	sort($v);
	return implode(",",$v);
}

/**
 * Créer la première révision d'un objet si nécessaire
 * 
 * À faire notamment si on vient d'activer l'extension et qu'on fait une modif
 * sur un objet qui était déjà en base, mais non versionné
 *
 * La fonction renvoie le numéro de la dernière version de l'objet,
 * et 0 si pas de version pour cet objet
 * 
 * @param string $table
 * @param string $objet
 * @param int $id_objet
 * @param array $champs
 * @param int $id_auteur
 * @return int
 */
function verifier_premiere_revision($table,$objet,$id_objet,$champs=null, $id_auteur=0){

	$id_table_objet = id_table_objet($objet);
	if (!$champs)
		$champs = liste_champs_versionnes($table);
	if (!$champs)
		return false;

	if (!$id_version = Sql::getfetsel('id_version','spip_versions',"id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet),'','id_version DESC','0,1')) {
		// recuperer toutes les valeurs actuelles des champs
		// pour l'objet
		$originaux = Sql::fetsel("*", $table, "$id_table_objet=".intval($id_objet));
		$premiere = false;
		foreach($champs as $v){
			if (isset($originaux[$v])){
				$champs_originaux[$v] = $originaux[$v];
			}
			else if(strncmp($v,'jointure_',9)==0) {
				$champs_originaux[$v] = recuperer_valeur_champ_jointure($objet,$id_objet,substr($v,9));
			}
			if (isset($champs_originaux[$v]) AND strlen($originaux[$v]))
				$premiere = true;
		}

		// Si un champ est non vide,
		// il faut creer une premiere revision
		if ($premiere) {
			$trouver_table = charger_fonction('trouver_table','base');
			$desc = $trouver_table($table);

			// "trouver" une date raisonnable pour la version initiale

			$date_modif = "";
			foreach(array('date_modif','maj') as $d){
				if (!$date_modif AND isset($originaux[$d]) AND $t=strtotime($d))
					$date_modif = date("Y-m-d H:i:s", $t);
			}
			if (!$date_modif
			  AND isset($desc['date'])
			  AND isset($originaux[$desc['date']])) {
				$date_modif = $originaux[$desc['date']];
			}
			elseif (!$date_modif)
				$date_modif = date("Y-m-d H:i:s", time()-7200);
			
			if ($id_version = ajouter_version($id_objet, $objet, $champs_originaux, _T('revisions:version_initiale'), $id_auteur))
				Sql::updateq('spip_versions', array('date' => $date_modif), "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet)." AND id_version=$id_version");
		}
	}
	return $id_version;
}

?>
