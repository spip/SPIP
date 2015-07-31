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

include_spip('inc/minipres'); 
include_spip('inc/layer');
include_spip('inc/texte'); // inclut inc_filtre

charger_generer_url();

//  Typographie generale des calendriers de 3 type: jour/semaine/mois(ou plus)

// Notes: pour toutes les fonctions ayant parmi leurs parametres
// annee, mois, jour, echelle, partie_cal, script, ancre
// ceux-ci apparaissent TOUJOURS dans cet ordre 

define('DEFAUT_D_ECHELLE',120); # 1 pixel = 2 minutes

define('DEFAUT_PARTIE_M', "matin");
define('DEFAUT_PARTIE_S', "soir");
define('DEFAUT_PARTIE_T', "tout");
define('DEFAUT_PARTIE_R', "sansheure");
define('DEFAUT_PARTIE', DEFAUT_PARTIE_R);

// 
// Utilitaires sans html ni sql
//

// utilitaire de separation script / ancre
// et de retrait des arguments a remplacer
// (a mon avis cette fonction ne sert a rien, puisque parametre_url()
// sait remplacer les arguments au bon endroit -- Fil)
// Pas si simple: certains param ne sont pas remplaces 
// et doivent reprendre leur valeur par defaut -- esj.
// http://doc.spip.org/@calendrier_retire_args_ancre
function calendrier_retire_args_ancre($script)
{
	$script = str_replace('&amp;', '&', $script);
  $script = str_replace('?bonjour=oui&?','?',$script);
  if (preg_match(',^(.*)#([^=&]*)$,',$script, $m)) {
	  $script = $m[1];
	  $ancre = $m[2];
  } else { $ancre = ''; }
  if ($script[strlen($script)-1] == '?')  $script = substr($script,0,-1);
  foreach(array('echelle','jour','mois','annee', 'type', 'partie_cal') as $arg) {
		$script = preg_replace("/([?&])$arg=[^&]*&/",'\1', $script);
		$script = preg_replace("/([?&])$arg=[^&]*$/",'\1', $script);
	}
  if (in_array(substr($script,-1),array('&','?'))) $script =   substr($script,0,-1);
  return array(quote_amp($script), $ancre);
}

// tous les liens de navigations sont issus de cette fonction
// on peut definir generer_url_date et un htacces pour simplifier les URL

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

// http://doc.spip.org/@calendrier_href
function calendrier_href($script, $annee, $mois, $jour, $type, $fin, $ancre, $img, $titre, $class='', $alt='', $clic='', $style='', $evt='')
{
	$c = ($class ? " class=\"$class\"" : '');
	$h = calendrier_args_date($script, $annee, $mois, $jour, $type, $fin);
	$a = ($ancre ? "#$ancre" : '');
	$t = ($titre ? " title=\"$titre\"" : '');
	$s = ($style ? " style=\"$style\"" : '');

	$moi = preg_match("/exec=" . $GLOBALS['exec'] .'$/', $script);
	if ($img) $clic =  http_img_pack($img, ($alt ? $alt : $titre), $c);
	  // pas d'Ajax pour l'espace public pour le moment ou si indispo
	if (_DIR_RESTREINT  || !$moi || (_SPIP_AJAX !== 1 ))

		return http_href("$h$a", $clic, $titre, $style, $class, $evt);
	else {
		$evt .= "\nonclick=" . ajax_action_declencheur($h,$ancre);
		return "<a$c$s\nhref='$h$a'$evt>$clic</a>";
	}
}

# prend une heure de debut et de fin, ainsi qu'une echelle (seconde/pixel)
# et retourne un tableau compose
# - taille d'une heure
# - taille d'une journee
# - taille de la fonte
# - taille de la marge

// http://doc.spip.org/@calendrier_echelle
function calendrier_echelle($debut, $fin, $echelle)
{
  if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;
  if ($fin <= $debut) $fin = $debut +1;

  $duree = $fin - $debut;
  $dimheure = floor((3600 / $echelle));
  return array($dimheure,
	       (($duree+2) * $dimheure),
	       floor (14 / (1+($echelle/240))),
	       floor(240 / $echelle));
}

# Calcule le "top" d'une heure

// http://doc.spip.org/@calendrier_top
function calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize) {
	
	$h_heure = substr($heure, 0, strpos($heure, ":"));
	$m_heure = substr($heure, strpos($heure,":") + 1, strlen($heure));
	$heure100 = $h_heure + ($m_heure/60);

	if ($heure100 < $debut) $heure100 = ($heure100 / $debut) + $debut - 1;
	if ($heure100 > $fin) $heure100 = (($heure100-$fin) / (24 - $fin)) + $fin;

	$top = floor(($heure100 - $debut + 1) * $dimheure);

	return $top;	
}

# Calcule la hauteur entre deux heures
// http://doc.spip.org/@calendrier_height
function calendrier_height ($heure, $heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) {

	$height = calendrier_top ($heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) 
				- calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize);

	$padding = floor(($dimheure / 3600) * 240);
	$height = $height - (2* $padding + 2); // pour padding interieur
	
	if ($height < ($dimheure/4)) $height = floor($dimheure/4); // eviter paves totalement ecrases
	
	return $height;	
}

//
// init: calcul generique des evenements a partir des tables SQL
//

// http://doc.spip.org/@http_calendrier_init
function http_calendrier_init($time='', $ltype='', $lechelle='', $lpartie_cal='', $script='', $evt='')
{
	global $mois, $annee, $jour, $type, $echelle, $partie_cal;
	if (!$time) 
	  {
	    if (!$mois)
	      $time = time();
	    else
	      $time = mktime(0,0,0,$mois,$jour,$annee);
	    $type= 'mois';
	  }

	$jour = date("d",$time);
	$mois = date("m",$time);
	$annee = date("Y",$time);
	if (!$ltype) $ltype = $type ? $type : 'mois';
	if (!$lechelle) $lechelle = $echelle;


	if (!$lpartie_cal) 
		$lpartie_cal = $partie_cal ? $partie_cal : DEFAUT_PARTIE;
	list($script, $ancre) = 
	  calendrier_retire_args_ancre($script); 
	if (!$evt) {
	  $g = 'sql_calendrier_' . $ltype;
	  $evt = sql_calendrier_interval($g($annee,$mois, $jour));
	  sql_calendrier_interval_articles("'$annee-$mois-00'", "'$annee-$mois-1'", $evt[0]);
	  // si on veut les forums, decommenter
#	  sql_calendrier_interval_forums($g($annee,$mois,$jour), $evt[0]);
	}
	$f = 'http_calendrier_' . $ltype;
	return $f($annee, $mois, $jour, $lechelle, $lpartie_cal, $script, $ancre, $evt);

}

