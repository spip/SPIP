<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CALENDRIER")) return;
define("_ECRIRE_INC_CALENDRIER", "1");

# Typographie generale des calendriers de 3 type: jour/semaine/mois(ou plus)

define(DEFAUT_D_ECHELLE,120); # 1 pixel = 2 minutes

// Ecrire cookies

if ($GLOBALS['set_echelle'] > 0) {
	spip_setcookie('spip_calendrier_echelle', floor($GLOBALS['set_echelle']), time() + 365 * 24 * 3600);
	$GLOBALS['echelle'] = floor($GLOBALS['set_echelle']);
} else 
	$GLOBALS['echelle'] = $GLOBALS['_COOKIE']['spip_calendrier_echelle'];

if ($GLOBALS['set_partie_cal']) {
	spip_setcookie('spip_partie_cal', $GLOBALS['set_partie_cal'], time() + 365 * 24 * 3600);
	$GLOBALS['partie_cal'] = $GLOBALS['set_partie_cal'];
} else 
	$GLOBALS['partie_cal'] = $GLOBALS['_COOKIE']['spip_partie_cal'];

// icones standards, fonction de la direction de la langue

global $bleu, $vert, $jaune;
$bleu = http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "class='calendrier-icone'");
$vert = http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "class='calendrier-icone'");
$jaune= http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "class='calendrier-icone'");

// init: calcul generique des evenements a partir des tables SQL
// et lancement de la mise en page du type jour/semaine/mois demandee

function http_calendrier_init($date='', $ltype='', $lechelle='', $lpartie_cal='', $script='', $evt='')
{
	global $mois, $annee, $jour, $type, $echelle, $partie_cal;

	if (!$mois){
		$today=getdate($date ? strtotime($date) : time());
		$jour = $today["mday"];
		$mois = $today["mon"];
		$annee = $today["year"];
	    } else {if (!isset($jour)) {$jour = 1; $type= 'mois';}}
	if (!$date) $date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	if (!$script) $script = $GLOBALS['REQUEST_URI']; 
	if (!$type AND !($type = $ltype)) $type = 'mois';
	if (!isset($echelle)) $echelle = $lechelle;
	if (!isset($lpartie_cal)) $partie_cal = $lpartie_cal;
	$script = http_calendrier_retire_args($script);
	if (!_DIR_RESTREINT) http_calendrier_titre($date, $type);
	$f = 'http_calendrier_init_' . $type;
	if (!$evt) {
	  $g = 'sql_calendrier_' . $type;
	  $evt = sql_calendrier_interval($g($annee,$mois, $jour));
	}
	return $f($date, $echelle, $partie_cal, $script, $evt);

}

// titre de la page, si on est dans l'espace de redaction

function http_calendrier_titre($date, $type)
{
if ($type == 'semaine') {

	$GLOBALS['afficher_bandeau_calendrier_semaine'] = true;

	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }
elseif ($type == 'jour') {
	$titre = nom_jour($date)." ". affdate_jourcourt($date);
 }
 else {
	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }

 debut_page($titre, "redacteurs", "calendrier");
 echo "<div>&nbsp;</div>" ;
}

// utilitaire de separation script / ancre

function http_calendrier_script($script)
{
	if (ereg('^(.*)(#[^=&]*)$',$script, $m)) {
	  $purscript = $m[1];
	  $ancre = $m[2];
	} else { $ancre = ''; $purscript = $script; }
	if ($purscript[strlen($purscript)-1] == '?') 
	  $purscript = substr($purscript,0,-1);
	return array($purscript, $ancre);
}

// utilitaire de retrait des arguments a remplacer

function http_calendrier_retire_args($script)
{
  $script = str_replace('?bonjour=oui&?','?',$script);
  foreach(array('echelle','jour','mois','annee', 'type', 'set_echelle', 'set_partie_cal') as $arg) {
		$script = preg_replace("/([?&])$arg=[^&]*&/",'\1', $script);
		$script = preg_replace("/([?&])$arg=[^#]*#/",'\1#', $script);
		$script = preg_replace("/([?&])$arg=[^&#]*$/",'\1', $script);
	}
	return $script;
}

# prend une heure de debut et de fin, ainsi qu'une echelle (seconde/pixel)
# et retourne un tableau compose
# - taille d'une heure
# - taille d'une journee
# - taille de la fonte
# - taille de la marge

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

function http_cal_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize) {
	
	$h_heure = substr($heure, 0, strpos($heure, ":"));
	$m_heure = substr($heure, strpos($heure,":") + 1, strlen($heure));
	$heure100 = $h_heure + ($m_heure/60);

	if ($heure100 < $debut) $heure100 = ($heure100 / $debut) + $debut - 1;
	if ($heure100 > $fin) $heure100 = (($heure100-$fin) / (24 - $fin)) + $fin;

	$top = floor(($heure100 - $debut + 1) * $dimheure);

	return $top;	
}

# Calcule la hauteur entre deux heures
function http_cal_height ($heure, $heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) {

	$height = http_cal_top ($heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) 
				- http_cal_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize);

	$padding = floor(($dimheure / 3600) * 240);
	$height = $height - (2* $padding + 2); // pour padding interieur
	
	if ($height < ($dimheure/4)) $height = floor($dimheure/4); // eviter paves totalement ecrases
	
	return $height;	
}

# affiche un mois en grand, avec des tableau de clics vers d'autres mois
# si premier jour et dernier_jour sont donnes, affiche les semaines delimitees.

function http_calendrier_init_mois($date, $echelle, $partie_cal, $script ,$evt)
{
	list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;

	if ($sansduree)
		foreach($sansduree as $d => $r) 
			{
			  $evenements[$d] = !$evenements[$d] ? $r : 
			     array_merge($evenements[$d], $r); }
	$annee = annee($date);
	$mois = mois($date);
	if (!$premier_jour) $premier_jour = '01';
	if (!$dernier_jour)
	  {
	    $dernier_jour = 31;
	    while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	  }

	return 
	  http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, $partie_cal, $echelle, $evenements, $script) .
	  http_calendrier_sans_date($evenements["0"]) .
	  http_calendrier_aide_mess();
}

function http_calendrier_sans_date($evenements)
{
  $r = http_calendrier_ics($evenements);
  return  (!$r ? ''  :
	   ("\n<table><tr><td class='calendrier-arial10'><b>".
	    _T('info_mois_courant').
	    "</b>" .
	    $r .
	    "</td></tr>\n</table>"));
}

