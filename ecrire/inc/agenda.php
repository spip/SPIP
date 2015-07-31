<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/layer');
include_spip('inc/texte'); // inclut inc_filtre 

/// @file
/// Typographie generale des calendriers de 3 type: jour/semaine/mois(ou plus)

/// Notes: pour toutes les fonctions ayant parmi leurs parametres
/// annee, mois, jour, echelle, partie_cal, script, ancre
/// ceux-ci apparaissent TOUJOURS dans cet ordre 

define('DEFAUT_D_ECHELLE',120); # 1 pixel = 2 minutes
define('DEFAUT_DECALE', 4); # marge gauche en EM
define('DEFAUT_TAILLE_HEURE', 0.7); # marge gauche en EM

define('DEFAUT_PARTIE_M', "matin");
define('DEFAUT_PARTIE_S', "soir");
define('DEFAUT_PARTIE_T', "tout");
define('DEFAUT_PARTIE_R', "sansheure");
define('DEFAUT_PARTIE', DEFAUT_PARTIE_R);

$GLOBALS['calendrier_partie'] = array(
		DEFAUT_PARTIE_S => array('debut' => 12, 'fin' => 23),
		DEFAUT_PARTIE_M => array('debut' => 4, 'fin' => 15),
		DEFAUT_PARTIE_T => array('debut' => 7, 'fin' => 21)
				      );
///
///Utilitaires sans html ni sql
///

/// Utilitaire de separation script / ancre
/// et de retrait des arguments a remplacer
/// (a mon avis cette fonction ne sert a rien, puisque parametre_url()
/// sait remplacer les arguments au bon endroit -- Fil)
/// Pas si simple: certains param ne sont pas remplaces 
/// et doivent reprendre leur valeur par defaut -- esj.
/// http://doc.spip.org/@calendrier_retire_args_ancre
function calendrier_retire_args_ancre($script)
{

  if (preg_match(',^(.*)#([\w-]+)$,',$script, $m)) {
	  $script = $m[1];
	  $ancre = $m[2];
  } else { $ancre = ''; }

  foreach(array('echelle','jour','mois','annee', 'type', 'partie_cal', 'bonjour') as $arg) {
		$script = preg_replace("/([?&])$arg=[^&]*&/",'\1', $script);
		$script = preg_replace("/([?&])$arg=[^&]*$/",'\1', $script);
	}
  if (in_array(substr($script,-1),array('&','?'))) $script =   substr($script,0,-1);
  return array(quote_amp($script), $ancre);
}

/// construit un bout de Query-String en eliminant le superflu

function calendrier_retire_defaults($echelle, $partie_cal)
{
	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;

	return (($echelle != DEFAUT_D_ECHELLE) ? "&amp;echelle=$echelle" : '')
	. (($partie_cal != DEFAUT_PARTIE) ? "&amp;partie_cal=$partie_cal" : '');
}

/// tous les liens de navigations sont issus de cette fonction
/// on peut definir generer_url_date et un htacces pour simplifier les URL

// http://doc.spip.org/@calendrier_args_date
function calendrier_args_date($script, $annee, $mois, $jour, $type, $finurl) {
	if (function_exists('generer_url_date'))
		return generer_url_date($script, $annee, $mois, $jour, $type, $finurl);

	$script = parametre_url($script, 'annee', sprintf("%04d", $annee));
	$script = parametre_url($script, 'mois',  sprintf("%02d", $mois));
	$script = parametre_url($script, 'jour',  sprintf("%02d", $jour));
	$script = parametre_url($script, 'type',  $type);
	return $script . $finurl;
}

function calendrier_args_time($time, $script, $type, $fin='')
{
	if (!is_numeric($time)) $time = strtotime($time);
	$jour = date("d",$time);
	$mois = date("m",$time);
	$annee = date("Y",$time);

	return calendrier_args_date($script, $annee, $mois, $jour, $type, $fin);
}

/// utilise la precedente pour produire une balise A avec tous les accessoires

// http://doc.spip.org/@calendrier_href
function calendrier_href($script, $annee, $mois, $jour, $type, $fin, $ancre, $img, $titre, $class='', $alt='', $clic='', $style='', $evt='')
{
	static $moi = NULL;
	// pas d'Ajax pour l'espace public pour le moment ou si indispo
	// sinon preparer la RegExp qui l'empeche aussi pour la page elle-meme
	if ($moi === NULL)  {
		$moi = (test_espace_prive() AND (_SPIP_AJAX === 1 ))
		? ("/exec=" . _request('exec') .'$/')
		: '';
	}
	$d = mktime (1,1,1, $mois, $jour, $annee);
	$url = calendrier_args_time($d, $script, $type, $fin) . ($ancre ? "#$ancre" : '');
	$c = ($class ? " class=\"$class\"" : '');
	
	if ($img) $clic =  http_img_pack($img, ($alt ? $alt : $titre), $c);

	if ($moi AND preg_match($moi, $script))
		$evt .= "\nonclick=" . ajax_action_declencheur($h,$ancre);
	return http_href($url, PtoBR($clic), attribut_html($titre), $style, $class, $evt);
}

/// Fabrique une balise A, avec tous les attributs possibles
/// attention au cas ou la href est du Javascript avec des "'"
/// pour un href conforme au validateur W3C, faire & --> &amp; avant

// http://doc.spip.org/@http_href
function http_href($href, $clic, $title='', $style='', $class='', $evt='') {
	if ($style) $evt .= " style='$style'";
	$r = lien_ou_expose($href, $clic, false, $class, $title, 'nofollow', $evt);
	return str_replace('<a href=', "<a\nhref=", $r);
}

/// prend une heure de debut et de fin, ainsi qu'une echelle (seconde/pixel)
/// et retourne un tableau compose
/// - taille d'une heure
/// - taille d'une journee
/// - taille de la fonte
/// - taille de la marge

// http://doc.spip.org/@calendrier_echelle
function calendrier_echelle($debut, $fin, $echelle)
{
  if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;
  if ($fin <= $debut) $fin = $debut +1;

  $duree = $fin - $debut;
  $dimheure = floor(3600 / $echelle);
  return array($dimheure,
	       (($duree+2) * $dimheure),
	       floor(14 / (1+($echelle/240))),
	       floor(240 / $echelle));
}

/// Calcule le "top" d'une heure

// http://doc.spip.org/@calendrier_top
function calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour) {
	
	$h_heure = substr($heure, 0, strpos($heure, ":"));
	$m_heure = substr($heure, strpos($heure,":") + 1, strlen($heure));
	$heure100 = $h_heure + ($m_heure/60);

	if ($heure100 < $debut) $heure100 = ($heure100 / $debut) + $debut - 1;
	if ($heure100 > $fin) $heure100 = (($heure100-$fin) / (24 - $fin)) + $fin;

	$top = floor(($heure100 - $debut + 1) * $dimheure);

	return $top;	
}

/// Calcule la hauteur entre deux heures
// http://doc.spip.org/@calendrier_height
function calendrier_height ($heure, $heurefin, $debut, $fin, $dimheure, $dimjour) {

	$height = calendrier_top ($heurefin, $debut, $fin, $dimheure, $dimjour)
				- calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour);

	$padding = floor(($dimheure / 3600) * 240);
	$height = $height - (2* $padding + 2); // pour padding interieur
	
	if ($height < ($dimheure/4)) $height = floor($dimheure/4); // eviter paves totalement ecrases
	
	return $height;	
}

///
/// init: calcul generique des evenements a partir des tables SQL
///

// http://doc.spip.org/@http_calendrier_init
function http_calendrier_init($time='', $type='mois', $echelle='', $partie_cal='', $script='', $evt=null)
{
	if (is_array($time)) {
		list($j,$m,$a) = $time;
		if ($j+$m+$a) $time = @mktime(0,0,0, $m, $j, $a);
	}

	if (!is_numeric($time)) $time = time();

	$jour = date("d",$time);
	$mois = date("m",$time);
	$annee = date("Y",$time);
        if (!$echelle = intval($echelle)) $echelle = DEFAUT_D_ECHELLE;
        if (!is_string($type) OR !preg_match('/^\w+$/', $type)) $type ='mois';
        if (!is_string($partie_cal) OR !preg_match('/^\w+$/', $partie_cal)) 
                $partie_cal =  DEFAUT_PARTIE;
	list($script, $ancre) = 
	  calendrier_retire_args_ancre($script); 
	if (is_null($evt)) {
	  $g = 'quete_calendrier_' . $type;
	  $evt = quete_calendrier_interval($g($annee,$mois, $jour));
	  quete_calendrier_interval_articles("'$annee-$mois-00'", "'$annee-$mois-1'", $evt[0]);
	  // si on veut les forums, decommenter
#	  quete_calendrier_interval_forums($g($annee,$mois,$jour), $evt[0]);
	}

	$f = 'http_calendrier_' . $type;
	if (!function_exists($f)) $f = 'http_calendrier_mois';
	return $f($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt);
}