# affichage d'un calendrier de plusieurs semaines
# si la periode est inferieure a 31 jours, on considere que c'est un mois

// http://doc.spip.org/@http_calendrier_mois
function http_calendrier_mois($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';
	$premier_jour = '01';
	$dernier_jour = '31';

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
	    http_calendrier_mois_noms($annee, $mois, $jour, $script, $ancre) .
	    http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, "&amp;echelle=$echelle&amp;partie_cal=$partie_cal",$ancre) ;
	} else $evt = "<tr><td>$evt</td></tr>";

	return 
	  "<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0'>" .
	  http_calendrier_mois_navigation($annee, $mois, $premier_jour, $dernier_jour, $echelle, $partie_cal, $script, $ancre) .
	  $evt .
	  '</table>' .
	  http_calendrier_sans_date($annee, $mois, $evenements) .
	  (_DIR_RESTREINT ? "" : http_calendrier_aide_mess());
}

// si la periore a plus de 31 jours, c'est du genre trimestre, semestre etc
// pas de navigation suivant/precedent alors

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
	  "\n<tr><td colspan='7'>" .
	  http_calendrier_navigation($annee,
				   $mois,
				   $jour,
				   $echelle,
				   $partie_cal,
				   $periode,
				   $script,
				   $prec,
				   $suiv,
				   'mois',
				   $ancre) .
	  "</td></tr>";

}

// http://doc.spip.org/@http_calendrier_mois_noms
function http_calendrier_mois_noms($annee, $mois, $jour, $script, $ancre){
	global $couleur_claire;

	$bandeau ="";
	for ($j=1; $j<8;$j++){
		$bandeau .= 
		  "\n\t<th class='calendrier-th'>" .
		  _T('date_jour_' . (($j%7)+1)) .
		  "</th>";
	}
	return "\n<tr" .
	  (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'") . 
	  ">$bandeau\n</tr>";
}

# dispose les lignes d'un calendrier de 7 colonnes (les jours)
# chaque case est garnie avec les evenements du jour figurant dans $evenements

// http://doc.spip.org/@http_calendrier_mois_sept
function http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, $finurl, $ancre='')
{
	global $couleur_claire, $spip_lang_left, $spip_lang_right;

	// affichage du debut de semaine hors periode
	$init = '';
	$debut = date("w",mktime(1,1,1,$mois,$premier_jour,$annee));
	for ($i=$debut ? $debut : 7;$i>1;$i--)
	  {$init .= "\n\t<td style=\"border-bottom: 1px solid $couleur_claire;\">&nbsp;</td>";}

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
		$couleur_fond = "";

		if ($jour_semaine == 0) $couleur_fond = $couleur_claire;
		else if ($jour_semaine==1)
			  { 
			    if ($ligne||$init)
			      $total .= "\n<tr>$init$ligne\n</tr>";
			    $ligne = $init = '';
			  }
		
		if ($amj == $today) {
			$couleur_texte = "red";
			$couleur_fond = "white";
		}
		$res = '';
		if ($evts = $evenements[$amj]) {
		  foreach ($evts as $evenement)
		    {
		      $res .= isset($evenement['DTSTART']) ?
			http_calendrier_avec_heure($evenement, $amj) :
			http_calendrier_sans_heure($evenement);
		    }
		}

		$ligne .= "\n\t\t<td\tclass='calendrier-td'
			style='height: 100px; border-bottom: 1px solid $couleur_claire; border-$spip_lang_right: 1px solid $couleur_claire;" .
		  ($couleur_fond ? " background-color: $couleur_fond;" : "") .
		  ($ligne ? "" :
		   " border-$spip_lang_left: 1px solid $couleur_claire;") .
		  "'>" .
		  (!_DIR_RESTREINT ? 
		   (calendrier_href($script,$annee_en_cours, $mois_en_cours, $jour, "jour", $finurl, $ancre, '', $jour, 'calendrier-helvetica16', '', $jour, "color: $couleur_texte") . 
		    http_calendrier_ics_message($annee_en_cours, $mois_en_cours, $jour, false)):
		   http_calendrier_mois_clics($annee_en_cours, $mois_en_cours, $jour, $script, $finurl, $ancre)) .
		  $res .
		  "\n\t</td>";
	}
	return  $total . ($ligne ? "\n<tr>$ligne\n</tr>" : '');
}

// typo pour l'espace public

// http://doc.spip.org/@http_calendrier_mois_clics
function http_calendrier_mois_clics($annee, $mois, $jour, $script, $finurl, $ancre)
{
      $d = mktime(0,0,0,$mois, $jour, $annee);
      $semaine = date("W", $d);
      return 
	"<table width='100%'>\n<tr><td style='text-align: left'>". 
	calendrier_href($script,$annee, $mois, $jour, "jour", $finurl, $ancre, '',
			 (_T('date_jour_'. (1+date('w',$d))) .
			  " $jour " .
			  _T('date_mois_'.(0+$mois))),
			 'calendrier-helvetica16','',
			 "$jour/$mois") .
	"</td><td style='text-align: right'>" .
	calendrier_href($script,$annee, $mois, $jour, "semaine", $finurl, $ancre,'',
			(_T('date_semaines') . ": $semaine"),
			'calendrier-helvetica16','', $semaine) .
	"</td></tr>\n</table>";
}

# dispose les evenements d'une semaine

// http://doc.spip.org/@http_calendrier_semaine
function http_calendrier_semaine($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';

	$init = date("w",mktime(1,1,1,$mois,$jour,$annee));
	$init = $jour+1-($init ? $init : 7);
	$sd = '';

	if (is_array($evt))
	  {
	  	if($partie_cal!= DEFAUT_PARTIE_R){
	    $sd = http_calendrier_sans_date($annee, $mois,$evt[0]);
	    $finurl = "&amp;echelle=$echelle&amp;partie_cal=$partie_cal";
	    $evt =
	      http_calendrier_semaine_noms($annee, $mois, $init, $script, $finurl, $ancre) .
	      http_calendrier_semaine_sept($annee, $mois, $init, $echelle, $partie_cal, $evt);
	  	}
	  	else {
			  list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;
			  if ($sansduree)
			    foreach($sansduree as $d => $r) 
			      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
		    $finurl = "&amp;echelle=$echelle&amp;partie_cal=$partie_cal";
		    $evt =
		      http_calendrier_semaine_noms($annee, $mois, $init, $script, $finurl, $ancre) .
		      http_calendrier_mois_sept($annee, $mois, $init, $init+ 6, $evenements, $script, $finurl, $ancre);
	  	}
	  } else $evt = "<tr><td>$evt</td></tr>";

	return 
	  "\n<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0'>" .
	  http_calendrier_semaine_navigation($annee, $mois, $init, $echelle, $partie_cal, $script, $ancre) .
	  $evt .
	  "</table>" .
	  $sd .
	  (_DIR_RESTREINT ? "" : http_calendrier_aide_mess());
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
    "\n<tr><td colspan='7'>" .
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
			       $ancre) .
    "</td></tr>\n";
}