function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune;
  if (_DIR_RESTREINT) return "";
  return
   "\n<br /><br /><br />\n<table width='700'>\n<tr><td><font face='arial,helvetica,sans-serif' size='2'>" .
    "<b>"._T('info_aide')."</b>" .
    "<br />$bleu\n"._T('info_symbole_bleu')."\n" .
    "<br />$vert\n"._T('info_symbole_vert')."\n" .
    "<br />$jaune\n"._T('info_symbole_jaune')."\n" .
    "</font></td></tr>\n</table>";
 }

# Bandeau superieur d'un calendrier selon son $type (jour/mois/annee):
# 2 icones vers les 2 autres types, a la meme date $jour $mois $annee
# 2 icones de loupes pour zoom sur la meme date et le meme type
# 2 fleches appelant le $script sur les periodes $pred/$suiv avec une $ancre
# et le $nom du calendrier

function http_calendrier_navigation($jour, $mois, $annee, $partie_cal, $echelle, $nom, $script, $args_pred, $args_suiv, $type, $ancre)
{
	global $spip_lang_right, $spip_lang_left, $couleur_foncee;

	if (!isset($couleur_foncee)) $couleur_foncee = '#aaaaaa';
	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;
	$script = http_calendrier_retire_args($script);
	if (!ereg('[?&]$', $script)) $script .= (strpos($script,'?') ? '&' : '?');
	$args = "jour=$jour&mois=$mois&annee=$annee$ancre";
	  
	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$id = 'nav-agenda' .ereg_replace('[^A-Za-z0-9]', '', $ancre);

	return 
	  "<div class='navigation-calendrier calendrier-moztop8'\nstyle='background-color: $couleur_foncee;'>"
	  . "<div style='float: $spip_lang_right; padding-left: 5px; padding-right: 5px;'>"
	  . (($type == "mois") ? '' :
	     (
		  http_href_img(("$script$args&type=$type&set_partie_cal=tout"),
				 "heures-tout.png",
				 "class='calendrier-png" .
				 (($partie_cal == "tout") ? " calendrier-opacity'" : "'"),
				 _T('cal_jour_entier'))
		  .http_href_img(("$script$args&type=$type&set_partie_cal=matin"),
				 "heures-am.png",
				 "class='calendrier-png" .
				 (($partie_cal == "matin") ? " calendrier-opacity'" : "'"),
				 _T('cal_matin'))

		  .http_href_img(("$script$args&type=$type&set_partie_cal=soir"),
				 "heures-pm.png", 
				 "class='calendrier-png" .
				 (($partie_cal == "soir") ? " calendrier-opacity'" : "'"),
				 _T('cal_apresmidi'))
		  . "&nbsp;"
		  . http_href_img(("$script$args&type=$type&set_echelle=" .
					  floor($echelle * 1.5)),
					 "loupe-moins.gif",
					 '',
					 _T('info_zoom'). '-')
		  . http_href_img(("$script$args&type=$type&set_echelle=" .
					  floor($echelle / 1.5)), 
					 "loupe-plus.gif",
					 '', 
					 _T('info_zoom'). '+')
		  ))
	  . http_href_img(("$script$args&type=jour&echelle=$echelle"),"cal-jour.gif",
			  (($type == 'jour') ? " class='calendrier-opacity'" : ''),
			  _T('cal_par_jour'))

	  . http_href_img("$script$args&type=semaine&echelle=$echelle", "cal-semaine.gif", 
			  (($type == 'semaine') ?  " class='calendrier-opacity'" : "" ),
			  _T('cal_par_semaine'))

	  . http_href_img("$script$args&type=mois&echelle=$echelle","cal-mois.gif",
			  (($type == 'mois') ? " class='calendrier-opacity'" : "" ),
			  _T('cal_par_mois'))
	  . "</div>"
	  . "&nbsp;&nbsp;"
	  . http_href_img($script . "type=$type&echelle=$echelle&jour=$jour_today&mois=$mois_today&annee=$annee_today$ancre",
			  "cal-today.gif",
			  (" onmouseover=\"montrer('$id');\"" .
			   (($annee == $annee_today && $mois == $mois_today && (($type == 'mois')  || ($jour == $jour_today)))
			    ? " class='calendrier-opacity'" : "")),
			  _T("info_aujourdhui"))
	  . "&nbsp;"
	  . (!$args_pred ? '' :
	     http_href($script . "type=$type&echelle=$echelle&$args_pred$ancre",
		       http_img_pack("fleche-$spip_lang_left.png", '&lt;&lt;&lt;', "class='calendrier-png'"),
		       _T('precedent')))
	  . (!$args_suiv ? '' :
	     http_href(($script . "type=$type&echelle=$echelle&$args_suiv$ancre"),
		       http_img_pack("fleche-$spip_lang_right.png",  '&gt;&gt;&gt;', "class='calendrier-png'"),
		       _T('suivant')))
	  . "&nbsp;&nbsp;"
	  . $nom
	  . (_DIR_RESTREINT ? '' :  aide("messcalen"))
	  . "</div>"
	  . http_agenda_invisible($id, $annee, $jour, $mois, $script, $ancre);
}


// fabrique un petit agenda accessible par survol