/// affichage d'un calendrier de mois, avec son bandeau de navigation


// http://doc.spip.org/@http_calendrier_mois
function http_calendrier_mois($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "large";

	if (is_array($evt)) {

	list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;
	if (!$premier_jour) $premier_jour = '01';
	if (!$dernier_jour)
	    {
	      $dernier_jour = 31;
	      while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	    }
	if ($sansduree)
	    foreach($sansduree as $d => $r) 
	      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
	$evt =
	    http_calendrier_mois_noms() .
	    http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, calendrier_retire_defaults($echelle, $partie_cal), $ancre) ;
	} else {
	  $evt = "<tr><td>$evt</td></tr>";
	  $premier_jour = '01';
	  $dernier_jour = '31';
	}

	$id = ($ancre ? $ancre : 'agenda') . "-nav";

	return 
	  "<div><div id='$id'></div>" .
	  "<table class='calendrier calendrier-$spip_ecran'>" .
	  http_calendrier_mois_navigation($annee, $mois, $premier_jour, $dernier_jour, $echelle, $partie_cal, $script, $ancre) .
	  $evt .
	  '</table>' .
	  http_calendrier_sans_date($annee, $mois, $evenements) .
	  (!test_espace_prive() ? "" : http_calendrier_aide_mess()) .
	  "</div>";
}

/// si la periore a plus de 31 jours, c'est du genre trimestre, semestre etc
/// pas de navigation suivant/precedent alors

// http://doc.spip.org/@http_calendrier_mois_navigation
function http_calendrier_mois_navigation($annee, $mois, $premier_jour, $dernier_jour, $echelle, $partie_cal, $script, $ancre){
	if ($dernier_jour > 31) {
	  $prec = $suiv = '';
	  $periode = affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$premier_jour,$annee))) . ' - '. affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$dernier_jour,$annee)));
	} else {

	$mois_suiv=$mois+1;
	$annee_suiv=$annee;
	$mois_prec=$mois-1;
	$annee_prec=$annee;
	if ($mois==1){
	  $mois_prec=12;
	  $annee_prec=$annee-1;
	}
	else if ($mois==12){$mois_suiv=1;	$annee_suiv=$annee+1;}
	$prec = array($annee_prec, $mois_prec, 1, "mois");
	$suiv = array($annee_suiv, $mois_suiv, 1, "mois");
	$periode = affdate_mois_annee("$annee-$mois-1");
	}
	return
	  http_calendrier_navigation($annee,
				   $mois,
				   1,
				   $echelle,
				   $partie_cal,
				   $periode,
				   $script,
				   $prec,
				   $suiv,
				   'mois',
				   $ancre);

}

// http://doc.spip.org/@http_calendrier_mois_noms
function http_calendrier_mois_noms(){

	$bandeau ="";
	for ($j=1; $j<8;$j++){
		$bandeau .= 
		  "\n<th>" .
		  _T('date_jour_' . (($j%7)+1)) .
		  "</th>";
	}
	return "\n<tr>$bandeau</tr>";
}

/// dispose les lignes d'un calendrier de 7 colonnes (les jours)
/// chaque case est garnie avec les evenements du jour figurant dans $evenements

// http://doc.spip.org/@http_calendrier_mois_sept
function http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, $finurl, $ancre='')
{
	global $spip_lang_left, $spip_lang_right;

	// affichage du debut de semaine hors periode
	$init = '';
	$debut = date("w",mktime(1,1,1,$mois,$premier_jour,$annee));

	for ($i=$debut ? $debut : 7;$i>1;$i--)
	  {$init .= "\n<td></td>";}

	$total = '';
	$ligne = '';
	$today=date("Ymd");
	for ($j=$premier_jour; $j<=$dernier_jour; $j++){
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour = date("d",$nom);
		$jour_semaine = date("w",$nom);
		$mois_en_cours = date("m",$nom);
		$annee_en_cours = date("Y",$nom);
		$amj = date("Y",$nom) . $mois_en_cours . $jour;
		$couleur_texte = "black";
		$fond = "";

		if ($jour_semaine == 0) $fond = 'jour_dimanche ';
		else if ($jour_semaine==1)
			  { 
			    if ($ligne||$init)
			      $total .= "\n<tr>$init$ligne</tr>";
			    $ligne = $init = '';
			  }
		
		if ($amj == $today) {
			$couleur_texte = "red";
			$fond = "jour_encours ";
		}

		$res =  (test_espace_prive() ? 
		   (calendrier_href($script,$annee_en_cours, $mois_en_cours, $jour, "jour", $finurl, $ancre, '', $jour, 'calendrier-helvetica16', '', $jour, "color: $couleur_texte") . 
		    http_calendrier_ics_message($annee_en_cours, $mois_en_cours, $jour, false)):
		   http_calendrier_mois_clics($annee_en_cours, $mois_en_cours, $jour, $script, $finurl, $ancre));

		if ($evts = $evenements[$amj]) {
		  foreach ($evts as $evenement)
		    {
		      $res .= isset($evenement['DTSTART']) ?
			http_calendrier_avec_heure($evenement, $amj) :
			http_calendrier_sans_heure($evenement);
		    }
		}
		$fond .= $ligne ? "bordure_$spip_lang_right" :'bordure_double';
		$d = sprintf('D%4d-%02d-%02d', $annee_en_cours,$mois_en_cours,$jour);
		$ligne .= "\n<td id='$d' class='$fond'>$res</td>";
	}
	return  $total . ($ligne ? "\n<tr>$ligne</tr>" : '');
}

/// Appelle la fonction precedente en sachant que ca produira 1 TD/TR unique.
/// retourne le contenu du TD

function http_calendrier_sept_un($annee, $mois, $jour,$evenements, $script, $finurl, $ancre='', $class='', $att='')
{
	$res = http_calendrier_mois_sept($annee, $mois, $jour, $jour,$evenements, $script, $finurl, $ancre);
	preg_match(',^\s*<tr>\s*<td[^>]*>(.*?)</td>\s*</tr>\s*$,s', $res, $m);

	return $m[1];
}

/// typo pour l'espace public
// http://doc.spip.org/@http_calendrier_mois_clics
function http_calendrier_mois_clics($annee, $mois, $jour, $script, $finurl, $ancre)
{
	$d = mktime(0,0,0,$mois, $jour, $annee);
	$semaine = date("W", $d);

	$semaine = calendrier_href($script,$annee, $mois, $jour, "semaine", $finurl, $ancre,'',
			(_T('date_semaines') . ": $semaine"),
				   '','', $semaine);

	$jour =	calendrier_href($script,$annee, $mois, $jour, "jour", $finurl, $ancre, '',
			 (_T('date_jour_'. (1+date('w',$d))) .
			  " $jour " .
			  _T('date_mois_'.(0+$mois))),
				'', '',
				"$jour/$mois");

	return "$jour$semaine";
}

/// dispose les evenements d'une semaine

// http://doc.spip.org/@http_calendrier_semaine
function http_calendrier_semaine($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "large";

	$finurl = calendrier_retire_defaults($echelle, $partie_cal);
	$init = date("w",mktime(1,1,1,$mois,$jour,$annee));
	$init = $jour+1-($init ? $init : 7);
	$sd = '';

	if (is_array($evt)) {
		if ($partie_cal!= DEFAUT_PARTIE_R) {
			$sd = http_calendrier_sans_date($annee, $mois, $evt[0]);
			$evt = http_calendrier_semaine_sept($annee, $mois, $init, $echelle, $partie_cal, $evt);
	  	} else {
			list($sansduree, $evenements, , ) = $evt;
			if ($sansduree)
			    foreach($sansduree as $d => $r) 
			      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
			$evt = http_calendrier_mois_sept($annee, $mois, $init, $init+ 6, $evenements, $script, $finurl, $ancre);
	  	}
	} else $evt = "<tr><td >$evt</td></tr>";

	$id = ($ancre ? $ancre : 'agenda') . "-nav";

	return 
	  "<div><div id='$id'></div>" .
	  "<table class='calendrier calendrier-$spip_ecran'>" .
	  http_calendrier_semaine_navigation($annee, $mois, $init, $echelle, $partie_cal, $script, $ancre) .
	  http_calendrier_semaine_noms($annee, $mois, $init, $script, $finurl, $ancre) .
	  $evt .
	  "</table>" .
	  $sd .
	  (!test_espace_prive() ? "" : http_calendrier_aide_mess()) .
	  "</div>";
}