// http://doc.spip.org/@http_calendrier_semaine_noms
function http_calendrier_semaine_noms($annee, $mois, $jour, $script, $finurl, $ancre){
	global $couleur_claire;

	$bandeau = '';

	for ($j=$jour; $j<$jour+7;$j++){
		$nom = mktime(0,0,0,$mois,$j,$annee);
		$num = intval(date("d", $nom)) ;
		$numois = date("m",$nom);
		$nomjour = _T('date_jour_'. (1+date('w',$nom)));
		$clic = ($nomjour . " " . $num . (($num == 1) ? 'er' : '') .
			 ($ancre  ? ('/' . $numois) : ''));
		$bandeau .= 
		  "\n\t<th class='calendrier-th'>" .
		  calendrier_href($script, date("Y",$nom), $numois, $num, 'jour', $finurl, $ancre, '', $clic, '', '', $clic) .
		  "</th>";
	}
	return "\n<tr" .
	  (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'") . 
	  ">$bandeau\n</tr>";
}

// http://doc.spip.org/@http_calendrier_semaine_sept
function http_calendrier_semaine_sept($annee, $mois, $jour, $echelle, $partie_cal, $evt)
{
	global $couleur_claire, $spip_ecran, $spip_lang_left;

	$largeur =  ($spip_ecran == "large") ? 90 : 60;

	$today=date("Ymd");
	$total = '';
	$style = "border-$spip_lang_left: 1px solid $couleur_claire; border-bottom: 1px solid $couleur_claire; border-top: 0px; border-right: 0px;";
	for ($j=$jour; $j<$jour+7;$j++){
		$v = mktime(0,0,0,$mois, $j, $annee);
		$total .= "\n<td class='calendrier-td'>" .
		  http_calendrier_ics($annee,$mois,$j, $echelle, $partie_cal, $largeur, $evt, ($style . ( (date("w",$v)==0 && isset($couleur_claire)) ? 
			  " background-color: $couleur_claire;" :
			  ((date("Ymd", $v) == $today) ? 
			   " background-color: white;" :
			   " background-color: #eeeeee;")))) .
		  "\n</td>";
	}
	return "\n<tr class='calendrier-verdana10'>$total</tr>";
}


// http://doc.spip.org/@http_calendrier_jour
function http_calendrier_jour($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';

	return 	
	  "\n<table class='calendrier-table-$spip_ecran'>" .
	  "\n<tr><td class='calendrier-td-gauche'></td>" .
	  "<td colspan='5' class='calendrier-td-centre'>" .
	  http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal,
				     (nom_jour("$annee-$mois-$jour") . " " .
				      affdate_jourcourt("$annee-$mois-$jour")),
				     $script,
				     array($annee, $mois, ($jour-1), "jour"),
				     array($annee, $mois, ($jour+1), "jour"),
				     'jour',
				     $ancre) .
	  "</td>" .
	  "<td class='calendrier-td-droit calendrier-arial10'></td>" .
	  "</tr>" .
	  (!is_array($evt) ? ("<tr><td>$evt</td></tr>") :
	   (http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre) .
	    http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt))) .
	  "</table>";
}

// http://doc.spip.org/@http_calendrier_jour_noms
function http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre){

	global $spip_ecran;
	$finurl = "&amp;echelle=$echelle&amp;partie_cal=$partie_cal";

	$gauche = (_DIR_RESTREINT  || ($spip_ecran != "large"));
	return
	  "\n<tr><td class='calendrier-td-gauche'>" .
	  ($gauche ? '' :
	   http_calendrier_ics_titre($annee,$mois,$jour-1,$script, $finurl, $ancre)) .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	  (_DIR_RESTREINT ? '' :
		   ("\n\t<div class='calendrier-titre'>" .
		    http_calendrier_ics_message($annee, $mois, $jour, true) .
		    '</div>')) .
	  "</td><td class='calendrier-td-droit calendrier-arial10'> " .
	  (_DIR_RESTREINT ? '' : http_calendrier_ics_titre($annee,$mois,$jour+1,$script, $finurl, $ancre)) .
	  "</td></tr>";
}

// http://doc.spip.org/@http_calendrier_jour_sept
function http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;

	$gauche = (_DIR_RESTREINT  || ($spip_ecran != "large"));
	if ($partie_cal!= DEFAUT_PARTIE_R)
		return
		  "<tr class='calendrier-verdana10'>" .
			# afficher en reduction le tableau du jour precedent
		  "\n<td class='calendrier-td-gauche'>" .
		  ($gauche  ? '' :
		   http_calendrier_ics($annee, $mois, $jour-1, $echelle, $partie_cal, 0, $evt)) .
		  "</td><td colspan='5' class='calendrier-td-centre'>" .
		   http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 300, $evt) .
		  '</td>' .
			# afficher en reduction le tableau du jour suivant
		  "\n<td class='calendrier-td-droit'>" .
	
		  (_DIR_RESTREINT ? '' :
		   http_calendrier_ics($annee, $mois, $jour+1, $echelle, $partie_cal, 0, $evt)) .
		  '</td>' .
		  "\n</tr>";
	else {
	  list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;
	  if ($sansduree)
	    foreach($sansduree as $d => $r) 
	      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
		return
		  "<tr class='calendrier-verdana10'>" .
			# afficher en reduction le tableau du jour precedent
		  "\n<td class='calendrier-td-gauche'>" .
		  ($gauche  ? '' :
		  "<table width='100%'>".
		   http_calendrier_mois_sept($annee, $mois, $jour-1, $jour-1,$evenements, $script, '', $ancre)
		   ."</table>"
		   ) .
		  "</td><td colspan='5' class='calendrier-td-centre'>" .
		  "<table width='100%'>".
		  http_calendrier_mois_sept($annee, $mois, $jour, $jour,$evenements, $script, '', $ancre) .
		   "</table>" .
		  '</td>' .
			# afficher en reduction le tableau du jour suivant
		  "\n<td class='calendrier-td-droit'>" .
		  (_DIR_RESTREINT ? '' :
		  "<table width='100%'>".
		   http_calendrier_mois_sept($annee, $mois, $jour+1, $jour+1,$evenements, $script, '', $ancre)
		   ."</table>"
		   ) .
		  '</td>' .
		  "\n</tr>";
		
	}
}