function http_agenda_invisible($id, $annee, $jour, $mois, $script, $ancre)
{
	global $spip_lang_right, $spip_lang_left, $couleur_claire;
	if (!isset($couleur_claire)) $couleur_claire = 'white';
	$gadget = "<div style='position: relative;z-index: 1000;'
			onmouseover=\"montrer('$id');\"
			onmouseout=\"cacher('$id');\">";

	$gadget .= "<table id='$id' class='calendrier-cadreagenda' style='position: absolute; background-color: $couleur_claire'>";
	$gadget .= "\n<tr><td colspan='3' style='text-align:$spip_lang_left;'>";

	$annee_avant = $annee - 1;
	$annee_apres = $annee + 1;

	for ($i=$mois; $i < 13; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee_avant$ancre",
				     nom_mois("$annee_avant-$i-1"),'','', 'calendrier-annee') ;
			}
	for ($i=1; $i < $mois - 1; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee$ancre",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	$gadget .= "</td></tr>"
		. "\n<tr><td class='calendrier-tripleagenda'>"
		. http_calendrier_agenda($mois-1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td class='calendrier-tripleagenda'>"
		. http_calendrier_agenda($mois, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td class='calendrier-tripleagenda'>"
		. http_calendrier_agenda($mois+1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>"
		. "</tr>"
		. "\n<tr><td colspan='3' style='text-align:$spip_lang_right;'>";
	for ($i=$mois+2; $i <= 12; $i++) {
				$gadget .= http_href($script. "mois=$i&annee=$annee$ancre",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	for ($i=1; $i < $mois+1; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee_apres$ancre",
					nom_mois("$annee_apres-$i-1"),'','', 'calendrier-annee');
			}
	return $gadget . "</td></tr></table></div>";
}


# affichage du bandeau d'un calendrier de plusieurs semaines
# si la periode est inferieure a 31 jours, on considere que c'est un mois
# et on place les boutons de navigations vers les autres mois et connexe;
# sinon on considere que c'est un planning ferme et il n'y a pas de navigation

function http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, $partie_cal, $echelle, $evenements, $script)
{
	global $spip_ecran, $couleur_claire, $couleur_foncee;

	$today=getdate(time());
	$j=$today["mday"];
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
	$prec = "mois=$mois_prec&annee=$annee_prec";
	$suiv = "mois=$mois_suiv&annee=$annee_suiv";
	$periode = affdate_mois_annee("$annee-$mois-1");
	}
	list($purscript, $ancre) = http_calendrier_script($script);

      return 
	"<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0' border='0'>" .
	"\n<tr><td colspan='7'>" .
	http_calendrier_navigation($j,
				   $mois,
				   $annee,
				   $partie_cal,
				   $echelle,
				   $periode,
				   $purscript,
				   $prec,
				   $suiv,
				   'mois',
				   $ancre) .
	"</td></tr>" .
	http_calendrier_les_jours(array(_T('date_jour_2'),
			    _T('date_jour_3'),
			    _T('date_jour_4'),
			    _T('date_jour_5'),
			    _T('date_jour_6'),
			    _T('date_jour_7'),
			    _T('date_jour_1')),
		      $couleur_claire,
		      $couleur_foncee) .
	http_calendrier_suitede7($mois,$annee, $premier_jour, $dernier_jour,$evenements, $purscript, $ancre) .
	'</table>';
}

# affiche le bandeau des jours

function http_calendrier_les_jours($intitul, $claire, $foncee)
{
  $nb = count($intitul);
  if (!$nb) return '';
  $r = '';
  $bo = "\n\tstyle='width: " .    round(100/$nb) .  "%'";
  foreach($intitul as $j) $r .= "\n\t<td class='calendrier-titre calendrier-verdana10' $bo>$j</td>";
  return  "\n<tr style='background-color: $claire'>$r\n</tr>";
}

# dispose les lignes d'un calendrier de 7 colonnes (les jours)
# chaque case est garnie avec les evenements du jour figurant dans $evenements

function http_calendrier_suitede7($mois_today,$annee_today, $premier_jour, $dernier_jour,$evenements, $script, $ancre='')
{
	global $couleur_claire, $spip_lang_left, $spip_lang_right;

	if (!ereg('[?&]$', $script))
	  $script .= (strpos($script,'?') ? '&' : '?');

	$class_dispose = "border-bottom: 1px solid $couleur_claire; border-$spip_lang_right: 1px solid $couleur_claire; height: 100px; width: 14%; vertical-align: top;"; 
  
	// affichage du debut de semaine hors periode
	$jour_semaine = date("w",mktime(1,1,1,$mois_today,$premier_jour,$annee_today));
	if ($jour_semaine==0) $jour_semaine=7;

	$total = '';
	$ligne = '';
	for ($i=1;$i<$jour_semaine;$i++){$ligne .= "\n\t<td style=\"border-bottom: 1px solid $couleur_claire;\">&nbsp;</td>";}

	$ce_jour=date("Ymd");
	$border_left = " border-$spip_lang_left: 1px solid $couleur_claire;";
	for ($j=$premier_jour; $j<=$dernier_jour; $j++){
		$nom = mktime(1,1,1,$mois_today,$j,$annee_today);
		$jour = date("d",$nom);
		$jour_semaine = date("w",$nom);
		$mois_en_cours = date("m",$nom);
		$annee_en_cours = date("y",$nom);
		$amj = date("Y",$nom) . $mois_en_cours . $jour;

		if ($jour_semaine == 0) {
			$couleur_lien = "black";
			$couleur_fond = $couleur_claire;
		}
		else {
			$couleur_lien = "black";
			$couleur_fond = "#eeeeee";
		}
		
		if ($amj == $ce_jour) {
			$couleur_lien = "red";
			$couleur_fond = "white";
		}

		$ligne .= "\n\t<td style='$class_dispose background-color: $couleur_fond;$border_left'>" .
		  http_calendrier_clics($annee_en_cours, $mois_en_cours, $jour, $script, $ancre) .
		  http_calendrier_ics($evenements[$amj], $amj).
			"\n\t</td>";
		if ($jour_semaine==0) 
		{ 
			$total .= "\n<tr>$ligne\n</tr>";
			$ligne = '';
			$border_left = " border-$spip_lang_left: 1px solid $couleur_claire;";
		} else	$border_left = "";
	}
	return  $total . ($ligne ? "\n<tr>$ligne\n</tr>" : '');
}

function http_calendrier_clics($annee, $mois, $jour, $script, $ancre)
{
  $script .= "jour=$jour&mois=$mois&annee=$annee";

  if (!_DIR_RESTREINT) 
    return http_href("$script&type=jour" . $ancre, $jour,
		     '', '','calendrier-helvetica16') . 
      http_calendrier_message3(false, $jour,$mois,$annee);
  else
    {
      $d = mktime(0,0,0,$mois, $jour, $annee);
      $semaine = date("W", $d);
      return 
	"<table width='100%'>\n<tr><td style='text-align: left'>". 
	http_href("$script&type=jour" . $ancre, 
		  "$jour/$mois",
		  _T('date_jour_'. (1+date('w',$d))) .
		  " $jour " .
		  _T('date_mois_'.(0+$mois)),
		  '','calendrier-helvetica16') .
	"</td><td style='text-align: right'>" .
	http_href("$script&type=semaine" . $ancre,
		  $semaine,
		  _T('date_semaines') . " $semaine",
		  '',
		  'calendrier-helvetica16') .
	"</td></tr>\n</table>";
    }
}

# dispose les evenements d'une semaine

function http_calendrier_init_semaine($date, $echelle, $partie_cal, $script, $evt)
{
	global $couleur_claire, $couleur_foncee, $spip_ecran, $spip_lang_left;
	$jour_today = journum($date);
	$mois_today = mois($date);
	$annee_today = annee($date);

	list($articles, $evenements) = $evt;
	list($script, $nav) = http_calendrier_script($script);

	if ($partie_cal == "soir") {
		$debut = 12;
		$fin = 23;
	} else if ($partie_cal == "matin") {
		$debut = 4;
		$fin = 15;
	} else {
		$debut = 7;
		$fin =20;
	}
	
	if ($spip_ecran == "large") $largeur = 90;
	else $largeur = 60;

	$jour_semaine = date("w",mktime(1,1,1,$mois_today,$jour_today,$annee_today));
	if ($jour_semaine==0) $jour_semaine=7;
	$intitul = array();
	$liens = array();
	$href = $script .
	  (ereg('[?&]$', $script) ? '' : (strpos($script,'?') ? '&' : '?')) .
	  "type=jour";
	for ($j=0; $j<7;$j++){
		$nom = mktime(0,0,0,$mois_today,$jour_today-$jour_semaine+$j+1,$annee_today);
		$date = date("Y-m-d", $nom);
		$v = array('date' => date("Ymd", $nom),
			'nom' => nom_jour($date),
			'jour' => journum($date),
			'mois' => mois($date),
			'annee' => annee($date),
			'index' => date("w", $nom));
		$intitul[$j] = $v;
		$liens[$j] = 
		http_href(($href .
			   "&jour=" .
			   $v['jour'] .
			   "&mois=" .
			   $v['mois'] .
			   "&annee=" .
			   $v['annee'] .
			   $nav),
			  ($v['nom'] .
				" " .
				$v['jour'] .
				(($v['jour'] ==1) ? 'er' : '') .
				($nav  ? ('/' . (0+$v['mois'])) : '')),
				'',
				'color:black;');
	}

	list($dimheure, $dimjour, $fontsize, $padding) =
	calendrier_echelle($debut, $fin, $echelle);

	$today=getdate(time());
	$jour_t = $today["mday"];
	$mois_t = $today["mon"];
	$annee_t = $today["year"];
	$total = '';
	foreach($intitul as $k => $v) {
		$d = $v['date'];
		$total .= "\n<td style='width: 14%; vertical-align: top'>" .
		  "<div class='calendrier-verdana10' style='color: #999999; background-color: " .
					     (($v['index'] == 0) ? 
					      $couleur_claire :
					      (($v['jour'] == $jour_t AND 
						$v['mois'] == $mois_t AND
						$v['annee'] == $annee_t) ? 
					       "white" :
					       "#eeeeee")) .
		  "; position: relative; height: ${dimjour}px; font-size: ${fontsize}px;\n" .
		  " border-$spip_lang_left: 1px solid $couleur_claire; border-bottom: 1px solid $couleur_claire;'>" . 
		  http_calendrier_jour_ics($debut,$fin,$largeur, $echelle, $evenements[$d], $d).
		  "</div>" .
		  http_calendrier_jour_trois($articles[$d], 0, $dimjour, $fontsize, $couleur_claire) . 
		  "\n</td>";
	}
	$debut = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today));
	$fin = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+7, $annee_today));

	return 
	  "\n<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0' border='0'>" .
	  "\n<tr><td colspan='7'>" .
	  http_calendrier_navigation($jour_today,
				     $mois_today,
				     $annee_today,
				     $partie_cal, 
				     $echelle,
				     ((annee($debut) != annee($fin)) ?
				      (affdate($debut)." -<br />".affdate($fin)) :
				      ((mois($debut) == mois($fin)) ?
				       (journum($debut)." - ".affdate_jourcourt($fin)) :
				       (affdate_jourcourt($debut)." - ".affdate_jourcourt($fin)))),
				     $script,
				     "mois=$mois_today&annee=$annee_today&jour=".($jour_today-7),
				     "mois=$mois_today&annee=$annee_today&jour=".($jour_today+7),
				     'semaine',
				     $nav) .
	  "</td></tr>\n" .
	  http_calendrier_les_jours($liens, $couleur_claire, $couleur_foncee) .
	  "\n<tr>$total</tr>" .
	  "</table>" .
	  http_calendrier_sans_date($articles["0"]) .
	  http_calendrier_aide_mess();
}

