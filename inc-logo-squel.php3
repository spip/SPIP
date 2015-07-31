<?php


# Fonctions de traitement de champs Spip homonymes de champs SQL
# mais non e'quivalent


// #EXTRA [(#EXTRA|isbn)]
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc_extra.php3
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		include_ecrire("inc_extra.php3");
		list ($key, $champ_extra) = each($p->fonctions);	// le premier filtre
		$type_extra = $p->boucles[$p->id_boucle]->type_requete;
			// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
			// depuis un sous-objet sans champ extra d'un objet a champ extra,
			// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ_extra)) {
			unset($p->fonctions[$key]);
			$p->code = "extra($p->code, '".addslashes($champ_extra)."')";

			// Appliquer les filtres definis par le webmestre
			$filtres = extra_filtres($type_extra, $champ_extra);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		}
	}

	$p->type = 'html';
	return $p;
}


// #LANG
// non documente ?
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	$p->type = 'php';
	return $p;
}


// #LESAUTEURS
// les auteurs d'un article (ou d'un article syndique)
// http://www.spip.net/fr_article902.html
// http://www.spip.net/fr_article911.html
function balise_LESAUTEURS_dist ($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p);

	// Si le champ n'existe pas (cas de spip_articles), on donne la
	// construction speciale sql_auteurs(id_article) ;
	// dans le cas contraire on prend le champ 'les_auteurs' (cas de
	// spip_syndic_articles)
	if ($_lesauteurs AND $_lesauteurs != '$Pile[0][lesauteurs]') {
		$p->code = $_lesauteurs;
	} else {
		$_id_article = champ_sql('id_article', $p);
		$p->code = "sql_auteurs($_id_article)";
	}

	$p->type = 'html';
	return $p;
}


// #PETITION
// Champ testant la presence d'une petition
// non documente ???
function balise_PETITION_dist ($p) {
	$_id_article = champ_sql('id_article', $p);
	$p->code = 'sql_petitions($_id_article)';
	$p->type = 'php';
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + lire_meta('popularite_max'))))";
	$p->type = 'php';
	return $p;
}


# Fonction commune aux logos (rubriques, articles...)

function calculer_champ_LOGO($p)
{
  ereg("^LOGO_(([a-zA-Z]+).*)$", $p->nom_champ, $regs);
  $type_logo = $regs[1];
  $type_objet = strtolower($regs[2]);
  $flag_fichier = 0;  // compatibilite ascendante
  $filtres = '';
  if (is_array($p->fonctions)) {
    foreach($p->fonctions as $nom) {
      if (ereg('^(left|right|center|top|bottom)$', $nom))
	$align = $nom;
      else if ($nom == 'lien') {
	$flag_lien_auto = 'oui';
	$flag_stop = true;
      }
      else if ($nom == 'fichier') {
	$flag_fichier = 1;
	$flag_stop = true;
      }
      else if ($nom == '')	// double || signifie "on passe aux filtres"
	$flag_stop = true;
      else if (!$flag_stop) {
	$lien = $nom;
	$flag_stop = true;
      }
      else // apres un URL ou || ou |fichier ce sont des filtres (sauf left...lien...fichier)
	{
	$filtres[] = $nom;
	}
    }
    // recuperer les filtres s'il y en a
    $p->fonctions = $filtres;
  }
  if ($flag_lien_auto && !$lien) {
    $p->entete .= "\n\t\$lien = generer_url_$type_objet(" .
      champ_sql("id_$type_objet", $p) .
      ");\n";
  }
  else
    {
      $p->entete .= "\n\t\$lien = ";
      $a = $lien;
      while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $a, $match))
	{
	  list($c,$m) = calculer_champ(array(), $match[2], $p->id_boucle, $p->boucles, $p->id_mere);
	  // $m est nul dans les cas pre'vus
	  $p->entete .= ((!$match[1]) ? "" :"'$match[1]' .") . " $c .";
	  $a = $match[3];
	}
      if ($a) $p->entete .= "'$lien';"; 
      else 
	{
	  if ($lien) $p->entete = substr($p->entete,1,-1) .";";
	  else $p->entete .= "'';";
	}
    }
  
  if ($type_logo == 'RUBRIQUE') {
    $p->entete .= '
			list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'RUBRIQUE_NORMAL') {
    $p->entete .= '
			list($logon,) = image_rubrique(' .
      champ_sql('id_rubrique', $p) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'RUBRIQUE_SURVOL') {
    $p->entete .= '
			list(,$logon) = image_rubrique(' .
      champ_sql('id_rubrique', $p) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'DOCUMENT'){
    // Recours a une globale pour compatibilite avec l'ancien code. 
    // Il faudra reprendre inc_documents entierement (tu parles !)
    $p->entete .= ' 
		$logoff = ' .
      champ_sql('id_document', $p) . 
      '; 
		$logon = integre_image($logoff,"","fichier_vignette");
		$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR') {
    $p->entete .= '
			list($logon, $logoff) = image_auteur(' .
      champ_sql('id_auteur', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'AUTEUR_NORMAL') {
    $p->entete .= '
			list($logon,) = image_auteur(' .
      champ_sql('id_auteur', $p) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR_SURVOL') {
    $p->entete .= '
			list(,$logon) = image_auteur(' .
      champ_sql('id_auteur', $p) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'BREVE') {
    $p->entete .= '
			list($logon, $logoff) = image_breve(' .
      champ_sql('id_breve', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'BREVE_RUBRIQUE') {
    $p->entete .= '
			list($logon, $logoff) = image_breve(' .
      champ_sql('id_breve', $p) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $p) . ", $flag_fichier);
		  ";
  }
  else if ($type_logo == 'SITE') {
    $p->entete .= '
			list($logon, $logoff) = image_site(' .
      champ_sql('id_syndic', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'MOT') {
    $p->entete .= '
			list($logon, $logoff) = image_mot(' .
      champ_sql('id_mot', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE') {
    $p->entete .= '
			list($logon, $logoff) = image_article(' .
      champ_sql('id_article', $p) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE_NORMAL') {
    $p->entete .= '
			list($logon,) = image_article(' .
      champ_sql('id_article', $p) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_SURVOL') {
    $p->entete .= '
			list(,$logon) = image_article(' .
      champ_sql('id_article', $p) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_RUBRIQUE') {
    $p->entete .= '
			list($logon, $logoff) = image_article(' .
      champ_sql('id_article', $p) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $p) . ", $flag_fichier);
			";
  }

	// Pour les documents comme pour les logos, le filtre |fichier donne
	// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
	// remarquable, mais a conserver pour compatibilite ascendante.
	// -> http://www.spip.net/fr_article901.html
	if ($flag_fichier)
		$p->code = 'ereg_replace("^IMG/","",$logon)';
	else
		$p->code = "affiche_logos(\$logon, \$logoff, \$lien, '".
		addslashes($align) . "')";

	$p->type = 'php';
	return $p;
}

?>