// Conversion d'un tableau de champ ics en des balises div positionnees    
// Le champ categories indique la Classe de CSS a prendre
// $echelle est le nombre de secondes representees par 1 pixel

// http://doc.spip.org/@http_calendrier_ics
function http_calendrier_ics($annee, $mois, $jour,$echelle, $partie_cal,  $largeur, $evt, $style='') {
	global $spip_lang_left;

	// tableau
	if ($partie_cal == DEFAUT_PARTIE_M) {
		$debut = 12;
		$fin = 23;
	} else if ($partie_cal == DEFAUT_PARTIE_M) {
		$debut = 4;
		$fin = 15;
	} else {
		$debut = 7;
		$fin =21;
	}
	
	if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;

	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut, $fin, $echelle);
	$modif_decalage = round($largeur/8);

	$date = date("Ymd", mktime(0,0,0,$mois, $jour, $annee));
	list($sansheure, $avecheure) = $evt;
	$avecheure = isset($avecheure[$date]) ? $avecheure[$date] : array();
	$sansheure = isset($sansheure[$date]) ? $sansheure[$date] : array();

	$total = '';

	if ($avecheure)
    {
		$tous = 1 + count($avecheure);
		$i = $bas_prec = 0;
		foreach($avecheure as $evenement){

			$d = $evenement['DTSTART'];
			$e = $evenement['DTEND'];
			$d_jour = substr($d,0,8);
			$e_jour = $e ? substr($e,0,8) : $d_jour;
			$debut_avant = false;
			$fin_apres = false;
			
			/* disparues sauf erreur
			 $radius_top = " radius-top";
			$radius_bottom = " radius-bottom";
			*/
			if ($d_jour <= $date AND $e_jour >= $date)
			{

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
			
			if ($debut_avant && $fin_apres)  $opacity = "-moz-opacity: 0.6; filter: alpha(opacity=60);";
			else $opacity = "";
						
			$haut = calendrier_top ("$heure_debut:$minutes_debut", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$bas =  !$e ? $haut :calendrier_top ("$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$hauteur = calendrier_height ("$heure_debut:$minutes_debut", "$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);

			if ($bas_prec >= $haut) $decale += $modif_decalage;
			else $decale = (4 * $fontsize);
			if ($bas > $bas_prec) $bas_prec = $bas;
			
			$url = isset($evenement['URL'])
			  ? $evenement['URL'] : ''; 
			$desc = propre($evenement['DESCRIPTION']);
			$perso = substr($evenement['ATTENDEE'], 0,strpos($evenement['ATTENDEE'],'@'));
			$lieu = isset($evenement['LOCATION']) ?
				$evenement['LOCATION'] : '';
			$sum = preg_replace('/ \+/','&nbsp;', typo($evenement['SUMMARY']));
			if (!$sum) { $sum = $desc; $desc = '';}
			if (!$sum) { $sum = $lieu; $lieu = '';}
			if (!$sum) { $sum = $perso; $perso = '';}
			if ($sum)
			  $sum = "<span class='calendrier-verdana10'><span  style='font-weight: bold;'>$sum</span>$lieu $perso</span>";
			if (($largeur > 90) && $desc)
			  $sum .=  "\n<br /><span class='calendrier-noir'>$desc</span>";
			$colors = $evenement['CATEGORIES'];
		  $sum = ((!$url) ? $sum : http_href(quote_amp($url), $sum, $desc,"border: 0px",$colors));
			$sum = pipeline('agenda_rendu_evenement',array('args'=>array('evenement'=>$evenement,'type'=>'ics'),'data'=>$sum));

			$total .= "\n<div class='calendrier-arial10 $colors' 
	style='cursor: auto; position: absolute; overflow: hidden;$opacity z-index: " .
				$i .
				"; $spip_lang_left: " .
				$decale .
				"px; top: " .
				$haut .
				"px; height: " .
				$hauteur .
				"px; width: ".
				($largeur - 2 * ($padding+1)) .
				"px; font-size: ".
				floor($fontsize * 1.3) .
				"px; padding: " .
				$padding . 
				"px; $bordure'
	onmouseover=\"this.style.zIndex=" . $tous . "\"
	onmouseout=\"this.style.zIndex=" . $i . "\">" .
			  $sum . 
				"</div>";
			}
		}
    }
	return
	   "\n<div class='calendrier-jour' style='height: ${dimjour}px; font-size: ${fontsize}px;$style'>\n" .
	  http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $fontsize) .
	  $total .
	  "\n</div>" .
	  (!$sansheure ? "" :
	   http_calendrier_ics_trois($sansheure, $largeur, $dimjour, $fontsize, '')) ;

}

# Affiche une grille horaire 
# Selon l'echelle demandee, on affiche heure, 1/2 heure 1/4 heure, 5minutes.

// http://doc.spip.org/@http_calendrier_ics_grille
function http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $fontsize)
{
	global $spip_lang_left, $spip_lang_right;
	$slice = floor($dimheure/(2*$fontsize));
	if ($slice%2) $slice --;
	if (!$slice) $slice = 1;

	$total = '';
	for ($i = $debut; $i < $fin; $i++) {
		for ($j=0; $j < $slice; $j++) 
		{
			$total .= "\n<div class='calendrier-heure" .
				($j  ? "face" : "pile") .
				"' style='$spip_lang_left: 0px; top: ".
				calendrier_top ("$i:".sprintf("%02d",floor(($j*60)/$slice)), $debut, $fin, $dimheure, $dimjour, $fontsize) .
				"px;'>$i:" .
				sprintf("%02d",floor(($j*60)/$slice)) . 
				"</div>";
		}
	}

	return "\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: 2px;'>0:00</div>" .
		$total .
		"\n<div class='calendrier-heurepile' style='$spip_lang_left: 0px; top: ".
		calendrier_top ("$fin:00", $debut, $fin, $dimheure, $dimjour, $fontsize).
		"px;'>$fin:00</div>" .
		"\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: ".
		($dimjour - $fontsize - 2) .
		"px;'>23:59</div>";
}

# si la largeur le permet, les evenements sans duree, 
# se placent a cote des autres, sinon en dessous