// agenda mensuel 

function http_calendrier_agenda ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, $semaine = false,  $script='', $ancre='', $evt='') {

  if (!$script) $script =  $GLOBALS['PHP_SELF'] ;
  if (!strpos($script, '?')) $script .= '?';
  if (!$mois) {$mois = 12; $annee--;}
  elseif ($mois==13) {$mois = 1; $annee++;}
  if (!$evt) $evt = sql_calendrier_agenda($mois, $annee);
  return 
    "<div class='calendrier-titre calendrier-arial10'>" .
    http_href($script . "mois=$mois&annee=$annee$ancre",
	      affdate_mois_annee("$annee-$mois-1"),
	      '',
	      'color: black;') .
    "<table width='100%' cellspacing='0' cellpadding='0'>" .
    http_calendrier_agenda_rv ($annee, $mois, $evt,				
			        'http_jour_clic', array($script, $ancre),
			        $jour_ved, $mois_ved, $annee_ved, 
				$semaine) .
    "</table>" .
    "</div>";
}

function http_jour_clic($annee, $mois, $jour, $type, $couleur, $perso)
{

  list($script, $ancre) = $perso;

  return http_href($script . "type=$type&jour=$jour&mois=$mois&annee=$annee$ancre", 
		   $jour,
		   '',
		   "color: $couleur; font-weight: bold");
}

// typographie un mois sous forme d'un tableau de 7 colonnes

