<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHERCHE")) return;
define("_INC_CHERCHE", "1");

# Ce fichier doit IMPERATIVEMENT contenir la fonction chercher-squelette
# (cf commentaires dans inc-calcul)

function chercher_squelette($fond, $id_rubrique, $dossier, $lang) {
  $e = $GLOBALS['extension_squelette'];
  if ($lang)
    {
	lang_select($lang);
	$f = "$fond.$lang";
	if (@file_exists($f . '.' . $e)) $fond = $f;
    }
  $d ="$dossier$fond";
  // On selectionne, dans l'ordre :
  // fond=10, fond-10 fond-<rubriques parentes> fond fond-dist
  $f = "$d=$id_rubrique";
  if (($id_rubrique > 0) AND (@file_exists($f . '.' . $e))) return $f;
  while ($id_rubrique) {
    if (file_exists("$d-$id_rubrique.$e")) {
      return "$d-$id_rubrique";
    } else {
      $id_rubrique = query_parent($id_rubrique);
    }
  }
  if (@file_exists("$d.$e")) {
    return $d;
  } else if (@file_exists("$fond.$e")) {
    return $fond;
  } else if (@file_exists("$fond-dist.$e")) {
    return "$fond-dist";
  } else {
   // erreur webmaster : $fond ne correspond a rien
    include_ecrire ("inc_presentation.php3");
    install_debut_html(_T('info_erreur_squelette'));
    echo "<P>"._T('info_erreur_squelette2', array('fichier'=>"$d"))."</P>";
    install_fin_html();
    spip_log ("ERREUR: aucun squelette $d n'est disponible...");
    exit;
    }
}
?>