// http://doc.spip.org/@http_calendrier_ics_trois
function http_calendrier_ics_trois($evt, $largeur, $dimjour, $fontsize, $border)
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
	  $res .= "\n<div class='calendrier-verdana10 calendrier-titre'>".
	    _T($k) .
	    "</div>" .
	    $res2;
	}
		
	if ($largeur > 90) {
		$largeur += (5*$fontsize);
		$pos = "-$dimjour";
	} else { $largeur = (3*$fontsize); $pos= 0; }
	  
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
	$desc = propre($ev['DESCRIPTION']);
	$sum = $ev['SUMMARY'];
	if (!$sum) $sum = $desc;
	$i = isset($ev['DESCRIPTION']) ? 11 : 9; // 11: article; 9:autre
	if ($ev['URL'])
	  $sum = http_href(quote_amp($ev['URL']), $sum, $desc);
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
	
	$desc = propre($evenement['DESCRIPTION']);
	$sum = $evenement['SUMMARY'];
	$sum = preg_replace('/ \+/','&nbsp;', typo($sum));
	if (!$sum) $sum = $desc;
	if ($lieu = $evenement['LOCATION'])
	  $sum .= '<br />' . $lieu;
	if ($perso = $evenement['ATTENDEE'])
	  $sum .=  '<br />' . substr($evenement['ATTENDEE'], 0,strpos($evenement['ATTENDEE'],'@'));
	if ($evenement['URL'])
	  $sum = http_href(quote_amp($evenement['URL']), $sum, $desc, 'border: 0px');

	$sum = pipeline('agenda_rendu_evenement',array('args'=>array('evenement'=>$evenement,'type'=>'avec_heure'),'data'=>$sum));
	$opacity = "";
	$deb_h = substr($evenement['DTSTART'],-6,2);
	$deb_m = substr($evenement['DTSTART'],-4,2);
	$fin_h = substr($evenement['DTEND'],-6,2);
	$fin_m = substr($evenement['DTEND'],-4,2);
	
	if ($deb_h >0 OR $deb_m > 0) {
	  if ((($deb_h > 0) OR ($deb_m > 0)) AND $amj == $jour_debut)
	    { $deb = "<span  style='font-weight: bold;'>" . $deb_h . ':' . $deb_m . '</span>';}
	  else { 
	    $deb = '...'; 
	  }
	  
	  if ((($fin_h > 0) OR ($fin_m > 0)) AND $amj == $jour_fin)
	    { $fin = "<span  style='font-weight: bold;'>" . $fin_h . ':' . $fin_m . '</span> ';}
	  else { 
	    $fin = '...'; 
	  }
	  
	  if ($amj == $jour_debut OR $amj == $jour_fin) {
	    $sum = "<div>$deb-$fin</div>$sum";
	  } else {
	    $opacity =' calendrier-opacity';
	  }
	}
	return "\n<div class='calendrier-arial10 calendrier-evenement " . $evenement['CATEGORIES'] ."$opacity'>$sum\n</div>\n"; 
}

# Bandeau superieur d'un calendrier selon son $type (jour/mois/annee):
# 2 icones vers les 2 autres types, a la meme date $jour $mois $annee
# 2 icones de loupes pour zoom sur la meme date et le meme type
# 2 fleches appelant le $script sur les periodes $pred/$suiv avec une $ancre
# et le $nom du calendrier

// http://doc.spip.org/@http_calendrier_navigation
function http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal, $nom, $script, $args_pred, $args_suiv, $type, $ancre)
{
	global $spip_lang_right, $spip_lang_left, $couleur_foncee;

	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;

	if ($args_pred) {
	  list($a, $m, $j, $t) = $args_pred;
	  $args_pred = calendrier_href($script, $a, $m, $j, $t, "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre,
				       "fleche-$spip_lang_left.png",
				       _T('precedent'),
				       'calendrier-png',
				       '&lt;&lt;&lt;');

	}

	if ($args_suiv) {
	  list($a, $m, $j, $t) = $args_suiv;
	  $args_suiv = calendrier_href($script, $a, $m, $j, $t, "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre,
				       "fleche-$spip_lang_right.png",
				       _T('suivant'),
				       'calendrier-png',
				       '&gt;&gt;&gt;');
	}

	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$id = 'nav-agenda' .preg_replace('/[^\w\d]/', '', $ancre);

	return 
	  "\n<div class='navigation-calendrier calendrier-moztop8'" 
	  . (!isset($couleur_foncee) ? "" : "\nstyle='background-color: $couleur_foncee;'")
	  . ">\n<div style='float: $spip_lang_right; padding-left: 5px; padding-right: 5px;'>"
	  . (($type == "mois") ? '' :
	     (calendrier_href($script, $annee, $mois, $jour, $type, "&amp;echelle=$echelle&amp;partie_cal=" . DEFAUT_PARTIE_R, $ancre,
				 "sans-heure.gif",
				 _T('sans_heure'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_R) ? " calendrier-opacity'" : ""))
	      . calendrier_href($script, $annee, $mois, $jour, $type, "&amp;echelle=$echelle&amp;partie_cal=" . DEFAUT_PARTIE_T, $ancre,
				 "heures-tout.png",
				 _T('cal_jour_entier'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_T) ? " calendrier-opacity'" : ""))
	      . calendrier_href($script, $annee, $mois, $jour, $type, "&amp;echelle=$echelle&amp;partie_cal=" . DEFAUT_PARTIE_M, $ancre,
				 "heures-am.png",
				 _T('cal_matin'),
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_M) ? " calendrier-opacity'" : ""))

	      . calendrier_href($script, $annee, $mois, $jour, $type, "&amp;echelle=$echelle&amp;partie_cal=" . DEFAUT_PARTIE_S, $ancre,
				 "heures-pm.png",
				 _T('cal_apresmidi'), 
				 "calendrier-png" .
				 (($partie_cal == DEFAUT_PARTIE_S) ? " calendrier-opacity'" : ""))
		  . "&nbsp;"
	      . calendrier_href($script, $annee, $mois, $jour, $type, "&amp;partie_cal=$partie_cal&amp;echelle=" . floor($echelle * 1.5),
				$ancre,
				"loupe-moins.gif",
				_T('info_zoom'). '-')
	      . calendrier_href($script, $annee, $mois, $jour, $type, "&amp;partie_cal=$partie_cal&amp;echelle=" .  floor($echelle / 1.5),
				$ancre, 
				"loupe-plus.gif",
				_T('info_zoom'). '+')
	      ))
	  . calendrier_href($script,$annee, $mois, $jour, "jour", "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre,"cal-jour.gif",
			  _T('cal_par_jour'),
			  (($type == 'jour') ? " calendrier-opacity" : ''))

	  . calendrier_href($script,$annee, $mois, $jour, "semaine", "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre, "cal-semaine.gif",
			  _T('cal_par_semaine'), 
			  (($type == 'semaine') ?  " calendrier-opacity" : "" ))

	  . calendrier_href($script,$annee, $mois, $jour, "mois", "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre,"cal-mois.gif",
			  _T('cal_par_mois'),
			  (($type == 'mois') ? "calendrier-opacity" : "" ))
	  . "</div>"
	  . "&nbsp;&nbsp;"
	  . calendrier_href($script,$annee_today, $mois_today, $jour_today, $type, "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre,
			    "cal-today.gif",
			    _T("ecrire:info_aujourdhui"),
			    (($annee == $annee_today && $mois == $mois_today && (($type == 'mois')  || ($jour == $jour_today)))
			     ? "calendrier-opacity" : ""),
			    '','','',
			    (" onmouseover=\"findObj_test_forcer('$id',true).style.visibility='visible';\"" ))
	  . "&nbsp;"
	  . $args_pred 
	  . $args_suiv
	  . "&nbsp;&nbsp;"
	  . $nom
	  . (_DIR_RESTREINT ? '' :  aide("messcalen"))
	  . "</div>"
	  . http_calendrier_invisible($annee, $mois, $jour, $script, "&amp;echelle=$echelle&amp;partie_cal=$partie_cal", $ancre, $id);
}