function http_calendrier_agenda_rv ($annee, $mois, $les_rv, $fclic, $perso='',
				    $jour_ved='', $mois_ved='', $annee_ved='',
				    $semaine='') {
	global $couleur_foncee;
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
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n\t<td></td>";
	$style0 = "border: 1px solid $couleur_foncee;";
	for ($j=1; (checkdate($mois,$j,$annee)); $j++) {
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		if ($jour_semaine==0) $jour_semaine=7;

		if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
		  $class= 'calendrier-arial11 calendrier-demiagenda';
		  $style = $style0;
		  $type = 'jour';
		  $couleur = "black";
		  } else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		  $class= 'calendrier-arial11 calendrier-demiagenda' . 
 		      (($jour_semaine==1) ? " calendrier-$spip_lang_left"  :
		       (($jour_semaine==7) ? " calendrier-$spip_lang_right" :
			''));
		  $style = $style0;
		  $type = ($semaine ? 'semaine' : 'jour') ;
		  $couleur = "black";
		} else {
		  if ($j == $jour_today AND $cemois) {
			$style = "background-color: $couleur_foncee";
			$couleur = "white";
		    } else {
			if ($jour_semaine == 7) {
				$style = "background-color: #aaaaaa";
				$couleur = 'white';
			} else {
				$style = "background-color: #ffffff";
				$couleur = "#aaaaaa";
			}
			if ($les_rv[$j] > 0) {
			  $style = "background-color: #ffffff";
			  $couleur = "black";
			}
		  }
		  $class= 'calendrier-arial11 calendrier-agenda';
		  $type = ($semaine ? 'semaine' : 'jour') ;
		}
		$ligne .= "\n\t<td><div class='$class' style='$style'>" .
		  $fclic($annee,$mois, $j, $type, $couleur, $perso) .
		  "</div></td>";
		if ($jour_semaine==7) 
		    {
		      $total .= "\n<tr>$ligne\n</tr>";
		      $ligne = '';
		    }
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne\n</tr>");
}


# si la largeur le permet, les evenements sans duree, 
# se placent a cote des autres, sinon en dessous

function http_calendrier_jour_trois($evt, $largeur, $dimjour, $fontsize, $border)
{
	global $spip_lang_left,  $couleur_claire; 
	if (!$evt) return '';
	$types = array();
	foreach($evt as $v)	$types[$v['CATEGORIES']][] = $v;
	$res = '';
	foreach ($types as $k => $v) {
		$res .= "\n<div class='calendrier-verdana10 calendrier-titre'>".
		  _T($k) .
		  "</div>" .
		  http_calendrier_ics($v);
	}
		
	$pos = ((_DIR_RESTREINT || $largeur) ? "-$dimjour" : 0);
	if ($largeur) $largeur += (5*$fontsize);
	else if (_DIR_RESTREINT) $largeur = (3*$fontsize);
	  
	return "\n<div style='position: relative; z-index: 2; top: ${pos}px; margin-$spip_lang_left: " . $largeur . "px'>$res</div>";
}

# Affiche une grille horaire 
# Selon l'echelle demandee, on affiche heure, 1/2 heure 1/4 heure, 5minutes.

function http_calendrier_heures($debut, $fin, $dimheure, $dimjour, $fontsize)
{
	global $spip_lang_left, $spip_lang_right;
	$slice = floor($dimheure/(2*$fontsize));
	if ($slice%2) $slice --;
	if (!$slice) $slice = 1;

	$total = '';
	for ($i = $debut; $i < $fin; $i++) {
		for ($j=0; $j < $slice; $j++) 
		{
			$gras = "calendrier-heure" . ($j  ? "face" : "pile");
			
			$total .= "\n<div class='$gras' style='$spip_lang_left: 0px; top: ".
				http_cal_top ("$i:".sprintf("%02d",floor(($j*60)/$slice)), $debut, $fin, $dimheure, $dimjour, $fontsize) .
				"px;'>$i:" .
				sprintf("%02d",floor(($j*60)/$slice)) . 
				"</div>";
		}
	}

	return "\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: 2px;'>0:00</div>" .
		$total .
		"\n<div class='calendrier-heurepile' style='$spip_lang_left: 0px; top: ".
		http_cal_top ("$fin:00", $debut, $fin, $dimheure, $dimjour, $fontsize).
		"px;'>$fin:00</div>" .
		"\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: ".
		($dimjour - $fontsize - 2) .
		"px;'>23:59</div>";
}


// Conversion d'un tableau de champ ics en des balises div successives
// un champ CATEGORIES numerique indique un evenement avec heure

function http_calendrier_ics($evenements, $amj = "") 
{
	$res = '';
	if ($evenements)
	{
		foreach ($evenements as $evenement)
		{
		  list($ev, $style, $class) =
				is_int($evenement['CATEGORIES']) ?
				http_calendrier_avec_heure($evenement, $amj) :
				http_calendrier_sans_heure($evenement);
		  $res .= "\n<div class='$class' style='$style'>$ev\n</div>\n"; 
		}
	}
	return $res;
}

function http_calendrier_sans_heure($evenement)
{
	if ($evenement['CATEGORIES'] == 'info_articles')
	  $i = 'puce-verte-breve.gif';
	else
	  $i = 'puce-blanche-breve.gif';
	$desc = propre($evenement['DESCRIPTION']);
	$sum = $evenement['SUMMARY'];
	if (!$sum) $sum = $desc;
	$sum = http_img_pack($i, $desc,  "style='width: 8px; height: 9px; border: 0px'") . '&nbsp;' . $sum;
	if ($evenement['URL']) {
		$sum = http_href($evenement['URL'], $sum, $desc);
	}
	return array($sum, "color: black", 'calendrier-arial10');
}

function http_calendrier_avec_heure($evenement, $amj)
{
	$jour_debut = substr($evenement['DTSTART'], 0,8);
	$jour_fin = substr($evenement['DTEND'], 0, 8);
	if (!($jour_fin > 0)) $jour_fin = $jour_debut;
	if (!(($jour_debut > 0) AND
	      (($jour_debut <= $amj) AND ($jour_fin >= $amj))))
	  return array();
	
	$desc = propre($evenement['DESCRIPTION']);
	$sum = $evenement['SUMMARY'];
	if (!$sum) $sum = $desc;
	$sum = "<span style='color: black'>" .
	  ereg_replace(' +','&nbsp;', typo($sum)) .
	  "</span>";
	if ($evenement['URL'])
	  $sum = http_href($evenement['URL'], $sum, $desc);
	$radius_top = " radius-top";
	$radius_bottom = " radius-bottom";
	$deb_h = substr($evenement['DTSTART'],-6,2);
	$deb_m = substr($evenement['DTSTART'],-4,2);
	$fin_h = substr($evenement['DTEND'],-6,2);
	$fin_m = substr($evenement['DTEND'],-4,2);
	
	if ($deb_h >0 OR $deb_m > 0) {
	  if ((($deb_h > 0) OR ($deb_m > 0)) AND $amj == $jour_debut)
	    { $deb = '<b>' . $deb_h . ':' . $deb_m . '</b> ';}
	  else { 
	    $deb = '...'; 
	    $radius_top = "";
	  }
	  
	  if ((($fin_h > 0) OR ($fin_m > 0)) AND $amj == $jour_fin)
	    { $fin = '<b>' . $fin_h . ':' . $fin_m . '</b> ';}
	  else { 
	    $fin = '...'; 
	    $radius_bottom = "";
	  }
	  
	  if ($amj == $jour_debut OR $amj == $jour_fin) {
	    $sum = "<div>$deb-$fin</div>$sum";
	    $opacity = "";
	  }
	  else {
	    $opacity = " -moz-opacity: 0.5; filter: alpha(opacity=50);";
	  }
	} else { $opacity = "";	}
	list($b,$c) = calendrier_div_style($evenement);

	return array($sum,
		     "padding: 2px; margin-top: 2px;
$opacity background-color: $b; color: $c; border: 1px solid $c",
		     "calendrier-arial10$radius_top$radius_bottom");
}

