<?php

# traduction des champs "formulaire" et "parametres

# Boutons d'administration: 
# comme c'est soumis a` une condition dynamique (adminitrateur ?)
# on produit un appel a` une fonction Javascript
# a chaque utilisation du squelette, on produira la de'finition ad hoc

function calculer_champ_FORMULAIRE_ADMIN($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  return array("envoi_script('admin()')",'');
}

function calculer_champ_FORMULAIRE_RECHERCHE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  if ($fonctions) {
    list(, $lien) = each($fonctions);	// le premier est un url
    while (list(, $filtre) = each($fonctions)) {
      $filtres[] = $filtre;		// les suivants sont des filtres
    }
    $fonctions = $filtres;
  }
  if (!$lien) $lien = 'recherche.php3';
  $code = "((lire_meta('activer_moteur') != 'oui') ? '' : calcul_form_rech('$lien'))";
		
  return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
}


// Formulaire d'inscription comme redacteur (dans inc-formulaires.php3)

function calculer_champ_FORMULAIRE_INSCRIPTION($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $milieu = '
		$spip_lang = $GLOBALS["spip_lang"];';
  $code = '(lire_meta("accepter_inscriptions") != "oui") ? "" :
			("<"."?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_inscription(\"redac\"); lang_dselect(); ?".">")';
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}

function calculer_champ_FORMULAIRE_ECRIRE_AUTEUR($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $milieu = '
		$spip_lang = $GLOBALS["spip_lang"];
		$mailauteur = ' .
    index_pile($id_boucle,  'email', &$boucles) . ';
		$nomauteur = ' .
    index_pile($id_boucle,  'id_auteur', &$boucles) . ';';
  $code = '(!email_valide($mailauteur) ? "" :
			("<'.'?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_ecrire_auteur(\"$nomauteur\", trim(\"$mailauteur\")); lang_dselect();
			?'.'>"))';
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);  
}

function calculer_champ_FORMULAIRE_SIGNATURE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $milieu = '
		$spip_lang = $GLOBALS["spip_lang"];
		$lacible = ' . 	index_pile($id_boucle, 'id_article', &$boucles) . ";";
   $code = '(!query_petitions($lacible) ? "" :
 			 ("<"."?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_signature($lacible); lang_dselect();
				?".">"))';
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}

	//
	// Formulaire de referencement d'un site
	//
function calculer_champ_FORMULAIRE_SITE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $milieu = '
		$spip_lang = $GLOBALS["spip_lang"];
		$lacible = ' .
    index_pile($id_boucle,  'id_rubrique', &$boucles) . ';';
  $code = '(lire_meta("proposer_sites") != "2") ? "" :
			("<"."?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_site($lacible); lang_dselect();
				?".">")';
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}


// Formulaire de reponse a un forum

function calculer_champ_FORMULAIRE_FORUM($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $type = $boucles[$id_boucle]->type_requete;
  $milieu ='';
  switch ($type) {
  case 'breves':
    $code = "
			boutons_de_forum('', '', ''," .
      index_pile($id_boucle,  'id_breve', &$boucles) .
      ", '', " .
      index_pile($id_boucle,  'titre', &$boucles) .
      ", '$type', substr(lire_meta('forums_publics'),0,3)), &\$Cache)";
    break;
    
  case 'rubriques':
    $code = '
			boutons_de_forum(' .
      index_pile($id_boucle,  'id_rubrique', &$boucles) .
      ", '', '', '', ''," .
      index_pile($id_boucle,  'titre', &$boucles) .
      ", '$type', substr(lire_meta('forums_publics'),0,3)), &\$Cache)";
    break;
    
  case 'syndication':
    $code = "
			boutons_de_forum('', '', '','', " .
      index_pile($id_boucle, 'id_rubrique', &$boucles) .
      ", " .
      index_pile($id_boucle,  'nom_site', &$boucles) .
      ", '$type', substr(lire_meta('forums_publics'),0,3)), &\$Cache)";
    break;
    
  case 'articles': 
    $code = "
			boutons_de_forum('', '', " .
      index_pile($id_boucle, 'id_article', &$boucles) .
      ", '','', " .
      index_pile($id_boucle,  'nom_site', &$boucles) .
      "'$type', " .
      index_pile($id_boucle,  'accepter_forum', &$boucles) .
      ', &$Cache)';
    break;
    
  case 'forums':
  default:
    $code = "
		boutons_de_forum(" .
      index_pile($id_boucle, 'id_rubrique', &$boucles) . ', ' .
      index_pile($id_boucle, 'id_forum', &$boucles) . ', ' .
      index_pile($id_boucle, 'id_article', &$boucles) . ', ' .
      index_pile($id_boucle, 'id_breve', &$boucles) . ', ' .
      index_pile($id_boucle, 'id_syndic', &$boucles) . ', ' .
      index_pile($id_boucle, 'titre', &$boucles) .
      ", '$type', '', &\$Cache)";
    break;
  }
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}

function calculer_champ_PARAMETRES_FORUM($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  $milieu = '
		$forums_publics = ((' .
    index_pile($id_boucle,  "accepter_forum", &$boucles) . ' == ""
				AND lire_meta("forums_publics") != "non")
			OR (' .
    index_pile($id_boucle,  "accepter_forum", &$boucles) . ' != ""
				AND ' .
    index_pile($id_boucle,  "accepter_forum", &$boucles) . ' != "non"));
		if ($forums_publics) {
			if (!($lien = $GLOBALS["HTTP_GET_VARS"]["retour"])) {
				$lien = $GLOBALS["REQUEST_URI"];
				$lien = ereg_replace("&recalcul=oui","",substr($lien, strrpos($lien, "/") + 1)); }
		$lien = rawurlencode($lien); ';
  switch ($boucles[$id_boucle]->type_requete) {
  case 'articles':
    $c = '"id_article=".' .
      index_pile($id_boucle,  id_article, &$boucles);
    break;
  case 'breves':
    $c = '"id_breve=".' .
      index_pile($id_boucle,  id_breve, &$boucles);
    break;
  case 'rubriques':
    $c = '"id_rubrique=".' .
      index_pile($id_boucle,  id_rubrique, &$boucles);
    break;
  case 'syndication':
    $c = '"id_syndic=".' .
      index_pile($id_boucle,  id_syndic, &$boucles);
    break;
  case 'forums':
  default:
    $liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
    $c="";$s="";
    while (list(,$champ) = each ($liste_champs)) {
      $x = index_pile($id_boucle,  $champ, &$boucles);
      $c .= (($c) ? ".\n" : "") . 
		        "((!$x) ? '' : ('$s$champ='.$x))";
      $s="&";}
    break;
  }
  $milieu .= "}\n";
  $code = "(!\$forums_publics) ? '' :
 			($c .\n" . '"&cache=".$Cache[cache] .' . "\n\"&retour=\$lien\")";
  
  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}

?>