// fabrique un petit agenda accessible par survol

// http://doc.spip.org/@http_calendrier_invisible
function http_calendrier_invisible($annee, $mois, $jour, $script, $finurl, $ancre, $id)
{
	global $spip_lang_right, $spip_lang_left, $couleur_claire;
	$gadget = "<div style='position: relative;z-index: 1000;'
			onmouseover=\"findObj_test_forcer('$id',true).style.visibility='visible';\"
			onmouseout=\"cacher('$id');\">"
	  . "<table id='$id' class='calendrier-cadreagenda'"
	  . (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'")
	  . ">\n<tr><td colspan='3' style='text-align:$spip_lang_left;'>";

	$annee_avant = $annee - 1;
	$annee_apres = $annee + 1;

	for ($i=$mois; $i < 13; $i++) {
	  $nom = nom_mois("$annee_avant-$i-1");
	  $gadget .= calendrier_href($script,$annee_avant, $i, 1, "mois", $finurl, $ancre,'', ($nom . ' ' . $annee_avant), 'calendrier-annee','', $nom) ;
			}
	for ($i=1; $i < $mois - 1; $i++) {
	  $nom = nom_mois("$annee-$i-1");
	  $gadget .= calendrier_href($script,$annee, $i, 1, "mois", $finurl, $ancre,'',($nom . ' ' . $annee),'calendrier-annee','', $nom);
			}
	$gadget .= "</td></tr>"
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
	  $gadget .= calendrier_href($script,$annee, $i, 1, "mois", $finurl, $ancre,'',$nom . ' ' . $annee, 'calendrier-annee','', $nom);
			}
	for ($i=1; $i < $mois+1; $i++) {
	  $nom = nom_mois("$annee_apres-$i-1");
	  $gadget .= calendrier_href($script, $annee_apres, $i, 1, "mois", $finurl, $ancre,'',$nom . ' ' . $annee_apres, 'calendrier-annee','',$nom);
			}
	return $gadget . "</td></tr></table></div>";
}

// agenda mensuel 

// http://doc.spip.org/@http_calendrier_agenda
function http_calendrier_agenda ($annee, $mois, $jour_ved, $mois_ved, $annee_ved, $semaine = false,  $script='', $ancre='', $evt='') {

  if (!$script) $script =  $GLOBALS['PHP_SELF'] ;

  if (!$mois) {$mois = 12; $annee--;}
  elseif ($mois==13) {$mois = 1; $annee++;}
  if (!$evt) $evt = sql_calendrier_agenda($annee, $mois);
  $nom = affdate_mois_annee("$annee-$mois-1");
  return 
    "<div class='calendrier-titre calendrier-arial10'>" .
    calendrier_href($script, $annee, $mois, 1, 'mois', '', $ancre,'', $nom,'','',    $nom,'color: black;') .
    "<table width='100%' cellspacing='0' cellpadding='0'>" .
    http_calendrier_agenda_rv ($annee, $mois, $evt,				
			        'calendrier_href', $script, $ancre,
			        $jour_ved, $mois_ved, $annee_ved, 
				$semaine) . 
    "</table>" .
    "</div>";
}

// typographie un mois sous forme d'un tableau de 7 colonnes

// http://doc.spip.org/@http_calendrier_agenda_rv
function http_calendrier_agenda_rv ($annee, $mois, $les_rv, $fclic,
				    $script='', $ancre='',
				    $jour_ved='', $mois_ved='', $annee_ved='',
				    $semaine='') {
	global $couleur_foncee, $spip_lang_left, $spip_lang_right;

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
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n\t<td></td>";
	$style0 = (!isset($couleur_foncee)) ? "" : " style='border: 1px solid $couleur_foncee;'";
	for ($j=1; (checkdate($mois,$j,$annee)); $j++) {
		$style = "";
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		$title = "$j-$mois-$annee";
		if ($jour_semaine==0) $jour_semaine=7;

		if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
		  $class= 'calendrier-arial11 calendrier-demiagenda';
		  $type = 'jour';
		  $couleur = "black";
		  } else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		  $class= 'calendrier-arial11 calendrier-demiagenda' . 
 		      (($jour_semaine==1) ? " calendrier-$spip_lang_left"  :
		       (($jour_semaine==7) ? " calendrier-$spip_lang_right" :
			''));
		  $type = ($semaine ? 'semaine' : 'jour') ;
		  $couleur = "black";
		} else {
		  if ($j == $jour_today AND $cemois) {
			$style = $couleur_foncee;
			if(!$style) $style = '#333333';
			$couleur = "white";
		    } else {
			if ($jour_semaine == 7) {
				$style = "#aaaaaa";
				$couleur = 'white';
			} else {
				$style = "#ffffff";
				$couleur = "#aaaaaa";
			}
			if (isset($les_rv[$j])) {
			  $style = "#ffffff";
			  $couleur = "black";
			  $title = textebrut($les_rv[$j]['SUMMARY']);
			}
		  }
		  $class= 'calendrier-arial11 calendrier-agenda';
		  $type = ($semaine ? 'semaine' : 'jour') ;
		}
		if ($style)
		  $style = " style='background-color: $style'";
		else $style = $style0;
		$ligne .= "\n\t<td><div class='$class'$style>" .
		  $fclic($script, $annee, $mois, $j,$type, '', $ancre,'', $title ,'','', $j, "color: $couleur; font-weight: bold") .
		  "</div></td>";
		if ($jour_semaine==7) 
		    {
		      if ($ligne) $total .= "\n<tr>$ligne\n</tr>";
		      $ligne = '';
		    }
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne\n</tr>");
}



