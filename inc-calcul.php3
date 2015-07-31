<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL")) return;
define("_INC_CALCUL", "1");

// ce fichier exécute un squelette.


include_ecrire("inc_index.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_lang.php3");
include_ecrire("inc_documents.php3");
if (file_exists("inc-urls.php3")) {
	include_local ("inc-urls.php3");
}
else {
	include_local ("inc-urls-dist.php3");
}

include_local("inc-calcul_mysql3.php");
include("inc-calcul_html4.php");

# Ce fichier peut contenir une affectation de $dossier_squelettes  indiquant
# le répertoire du source des squelettes (les pseudo-html avec <BOUCLE...)

if (file_exists("mes_fonctions.php3")) 
    include_local ("mes_fonctions.php3");

# Provoque la recherche du squelette $fond d'une $lang donnée,
# et l'applique sur un $contexte pour un certain $cache.
# Retourne un tableau de 3 éléments:
# 'texte' => la page calculée
# 'process_ins' => 'html' ou 'php' si présence d'un '<?php'
# 'invalideurs' => les invalideurs (cf inc-calcul-squel)

# La recherche est assurée par la fonction cherche_squelette
# définie dans inc-chercher, fichier non chargé s'il existe un fichier
# mon-chercher dans $dossier_squelettes ou dans le rep principal de Spip,
# pour charger une autre définition de cette fonction.

# L'exécution est précédée du chargement éventuel d'un fichier homonyme
# de celui du squelette mais d'extension .php  pouvant contenir:
# - des filtres
# - des fonctions de traduction de balise (cf inc-index-squel)

function cherche_page($fond, $cache, $contexte, $id_rubrique, $lang='') 
{
  global $dossier_squelettes;

  $dir = "$dossier_squelettes/mon-chercher.php3";
  if (file_exists($dir)) include($dir); else include_local("inc-chercher.php3");

  $skel = chercher_squelette($fond,
			     $id_rubrique,
			     $dossier_squelettes ? "$dossier_squelettes/" :'',
			     $lang);

  $dir = "$skel" . '_fonctions.php3';
  if (file_exists($dir)) include($dir);

  $fonc =  ramener_squelette($skel);
  $timer_a = explode(" ", microtime());
  $page =  $fonc(array('cache' =>$cache), array($contexte));
  if ($GLOBALS['xhtml']) {
    include_ecrire("inc_tidy.php");
    $page['texte'] = xhtml($page['texte']);
  }
  $timer_b = explode(" ", microtime());
  $timer = ceil(1000*($timer_b[0] + $timer_b[1] - $timer_a[0] - $timer_a[1]));
  spip_log("Page $skel: " . strlen($page['texte']) . " octets, $timer ms");
  return $page;
}

function cherche_page_incluse($cache, $contexte)
{
  $contexte_inclus = $contexte['contexte'];
  return cherche_page($contexte['fond'], 
		      $cache,
		      $contexte_inclus,
		      $contexte_inclus['id_rubrique']);
}

function calculer_page_globale($cache, $fond, $var_recherche)
 {
   global $spip_lang;
	global $contexte;	// va avec le truc sale ci-dessous :-)

   $contexte = $GLOBALS['HTTP_GET_VARS'];
   if ($GLOBALS['date'])
    $contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
  else
    $contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	// Analyser les URLs personnalisees (inc-urls-...)
	/* attention c'est assez sale */
	$fichier_requete = $GLOBALS['REQUEST_URI'];
	$fichier_requete = strtr($fichier_requete, '?', '&');
	$fichier_requete = eregi_replace('&(submit|valider|PHPSESSID|(var_[^=&]*)|recalcul)=[^&]*', '', $fichier_requete);
	recuperer_parametres_url($fond, $fichier_requete);
	/* fin du truc sale */
  
   $id_rubrique_fond = 0;
   $lang = $contexte['lang'];	// si inc-urls veut fixer la langue
   if ($r = cherche_rubrique_fond($contexte, $lang ? $lang : lire_meta('langue_site')))
     list($id_rubrique_fond, $lang) = $r;
  $signale_globals = "";
  foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
		'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article', 'id_mot', 'id_groupe', 'id_document') as $val)
    {
      if ($contexte[$val])
	$signale_globals .= '$GLOBALS[\''.$val.'\'] = '.intval($contexte[$val]).";";
    }
  if (!$GLOBALS['forcer_lang'])
    lang_select($lang);

  $page = cherche_page($fond, $cache, $contexte, $id_rubrique_fond, $spip_lang);
  $texte = $page['texte'];

  if ($var_recherche)
    {
      include_ecrire("inc_surligne.php3");
      $texte = surligner_mots($texte, $var_recherche);
    } 

  return array('texte' => 
	       (($page['process_ins'] || (!$signale_globals)) ? $texte :
		('<'."?php $signale_globals ?".'>'.$texte)),
	       'process_ins' => $page['process_ins'],
	       'invalideurs' => $page['invalideurs']);
}

function cherche_page_incluante($cache, $contexte)
{
    // si le champ chapo commence par '=' c'est une redirection.

  if ($id_article = intval($GLOBALS['id_article'])) {
    $page = query_chapo($id_article);
    if (!$page) return '';
    $page = $page['chapo'];
    if (substr($page, 0, 1) == '=') {
      include_ecrire('inc_texte.php3');
      list(,$page) = extraire_lien(array('','','',substr($page, 1)));
      if ($page) // sinon les navigateurs pataugent
	{
	  $page = addslashes($page);
	  return array('texte' =>
		       ("<". "?php header(\"Location: $page\"); ?" . ">"),
		       'process_ins' => 'php');
	}
    }
  }
  return calculer_page_globale($cache,
			       $contexte['fond'],
			       $contexte['var_recherche']);
}

# Fonctions appelées par les squelettes (insertion dans le code trop lourde)

tester_variable('espace_logos',3);  // HSPACE=xxx VSPACE=xxx pour les logos (#LOGO_ARTICLE)
tester_variable('espace_images',3);  // HSPACE=xxx VSPACE=xxx pour les images integrees

//
// Retrouver le logo d'un objet (et son survol)
//

function cherche_image($id_objet, $type_objet, $flag_fichier) {
	$image = array('', '');
	$dossier = $GLOBALS['dossier_images'] . '/';
	// cherche l'image liee a l'objet
	$image[0] = cherche_image_nommee($type_objet.'on'.$id_objet,$dossier);

	// cherche un survol
	if ($image[0]) {
	  $image[1] = cherche_image_nommee($type_objet.'off'.$id_objet,$dossier);
	}
	if ($flag_fichier)
	  { 
	    $image[0] = ereg_replace("^$dossier", '', $image[0]);
	    $image[1] = ereg_replace("^$dossier", '', $image[1]);}
	return $image;
}

function image_article($id_article, $dossier){
  return cherche_image($id_article,'art', $dossier);
}

function image_auteur($id_auteur, $dossier){
  return cherche_image($id_auteur,'aut', $dossier);
}

function image_breve($id_breve, $dossier){
  return cherche_image($id_breve,'breve', $dossier);
}

function image_site($id_syndic, $dossier){
  return cherche_image($id_syndic,'site', $dossier);
}

function image_mot($id_mot, $dossier){
  return cherche_image($id_mot,'mot', $dossier);
}

function image_rubrique($id_rubrique, $dossier) {
	// Recherche recursive vers les rubriques parentes (y compris racine)
	while ($id_rubrique) {
	  $image = cherche_image($id_rubrique, 'rub', $dossier);
	  if ($image[0]) return $image;
	  $id_rubrique = query_parent($id_rubrique);
	  }
	return '';
}
?>
