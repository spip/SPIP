<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_REVISIONS")) return;
define("_ECRIRE_INC_REVISIONS", "1");


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

//
// Ajouter les fragments de la derniere version (tableau associatif id_fragment => texte)
//
function ajouter_fragments($id_article, $id_version, $fragments) {
	global $flag_gz;

	$replaces = array();
	foreach ($fragments as $id_fragment => $texte) {
		$nouveau = true;
		// Recuperer la version la plus recente
		$query = "SELECT compress, fragment, version_min, version_max FROM spip_versions_fragments ".
			"WHERE id_article=$id_article AND id_fragment=$id_fragment AND version_min<=$id_version ".
			"ORDER BY version_min DESC LIMIT 0,1";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$fragment = $row['fragment'];
			$version_min = $row['version_min'];
			if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
			$fragment = unserialize($fragment);
			if (is_array($fragment)) {
				unset($fragment[$id_version]);
				// Si le fragment n'est pas trop gros, prolonger celui-ci
				$nouveau = count($fragment) > 8 && strlen($row['fragment']) > 1000;
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
		$fragment = serialize($fragment);
		$compress = 0;
		$version_max = $id_version;
		if ($flag_gz) {
			$s = gzcompress($fragment);
			if (strlen($s) < strlen($fragment)) {
				//echo "gain gz: ".(100 - 100 * strlen($s) / strlen($fragment))."%<br />";
				$compress = 1;
				$fragment = $s;
			}
		}
		// (attention a bien echapper le $fragment qui est en binaire)
		$replaces[] = "($id_article, $version_min, $version_max, $id_fragment, $compress, '"
			.mysql_escape_string($fragment)."')";
	}

	if (count($replaces)) {
		$query = "REPLACE spip_versions_fragments (id_article, version_min, version_max, id_fragment, compress, fragment) ".
			"VALUES ".join(", ", $replaces);
		spip_query($query);
	}
}

//
// Recuperer les fragments d'une version donnee
// renvoie un tableau associatif (id_fragment => texte)
//
function recuperer_fragments($id_article, $id_version) {
	$id_version = intval($id_version);
	$fragments = array();

	$query = "SELECT id_fragment, version_min, compress, fragment FROM spip_versions_fragments ".
		"WHERE id_article=$id_article AND version_min<=$id_version AND version_max>=$id_version";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$id_fragment = $row['id_fragment'];
		$version_min = $row['version_min'];
		$fragment = $row['fragment'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $id_version; $i >= $version_min; $i--) {
			if (isset($fragment[$i])) {
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
		$t1[$key] = preg_replace("/[[:punct:][:space:]]+/", " ", $val);
	}
	foreach($dest as $key => $val) {
		$t2[$key] = preg_replace("/[[:punct:][:space:]]+/", " ", $val);
	}

	// Premiere passe : chercher les correspondance exactes
	foreach($t1 as $key => $val) $md1[$key] = md5($val);
	foreach($t2 as $key => $val) $md2[md5($val)][$key] = $key;
	foreach($md1 as $key1 => $h) {
		if (count($md2[$h])) {
			$key2 = reset($md2[$h]);
			if ($t1[$key1] == $t2[$key2]) {
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
				if (!$gz_min1[$key1] || $gz_min1[$key1] > $taux) {
					$gz_min1[$key1] = $taux;
					$gz_trans1[$key1] = $key2;
				}
				if (!$gz_min2[$key2] || $gz_min2[$key2] > $taux) {
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
function recuperer_version($id_article, $id_version) {
	if (!$id_version) $id_version = '0';
	$query = "SELECT champs FROM spip_versions ".
		"WHERE id_article=$id_article AND id_version=$id_version";
	$result = spip_query($query);
	
	if (!($row = spip_fetch_array($result))) return false;

	$fragments = recuperer_fragments($id_article, $id_version);
	$champs = unserialize($row['champs']);
	$textes = array();
	foreach ($champs as $nom_champ => $code) {
		$textes[$nom_champ] = "";
		$code = explode(' ', $code);
		foreach ($code as $id_fragment) {
			$textes[$nom_champ] .= $fragments[$id_fragment];
		}
	}
	return $textes;
}

//
// Ajouter une version a un article
//
function ajouter_version($id_article, $champs) {
	global $connect_id_auteur;

	// Eviter les validations entremelees
	$lock = "ajout_version $id_article";
	spip_get_lock($lock, 10);
	
	// Examiner la derniere version
	$query = "SELECT id_version, (id_auteur=$connect_id_auteur AND date > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND permanent!='oui') AS flag ".
		"FROM spip_versions WHERE id_article=$id_article ".
		"ORDER BY id_version DESC LIMIT 0,1";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$nouveau = !$row['flag'];
		$id_version = $row['id_version'];
		if ($nouveau) $id_version_new = $id_version + 1;
		else $id_version_new = $id_version;
	}
	else {
		$nouveau = true;
		$id_version_new = 1;
	}
	$query = "SELECT id_fragment FROM spip_versions_fragments ".
		"WHERE id_article=$id_article ORDER BY id_fragment DESC LIMIT 0,1";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result))
		$id_fragment_next = $row['id_fragment'] + 1;
	else
		$id_fragment_next = 1;

	// Generer les nouveaux fragments
	$fragments = array();
	$paras_old = recuperer_fragments($id_article, $id_version);
	$paras_new = $paras_champ = array();
	foreach ($champs as $nom_champ => $texte) {
		$codes[$nom_champ] = array();
		$paras_new = separer_paras($texte, $paras_new);
		$paras_champ[$nom_champ] = count($paras_new);
	}

	// Apparier les fragments de maniere optimale
	$n = count($paras_new);
	if ($n) {
		// Tables d'appariement dans les deux sens
		list($trans, $trans_rev) = apparier_paras($paras_old, $paras_new);
		reset($champs);
		$nom_champ = '';
		for ($i = 0; $i < $n; $i++) {
			while ($i >= $paras_champ[$nom_champ]) list($nom_champ, ) = each($champs);
			// Lier au fragment existant si possible, sinon creer un nouveau fragment
			if (isset($trans_rev[$i])) $id_fragment = $trans_rev[$i];
			else $id_fragment = $id_fragment_next++;
			$codes[$nom_champ][] = $id_fragment;
			$fragments[$id_fragment] = $paras_new[$i];
		}
	}
	foreach ($champs as $nom_champ => $t) {
		$codes[$nom_champ] = join(' ', $codes[$nom_champ]);
		if (!strlen($codes[$nom_champ])) unset($codes[$nom_champ]);
	}

	// Enregistrer les modifications
	ajouter_fragments($id_article, $id_version_new, $fragments);
	if (!$codes) $codes = array();
	$codes = addslashes(serialize($codes));
	if ($nouveau) {
		$query = "INSERT spip_versions (id_article, id_version, permanent, date, id_auteur, champs) ".
			"VALUES ($id_article, $id_version_new, 'non', NOW(), '$connect_id_auteur', '$codes')";
		spip_query($query);
	}
	else {
		$query = "UPDATE spip_versions SET date=NOW(), id_auteur=$connect_id_auteur, champs='$codes' ".
		 	"WHERE id_article=$id_article AND id_version=$id_version";
		spip_query($query);
	}
	$query = "UPDATE spip_articles SET id_version=$id_version_new WHERE id_article=$id_article";
	spip_query($query);

	spip_release_lock($lock);

	return $id_version_new;
}


?>