// Fonctions pour la messagerie, la page d'accueil et les gadgets

// http://doc.spip.org/@http_calendrier_messages
function http_calendrier_messages($annee='', $mois='', $jour='', $heures='')
{
	global  $partie_cal, $echelle;

	$evtm = sql_calendrier_agenda($annee, $mois);
	if ($evtm OR !$heures)
		$evtm = http_calendrier_agenda($annee, $mois, $jour, $mois, $annee, false, generer_url_ecrire('calendrier'), '', $evtm);
	else $evtm= '';

	$evtt = http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces")
	  . http_calendrier_rv(sql_calendrier_taches_pb(),"pb")
	  . http_calendrier_rv(sql_calendrier_taches_rv(), "rv");

	$evtr= '';
	if ($heures) {
		$date = date("$annee-$mois-$jour");
		$datef = "'$date $heures'";
		if ($heures = sql_calendrier_interval_rv("'$date'", $datef))
			$evtr = http_calendrier_ics_titre($annee,$mois,$jour,generer_url_ecrire('calendrier')) . http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 90, array('', $heures));
	}
	return array($evtm, $evtt, $evtr);
}



// http://doc.spip.org/@http_calendrier_rv
function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right, $connect_id_auteur, $connect_quand;

	$total = $date_rv = '';
	if (!$messages) return $total;

	foreach ($messages as $row) {
		$rv = ($row['LOCATION'] == 'oui');
		$date = $row['DTSTART'];
		$date_fin = $row['DTEND'];
		if ($row['CATEGORY']=="pb") $bouton = "pense-bete";
		else if ($row['CATEGORY']=="affich") $bouton = "annonce";
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

		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		  http_href($row['URL'],
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;")) : 
				      http_img_pack($bouton.".gif", $bouton, "")),
				     '', '') .
		"</td>\n" .
		"<td valign='middle'><div style='font-weight: bold;" .
		((strtotime($date) <= $connect_quand) ? '' : " color: red;") .
		"'>" .
		$rv .
		http_href($row['URL'], typo($row['SUMMARY']), '', '', 'calendrier-verdana10') .
		"</div></td></tr>\n";
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "<table width='100%' border='0' cellpadding='0' cellspacing='2'>" .
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
    $result= spip_fetch_array(spip_query("SELECT " . (($objet != 'id_breve') ? 'id_secteur' : 'id_rubrique') . " AS id FROM	$table WHERE	$objet=$num"));
    if ($result) $num = $result['id'];
    return 'calendrier-couleur' . (($num%14)+1);
  }
}


// icones standards, fonction de la direction de la langue

global $bleu, $vert, $jaune, $spip_lang_rtl;
$bleu = http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "class='calendrier-icone'");
$vert = http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "class='calendrier-icone'");
$jaune= http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "class='calendrier-icone'");

// http://doc.spip.org/@http_calendrier_ics_message
function http_calendrier_ics_message($annee, $mois, $jour, $large)
{	
  global $bleu, $vert,$jaune, $connect_login;
  $b = _T("lien_nouvea_pense_bete");
  $v = _T("lien_nouveau_message");
  $j=  _T("lien_nouvelle_annonce");

  if (!$connect_login) return '';
  return 
    http_href(generer_action_auteur("editer_message","pb/$annee-$mois-$jour"), 
	      $bleu . ($large ? $b : ''), 
	      $b,
	      'color: blue;',
	      'calendrier-arial10') .
    "\n" .
    http_href(generer_action_auteur("editer_message","normal/$annee-$mois-$jour"), 
	      $vert . ($large ? $v : ''), 
	      $v,
	      'color: green;',
	      'calendrier-arial10') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
    http_href(generer_action_auteur("editer_message","affich/$annee-$mois-$jour"), 
		$jaune . ($large ? $j : ''), 
		$j,
		'color: #ff9900;',
		'calendrier-arial10')));
}

// http://doc.spip.org/@http_calendrier_aide_mess
function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune, $spip_lang_left, $connect_login;
  if (!$connect_login) return '';
  return
   "\n<br /><br /><br />\n<table width='700' class='arial1 spip_xx-small'>\n<tr><th style='text-align: $spip_lang_left; font-weight: bold;'> " . _T('info_aide').
    "</th></tr><tr><td>$bleu\n"._T('info_symbole_bleu')."\n" .
    "</td></tr><tr><td>$vert\n"._T('info_symbole_vert')."\n" .
    "</td></tr><tr><td>$jaune\n"._T('info_symbole_jaune')."\n" .
    "</td></tr>\n</table>\n";
 }

//------- fonctions d'appel MySQL. 
// au dela cette limite, pas de production HTML

// http://doc.spip.org/@sql_calendrier_mois
function sql_calendrier_mois($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(0,0,0,$mois+1,1,$annee)) .
	" 00:00:00'";
	return array($avant, $apres);
}