// http://doc.spip.org/@http_calendrier_semaine_navigation
function http_calendrier_semaine_navigation($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre){

	$fin = mktime (1,1,1,$mois, $jour+6, $annee);
	$fjour = date("d",$fin);
	$fmois = date("m",$fin);
	$fannee = date("Y",$fin);
	$fin = date("Y-m-d", $fin);
	$debut = mktime (1,1,1,$mois, $jour, $annee);
	$djour = date("d",$debut)+0;
	$dmois = date("m",$debut);
	$dannee = date("Y",$debut);
	$debut = date("Y-m-d", $debut);
	$periode = (($dannee != $fannee) ?
		    (affdate($debut)." - ".affdate($fin)) :
		    (($dmois == $fmois) ?
		     ($djour ." - ".affdate_jourcourt($fin)) :
		     (affdate_jourcourt($debut)." - ".affdate_jourcourt($fin))));

  return
    http_calendrier_navigation($annee,
			       $mois,
			       $jour,
			       $echelle,
			       $partie_cal, 
			       $periode,
			       $script,
			       array($dannee, $dmois, ($djour-7), "semaine"),
			       array($fannee, $fmois, ($fjour+1), "semaine"),
			       'semaine',
			       $ancre);
}

// http://doc.spip.org/@http_calendrier_semaine_noms
function http_calendrier_semaine_noms($annee, $mois, $jour, $script, $finurl, $ancre){

	$bandeau = '';

	for ($j=$jour; $j<$jour+7;$j++){
		$nom = mktime(0,0,0,$mois,$j,$annee);
		$num = intval(date("d", $nom)) ;
		$numois = date("m",$nom);
		$nomjour = _T('date_jour_'. (1+date('w',$nom)));
		$clic = ($nomjour . " " . $num . (($num == 1) ? 'er' : '') .
			 ($ancre  ? ('/' . $numois) : ''));
		$bandeau .= 
		  "\n\t<th>" .
		  calendrier_href($script, date("Y",$nom), $numois, $num, 'jour', $finurl, $ancre, '', $clic, '', '', $clic) .
		  "</th>";
	}
	return "\n<tr>$bandeau</tr>";
}

// http://doc.spip.org/@http_calendrier_semaine_sept
function http_calendrier_semaine_sept($annee, $mois, $jour, $echelle, $partie_cal, $evt)
{
	global $spip_ecran, $spip_lang_left;

	$largeur =  ($spip_ecran == "large") ? 80 : 60;

	$today=date("Ymd");
	$total = '';

	for ($j=$jour; $j<$jour+7;$j++){
		$d = mktime(0,0,0,$mois, $j, $annee);
		$v = http_calendrier_ics($annee, $mois, $j, $echelle, $partie_cal, $largeur, $evt, '', ( (date("w",$d)==0 && test_espace_prive()) ? 
			  " jour_dimanche" :
			  ((date("Ymd", $d) == $today) ? 
			   " jour_encours" :
			   " jour_gris") ) ) ;
		$d = sprintf('D%4d-%02d-%02d', $annee,$mois,$j);
		$total .= "\n<td id='$d'>$v</td>";
	}
	return "\n<tr class='heure'>$total</tr>";
}


// http://doc.spip.org/@http_calendrier_jour
function http_calendrier_jour($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "large";

	$id = ($ancre ? $ancre : 'agenda') . "-nav";

	return 
	  "<div><div id='$id'></div>" .
	  "<table class='calendrier calendrier-$spip_ecran'>" .
	  http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal,
				     (nom_jour("$annee-$mois-$jour") . " " .
				      affdate_jourcourt("$annee-$mois-$jour")),
				     $script,
				     array($annee, $mois, ($jour-1), "jour"),
				     array($annee, $mois, ($jour+1), "jour"),
				     'jour',
				     $ancre) .
	  (!is_array($evt) ? ("<tr><td>$evt</td></tr>") :
	   (http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre) .
	    http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt))) .
	  "</table>" .
	  "</div>";
}

// http://doc.spip.org/@http_calendrier_jour_noms
function http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre){

	global $spip_ecran;

	if (!test_espace_prive()) return '';

	$finurl = calendrier_retire_defaults($echelle, $partie_cal);

	if ($spip_ecran != "large") {
	  $c = '';
	} else { $c = http_calendrier_ics_titre($annee,$mois,$jour-1,$script, $finurl, $ancre);
	}
	return
	  "\n<tr class='calendrier-titre-jour'><th>$c</th><th></th><th>" .
	  ("\n<div class='calendrier-titre'>" .
		    http_calendrier_ics_message($annee, $mois, $jour, true) .
		    '</div>') .
	  "</th><th></th><th>" .
	  http_calendrier_ics_titre($annee,$mois,$jour+1,$script, $finurl, $ancre) .
	  "</th></tr>";
}

// http://doc.spip.org/@http_calendrier_jour_sept
function http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;

	$gauche = (test_espace_prive() AND ($spip_ecran == "large"));

	if ($partie_cal!= DEFAUT_PARTIE_R) {
		$gauche = !$gauche ? '' : http_calendrier_ics($annee, $mois, $jour-1, $echelle, $partie_cal, 0, $evt);
		$mil = http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 300, $evt);
		$droite = (!test_espace_prive() ? '':
			   http_calendrier_ics($annee, $mois, $jour+1, $echelle, $partie_cal, 0, $evt));
	} else {
	  list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;

	  if ($sansduree)
	    foreach($sansduree as $d => $r) 
	      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
	  $gauche = (!$gauche  ?  "" : 
		   http_calendrier_sept_un($annee, $mois, $jour-1, $evenements, $script, '', $ancre)
		       );
	  $mil = http_calendrier_sept_un($annee, $mois, $jour, $evenements, $script, '', $ancre);
	  $droite = (!test_espace_prive() ? "" :http_calendrier_sept_un($annee, $mois, $jour+1,$evenements, $script, '', $ancre));
	}
	$d = sprintf('D%4d-%02d-%02d', $annee,$mois,$jour);
	if (!test_espace_prive())
		return "<tr class='calendrier-3jours'><td id='$d' colspan='5'>$mil</td></tr>";
	$gauche = !$gauche ? "<td colspan='3' id='$d'>" : "<td>$gauche</td><td></td><td id='$d'>";
	return  "<tr class='calendrier-3jours'>$gauche$mil</td><td></td><td>$droite</td></tr>";
}


/// Conversion d'un tableau de champ ics en des balises div positionnees    
/// Le champ categories indique la Classe de CSS a prendre
/// $echelle est le nombre de secondes representees par 1 pixel
/// $partie_cal donne l'intervalle des heures affichee
/// a travers la globale calendrier_partie ou sous la forme D_F

// http://doc.spip.org/@http_calendrier_ics
function http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, $largeur, $evt, $style='', $class='') {
	global $spip_lang_left;

	if (is_array($GLOBALS['calendrier_partie'][$partie_cal])) {
		$debut = $GLOBALS['calendrier_partie'][$partie_cal]['debut'];
		$fin = $GLOBALS['calendrier_partie'][$partie_cal]['fin'];
	} elseif (preg_match('/^(\d+)\D(\d+)$/', $partie_cal, $m))
		list(,$debut, $fin)  = $m;
	else {
		$debut = 7;
		$fin =21;
	}

	if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;

	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut, $fin, $echelle);
	$size = sprintf("%0.2f", DEFAUT_TAILLE_HEURE+(10/$echelle));
	$style .= "height:${dimjour}px;font-size:${size}em;";
	$date = date("Ymd", mktime(0,0,0,$mois, $jour, $annee));

	$avecheure = !isset($evt[1][$date]) ? '' : http_calendrier_ics_div($evt[1][$date], $date, $debut, $fin, $dimheure, $dimjour, $echelle, $largeur, $padding);

	$sansheure = !isset($evt[0][$date]) ? '' : http_calendrier_ics_trois($evt[0][$date], $largeur, $dimjour, $fontsize, '');


	return
	   "\n<div class='calendrier-jour$class' style='$style'>" .
	  http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $echelle) .
	  $avecheure .
	  "\n</div>" .
	  $sansheure;
}