// Conversion d'un tableau de champ ics en des balises div positionnees    
// Les $evenements a $date commencant a $debut heure et finissant a $fin heure// ont des couleurs definies par calendrier_div_style 
// $echelle est le nombre de secondes representees par 1 pixel

function http_calendrier_jour_ics($debut, $fin, $largeur, $echelle, $evenements, $date) {
	global $spip_lang_left;

	if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;


	list($dimheure, $dimjour, $fontsize, $padding) = calendrier_echelle($debut, $fin, $echelle);
	$modif_decalage = round($largeur/8);

	$total = '';

	if ($evenements)
    {
		$tous = 1 + count($evenements);
		$i = 0;
		foreach($evenements as $evenement){

			$d = $evenement['DTSTART'];
			$e = $evenement['DTEND'];
			$d_jour = substr($d,0,8);
			$e_jour = substr($e,0,8);
			$debut_avant = false;
			$fin_apres = false;
			
			$radius_top = " radius-top";
			$radius_bottom = " radius-bottom";
			
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
				$haut = 0;
				$bordure = "border-bottom: dashed 2px";
			}
			else
			{
				$bordure = "border: 1px solid";
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
						
						
			$haut = http_cal_top ("$heure_debut:$minutes_debut", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$bas = http_cal_top ("$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$hauteur = http_cal_height ("$heure_debut:$minutes_debut", "$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
			if ($bas_prec > $haut) $decale += $modif_decalage;
			else $decale = (4 * $fontsize);
			if ($bas > $bas_prec) $bas_prec = $bas;
			$url = $evenement['URL']; 
			$desc = propre($evenement['DESCRIPTION']);
			$perso = $evenement['ATTENDEE'];
			$lieu = $evenement['LOCATION'];
			$sum = ereg_replace(' +','&nbsp;', typo($evenement['SUMMARY']));
			if (!$sum) { $sum = $desc; $desc = '';}
			if (!$sum) { $sum = $lieu; $lieu = '';}
			if (!$sum) { $sum = $perso; $perso = '';}
			if ($sum)
			  $sum = "<span class='calendrier-verdana10'><b>$sum</b>$lieu $perso</span>";
			if (($largeur > 90) && $desc)
			  $sum .=  "\n<br /><span style='color: black'>$desc</span>";
			$colors = calendrier_div_style($evenement);
			if ($colors)
			{
				list($bcolor,$fcolor) = $colors;
			}
			else 
			{ 
				$bcolor = 'white';
				$fcolor = 'black';
			}
			$total .= "\n<div class='calendrier-arial10$radius_top$radius_bottom' 
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
				"px; background-color: " .
				$bcolor .
				";color: " .
				$fcolor .
				"; $bordure $fcolor;'
	onmouseover=\"this.style.zIndex=" . $tous . "\"
	onmouseout=\"this.style.zIndex=" . $i . "\">" .
			  ((!$url) ? 
					$sum :
				 http_href($url, $sum, $desc,"color: $fcolor")) . 
				"</div>";
			}
		}
    }
	return
		http_calendrier_heures($debut, $fin, $dimheure, $dimjour, $fontsize) .
			$total ;
}

function http_calendrier_init_jour($date, $echelle,  $partie_cal, $script, $evt){
	global $spip_ecran;
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	list($script, $ancre) = http_calendrier_script($script);
	$gauche = (_DIR_RESTREINT  || ($spip_ecran != "large"));
	return 	
	  "\n<table class='calendrier-table-$spip_ecran'>" .
	  "\n<tr><td class='calendrier-td-gauche'> " .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	  http_calendrier_navigation($jour, $mois, $annee, $partie_cal, $echelle,
				     (nom_jour("$annee-$mois-$jour") . " " .
				      affdate_jourcourt("$annee-$mois-$jour")),
				     $script,
				     "jour=".($jour-1)."&mois=$mois&annee=$annee",
				     "jour=".($jour+1)."&mois=$mois&annee=$annee",
				     'jour',
				     $nav) .
	  "</td><td class='calendrier-td-droit calendrier-arial10'> " .
	  "</td></tr>" .
	  "\n<tr><td class='calendrier-td-gauche'>" .
	  ($gauche ? '' :
	   http_calendrier_entetecol($script, $jour-1,$mois,$annee)) .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	  (_DIR_RESTREINT ? '' :
		   ("\n\t<div class='calendrier-titre'>" .
		    http_calendrier_message3($script,$jour,$mois,$annee) .
		    '</div>')) .
	  "</td><td class='calendrier-td-droit calendrier-arial10'> " .
	  (_DIR_RESTREINT ? '' :  http_calendrier_entetecol($script, $jour+1,$mois,$annee)) .
	  "</td></tr><tr>" .
		# afficher en reduction le tableau du jour precedent
	  "\n<td class='calendrier-td-gauche calendrier-arial10'>" .
	  ($gauche  ? '' :
	   http_calendrier_jour($jour-1,$mois,$annee, 0, $partie_cal, $echelle, 0, $script, $ancre, $evt)) .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	  http_calendrier_jour($jour,$mois,$annee, 300, $partie_cal, $echelle, 0, $script, $ancre, $evt) .
	  '</td>' .
		# afficher en reduction le tableau du jour suivant
	  "\n<td class='calendrier-td-droit calendrier-arial10'>" .

	  (_DIR_RESTREINT ? '' :
	   http_calendrier_jour($jour+1,$mois,$annee, 0, $partie_cal, $echelle, 0, $script, $ancre, $evt)) .
	  '</td>' .
	  "\n</tr></table>";
}


function http_calendrier_entetecol($script, $jour,$mois,$annee)
{
	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	
	return "<div class='calendrier-arial10 calendrier-titre'>" .
	  http_href("$script?type=jour&jour=$jour&mois=$mois&annee=$annee",
		      affdate_jourcourt("$annee-$mois-$jour"),
		      '',
		      'color:black;') .
	  "</div>";
}

function http_calendrier_message3($large, $jour,$mois,$annee)
{	
  global $bleu, $vert,$jaune;
  $b = _T("lien_nouvea_pense_bete");
  $v = _T("lien_nouveau_message");
  $j=  _T("lien_nouvelle_annonce");
  $href = "message_edit.php3?rv=$annee-$mois-$jour&new=oui";
  return 
    http_href("$href&type=pb", 
	      $bleu . ($large ? $b : ''), 
	      $b,
	      'color: blue;',
	      'calendrier-arial10') .
    "\n" .
    http_href("$href&type=normal",
	      $vert . ($large ? $v : ''), 
	      $v,
	      'color: green;',
	      'calendrier-arial10') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
      http_href("$href&type=affich",
		$jaune . ($large ? $j : ''), 
		$j,
		'color: #ff9900;',
		'calendrier-arial10')));
}