// http://doc.spip.org/@sql_calendrier_semaine
function sql_calendrier_semaine($annee,$mois,$jour) {
	$w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
	if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
	$debut = $jour-$w_day;
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$debut,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut+7,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// ici on prend en fait le jour, la veille et le lendemain

// http://doc.spip.org/@sql_calendrier_jour
function sql_calendrier_jour($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$jour-1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$jour+1,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// retourne un tableau de 2 tableaux indexes par des dates
// - le premier indique les evenements du jour, sans indication de duree
// - le deuxime indique les evenements commencant ce jour, avec indication de duree

// http://doc.spip.org/@sql_calendrier_interval
function sql_calendrier_interval($limites) {
	list($avant, $apres) = $limites;
	$evt = array();
	sql_calendrier_interval_articles($avant, $apres, $evt);
	sql_calendrier_interval_breves($avant, $apres, $evt);
	sql_calendrier_interval_rubriques($avant, $apres, $evt);
	return array($evt, sql_calendrier_interval_rv($avant, $apres));
}

// http://doc.spip.org/@sql_calendrier_interval_forums
function  sql_calendrier_interval_forums($limites, &$evenements) {
	list($avant, $apres) = $limites;
	$result=spip_query("SELECT DISTINCT titre, date_heure, id_forum FROM	spip_forum WHERE date_heure >= $avant  AND	date_heure < $apres ORDER BY date_heure");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_forum'];
		if (autoriser('voir','forum',$id))
			$evenements[$amj][]=
			array(
				'URL' => generer_url_forum($id),
				'CATEGORIES' => 'calendrier-couleur7',
				'SUMMARY' => $row['titre'],
				'DTSTART' => date_ical($row['date_heure']));
	}
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.

// http://doc.spip.org/@sql_calendrier_interval_articles
function sql_calendrier_interval_articles($avant, $apres, &$evenements) {
	
	$result=spip_query("SELECT id_article, titre, date, descriptif, chapo FROM	spip_articles WHERE statut='publie' AND	date >= $avant  AND	date < $apres ORDER BY date");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_article'];
		if (autoriser('voir','article',$id))
			$evenements[$amj][]=
			    array(
				  'CATEGORIES' => calendrier_categories('spip_articles', $id, 'id_article'),
				'DESCRIPTION' => $row['descriptif'],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_article($id, 'prop'));
	}
}

// http://doc.spip.org/@sql_calendrier_interval_rubriques
function sql_calendrier_interval_rubriques($avant, $apres, &$evenements) {
	
	$result=spip_query("SELECT DISTINCT R.id_rubrique, titre, descriptif, date FROM spip_rubriques AS R, spip_documents_rubriques AS L WHERE statut='publie' AND	date >= $avant AND	date < $apres AND	R.id_rubrique = L.id_rubrique ORDER BY date");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_rubrique'];
		if (autoriser('voir','rubrique',$id))
			$evenements[$amj][]=
			    array(
				  'CATEGORIES' => calendrier_categories('spip_rubriques', $id, 'id_rubrique'),
				'DESCRIPTION' => $row['descriptif'],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_rubrique($id, 'prop'));
	}
}

// http://doc.spip.org/@sql_calendrier_interval_breves
function sql_calendrier_interval_breves($avant, $apres, &$evenements) {
	$result=spip_query("SELECT id_breve, titre, date_heure, id_rubrique FROM spip_breves WHERE	statut='publie'  AND	date_heure >= $avant AND	date_heure < $apres ORDER BY date_heure");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_breve'];
		$ir = $row['id_rubrique'];
		if (autoriser('voir','breve',$id))
			$evenements[$amj][]=
			array(
			      'URL' => generer_url_breve($id, 'prop'),
			      'CATEGORIES' => calendrier_categories('spip_breves', $ir, 'id_breve'),
			      'SUMMARY' => $row['titre']);
	}
}

// http://doc.spip.org/@sql_calendrier_interval_rv
function sql_calendrier_interval_rv($avant, $apres) {
	global $connect_id_auteur;
	$evenements= array();
	if (!$connect_id_auteur) return $evenements;
	$result=spip_query("SELECT messages.id_message, messages.titre, messages.texte, messages.date_heure, messages.date_fin, messages.type FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE	((lien.id_auteur='$connect_id_auteur' AND	lien.id_message=messages.id_message) OR messages.type='affich') AND	messages.rv='oui'  AND	((messages.date_fin >= $avant OR messages.date_heure >= $avant) AND messages.date_heure <= $apres) AND	messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result)){
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
		      $auteurs = array();
		      $result_aut=spip_query("SELECT nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE	(lien.id_message='$id_message'  AND	(auteurs.id_auteur!='$connect_id_auteur'  AND	lien.id_auteur=auteurs.id_auteur))");
			while($row_auteur=spip_fetch_array($result_aut)){
				$auteurs[] = $row_auteur['nom'];
			}
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
				'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));
			
			$j ++; 
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
			
			$amj = date_anneemoisjour($ladate);

		}

	}
  return $evenements;
}

// fonction SQL, pour la messagerie

// http://doc.spip.org/@tache_redirige
function tache_redirige ($row) {

	$m = $row['DESCRIPTION'];
	if ($m[0] == '=')
	    if ($m = chapo_redirige(substr($m,1)))
	      if ($m = calculer_url($m[3], '', 'url'))
		return $m;
	return generer_url_ecrire("message", "id_message=".$row['UID']);
}

// http://doc.spip.org/@sql_calendrier_taches_annonces
function sql_calendrier_taches_annonces () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;

	$result = spip_query("SELECT texte AS DESCRIPTION, id_message AS UID, date_heure AS DTSTART, date_fin AS DTEND, titre AS SUMMARY, type AS CATEGORY, rv AS LOCATION FROM spip_messages WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC");

	while ($row = spip_fetch_array($result, SPIP_ASSOC)) {
		$row['URL'] = tache_redirige($row);
		$r[] = $row;
	}
	return $r;
}

// http://doc.spip.org/@sql_calendrier_taches_pb
function sql_calendrier_taches_pb () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;

	$result = spip_query("SELECT texte AS DESCRIPTION, id_message AS UID, date_heure AS DTSTART, date_fin AS DTEND, titre AS SUMMARY, type AS CATEGORY, rv AS LOCATION FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'");

	while ($row = spip_fetch_array($result, SPIP_ASSOC)) {
		$row['URL'] = tache_redirige($row);
		$r[] = $row;
	}

	return $r;
}

// http://doc.spip.org/@sql_calendrier_taches_rv
function sql_calendrier_taches_rv () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;

	$result = spip_query("SELECT messages.texte AS DESCRIPTION, messages.id_message AS UID, messages.date_heure AS DTSTART, messages.date_fin AS DTEND, messages.titre AS SUMMARY, messages.type AS CATEGORY, messages.rv AS LOCATION FROM spip_messages AS messages, spip_auteurs_messages AS lien  WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND ( (messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH))	OR (messages.date_heure < NOW() AND messages.date_fin > NOW() )) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while ($row = spip_fetch_array($result,SPIP_ASSOC)) {
		$row['URL'] = tache_redirige($row);
		$r[] = $row;
	}

	return  $r;
}

// http://doc.spip.org/@sql_calendrier_agenda
function sql_calendrier_agenda ($annee, $mois) {
	global $connect_id_auteur;

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.titre AS SUMMARY, messages.texte AS DESCRIPTION, messages.id_message AS UID, messages.date_heure FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie'");
	while($row=spip_fetch_array($result_messages, SPIP_ASSOC)){
		$rv[journum($row['date_heure'])] = $row;
	}
	return $rv;
}

?>