function http_calendrier_ics_div($evts, $date, $debut, $fin, $dimheure, $dimjour, $echelle, $largeur, $padding)
{
	global $spip_lang_left;
	$total = '';
	$tous = 1 + count($evts);
	$i = $bas_prec = 0;

	foreach($evts as $evenement) {

		$d = $evenement['DTSTART'];
		$e = $evenement['DTEND'];
		$d_jour = substr($d,0,8);
		$e_jour = $e ? substr($e,0,8) : $d_jour;
		$debut_avant = false;
		$fin_apres = false;
			
		if ($d_jour > $date OR $e_jour < $date) continue;

		$i++;

		// Verifier si debut est jour precedent
		if (substr($d,0,8) < $date)
			{
				$heure_debut = 0; $minutes_debut = 0;
				$debut_avant = true;
				$radius_top = "";
			}
		else
			{
				$heure_debut = substr($d,-6,2);
				$minutes_debut = substr($d,-4,2);
			}

		if (!$e)
			{ 
				$heure_fin = $heure_debut ;
				$minutes_fin = $minutes_debut ;
				$bordure = "border-bottom: dashed 2px";
			}
		else
			{
				$bordure = "";
				if (substr($e,0,8) > $date) 
				{
					$heure_fin = 23; $minutes_fin = 59;
					$fin_apres = true;
					$radius_bottom = "";
				}
				else
				{
					$heure_fin = substr($e,-6,2);
					$minutes_fin = substr($e,-4,2);
				}
			}
			
		$opacity = ($debut_avant && $fin_apres) ? "calendrier-opacity60" : "";
						
		$haut = calendrier_top ("$heure_debut:$minutes_debut", $debut, $fin, $dimheure, $dimjour);
		$bas =  !$e ? $haut :calendrier_top ("$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour);
		$hauteur = calendrier_height ("$heure_debut:$minutes_debut", "$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour);

		if ($bas_prec >= $haut) $decale += 1;
		else $decale = DEFAUT_DECALE -
		       (($echelle >= DEFAUT_D_ECHELLE) ? 0 : 1);
		if ($bas > $bas_prec) $bas_prec = $bas;
			
		$colors = $evenement['CATEGORIES'];
		$url = isset($evenement['URL']) ? $evenement['URL'] : ''; 
		$desc = PtoBR(propre($evenement['DESCRIPTION']));
		$perso = construire_personne_ics($evenement['ATTENDEE']);
		$lieu = isset($evenement['LOCATION']) ?	$evenement['LOCATION'] : '';
		$sum = typo($evenement['SUMMARY']);
		if (!$sum) { $sum = $desc; $desc = '';}
		if (!$sum) { $sum = $lieu; $lieu = '';}
		if (!$sum) { $sum = textebrut($perso);}
		if ($sum) {
			if ($url)
			  $sum = http_href(quote_amp($url), $sum, attribut_html($desc), '', "calendrier-summary calendrier-url $colors");
			else $sum = "<span class='calendrier-summary'>$sum</span>";
		}

		if (($largeur > 90) && $desc)
			$sum .=  "\n<br /><span class='calendrier-noir'>$desc</span>";
		if ($lieu)
			$sum .= "\n<span class='calendrier-location'>$lieu</span>";
		if ($perso AND $perso != $sum)
			$sum .= "\n<span class='calendrier-attendee $colors'>$perso</span>";
		$sum = pipeline('agenda_rendu_evenement',array('args'=>array('evenement'=>$evenement,'type'=>'ics'),'data'=>$sum));

		$width = $largeur - ($padding<<1) - DEFAUT_DECALE;
		$fontsize = sprintf("%0.2f", 1+(10/$echelle));
		$style = "z-index:${i};${spip_lang_left}:${decale}em;top:${haut}px;height:${hauteur}px;width:${width}px;font-size:${fontsize}em;padding:${padding}px;$bordure";
		$total .= "\n<div class='$colors calendrier-evt' style='$style'
	onmouseover=\"this.style.zIndex=" . $tous . "\"
	onmouseout=\"this.style.zIndex=" . $i . "\">" .
				$sum . 
				"</div>";
	}
	return $total;
}

/// Affiche une grille horaire 
/// Selon l'echelle demandee, on affiche heure, 1/2 heure 1/4 heure, 5minutes.

// http://doc.spip.org/@http_calendrier_ics_grille
function http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $echelle)
{
	global $spip_lang_left, $spip_lang_right;
	$size = floor(14 / (1+($echelle/240)));
	$slice = floor($dimheure/(2*$size));
	if ($slice%2) $slice --;
	if (!$slice) $slice = 1;

	$total = '';
	for ($i = $debut; $i < $fin; $i++) {
		$k = "$i:00";
		$n = calendrier_top ($k, $debut, $fin, $dimheure, $dimjour);
		$total .= "\n<span style='$spip_lang_left:0;top:${n}px;'>$k</span>";
		for ($j=1; $j < $slice; $j++) 
		{
			$k = $i .':' . sprintf("%02d",floor(($j*60)/$slice));
			$n = calendrier_top ($k, $debut, $fin, $dimheure, $dimjour);
			$total .= "\n<span class='calendrier-jour-m' style='$spip_lang_left:0;top:${n}px;'>$k</span>";
		}
	}

	$n = calendrier_top ("$fin:00", $debut, $fin, $dimheure, $dimjour);

	$c = ($dimjour - $size - 2);

	return "\n<span style='border:0;$spip_lang_left:0;top:2px;'>0:00</span>" .
		$total .
		"\n<span style='$spip_lang_left:0;top:${n}px;'>$fin:00</span>" .
		"\n<span style='border:0;$spip_lang_left:0;top:${c}px;'>23:59</span>";
}

/// si la largeur le permet, les evenements sans duree, 
/// se placent a cote des autres, sinon en dessous

// http://doc.spip.org/@http_calendrier_ics_trois
function http_calendrier_ics_trois($evt, $largeur, $dimjour, $echelle, $border)
{
	global $spip_lang_left; 

	$types = array();
	foreach($evt as $v)
	  $types[isset($v['DESCRIPTION']) ? 'info_articles' :
		 (isset($v['DTSTART']) ? 'info_liens_syndiques_3' :
		  'info_breves_02')][] = $v;
	$res = '';
	foreach ($types as $k => $v) {
	  $res2 = '';
	  foreach ($v as $evenement) {
	    $res2 .= http_calendrier_sans_heure($evenement);
	  }
	  $res .= "\n<div class='calendrier-verdana calendrier-titre'>".
	    _T($k) .
	    "</div>" .
	    $res2;
	}
	$size = floor(14 / (1+($echelle/240)));
	if ($largeur > 90) {
		$largeur += (5*$size);
		$pos = "-$dimjour";
	} else { $largeur = (3*$size); $pos= 0; }
	  
	return "\n<div style='position: relative; z-index: 2; top: ${pos}px; margin-$spip_lang_left: " . $largeur . "px'>$res</div>";
}

// http://doc.spip.org/@http_calendrier_ics_titre
function http_calendrier_ics_titre($annee, $mois, $jour,$script, $finurl='', $ancre='')
{
	$date = mktime(0,0,0,$mois, $jour, $annee);
	$jour = date("d",$date);
	$mois = date("m",$date);
	$annee = date("Y",$date);
	$nom = affdate_jourcourt("$annee-$mois-$jour");
	return "<div class='calendrier-arial10 calendrier-titre'>" .
	  calendrier_href($script, $annee, $mois, $jour, 'jour', $finurl, $ancre, '', $nom, 'calendrier-noir','',$nom) .
	  "</div>";
}


// http://doc.spip.org/@http_calendrier_sans_date
function http_calendrier_sans_date($annee, $mois, $evenements)
{
  $r = $evenements[0+($annee . $mois . "00")];
  if (!$r) return "";
  $res = "\n<div class='calendrier-arial10 calendrier-titre'>".
    _T('info_mois_courant').
    "</div>";
  foreach ($r as $evenement) $res .= http_calendrier_sans_heure($evenement);
  return $res;
}


// http://doc.spip.org/@http_calendrier_sans_heure
function http_calendrier_sans_heure($ev)
{
	$desc = PtoBR(propre($ev['DESCRIPTION']));
	$sum = typo($ev['SUMMARY']);
	if (!$sum) $sum = $desc;
	$i = isset($ev['DESCRIPTION']) ? 11 : 9; // 11: article; 9:autre
	if ($ev['URL'])
	  $sum = http_href(quote_amp($ev['URL']), $sum, attribut_html($desc));
	$sum = pipeline('agenda_rendu_evenement',array('args'=>array('evenement'=>$ev,'type'=>'sans_heure'),'data'=>$sum));
	return "\n<div class='calendrier-arial$i calendrier-evenement'>" .
	  "<span class='" . $ev['CATEGORIES'] . "'>&nbsp;</span>&nbsp;$sum</div>"; 
}

// http://doc.spip.org/@http_calendrier_avec_heure
function http_calendrier_avec_heure($evenement, $amj)
{
	$jour_debut = substr($evenement['DTSTART'], 0,8);
	$jour_fin = substr($evenement['DTEND'], 0, 8);
	if ($jour_fin <= 0) $jour_fin = $jour_debut;
	if (($jour_debut <= 0) OR ($jour_debut > $amj) OR ($jour_fin < $amj))
	  return "";
	
	$desc = PtoBR(propre($evenement['DESCRIPTION']));
	$sum = $evenement['SUMMARY'];
	$u = $GLOBALS['meta']['pcre_u'];
	$sum = typo($sum);
	if (!$sum) $sum = $desc;
	if ($lieu = $evenement['LOCATION'])
	  $sum .= '<br />' . $lieu;
	if ($perso = construire_personne_ics($evenement['ATTENDEE']))
	  $sum .=  '<br />' . $perso;
	if ($evenement['URL'])
	  $sum = http_href(quote_amp($evenement['URL']), $sum, attribut_html($desc), 'border: 0');

	$sum = pipeline('agenda_rendu_evenement',array('args'=>array('evenement'=>$evenement,'type'=>'avec_heure'),'data'=>$sum));
	$deb_h = substr($evenement['DTSTART'],-6,2);
	$deb_m = substr($evenement['DTSTART'],-4,2);
	$fin_h = substr($evenement['DTEND'],-6,2);
	$fin_m = substr($evenement['DTEND'],-4,2);
	$opacity = $evenement['CATEGORIES'];
	
	if ($amj != $jour_debut AND $amj != $jour_fin) {
	    $opacity .= ' calendrier-opacity';
	  } else {
	  if ($deb_h >0 OR $deb_m > 0) {
	    if ((($deb_h > 0) OR ($deb_m > 0)) AND $amj == $jour_debut)
	      { $deb = $deb_h . ':' . $deb_m;}
	    else { 
	      $deb = '...'; 
	    }
	    if ((($fin_h > 0) OR ($fin_m > 0)) AND $amj == $jour_fin)
	      { $fin = $fin_h . ':' . $fin_m;}
	    else { 
	      $fin = '...'; 
	    }
	    $sum = "<div style='font-weight: bold;'>$deb-$fin</div>$sum";
	  }
	}
	return "\n<div class='calendrier-arial10 calendrier-evenement $opacity'>$sum\n</div>\n"; 
}

/// Gestion du champ ATTENDEE.
/// On admet un ID ou un mail, ou un tableau de ces choses.
/// Si c'est un ID, on va chercher le mail dans la table des auteurs, 
/// a defaut le nom.
/// Dans les deux cas, si on a bien un mail, on place le pseudo-protocole mailto

function construire_personne_ics($personnes)
{
	$r = is_array($personnes) ? $personnes : array($personnes);
	foreach ($r as $k => $p) {
		if (!is_numeric($p)) {
			$mail = email_valide($p);
			if (preg_match('/^[^@]+/', $mail, $m))
				$r[$k] = preg_replace('/[.]/', ' ', $m[0]);
		} else {
			$m = sql_fetsel("email, nom", 'spip_auteurs', "id_auteur=$p");
			if ($m) {
			  $mail = $m['email'];
			  $r[$k] = $m['nom'] ? $m['nom'] : ($mail ? $mail : $p);
			}
		}
		if ($mail)
			$r[$k] = "<a href='mailto:$mail'>" . $r[$k] . "</a>";
	}
	return join(' ', $r);
}

/// fabrique un agenda sur 3 mois. 
/// fonction appelee par le filtre agenda_affiche du squelette agenda_trimestre,
/// lui-meme issu d'un Ajax construit par la fonction http_calendrier_navigation,
/// qui fournit via $self la query-string de l'appel anterieur.
/// Celle-ci est vide sur les squelettes agenda std cependant.

function http_calendrier_trimestre($annee, $mois, $jour, $echelle, $partie_cal, $self, $ancre, $evt)
{
	global $spip_lang_right, $spip_lang_left, $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "large";

	$script = preg_match('/\bscript=(\w+)/', $self, $m) ? $m[1]:'';

	$script = (preg_match('/\bprive=(.)/', $self, $m) ? $m[1] : 0)
	? generer_url_ecrire($script) : generer_url_public($script);

	$fin = preg_replace('/^.*[?]page=agenda_trimestre/', '', $self)
	. calendrier_retire_defaults($echelle, $partie_cal);

	$res = "\n<tr><td colspan='3' style='text-align:$spip_lang_left;'>";

	$annee_avant = $annee - 1;
	$annee_apres = $annee + 1;

	for ($i=$mois; $i < 13; $i++) {
	  $nom = nom_mois("$annee_avant-$i-1");
	  $res .= calendrier_href($script,$annee_avant, $i, 1, "mois", $fin, $ancre,'', ($nom . ' ' . $annee_avant), 'calendrier-annee','', $nom) ;
			}
	for ($i=1; $i < $mois - 1; $i++) {
	  $nom = nom_mois("$annee-$i-1");
	  $res .= calendrier_href($script,$annee, $i, 1, "mois", $fin, $ancre,'',($nom . ' ' . $annee),'calendrier-annee','', $nom);
	}
	
	$script .= $fin ; // http_calendrier_agenda devrait avoir cet arg en +

	$res .= "</td></tr>"
	. "\n<tr><td class='calendrier-tripleagenda'>"
	. http_calendrier_agenda($annee, $mois-1, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
	. "</td>\n<td class='calendrier-tripleagenda'>"
	. http_calendrier_agenda($annee, $mois, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
	. "</td>\n<td class='calendrier-tripleagenda'>"
	. http_calendrier_agenda($annee, $mois+1, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
	. "</td>"
	. "</tr>"
	. "\n<tr><td colspan='3' style='text-align:$spip_lang_right;'>";

	for ($i=$mois+2; $i <= 12; $i++) {
	  $nom = nom_mois("$annee-$i-1");
	  $res .= calendrier_href($script, $annee, $i, 1, "mois", $fin, $ancre,'',$nom . ' ' . $annee, 'calendrier-annee','', $nom);
			}
	for ($i=1; $i < $mois+1; $i++) {
	  $nom = nom_mois("$annee_apres-$i-1");
	  $res .= calendrier_href($script, $annee_apres, $i, 1, "mois", $fin, $ancre,'',$nom . ' ' . $annee_apres, 'calendrier-annee','',$nom);
			}
	$res .= "</td></tr>";

	$id = ($ancre ? $ancre : 'agenda') . "-nav";

	return 
		"<div><div id='$id'></div>" .
		"<table class='calendrier-cadreagenda calendrier-$spip_ecran'
		onmouseover=\"$('#$id').show();\"
		onmouseout=\"$('#$id').hide();\">$res</table>" .
		"</div>";
}

/// Bandeau superieur d'un calendrier selon son $type (jour/mois/annee):
/// 2(+1) icones vers les 2 autres types, a la meme date $jour $mois $annee
/// 2 icones de loupes pour zoom sur la meme date et le meme type
/// 4 icones de selection de demi-journees, idem
/// 2 fleches appelant le $script sur les periodes $pred/$suiv avec une $ancre
/// 1 icone pour amener sur aujourd'hui au clic, et donner un triple agenda au survol
/// et le $nom du calendrier

// http://doc.spip.org/@http_calendrier_navigation
function http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal, $nom, $script, $args_pred, $args_suiv, $type, $ancre)
{
	global $spip_lang_right, $spip_lang_left;

	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;
	$arg_echelle = ($echelle != DEFAUT_D_ECHELLE) ? "&amp;echelle=$echelle" : '';
	$arg_partie = ($partie_cal != DEFAUT_PARTIE) ? "&amp;partie_cal=$partie_cal" : '';

	if ($args_pred) {
	  list($a, $m, $j, $t) = $args_pred;
	  $args_pred = calendrier_href($script, $a, $m, $j, $t, "$arg_echelle$arg_partie", $ancre,
				       "fleche-$spip_lang_left.png",
				       _T('precedent'),
				       'calendrier-png',
				       '&lt;&lt;&lt;');
	}

	if ($args_suiv) {
	  list($a, $m, $j, $t) = $args_suiv;
	  $args_suiv = calendrier_href($script, $a, $m, $j, $t, "$arg_echelle$arg_partie", $ancre,
				       "fleche-$spip_lang_right.png",
				       _T('suivant'),
				       'calendrier-png',
				       '&gt;&gt;&gt;');
	}

	$today = getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	if (preg_match('/[?&;](exec=(\w+)(&(amp;)?)?)/', $script, $regs)) {
	  $page = $regs[2]; $prive = 1; $raz = $regs[1];
	} elseif (preg_match('/[?&;](page=(\w+)(&(amp;)?)?)/', $script, $regs)) {
	  $page = $regs[2]; $prive = 0; $raz = $regs[1];
	} else $page = $prive = $raz = '';

	$href = generer_url_public('agenda_trimestre', substr(str_replace($raz, '', $script), strpos($script, '?')+1));
	$href = parametre_url($href, 'script', $page);
	$href = parametre_url($href, 'prive', $prive);
	$href = parametre_url($href, 'ancre', $ancre);

	$href = calendrier_args_date($href, $annee, $mois, $jour, '', "$arg_echelle" . ((DEFAUT_PARTIE == DEFAUT_PARTIE_R) ? '' : ("&amp;partie_cal=" . DEFAUT_PARTIE_R)));

	$id = ($ancre ? $ancre : 'agenda') . "-nav";
	$onmouseover = "if (!this.trimestre)\n{this.trimestre=!charger_node_url('$href', document.getElementById('$id'));}\n;$('#$id').css('visibility','visible').show();";

	return 
	  "\n<caption>"
	  . "\n<span style='float: $spip_lang_left;'>"
	  . calendrier_href($script,$annee_today, $mois_today, $jour_today, $type, "$arg_echelle$arg_partie", $ancre,
			    "cal-today.gif",
			    _T("ecrire:info_aujourdhui"),
			    (($annee == $annee_today && $mois == $mois_today && (($type == 'mois')  || ($jour == $jour_today)))
			     ? "calendrier-opacity" : ""),
			    '','','',
			    ("\nonmouseover=\"$onmouseover\"" ))
	  . "&nbsp;"
	  . $args_pred 
	  . $args_suiv
	  . "&nbsp;&nbsp;"
	  . $nom
	  . (!test_espace_prive() ? '' :  aide("messcalen"))
	  . "</span>"
	  . "&nbsp;\n" // Sans "nbsp" Safari (5.1) n'affiche aucun des 2 span !!
	  . "<span style='float: $spip_lang_right;'>"
	  . (($type == "mois") ? '' :
		calendrier_navigation_heures($annee, $mois, $jour, $echelle, $partie_cal, $script, $type, $ancre))
	  . calendrier_navigation_type($annee, $mois, $jour, "$arg_echelle$arg_partie", $script, $type)
	  . "</span></caption>";
}

/// Bloc de navigation pour zoom sur les heures

function calendrier_navigation_heures($annee, $mois, $jour, $echelle, $partie_cal, $script, $type, $ancre)
{
  return
	calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults($echelle, DEFAUT_PARTIE_R), $ancre,
				 "sans-heure.gif",
				 _T('sans_heure'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_R) ? " calendrier-opacity" : ""))
	      .	calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults($echelle, DEFAUT_PARTIE_T), $ancre,
				 "heures-tout.png",
				 _T('cal_jour_entier'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_T) ? " calendrier-opacity" : ""))
	      .	calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults($echelle, DEFAUT_PARTIE_M), $ancre,
				 "heures-am.png",
				 _T('cal_matin'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_M) ? " calendrier-opacity" : ""))

	      . calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults($echelle, DEFAUT_PARTIE_S), $ancre,
				 "heures-pm.png",
				 _T('cal_apresmidi'), 
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_S) ? " calendrier-opacity" : ""))
		  . "&nbsp;"
		. calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults(floor($echelle * 1.5), $partie_cal),
				$ancre,
				"loupe-moins.gif",
				_T('info_zoom'). '-')
		. calendrier_href($script, $annee, $mois, $jour, $type, calendrier_retire_defaults(floor($echelle / 1.5), $partie_cal),
				$ancre, 
				"loupe-plus.gif",
				_T('info_zoom'). '+')
	      ;

}