function http_calendrier_jour($jour,$mois,$annee,$largeur, $partie_cal, $echelle, $le_message = 0, $script =  'calendrier.php3', $ancre='', $evt='')
{

	global $spip_lang_left, $calendrier_message_fermeture;
	
	if ($partie_cal == "soir") {
		$debut_cal = 12;
		$fin_cal = 23;
	} else if ($partie_cal == "matin") {
		$debut_cal = 4;
		$fin_cal = 15;
	} else {
		$debut_cal = 7;
		$fin_cal =20;
	}

	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	
	list($articles, $messages) =
		($evt ? $evt : sql_calendrier_interval(sql_calendrier_jour($annee,$mois, $jour )));

	$j = sprintf("%04d%02d%02d", $annee,$mois,$jour);
	
	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut_cal, $fin_cal, $echelle);
	// faute de fermeture en PHP...
	$calendrier_message_fermeture = $le_message;

	return
 "<div	class='calendrier-verdana10 calendrier-jour' 
	style='position: relative; height: ${dimjour}px; font-size: ${fontsize}px'>\n" .
	  http_calendrier_jour_ics($debut_cal,$fin_cal,$largeur, $echelle, $messages[$j], $j) .
	  "</div>" .
	  http_calendrier_jour_trois($articles[$j], $largeur, $dimjour, $fontsize, '');
}

// Fonction pour la messagerie et ecrire/index.php

function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$total = '';
	if (!$messages) return $total;
	foreach ($messages as $row) {
		if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row['id_message'];

		$rv = ($row['rv'] == 'oui');
		$date = $row['date_heure'];
		$date_fin = $row['date_fin'];
		if ($row['type']=="pb") $bouton = "pense-bete";
		else if ($row['type']=="affich") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div  class='calendrier-arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
		}

		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		http_href($url,
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;' border='0'")) : 
				      http_img_pack($bouton.".gif", $bouton, "style='border: 0px'")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		((!$rv) ? '' :
		((affdate($date) == affdate($date_fin)) ?
		 ("<div class='calendrier-arial9'" . 
		  http_style_background('fond-agenda.gif', 
					"$spip_lang_right center no-repeat; float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px;") .'>'
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div class='calendrier-arial9'" . 
		  http_style_background('fond-agenda.gif', 
					"$spip_lang_right center no-repeat; float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px; text-align: center;") . '>'
		  . heures($date).":".minutes($date)."<br />...</div>" ))) .
		"<div><b>" .
		  http_href($url, typo($row['titre']), '', '', 'calendrier-verdana10') .
		"</b></div>" .
		"</td>" .
		"</tr>\n";

		$date_rv = $date_jour;
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



//------- fonctions d'appel MySQL. 
// au dela cette limite, pas de production HTML

function sql_calendrier_mois($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(0,0,0,$mois+1,1,$annee)) .
	" 00:00:00'";
	return array($avant, $apres);
}

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

