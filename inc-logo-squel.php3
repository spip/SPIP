<?php

# Fonctions de traitement de champs Spip homonymes de champs SQL
# mais non e'quivalent

function calculer_champ_EXTRA ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $code = 'trim(' . index_pile($id_boucle,  "extra", $boucles) . ')';
  if ($fonctions) {
    // Gerer la notation [(#EXTRA|isbn)]
    include_ecrire("inc_extra.php3");
    reset($fonctions);
    list($key, $champ_extra) = each($fonctions);
    $type_extra = $boucles[$id_boucle]->type_requete;
    if (extra_champ_valide($type_extra, $champ_extra)) {
      unset($fonctions[$key]);
      $code = "extra($code, '".addslashes($champ_extra  )."')";
    }
    // Appliquer les filtres definis par le webmestre
    $filtres = extra_filtres($type_extra, $champ_extra);
    if ($filtres) {
      reset($filtres);
      while (list(, $f) = each($filtres)) $code = "$f($code)";
    }
  }
  return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
}

function calculer_champ_LANG ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
 {
#	$code = "lire_meta('langue_site')"; # 1.7
   $code = "\$GLOBALS['spip_lang']";   # 1.7.2
   return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
}

function calculer_champ_LESAUTEURS ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $code = index_pile($id_boucle,  "lesauteurs", $boucles);
  if ((!$code) || ($code == '$PileRow[0][lesauteurs]'))
    $code = 'query_auteurs(' .
      index_pile($id_boucle,  "id_article", $boucles) .
      ')';    
   return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
}

function calculer_champ_PETITION ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
 {
   $code = 'query_petitions(' .
     index_pile($id_boucle,  'id_article', $boucles)
     . '")) ? " " : "")';
  return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
}

function calculer_champ_POPULARITE ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
 {
   $code = 'ceil(min(100, 100 * ' .
     index_pile($id_boucle,  "popularite", $boucles) .
     '/ max(1 , 0 + lire_meta(\'popularite_max\'))))';
  return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
 }


function calculer_champ_DATE ($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
# Uniquement hors-boucles, pour date passee dans l'URL ou  contexte_inclus
  return applique_filtres($fonctions,
			  index_pile($id_boucle,  'date', $boucles),
			  $id_boucle, $boucles, $id_mere);
}

# Fonction commune aux logos (rubriques, articles...)

function calculer_champ_LOGO($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  ereg("^LOGO_(([a-zA-Z]+).*)$", $nom_champ, $regs);
  $type_logo = $regs[1];
  $type_objet = strtolower($regs[2]);
  $flag_fichier = 0;  // compatibilite ascendante
  $filtres = '';
  if ($fonctions) {
    while (list(, $nom) = each($fonctions)) {
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
    $fonctions = $filtres;
  }
  if ($flag_lien_auto && !$lien) {
    $milieu .= "
			\$lien = generer_url_$type_objet(" .
      index_pile($id_boucle,  'id_$type_objet', $boucles) . ");
			";
  }
  else
    {
      $milieu .= "\n\t\$lien = ";
      $a = $lien;
      while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $a, $match))
	{
	  list($c,$m) = calculer_champ("", $match[2], $id_boucle, $boucles, $id_mere);
	  // $m est nul dans les cas pre'vus
	  $milieu .= ((!$match[1]) ? "" :"'$match[1]' .") . " $c .";
	  $a = $match[3];
	}
      if ($a) $milieu .= "'$lien';"; 
      else 
	{
	  if ($lien) $milieu = substr($milieu,1,-1) .";";
	  else $milieu .= "'';";
	}
    }
  
  if ($type_logo == 'RUBRIQUE') {
    $milieu .= '
			list($logon, $logoff) = image_rubrique(' .
      index_pile($id_boucle,  "id_rubrique", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'RUBRIQUE_NORMAL') {
    $milieu .= '
			list($logon,) = image_rubrique(' .
      index_pile($id_boucle,  "id_rubrique", $boucles) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'RUBRIQUE_SURVOL') {
    $milieu .= '
			list(,$logon) = image_rubrique(' .
      index_pile($id_boucle,  "id_rubrique", $boucles) . ", $flag_fichier); ". '
			$logoff = "";
			';
  }
  else if ($type_logo == 'DOCUMENT'){
    $milieu .= '
			$logon = integre_image(' .
      index_pile($id_boucle,  "id_document", $boucles) . ',"","fichier_vignette");
			$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR') {
    $milieu .= '
			list($logon, $logoff) = image_auteur(' .
      index_pile($id_boucle,  "id_auteur", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'AUTEUR_NORMAL') {
    $milieu .= '
			list($logon,) = image_auteur(' .
      index_pile($id_boucle,  "id_auteur", $boucles) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'AUTEUR_SURVOL') {
    $milieu .= '
			list(,$logon) = image_auteur(' .
      index_pile($id_boucle,  "id_auteur", $boucles) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'BREVE') {
    $milieu .= '
			list($logon, $logoff) = image_breve(' .
      index_pile($id_boucle,  "id_breve", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'BREVE_RUBRIQUE') {
    $milieu .= '
			list($logon, $logoff) = image_breve(' .
      index_pile($id_boucle,  "id_breve", $boucles) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      index_pile($id_boucle,  "id_rubrique", $boucles) . ", $flag_fichier);
		  ";
  }
  else if ($type_logo == 'SITE') {
    $milieu .= '
			list($logon, $logoff) = image_site(' .
      index_pile($id_boucle,  "id_syndic", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'MOT') {
    $milieu .= '
			list($logon, $logoff) = image_mot(' .
      index_pile($id_boucle,  "id_mot", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE') {
    $milieu .= '
			list($logon, $logoff) = image_article(' .
      index_pile($id_boucle,  "id_article", $boucles) . ", $flag_fichier);
			";
  }
  else if ($type_logo == 'ARTICLE_NORMAL') {
    $milieu .= '
			list($logon,) = image_article(' .
		index_pile($id_boucle,  "id_article", $boucles) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_SURVOL') {
    $milieu .= '
			list(,$logon) = image_article(' .
      index_pile($id_boucle,  "id_article", $boucles) . ", $flag_fichier);".'
			$logoff = "";
			';
  }
  else if ($type_logo == 'ARTICLE_RUBRIQUE') {
    $milieu .= '
			list($logon, $logoff) = image_article(' .
      index_pile($id_boucle,  "id_article", $boucles) . ", $flag_fichier);".'
			if (!$logon)
				list($logon, $logoff) = image_rubrique(' .
      index_pile($id_boucle,  "id_rubrique", $boucles) . ", $flag_fichier);
			";
  }
  if ($flag_fichier)
    $code = '$logon';
  else
    $code = "affiche_logos(\$logon, \$logoff, \$lien, '".
      addslashes($align) . "')";
  
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
		return array($c,$milieu . $m);
}

?>