/// Bloc de navigation sur le type mois/semaine/jour

function calendrier_navigation_type($annee, $mois, $jour, $finurl, $script, $type)
{
	return
	   calendrier_href($script,$annee, $mois, $jour, "jour", "$arg_echelle$arg_partie", $ancre,"cal-jour.gif",
			  _T('cal_par_jour'),
			  (($type == 'jour') ? " calendrier-opacity" : ''))

	  . calendrier_href($script,$annee, $mois, $jour, "semaine", "$arg_echelle$arg_partie", $ancre, "cal-semaine.gif",
			  _T('cal_par_semaine'), 
			  (($type == 'semaine') ?  " calendrier-opacity" : "" ))

	  . calendrier_href($script,$annee, $mois, $jour, "mois", "$arg_echelle$arg_partie", $ancre,"cal-mois.gif",
			  _T('cal_par_mois'),
			    (($type == 'mois') ? "calendrier-opacity" : "" ));
}


/// agenda mensuel 

// http://doc.spip.org/@http_calendrier_agenda
function http_calendrier_agenda ($annee, $mois, $jour_ved, $mois_ved, $annee_ved, $semaine = false,  $script='', $ancre='', $evt='') {

  if (!$script) $script =  $GLOBALS['PHP_SELF'] ;

  if (!$mois) {$mois = 12; $annee--;}
  elseif ($mois==13) {$mois = 1; $annee++;}
  if (!$evt) $evt = quete_calendrier_agenda($annee, $mois);
  $nom = affdate_mois_annee("$annee-$mois-1");
  return 
    "<div class='calendrier-titre calendrier-arial10'>" .
    calendrier_href($script, $annee, $mois, 1, 'mois', '', $ancre,'', $nom,'','',    $nom,'color: black;') .
    "<table width='100%'>" .
    http_calendrier_agenda_rv ($annee, $mois, $evt,				
			        'calendrier_href', $script, $ancre,
			        $jour_ved, $mois_ved, $annee_ved, 
				$semaine) . 
    "</table>" .
    "</div>";
}

