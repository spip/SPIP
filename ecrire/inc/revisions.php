<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

$GLOBALS['agregation_versions'] = 10;

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
function replace_fragment($id_article, $version_min, $version_max, $id_fragment, $fragment) {
	global $flag_gz;

	$fragment = serialize($fragment);
	$compress = 0;
	/* pour le portage en PG il faut l'equivalente au mysql_escape_string
	 et deporter son appel dans les fonctions d'abstraction. A revoir.
		if ($flag_gz) {
		$s = gzcompress($fragment);
		if (strlen($s) < strlen($fragment)) {
			//spip_log("gain gz: ".(100 - 100 * strlen($s) / strlen($fragment)));
			$compress = 1;
			$fragment = $s;
		}
	// Attention a bien echapper le $fragment qui est en binaire
		} */

	return array(
		     'id_article' => intval($id_article),
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
		sql_replace('spip_versions_fragments', $r, $desc);
}


// http://doc.spip.org/@envoi_delete_fragments
function envoi_delete_fragments($id_article, $deletes) {
	if (count($deletes)) {
		spip_query("DELETE FROM spip_versions_fragments WHERE id_article=$id_article AND ((".	join(") OR (", $deletes)."))");
	}
}


//
// Ajouter les fragments de la derniere version (tableau associatif id_fragment => texte)
//
// http://doc.spip.org/@ajouter_fragments
function ajouter_fragments($id_article, $id_version, $fragments) {
	global $flag_gz, $agregation_versions;

	$replaces = array();
	foreach ($fragments as $id_fragment => $texte) {
		$nouveau = true;
		// Recuperer la version la plus recente
		$result = spip_query("SELECT compress, fragment, version_min, version_max FROM spip_versions_fragments WHERE id_article=$id_article AND id_fragment=$id_fragment AND version_min<=$id_version ORDER BY version_min DESC LIMIT 1");

		if ($row = sql_fetch($result)) {
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
		$replaces[] = replace_fragment($id_article, $version_min, $id_version, $id_fragment, $fragment);
	}

	envoi_replace_fragments($replaces);
}

//
// Supprimer tous les fragments d'un article lies a un intervalle de versions
// (essaie d'eviter une trop grande fragmentation)
//
// http://doc.spip.org/@supprimer_fragments
function supprimer_fragments($id_article, $version_debut, $version_fin) {
	global $flag_gz, $agregation_versions;

	$replaces = array();
	$deletes = array();

	// D'abord, vider les fragments inutiles
	spip_query("DELETE FROM spip_versions_fragments WHERE id_article=$id_article AND version_min>=$version_debut AND version_max<=$version_fin");


	// Fragments chevauchant l'ensemble de l'intervalle, s'ils existent
	$result = spip_query("SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments WHERE id_article=$id_article AND version_min<$version_debut AND version_max>$version_fin");

	while ($row = sql_fetch($result)) {
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

		$replaces[] = replace_fragment($id_article,
			$row['version_min'], $row['version_max'], $id_fragment, $fragment);
	}

	// Fragments chevauchant le debut de l'intervalle, s'ils existent
	$result = spip_query("SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments WHERE id_article=$id_article AND version_min<$version_debut AND version_max>=$version_debut AND version_max<=$version_fin");

	$deb_fragment = array();
	while ($row = sql_fetch($result)) {
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
	$result = spip_query("SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments WHERE id_article=$id_article AND version_max>$version_fin AND version_min>=$version_debut AND version_min<=$version_fin");

	while ($row = sql_fetch($result)) {
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
				$replaces[] = replace_fragment($id_article,
					$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
					$id_fragment, $deb_fragment[$id_fragment]);
			}
			unset($deb_fragment[$id_fragment]);
		}
		if (!$agreger) {
			// Ajuster l'intervalle des versions
			$version_min = $version_fin + 1;
		}
		$replaces[] = replace_fragment($id_article, $version_min, $version_max, $id_fragment, $fragment);
	}

	// Ajouter fragments restants
	if (is_array($deb_fragment) && count($deb_fragment) > 0) {
		foreach ($deb_fragment as $id_fragment => $fragment) {
			$replaces[] = replace_fragment($id_article,
				$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
				$id_fragment, $deb_fragment[$id_fragment]);
		}
	}
	
	envoi_replace_fragments($replaces);
	envoi_delete_fragments($id_article, $deletes);
}

//
// Recuperer les fragments d'une version donnee
// renvoie un tableau associatif (id_fragment => texte)
//
// http://doc.spip.org/@recuperer_fragments
function recuperer_fragments($id_article, $id_version) {
	$fragments = array();

	if ($id_version == 0) return array();

	$result = spip_query("SELECT id_fragment, version_min, version_max, compress, fragment FROM spip_versions_fragments WHERE id_article=$id_article AND version_min<=$id_version AND version_max>=$id_version");

	while ($row = sql_fetch($result)) {
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
				AND !is_utf8($fragment[$i])) {
					include_spip('inc/charsets');
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
// http://doc.spip.org/@recuperer_version
function recuperer_version($id_article, $id_version) {

	$row = sql_fetch(spip_query("SELECT champs FROM spip_versions WHERE id_article=$id_article AND id_version=$id_version"));
	if (!$row OR !is_array($champs = unserialize($row['champs'])))
		return array();
	else return reconstuire_version($champs,
			 recuperer_fragments($id_article, $id_version));
}

// http://doc.spip.org/@reconstuire_version
function reconstuire_version($champs, $fragments, $res=array()) {

	static $msg;
	if (!$msg) $msg = _T('forum_titre_erreur');

	foreach ($champs as $nom => $code) {
		if (!isset($res[$nom])) {
			$t = '';
			foreach (explode(' ', $code) as $id) {
				$t .= isset($fragments[$id])?$fragments[$id]:"[$msg$id]";
			}
			$res[$nom] = $t;
		}
	}
	return $res;
}

// http://doc.spip.org/@supprimer_versions
function supprimer_versions($id_article, $version_min, $version_max) {
	spip_query("DELETE FROM spip_versions WHERE id_article=$id_article AND id_version>=$version_min AND id_version<=$version_max");

	supprimer_fragments($id_article, $version_min, $version_max);
}

//
// Ajouter une version a un article
//
// http://doc.spip.org/@ajouter_version
function ajouter_version($id_article, $champs, $titre_version = "", $id_auteur) {
	$paras = $paras_old = $paras_champ = $fragments = array();

	// Attention a une edition anonyme (type wiki): id_auteur n'est pas
	// definie, on enregistre alors le numero IP

	$str_auteur = intval($id_auteur) ? intval($id_auteur) : $GLOBALS['ip'];
	$permanent = empty($titre_version) ? 'non' : 'oui';
	$id_version = 0;

	// Eviter les validations entremelees
	$lock = "ajout_version $id_article";
	spip_get_lock($lock, 10);

	// Determiner le numero du prochain fragment
	$next = sql_fetch(spip_query("SELECT id_fragment FROM spip_versions_fragments WHERE id_article=$id_article ORDER BY id_fragment DESC LIMIT 1"));

	// Examiner la derniere version
	$result = sql_select("id_version, champs, (id_auteur='$str_auteur' AND date > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND permanent!='oui') AS flag", "spip_versions", "id_article=$id_article", '', "id_version DESC", "1"); // le champ id_auteur est un varchar dans cette table

	if ($row = sql_fetch($result)) {
		$id_version = $row['id_version'];
		$paras_old = recuperer_fragments($id_article, $id_version);
		$champs_old = $row['champs'];
		$row = $row['flag'];
	}
	if (!$row) {
		$id_version++;
		sql_insert('spip_versions', '(id_article, id_version, id_auteur)', "($id_article, $id_version, '$str_auteur')"); // le champ id_auteur est un varchar dans cette table
	}
	  // version precedente recente, on va la mettre a jour 
	  // avec les nouveaux arrivants si presents
	else $champs = reconstuire_version(unserialize($champs_old), $paras_old, $champs);

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
		if (!strlen($codes[$nom])) unset($codes[$nom]);
	}

	// Enregistrer les modifications
	ajouter_fragments($id_article, $id_version, $fragments);

	spip_query("UPDATE spip_versions SET date=NOW(), champs=" . _q(serialize($codes)) . ", permanent='$permanent', titre_version=" . _q($titre_version) . " WHERE id_article=$id_article AND id_version=$id_version");

	spip_query("UPDATE spip_articles SET id_version=$id_version WHERE id_article=$id_article");

	spip_release_lock($lock);

	spip_log("memorise la version $id_version de l'article $id_article $titre_version");

	return $id_version;
}

// les textes "diff" ne peuvent pas passer dans propre directement,
// car ils contiennent des <span> et <div> parfois mal places
// http://doc.spip.org/@propre_diff
function propre_diff($texte) {

	$span_diff = array();
	if (preg_match_all(',<(/)?(span|div) (class|rem)="diff-[^>]*>,', $texte, $regs, PREG_SET_ORDER)) {
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
		$GLOBALS['les_notes'] = str_replace('@@@SPIP_DIFF'.$c.'@@@', $$bal, $GLOBALS['les_notes']);
	}
	// quand le dernier tag est ouvrant le refermer ...
	$reg = end($regs);
	if (!$reg[1]) $texte.="</$reg[2]>";

	return $texte;
}


// liste les champs versionnes d'un objet
// http://doc.spip.org/@liste_champs_versionnes
function liste_champs_versionnes($table) {
	if ($table == 'spip_articles')
		return array('surtitre', 'titre', 'soustitre', 'descriptif',
		'nom_site', 'url_site', 'chapo', 'texte', 'ps');
	else
		return array();
}

// http://doc.spip.org/@enregistrer_premiere_revision
function enregistrer_premiere_revision($x) {

	if  ($GLOBALS['meta']["articles_versions"]=='oui'
	AND $x['args']['table'] == 'spip_articles') {

		$id_article = $x['args']['id_objet'];

		$query = spip_query("SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1");
		if (!spip_num_rows($query)) {
			$select = join(", ", liste_champs_versionnes($x['args']['table']));
			$query = spip_query("SELECT $select, date, date_modif FROM spip_articles WHERE id_article=$id_article");
			$champs_originaux = sql_fetch($query);
			// Si le titre est vide, c'est qu'on vient de creer l'article
			if ($champs_originaux['titre'] != '') {
				$date_modif = $champs_originaux['date_modif'];
				$date = $champs_originaux['date'];
				unset ($champs_originaux['date_modif']);
				unset ($champs_originaux['date']);
				$id_version = ajouter_version($id_article, $champs_originaux,
					_T('version_initiale'), 0);
				// Inventer une date raisonnable pour la version initiale
				if ($date_modif>'1970-')
					$date_modif = strtotime($date_modif);
				else if ($date>'1970-')
					$date_modif = strtotime($date);
				else
					$date_modif = time()-7200;
				spip_query("UPDATE spip_versions SET date=FROM_UNIXTIME($date_modif) WHERE id_article=$id_article AND id_version=$id_version");
			}
		}
	}
	return $x;
}


// http://doc.spip.org/@enregistrer_nouvelle_revision
function enregistrer_nouvelle_revision($x) {
	if  ($GLOBALS['meta']["articles_versions"]=='oui'
	AND $x['args']['table'] == 'spip_articles') {

		$champs = array();
		foreach (liste_champs_versionnes($x['args']['table']) as $key)
			if (isset($x['data'][$key]))
				$champs[$key] = $x['data'][$key];

		if (count($champs))
			ajouter_version($x['args']['id_objet'], $champs, '', $GLOBALS['auteur_session']['id_auteur']);
	}

	return $x;
}

?>