function sql_calendrier_jour($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$jour-1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$jour+1,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// retourne un tableau de 2 tableaux indexes par des dates
// - le premier indique les evenements du jour, sans indication de duree
// - le deuxime indique les evenements commencant ce jour, avec indication de duree

function sql_calendrier_interval($limites) {
	list($avant, $apres) = $limites;
	$evt = array();
	sql_calendrier_interval_articles($avant, $apres, $evt);
	sql_calendrier_interval_breves($avant, $apres, $evt);
	return array($evt, sql_calendrier_interval_rv($avant, $apres));
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.
# Pour les articles post-dates vus de l'espace public,
# on regarde si c'est une redirection pour avoir une url interessante
# sinon on prend " ", c'est-a-dire la page d'appel du calendrier

function sql_calendrier_interval_articles($avant, $apres, &$evenements) {
	
	$result=spip_query("
SELECT	id_article, titre, date, descriptif, chapo
FROM	spip_articles
WHERE	statut='publie'
 AND	date >= $avant
 AND	date < $apres
ORDER BY date
");
	if (!_DIR_RESTREINT)
	  $script = 'articles' . _EXTENSION_PHP . "?id_article=";
	else
	  {
	    $now = date("Ymd");
	    $script = 'article' . _EXTENSION_PHP . "?id_article=";
	  }
	while($row=spip_fetch_array($result)){
		$amj = sql_calendrier_jour_ical($row['date']);
		if ((!_DIR_RESTREINT) || ($now >= $amj))
			$url = $script . $row['id_article'];
		else {
			if (substr($row['chapo'], 0, 1) != '=')
				$url = " ";
			else {
				list(,$url) = extraire_lien(array('','','',
					substr($row['chapo'], 1)));
				if ($url)
					$url = texte_script(str_replace('&amp;', '&', $url));
				else $url = " ";
			}
		}

		$evenements[$amj][]=
		    array(
			'CATEGORIES' => 'info_articles',
			'DESCRIPTION' => $row['descriptif'],
			'SUMMARY' => $row['titre'],
			'URL' =>  $url);
	}
}

function sql_calendrier_interval_breves($avant, $apres, &$evenements) {
	$result=spip_query("
SELECT	id_breve, titre, date_heure
FROM	spip_breves
WHERE	statut='publie'
 AND	date_heure >= $avant
 AND	date_heure < $apres
ORDER BY date_heure
");
	while($row=spip_fetch_array($result)){
		$amj = sql_calendrier_jour_ical($row['date_heure']);
		$script = (_DIR_RESTREINT ? 'breve' : 'breves_voir');
		$evenements[$amj][]=
		array(
			'URL' => $script . _EXTENSION_PHP . "?id_breve=" . $row['id_breve'],
			'CATEGORIES' => 'info_breves_02',
			'SUMMARY' => $row['titre']);
	}
}

function sql_calendrier_interval_rv($avant, $apres) {
	global $connect_id_auteur;
	$evenements= array();
	if (!$connect_id_auteur) return $evenements;
	$result=spip_query("
SELECT	messages.id_message, messages.titre, messages.texte,
	messages.date_heure, messages.date_fin, messages.type
FROM	spip_messages AS messages, 
	spip_auteurs_messages AS lien
WHERE	((lien.id_auteur='$connect_id_auteur'
 AND	lien.id_message=messages.id_message) OR messages.type='affich')
 AND	messages.rv='oui' 
 AND	((messages.date_fin >= $avant OR messages.date_heure >= $avant) AND messages.date_heure <= $apres)
 AND	messages.statut='publie'
GROUP BY messages.id_message
ORDER BY messages.date_heure
");
	while($row=spip_fetch_array($result)){
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$type=$row["type"];
		$id_message=$row['id_message'];

		if ($type=="pb")
		  $cat = 2;
		else {
		  if ($type=="affich")
		  $cat = 4;
		  else {
		    if ($type!="normal")
		      $cat = 12;
		    else {
		      $cat = 9;
		      $auteurs = array();
		      $result_aut=spip_query("
SELECT	auteurs.nom 
FROM	spip_auteurs AS auteurs,
	spip_auteurs_messages AS lien 
WHERE	(lien.id_message='$id_message' 
  AND	(auteurs.id_auteur!='$connect_id_auteur'
  AND	lien.id_auteur=auteurs.id_auteur))");
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
		$ical_apres = sql_calendrier_jour_ical("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois 
 		$j = 0;
		$amj = sql_calendrier_jour_ical("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));

		while ($amj <= $ical_apres) {
		if (!($amj == sql_calendrier_jour_ical($date_fin) AND ereg("00:00:00", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
			$evenements[$amj][$id_message]=
			  array(
				'URL' => "message.php3?id_message=$id_message",
				'DTSTART' => date_ical($date_heure),
				'DTEND' => date_ical($date_fin),
				'DESCRIPTION' => $row['texte'],
				'SUMMARY' => $row['titre'],
				'CATEGORIES' => $cat,
				'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));
			
			$j ++; 
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
			
			$amj = sql_calendrier_jour_ical($ladate);

		}

	}
  return $evenements;
}

// fonction SQL, pour la messagerie

function sql_calendrier_taches_annonces () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages 
WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC");
	if (spip_num_rows($result) > 0)
		while ($x = spip_fetch_array($result)) $r[] = $x;
	return $r;
}

function sql_calendrier_taches_pb () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages AS messages 
WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return $r;
}

function sql_calendrier_taches_rv () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT messages.* 
FROM spip_messages AS messages, spip_auteurs_messages AS lien 
WHERE ((lien.id_auteur='$connect_id_auteur' 
	AND lien.id_message=messages.id_message) 
	OR messages.type='affich') 
AND messages.rv='oui'
AND ( (messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) 
	AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH))
	OR (messages.date_heure < NOW() AND messages.date_fin > NOW() ))
AND messages.statut='publie' 
GROUP BY messages.id_message 
ORDER BY messages.date_heure");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return  $r;
}

function sql_calendrier_agenda ($mois, $annee) {
	global $connect_id_auteur;

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.date_heure FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie'");
	while($row=spip_fetch_array($result_messages)){
		$rv[journum($row['date_heure'])] = 1;
	}
	return $rv;
}

function sql_calendrier_jour_ical($d)  {
	return  substr($d, 0, 4) . substr($d, 5, 2) .substr($d, 8, 2);
}

// ce tableau est l'equivalent du switch affectant des globales dans inc.php
// plus 2 autres issus du inc_agenda originel

global $contrastes;
$contrastes = array(
		/// Marron
		array("#8C6635","#F5EEE5","#1A64DF","#955708"),
		/// Fushia
		array("#CD006F","#FDE5F2","#E95503","#8F004D"),
		/// Bleu
		array("#5da7c5","#EDF3FE","#814E1B","#435E79"),
		/// Bleu pastel
		array("#766CF6","#EBE9FF","#869100","#5B55A0"),
		/// Orange
		array("#fa9a00","#ffeecc","#396B25","#472854"),
		/// Rouge (Vermillon)
		array("#FF0000","#FFEDED","#D302CE","#D40202"),
		/// Orange
		array("#E95503","#FFF2EB","#81A0C1","#FF5B00"),
		/// Jaune
		array("#ccaa00", "#ffffee", "#65659C","#6A6A43"),
		/// Vert pastel
		array("#009F3C","#E2FDEC","#EE0094","#02722C"),
		/// Vert
		array("#9DBA00", "#e5fd63","#304C38","#854270"),
		/// Rouge (Bordeaux)
		array("#640707","#FFE0E0","#346868","#684747"),
		/// Gris
		array("#3F3F3F","#F2F2F2","#854270","#666666"),
		// Noir
		array("black","#aaaaaa",  "#000000", "#ffffff"),
		/// Caca d'oie
		array("#666500","#FFFFE0","#65659C","#6A6A43")
		);

# Choisit dans le tableau ci-dessus les couleurs d'un evenement
# si l'indice fourni par CATEGORIES est negatif, inversion des plans
# +++ un hack pour le cas special de ecrire/message.php

function calendrier_div_style($evenement)
{
  global $contrastes;
  global $calendrier_message_fermeture;
  if (isset($calendrier_message_fermeture) && 
      (ereg("=$calendrier_message_fermeture$", $evenement['URL'])))
    {return array('white', 'black');}
  else
    {
      $categ = $evenement['CATEGORIES'];

      if (!is_int($categ))
	return "";
      else 
	{ 
	  if ($categ >= 0) {$f=0;$b=1;$i=$categ;}else{$f=1;$b=0;$i=0-$categ;}
	  $i %= count($contrastes);
	  return array($contrastes[$i][$b], $contrastes[$i][$f]);
	}
    }
}
?>