/// typographie un mois sous forme d'un tableau de 7 colonnes

// http://doc.spip.org/@http_calendrier_agenda_rv
function http_calendrier_agenda_rv ($annee, $mois, $les_rv, $fclic,
				    $script='', $ancre='',
				    $jour_ved='', $mois_ved='', $annee_ved='',
				    $semaine='') {
	global $spip_lang_left, $spip_lang_right;

	// Former une date correcte (par exemple: $mois=13; $annee=2003)
	$date_test = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date_test);
	$annee = annee($date_test);
	if ($semaine) 
	{
		$jour_semaine_valide = date("w",mktime(1,1,1,$mois_ved,$jour_ved,$annee_ved));
		if ($jour_semaine_valide==0) $jour_semaine_valide=7;
		$debut = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+1,$annee_ved);
		$fin = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+7,$annee_ved);
	} else { $debut = $fin = '';}
	
	$today=getdate(time());
	$jour_today = $today["mday"];
	$cemois = ($mois == $today["mon"] AND $annee ==  $today["year"]);

	$total = '';
	$ligne = '';
	$jour_semaine = date("w", mktime(1,1,1,$mois,1,$annee));
	if ($jour_semaine==0) $jour_semaine=7;
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n<td></td>";
	for ($j=1; (checkdate($mois,$j,$annee)); $j++) {
		$class = 'calendrier-agenda-abb11';
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		$title = "$j-$mois-$annee";
		if ($jour_semaine==0) $jour_semaine=7;

		if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {

		  $type = 'jour';
		} else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		  $class .= 
 		      (($jour_semaine==1) ? " calendrier-$spip_lang_left"  :
		       (($jour_semaine==7) ? " calendrier-$spip_lang_right" :
			''));
		  $type = ($semaine ? 'semaine' : 'jour') ;
		} else {
		  if ($j == $jour_today AND $cemois) {
			$toile = ' jour_encours';
		  } else {
		    if ($jour_semaine == 7) {
		      $toile = " jour_dimanche";
		    } else {
		      $toile = ' jour_gris';
		    }
		    if (isset($les_rv[$j])) {
		      $toile = " jour_pris$toile";
		      $title = textebrut($les_rv[$j]['SUMMARY']);
		    }
		  }
		  $class .= $toile;
		  $type = ($semaine ? 'semaine' : 'jour') ;
		}
		$corps = $fclic($script, $annee, $mois, $j,$type, '', $ancre,'', $title ,'','', $j);
		$ligne .= "\n<td class='$class'>$corps</td>";
		if ($jour_semaine==7) 
		    {
		      if ($ligne) $total .= "\n<tr>$ligne</tr>";
		      $ligne = '';
		    }
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne</tr>");
}



