<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_PUBLIC_GLOBAL")) return;
define("_INC_PUBLIC_GLOBAL", "1");

function inclure_subpage($fond, $delais_inclus, $contexte_inclus, $cache_incluant)
  {
    // ce perdant de PHP ne comprend pas f(x)[y]
    $page = inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant);
    return $page['texte']; 
  }

function inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant='')
  {
    global $delais;
    static $pile_delais = '', $ptr_delais = 0;
    $ptr_delais++;
    $pile_delais[$ptr_delais] = $delais_inclus;

    spip_log("Inclusion ($cache_incluant)");
    $cle = nom_du_cache($fond, $contexte_inclus);
    $page = ramener_cache($cle,
			  'cherche_page_incluse',
			  array('fond' => $fond, 
				'cache_incluant' => $cache_incluant,
				'contexte' => $contexte_inclus),
			  &$pile_delais[$ptr_delais]);
    
   // si son de'lai est + court que l'incluant, il pre'domine
   if ($ptr_delais == 1)
     { if ($delais > $pile_delais[$ptr_delais])
	 $delais = $pile_delais[$ptr_delais]; }
   else
     { 
       if ($pile_delais[$ptr_delais-1] > $pile_delais[$ptr_delais])
	 $pile_delais[$ptr_delais-1] = $pile_delais[$ptr_delais];
     }
    $ptr_delais--;
    return $page;
  }

# Le bouton des administrateurs est affiche' par une fonction JavaScript
# non mise en cache car de'pendant de l'utilisateur (pas d'affichage parfois)
# Elle est appele'e par le code d'un squelette utilisant FORMULAIRE_ADMIN

function admin_page($cached, $texte)
{
  if  ($GLOBALS['flag_preserver'] ||
       !($admin = $GLOBALS['HTTP_COOKIE_VARS']['spip_admin']))
    $a = 'function admin(){}';
  else
    {
      include_local('inc-admin.php3');
      $a = str_replace("/", '\/', addslashes(strtr(afficher_boutons_admin($cached ? ' *' : ''), "\n", ' ')));
      $a = "var bouton_admin = \"$a\";function admin() 
 {document.write(bouton_admin); document.close(); bouton_admin='';}";
    }
  if (eregi("^[[:space:]]*(<!DOCTYPE[^>]*>[[:space:]]*<html[^>]*>[[:space:]]<head[^>]*>)(.*)$", $texte, $m))
      return $m[1] . envoi_script($a) . $m[2];
  else
    {
    return envoi_script($a) . $texte;
    }
}

function nom_du_cache($fond, $contexte)
 {
   // Tenir compte de l'URL, pour le jour ou` on mutualisera Spip 
   $appel = $GLOBALS['PHP_SELF'];
   $appel = substr($appel,0, strrpos($appel, '/')+1) . $fond;
   // Virer les variables internes. 
   // Faudrait rationnaliser pour ne pas interfe'rer avec contexte_inclus
   if ($contexte)
     while(list($k, $v) = each($contexte))
       {if (!(ereg('((var_*)|recalcul)', $k)))
	   $appel .= "=$v";
       }
   return $appel;
 }

function envoi_script($code)
{
  return
  "<script type='text/javascript'><!--
  $code
--></script>\n";
}

function cherche_image_nommee($nom, $dossier) {
  $formats = array ('gif', 'jpg', 'png');
  while (list(, $format) = each($formats))
    {
      $d = "$dossier$nom.$format";
      if (file_exists($d)) return ($d);
    }
}

function taches_de_fond()
{
// Gestion des taches de fond ?  toutes les 5 secondes
// (on mettra 30 s quand on aura prevu la preemption par une image-cron)
if (!@file_exists('ecrire/data/cron.lock')
        OR (time() - @filemtime('ecrire/data/cron.lock') > 5)) {
        // Si MySQL est out, laisser souffler
        if (!@file_exists('ecrire/data/mysql_out')
                OR (time() - @filemtime('ecrire/data/mysql_out') > 300)) {
                include_ecrire('inc_cron.php3');
                spip_cron();
        }
 }

//
// Gestion des statistiques du site public
// (a la fin pour ne pas forcer le $db_ok)
//

if (lire_meta("activer_statistiques") != "non") {
        include_local ("inc-stats.php3");
        ecrire_stats();
 }
}
?>
