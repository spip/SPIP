<?php


# Fonctions de traitement de champs Spip homonymes de champs SQL
# mais non e'quivalent


// #EXTRA [(#EXTRA|isbn)]
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc_extra.php3
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = 'trim($_extra)';

    // Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		include_ecrire("inc_extra.php3");
		foreach ($p->fonctions as $key => $champ_extra)
			$type_extra = $p->boucles[$p->id_boucle]->type_requete;
			// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
			// depuis un sous-objet sans champ extra d'un objet a champ extra,
			// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ_extra)) {
			unset($p->fonctions[$key]);
			$p->code = "extra($p->code, '".addslashes($champ_extra)."')";
		}
		// Appliquer les filtres definis par le webmestre
		$filtres = extra_filtres($type_extra, $champ_extra);
		if ($filtres) foreach ($filtres as $f)
			$p->code = "$f($p->code)";
	}
	return $p;
}


// #LANG
// non documente ?
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "($_lang ? $_lang : \$GLOBALS['spip_lang'])";
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

	return $p;
}


// #PETITION
// Champ testant la presence d'une petition
// non documente ???
function balise_PETITION_dist ($p) {
	$_id_article = champ_sql('id_article', $p);
	$p->code = 'sql_petitions($_id_article)';
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + lire_meta('popularite_max'))))";
	return $p;
}


// #DATE
// Cette fonction n'est utile que parce qu'on a besoin d'aller chercher
// dans le contexte general quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	return $p;
}


# Fonction commune aux logos (rubriques, articles...)

function calculer_champ_LOGO($params)
{
  ereg("^LOGO_(([a-zA-Z]+).*)$", $params->nom_champ, $regs);
  $type_logo = $regs[1];
  $type_objet = strtolower($regs[2]);
  $flag_fichier = 0;  // compatibilite ascendante
  $filtres = '';
  if (is_array($params->fonctions)) {
    foreach($params->fonctions as $nom) {
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
    $params->fonctions = $filtres;
  }
  if ($flag_lien_auto && !$lien) {
    $params->entete .= "\n\t\$lien = generer_url_$type_objet(" .
      champ_sql("id_$type_objet", $params) .
      ");\n";
  }
  else
    {
      $params->entete .= "\n\t\$lien = ";
      $a = $lien;
      while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $a, $match))
	{
	  list($c,$m) = calculer_champ(array(), $match[2], $params->id_boucle, $params->boucles, $params->id_mere);
	  // $m est nul dans les cas pre'vus
	  $params->entete .= ((!$match[1]) ? "" :"'$match[1]' .") . " $c .";
	  $a = $match[3];
	}
      if ($a) $params->entete .= "'$lien';"; 
      else 
	{
	  if ($lien) $params->entete = substr($params->entete,1,-1) .";";
	  else $params->entete .= "'';";
	}
    }
  
  if ($type_logo == 'RUBRIQUE') {
    $params->entete .= '
			list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'RUBRIQUE_NORMAL') {
    $params->entete .= '
			list($logon,) = image_rubrique(' .
      champ_sql('id_rubrique', $params) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'RUBRIQUE_SURVOL') {
    $params->entete .= '
			list(,$logon) = image_rubrique(' .
      champ_sql('id_rubrique', $params) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'DOCUMENT'){
    // Recours a une globale pour compatibilite avec l'ancien code. 
    // Il faudra reprendre inc_documents entierement (tu parles !)
    $params->entete .= ' 
		$logoff = ' .
      champ_sql('id_document', $params) . 
      '; 
		$logon = integre_image($logoff,"","fichier_vignette");
		$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR') {
    $params->entete .= '
			list($logon, $logoff) = image_auteur(' .
      champ_sql('id_auteur', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'AUTEUR_NORMAL') {
    $params->entete .= '
			list($logon,) = image_auteur(' .
      champ_sql('id_auteur', $params) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR_SURVOL') {
    $params->entete .= '
			list(,$logon) = image_auteur(' .
      champ_sql('id_auteur', $params) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'BREVE') {
    $params->entete .= '
			list($logon, $logoff) = image_breve(' .
      champ_sql('id_breve', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'BREVE_RUBRIQUE') {
    $params->entete .= '
			list($logon, $logoff) = image_breve(' .
      champ_sql('id_breve', $params) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $params) . ", $flag_fichier);
		  ";
  }
  else if ($type_logo == 'SITE') {
    $params->entete .= '
			list($logon, $logoff) = image_site(' .
      champ_sql('id_syndic', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'MOT') {
    $params->entete .= '
			list($logon, $logoff) = image_mot(' .
      champ_sql('id_mot', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE') {
    $params->entete .= '
			list($logon, $logoff) = image_article(' .
      champ_sql('id_article', $params) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE_NORMAL') {
    $params->entete .= '
			list($logon,) = image_article(' .
      champ_sql('id_article', $params) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_SURVOL') {
    $params->entete .= '
			list(,$logon) = image_article(' .
      champ_sql('id_article', $params) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_RUBRIQUE') {
    $params->entete .= '
			list($logon, $logoff) = image_article(' .
      champ_sql('id_article', $params) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      champ_sql('id_rubrique', $params) . ", $flag_fichier);
			";
  }

	// Pour les documents comme pour les logos, le filtre |fichier donne
	// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
	// remarquable, mais a conserver pour compatibilite ascendante.
	// -> http://www.spip.net/fr_article901.html
	if ($flag_fichier)
		$params->code = 'ereg_replace("^IMG/","",$logon)';
	else
		$params->code = "affiche_logos(\$logon, \$logoff, \$lien, '".
		addslashes($align) . "')";

	return $params;
}

?>