/// Fonctions pour la messagerie, la page d'accueil et les gadgets

// http://doc.spip.org/@http_calendrier_messages
function http_calendrier_messages($annee='', $mois='', $jour='', $heures='', $partie_cal='', $echelle='')
{
	$evtm = quete_calendrier_agenda($annee, $mois);
	if ($evtm OR !$heures)
		$evtm = http_calendrier_agenda($annee, $mois, $jour, $mois, $annee, false, generer_url_ecrire('calendrier'), '', $evtm);
	else $evtm= '';

	$evtt = http_calendrier_rv(quete_calendrier_taches_annonces(),"annonces")
	  . http_calendrier_rv(quete_calendrier_taches_pb(),"pb")
	  . http_calendrier_rv(quete_calendrier_taches_rv(), "rv");

	$evtr= '';
	if ($heures) {
		$date = date("$annee-$mois-$jour");
		$datef = "'$date $heures'";
		if ($heures = quete_calendrier_interval_rv("'$date'", $datef))
		  $evtr = http_calendrier_ics_titre($annee,$mois,$jour,generer_url_ecrire('calendrier')) . http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 90, array('', $heures), '', ' calendrier-msg');
	}
	return array($evtm, $evtt, $evtr);
}

// http://doc.spip.org/@http_calendrier_rv
function http_calendrier_rv($messages, $type) {

	$total = $date_rv = '';
	if (!$messages) return $total;
	$connect_quand = $GLOBALS['visiteur_session']['quand'];

	foreach ($messages as $row) {
		$rv = ($row['location'] == 'oui');
		$date = $row['dtstart'];
		$date_fin = $row['dtend'];
		if ($row['category']=="pb") $bouton = "pense-bete";
		else if ($row['category']=="affich") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"\n<div  class='calendrier-arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
			$date_rv = $date_jour;
			$rv =
		((affdate($date) == affdate($date_fin)) ?
		 ("\n<div class='calendrier-arial9 fond-agenda'>"
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "\n<div class='calendrier-arial9 fond-agenda' style='text-align: center;'>"
		  . heures($date).":".minutes($date)."<br />...</div>" ));
		}

		$c = (strtotime($date) <= $connect_quand) ? '' : " color: red;";
		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		  http_href($row['url'],
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;")) : 
				      http_img_pack($bouton.".gif", $bouton, ""))) .
		"</td>\n" .
		"<td valign='middle'><div style='font-weight: bold;$c'>" .
		$rv .
		http_href($row['url'], typo($row['summary']), '', '', 'calendrier-verdana') .
		"</div></td></tr>";
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "\n<table>" .
	  $total .
	  "</table>" .
	  fin_cadre_enfonce(true);
}

// http://doc.spip.org/@calendrier_categories
function calendrier_categories($table, $num, $objet)
{
  if (function_exists('generer_calendrier_class'))
    return generer_calendrier_class($table, $num, $objet);
  else {
    // cf agenda.css
    $num= sql_getfetsel((($objet != 'id_breve') ? 'id_secteur' : 'id_rubrique') . " AS id", $table, "$objet=$num");

    return 'calendrier-couleur' . (($num%14)+1);
  }
}

// http://doc.spip.org/@http_calendrier_ics_message
function http_calendrier_ics_message($annee, $mois, $jour, $large)
{	

  if (!autoriser('ecrire')) return '';

  $b = _T("lien_nouvea_pense_bete");
  $v = _T("lien_nouveau_message");
  $j=  _T("lien_nouvelle_annonce");

  return "&nbsp;" .
    http_href(generer_action_auteur("editer_message","pb/$annee-$mois-$jour"), 
	       ($large ? $b : '&nbsp;'), 
	      attribut_html($b),
	      'color: blue;',
	      'calendrier-arial10 pense-bete') .
    "\n" .
    http_href(generer_action_auteur("editer_message","normal/$annee-$mois-$jour"), 
	       ($large ? $v : '&nbsp;'), 
	      attribut_html($v),
	      'color: green;',
	      'calendrier-arial10 message') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
    http_href(generer_action_auteur("editer_message","affich/$annee-$mois-$jour"), 
		($large ? $j : '&nbsp;'), 
		attribut_html($j),
		'color: #ff9900;',
		'calendrier-arial10 annonce')));
}

// http://doc.spip.org/@http_calendrier_aide_mess
function http_calendrier_aide_mess()
{
  global $spip_lang_left;

  if (!autoriser('ecrire')) return '';
  return
    "\n<br /><br /><br />" .
    "\n<div class='arial1 spip_xx-small'>" .
    "<span style='text-align: $spip_lang_left; font-weight: bold;'> " . _T('info_aide'). "</span>" .
    "<div class='pense-bete'>" ._T('info_symbole_bleu')."\n</div>" .
    "<div class='message'>" . _T('info_symbole_vert')."\n</div>" .
    "<div class='annonce'>" . _T('info_symbole_jaune')."\n</div>" .
    "</div>\n";
 }

//------- fonctions d'appel MySQL. 
// au dela cette limite, pas de production HTML

// http://doc.spip.org/@quete_calendrier_mois
function quete_calendrier_mois($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(0,0,0,$mois+1,1,$annee)) .
	" 00:00:00'";
	return array($avant, $apres);
}

// http://doc.spip.org/@quete_calendrier_semaine
function quete_calendrier_semaine($annee,$mois,$jour) {
	$w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
	if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
	$debut = $jour-$w_day;
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$debut,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut+7,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

/// ici on prend en fait le jour, la veille et le lendemain

// http://doc.spip.org/@quete_calendrier_jour
function quete_calendrier_jour($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$jour-1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$jour+1,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

/// retourne un tableau de 2 tableaux indexes par des dates
/// - le premier indique les evenements du jour, sans indication de duree
/// - le deuxime indique les evenements commencant ce jour, avec indication de duree

// http://doc.spip.org/@quete_calendrier_interval
function quete_calendrier_interval($limites) {
	include_spip('inc/urls');
	list($avant, $apres) = $limites;
	$evt = array();
	quete_calendrier_interval_articles($avant, $apres, $evt);
	quete_calendrier_interval_breves($avant, $apres, $evt);
	quete_calendrier_interval_rubriques($avant, $apres, $evt);
	return array($evt, quete_calendrier_interval_rv($avant, $apres));
}

// http://doc.spip.org/@quete_calendrier_interval_forums
function  quete_calendrier_interval_forums($limites, &$evenements) {
	list($avant, $apres) = $limites;
	$result=sql_select("DISTINCT titre, date_heure, id_forum",	"spip_forum", "date_heure >= $avant AND date_heure < $apres", '',  "date_heure");
	while($row=sql_fetch($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_forum'];
		if (autoriser('voir','forum',$id))
			$evenements[$amj][]=
			array(
				'URL' => generer_url_entite($id, 'forum'),
				'CATEGORIES' => 'calendrier-couleur7',
				'SUMMARY' => $row['titre'],
				'DTSTART' => date_ical($row['date_heure']));
	}
}

/// 3 fonctions retournant les evenements d'une periode
/// le tableau retourne est indexe par les balises du format ics
/// afin qu'il soit facile de produire de tels documents.
/// L'URL de chacun de ces evenements est celle de l'espace prive
/// pour faciliter la navigation, ce qu'on obtient utilisant 
/// le 4e argument des fonctions generer_url_ecrire_$table 

// http://doc.spip.org/@quete_calendrier_interval_articles
function quete_calendrier_interval_articles($avant, $apres, &$evenements) {
	
  $result=sql_select('id_article, titre, date, descriptif, chapo,  lang', 'spip_articles', "statut='publie' AND date >= $avant AND date < $apres", '', "date");

	if ($GLOBALS['meta']['multi_articles'] == 'oui') {
	  include_spip('inc/lang_liste');
	  $langues = $GLOBALS['codes_langues'];
	} else $langues = array();
	while($row=sql_fetch($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_article'];
		if (autoriser('voir','article',$id))
			$evenements[$amj][]=
			    array(
				'CATEGORIES' => calendrier_categories('spip_articles', $id, 'id_article'),
				'DESCRIPTION' => $row['descriptif'] ? $row['descriptif'] : $langues[$row['lang']],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_ecrire_article($id, '','','prop'));
	}
}

// http://doc.spip.org/@quete_calendrier_interval_rubriques
function quete_calendrier_interval_rubriques($avant, $apres, &$evenements) {
	
  $result=sql_select('DISTINCT R.id_rubrique, titre, descriptif, date', 'spip_rubriques AS R, spip_documents_liens AS L', "statut='publie' AND	date >= $avant AND	date < $apres AND	R.id_rubrique = L.id_objet AND L.objet='rubrique'",'', "date");
	while($row=sql_fetch($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_rubrique'];
		if (autoriser('voir','rubrique',$id))
			$evenements[$amj][]=
			    array(
				'CATEGORIES' => calendrier_categories('spip_rubriques', $id, 'id_rubrique'),
				'DESCRIPTION' => $row['descriptif'],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_ecrire_rubrique($id, '','', 'prop'));
	}
}

// http://doc.spip.org/@quete_calendrier_interval_breves
function quete_calendrier_interval_breves($avant, $apres, &$evenements) {
  $result=sql_select("id_breve, titre, date_heure, id_rubrique", 'spip_breves',	"statut='publie' AND date_heure >= $avant AND date_heure < $apres", '', "date_heure");
	while($row=sql_fetch($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_breve'];
		$ir = $row['id_rubrique'];
		if (autoriser('voir','breve',$id))
			$evenements[$amj][]=
			array(
			      'URL' => generer_url_ecrire_breve($id, '','', 'prop'),
			      'CATEGORIES' => calendrier_categories('spip_breves', $ir, 'id_breve'),
			      'SUMMARY' => $row['titre']);
	}
}

// http://doc.spip.org/@quete_calendrier_interval_rv
function quete_calendrier_interval_rv($avant, $apres) {
	global $connect_id_auteur;
	$evenements= $auteurs = array();
	if (!$connect_id_auteur) return $evenements;
	$result=sql_select("M.id_message, M.titre, M.texte, M.date_heure, M.date_fin, M.type", "spip_messages AS M LEFT JOIN spip_auteurs_messages AS L ON (L.id_message=M.id_message)", "(L.id_auteur=$connect_id_auteur OR M.type='affich') AND M.rv='oui'  AND ((M.date_fin >= $avant OR M.date_heure >= $avant) AND M.date_heure <= $apres) AND M.statut='publie'", "M.id_message", "M.date_heure");
	while($row=sql_fetch($result)){
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$type=$row["type"];
		$id_message=$row['id_message'];

		if ($type=="pb")
		  $cat = 'calendrier-couleur2';
		else {
		  if ($type=="affich")
		  $cat = 'calendrier-couleur4';
		  else {
		    if ($type!="normal")
		      $cat = 'calendrier-couleur12';
		    else {
		      $cat = 'calendrier-couleur9';
		      $auteurs = array_map('array_shift', sql_allfetsel('id_auteur', 'spip_auteurs_messages', "id_message=$id_message AND id_auteur!=$connect_id_auteur"));
		    }
		  }
		}

		$jour_avant = substr($avant, 9,2);
		$mois_avant = substr($avant, 6,2);
		$annee_avant = substr($avant, 1,4);
		$jour_apres = substr($apres, 9,2);
		$mois_apres = substr($apres, 6,2);
		$annee_apres = substr($apres, 1,4);
		$ical_apres = date_anneemoisjour("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois 
 		$j = 0;
		$amj = date_anneemoisjour("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));

		while ($amj <= $ical_apres) {
		if (!($amj == date_anneemoisjour($date_fin) AND preg_match(",00:00:00,", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
			$evenements[$amj][$id_message]=
			  array(
				'URL' => generer_url_ecrire("message","id_message=$id_message"),
				'DTSTART' => date_ical($date_heure),
				'DTEND' => date_ical($date_fin),
				'DESCRIPTION' => $row['texte'],
				'SUMMARY' => $row['titre'],
				'CATEGORIES' => $cat,
				'ATTENDEE' => $auteurs);
			
			$j ++; 
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
			
			$amj = date_anneemoisjour($ladate);

		}

	}
  return $evenements;
}

/// fonction SQL, pour la messagerie

// http://doc.spip.org/@tache_redirige
function tache_redirige ($row) {

	$m = $row['description'];
	if (substr($m,0,1) == '=')
	  if ($m = chapo_redirige(substr($m,1), true))
		return $m;
	return generer_url_ecrire("message", "id_message=".$row['uid']);
}

// http://doc.spip.org/@quete_calendrier_taches_annonces
function quete_calendrier_taches_annonces () {
	global $connect_id_auteur;

	if (!$connect_id_auteur) return array();

	$r = sql_allfetsel("texte AS description, id_message AS uid, date_heure AS dtstart, date_fin AS dtend, titre AS summary, type AS category, rv AS location", "spip_messages", "type = 'affich' AND rv != 'oui' AND statut = 'publie'", "", "date_heure DESC");

	foreach ($r as $k => $row) $r[$k]['url'] = tache_redirige($row);
	return $r;
}

// http://doc.spip.org/@quete_calendrier_taches_pb
function quete_calendrier_taches_pb () {
	global $connect_id_auteur;

	if (!$connect_id_auteur) return array();

	$r = sql_allfetsel("texte AS description, id_message AS uid, date_heure AS dtstart, date_fin AS dtend, titre AS summary, type AS category, rv AS location", "spip_messages", "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'");

	foreach ($r as $k => $row) $r[$k]['url'] = tache_redirige($row);
	return $r;
}

// http://doc.spip.org/@quete_calendrier_taches_rv
function quete_calendrier_taches_rv () {
	global $connect_id_auteur;

	if (!$connect_id_auteur) return array();

	$r = sql_allfetsel("M.texte AS description, M.id_message AS uid, M.date_heure AS dtstart, M.date_fin AS dtend, M.titre AS summary, M.type AS category, M.rv AS location", "spip_messages AS M LEFT JOIN spip_auteurs_messages AS L ON (L.id_message=M.id_message)", "(L.id_auteur=$connect_id_auteur OR M.type='affich') AND M.rv='oui' AND ( (M.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND M.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH))	OR (M.date_heure < NOW() AND M.date_fin > NOW() )) AND M.statut='publie'", "M.id_message",  "M.date_heure");
	foreach ($r as $k => $row) $r[$k]['url'] = tache_redirige($row);
	return  $r;
}

// http://doc.spip.org/@quete_calendrier_agenda
function quete_calendrier_agenda ($annee, $mois) {
	global $connect_id_auteur;

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages = sql_select("M.titre AS summary, M.texte AS description, M.id_message AS uid, M.date_heure", "spip_messages AS M, spip_auteurs_messages AS L", "((L.id_auteur=$connect_id_auteur AND L.id_message=M.id_message) OR M.type='affich') AND M.rv='oui' AND M.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND M.statut='publie'");
	while($row=sql_fetch($result_messages)) {
		$rv[journum($row['date_heure'])] = $row;
	}
	return $rv;
}

?>
