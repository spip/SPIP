<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CALENDRIER")) return;
define("_ECRIRE_INC_CALENDRIER", "1");

define(DEFAUT_D_ECHELLE,120); # 1 pixel = 2 minutes

// Ecrire cookies

if ($GLOBALS['set_echelle'] > 0) {
	spip_setcookie('spip_calendrier_echelle', floor($GLOBALS['set_echelle']), time() + 365 * 24 * 3600);
	$GLOBALS['echelle'] = floor($GLOBALS['set_echelle']);
} else 
	$GLOBALS['echelle'] = $GLOBALS['HTTP_COOKIE_VARS']['spip_calendrier_echelle'];

if ($GLOBALS['set_partie_cal']) {
	spip_setcookie('spip_partie_cal', $GLOBALS['set_partie_cal'], time() + 365 * 24 * 3600);
	$GLOBALS['partie_cal'] = $GLOBALS['set_partie_cal'];
} else 
	$GLOBALS['partie_cal'] = $GLOBALS['HTTP_COOKIE_VARS']['spip_partie_cal'];


# Typographie generale des calendriers de 3 type: jour/mois/annee
# Il faudrait rationnaliser le nom des fonctions 
# avec des suffixes identiques pour les memes fonctionnalites des 3 types

global $bleu, $vert, $jaune;

$bleu = "<img src='" . _DIR_IMG_PACK . "/m_envoi_bleu$spip_lang_rtl.gif' alt='B' width='14' height='7' border='0' />";
$vert = "<img src='" . _DIR_IMG_PACK . "/m_envoi$spip_lang_rtl.gif' alt='V' width='14' height='7' border='0' />";
$jaune= "<img src='" . _DIR_IMG_PACK . "/m_envoi_jaune$spip_lang_rtl.gif' alt='J' width='14' height='7' border='0' />";

// Conversion en HTML d'un tableau de champ ics
// Le champ URL devient une balise a href=URL entourant les champs SUMMARY et DESC
// Le champ CATEGORIES indique les couleurs pour le style CSS

function http_calendrier_ics($evenements, $amj = "") 
{
	$class_mois = '
		padding: 2px;
		margin-top: 2px;
		font-family: Arial, Sans, sans-serif;
		font-size: 10px; ';
	$res = '';
	if ($evenements)
	{
		foreach ($evenements as $evenement)
		{
			$url = $evenement['URL'];
			$afficher_ev = true;
						
			$jour_debut = substr($evenement['DTSTART'], 0,8);
			$jour_fin = substr($evenement['DTEND'], 0, 8);
			
			if ($jour_debut > 0) {
				if (!($jour_fin > 0)) $jour_fin = $jour_debut;
				if ($jour_debut > $amj OR $jour_fin < $amj) $afficher_ev = false;
			}
			
			if ($jour_debut < $amj) $afficher_suite = true;

			
			if ($afficher_ev) {			
				$radius_top = " -moz-border-radius-topleft: 6px; -moz-border-radius-topright: 6px;";
				$radius_bottom = " -moz-border-radius-bottomleft: 6px; -moz-border-radius-bottomright: 6px;";

				$deb_h = substr($evenement['DTSTART'],-6,2);
				$deb_m = substr($evenement['DTSTART'],-4,2);
				$fin_h = substr($evenement['DTEND'],-6,2);
				$fin_m = substr($evenement['DTEND'],-4,2);

				$desc = propre($evenement['DESCRIPTION']);
				$sum = $evenement['SUMMARY'];
				if (($sum) && ($sum[0] != '<'))
				{
					$sum = "<span style='color: black'>" .
						ereg_replace(' +','&nbsp;', typo($sum)) .
						"</span>";

				} else {
				  if ($desc) $sum .= " <span class='verdana1'>$desc</span>"; 
				}
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
						$date_affichee = "<div>$deb-$fin</div>";
						$opacity = "";
					}
					else {
						$date_affichee = "";
						$opacity = " -moz-opacity: 0.5; filter: alpha(opacity=50);";
						$desc = "";
					}
				} else {
					$date_affichee = "";
					$opacity = "";
				}

				$c = calendrier_div_style($evenement);
				if (!$c)
					$c = "font-size: 10px; color: black";
				else 
				{
					list($b,$c) = $c;
					$c = "$class_mois$radius_top$radius_bottom background-color: $b; color: $c; border: 1px solid $c;$opacity";
				}
				$res .=
					"\n<div style='$c'>" .
					$date_affichee .
				  (!$url ? "$sum $desc" : http_calendrier_href($url, $sum, $desc)) .
					"\n</div>\n"; 
			}
		}
	}
	return $res;
}

function http_calendrier_aujourdhui_et_aide($now, $texte, $href)
{
  return 
    (($now) ? '' :
     ("<a href='$href' class='cellule-h'>" .
      "<table cellpadding='0'><tr>\n" .
      "<td>" . http_img_pack("calendrier-24.gif", "alt=''") . "</td>\n" .
      "<td>" . _T("info_aujourdhui")."<br />".
      $texte .
      "</td></tr></table></a>\n")) .
    (_DIR_RESTREINT ? '' :  aide("messcalen"));
}
  
# affiche un mois en grand, avec des tableau de clics vers d'autres mois

function http_calendrier_tout($mois, $annee, $premier_jour, $dernier_jour)
{
	global $spip_lang_left, $largeur_table, $largeur_gauche, $spip_ecran;

	if ($spip_ecran == "large") {
		$largeur_gauche = 130;
		$largeur_table  = 954;
	} else {
		$largeur_gauche = 100;
		$largeur_table = 730;
	}

/*	$fclic = ((lire_meta("activer_messagerie") == "oui") AND 
		($GLOBALS["connect_activer_messagerie"] != "non"))?
		'http_calendrier_clics' : 
		'http_calendrier_sans_clics';*/
	$fclic = 'http_calendrier_clics';
	while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	$today=getdate(time());
	$m=$today["mon"];
	$a=$today["year"];

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_mois($annee,$mois, $premier_jour);
	if ($articles)
		foreach($articles as $d => $v) 
			{ $r = http_calendrier_image_et_typo($v);
			  $messages[$d] = !$messages[$d] ? $r : 
			     array_merge($messages[$d], $r); }
	if ($breves)
		foreach($breves as $d => $v) 
			{ $r = http_calendrier_image_et_typo($v);
			  $messages[$d] = !$messages[$d] ?  
			    $r : array_merge($messages[$d], $r); }

	$total = "<div>&nbsp;</div>" .
		"<table cellpadding=0 cellspacing=0 border=0 width='$largeur_table'>" .
		"\n<tr><td width='$largeur_table' valign='top'>" .
		http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, 
			$GLOBALS['echelle'], $messages, $fclic);
		"</td></tr>\n</table>";

	# messages sans date ?
	if ($messages["0"]){ 
		$total .=  "\n<table width='200'>\n<tr><td><font face='arial,helvetica,sans-serif' size='1'><b>".
		_T('info_mois_courant').
		"</b>" .
		http_calendrier_ics($messages["0"]) .
		"</font></td></tr>\n</table>";
	}

	if ($fclic == 'http_calendrier_clics')
		$total .= http_calendrier_aide_mess();

	return $total;
}

function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune;
 return
   "<br /><br /><br />\n<table width='700'>\n<tr><td><font face='arial,helvetica,sans-serif' size='2'>" .
    "<b>"._T('info_aide')."</b>" .
    "<br />$bleu "._T('info_symbole_bleu')."\n" .
    "<br />$vert "._T('info_symbole_vert')."\n" .
    "<br />$jaune "._T('info_symbole_jaune')."\n" .
    "</font></td></tr>\n</table>";
 }


function http_calendrier_retire_args($script, $args)
{
	foreach($args as $arg) {
		$script = ereg_replace("$arg=[^&]*&",'', $script);
		$script = ereg_replace("$arg=[^#]*#",'#', $script);
		$script = ereg_replace("$arg=[^&#]*$",'', $script);
	}
	return $script;
}

# Bandeau superieur d'un calendrier selon son $type (jour/mois/annee):
# 2 icones vers les 2 autres types, a la meme date $jour $mois $annee
# 2 icones de loupes pour zoom sur la meme date et le meme type
# 2 fleches appelant le $script sur les periodes $pred/$suiv avec une $ancre
# et au center le $nom du calendrier

function http_calendrier_navigation($jour, $mois, $annee, $echelle, $nom,
			    $script, $args_pred, $args_suiv, $type, $ancre)
{
  global $spip_lang_right, $spip_lang_left, $couleur_claire;

	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;

	$script = http_calendrier_retire_args($script, array('echelle'));
	$script .= (strpos($script,'?') ? '&' : '?');
	$args = "jour=$jour&mois=$mois&annee=$annee$ancre";
	  
  	$retour = "<div class='navigation-calendrier'>";
  
	$retour .= http_calendrier_href($script . "type=$type&echelle=$echelle&$args_pred$ancre",
			"<img\nsrc='" . _DIR_IMG_PACK . "/fleche-$spip_lang_left.png' class='format_png' alt='&lt;&lt;&lt;' width='12' height='12' />",
			'pr&eacute;c&eacute;dent');
	$retour .= "&nbsp;";
 
 	$retour .= "<b>$nom</b>";
 	$retour .= "&nbsp;";
	$retour .= http_calendrier_href(($script . "type=$type&echelle=$echelle&$args_suiv$ancre"),
					"<img\nsrc='" . _DIR_IMG_PACK . "/fleche-$spip_lang_right.png' class='format_png' alt='&gt;&gt;&gt;' width='12' height='12' />",
			'suivant');

 	$retour .= "&nbsp;&nbsp;&nbsp;&nbsp;";
 
 
        $img_att = ($type == 'jour') ? " class='navigation-bouton-desactive'" : '';
	$retour .= http_calendrier_href(($script . "type=jour&echelle=$echelle&$args"),
				    ("<img\nsrc='" . _DIR_IMG_PACK . "/cal-jour.gif' alt='jour' $img_att />"),
		    'calendrier par jour');
	($type == 'semaine') ? $img_att = " class='navigation-bouton-desactive'" : $img_att = "" ;
	$retour .= http_calendrier_href($script . "type=semaine&echelle=$echelle&$args",
					("<img\nsrc='" . _DIR_IMG_PACK . "/cal-semaine.gif' alt='semaine' $img_att />"),
		    'calendrier par semaine');
	($type == 'mois') ? $img_att = " class='navigation-bouton-desactive'" : $img_att = "" ;
	$retour .= http_calendrier_href($script . "type=mois&echelle=$echelle&$args",
					("<img\nsrc='" . _DIR_IMG_PACK . "/cal-mois.gif' alt='mois' $img_att />"),
		    'calendrier par mois');
	
  	$retour .= "&nbsp;&nbsp;&nbsp;&nbsp;";


	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$arguments = "jour=$jour_today&mois=$mois_today&annee=$annee_today$ancre" ;
	if ($type == 'mois') $condition = ($annee == $annee_today && $mois == $mois_today);
	else $condition = ($annee == $annee_today && $mois == $mois_today && $jour == $jour_today);
	
	$condition ? $img_att = " class='navigation-bouton-desactive'" : $img_att = "" ;
	
	$retour .= "<span onMouseOver=\"montrer('nav_agenda');\">";
	$retour .= http_calendrier_href($script . "type=$type&echelle=$echelle&$arguments",
					("<img\nsrc='" . _DIR_IMG_PACK . "/cal-today.gif' alt=\"aujourd\'hui\" $img_att />"),
		    'aujourd\'hui');
	$retour .= "</span>";

 	if ($type != "mois") {
		$retour .= "&nbsp;&nbsp;&nbsp;&nbsp;";
		$retour .= http_calendrier_href($script . "type=$type&set_echelle=" .
				floor($echelle * 1.5) . "&$args",
				"<img\nsrc='" . _DIR_IMG_PACK . "/loupe-moins.gif' alt='zoom-' />");
		$retour .= http_calendrier_href(($script . "type=$type&set_echelle=" .
			floor($echelle / 1.5) .
			"&$args"),
					"<img\nsrc='" . _DIR_IMG_PACK . "/loupe-plus.gif'  alt='zoom+' />");

 		$retour .= "&nbsp;&nbsp;";
	
	
		if ($GLOBALS['partie_cal'] == "tout") $img_att = " class='navigation-bouton-desactive'";
		else $img_att = "";
		$retour .= "<span$img_att>".http_calendrier_href(($script . "type=$type".
			"&set_partie_cal=tout" .
			"&$args"),
								 "<img\nsrc='" . _DIR_IMG_PACK . "/heures-tout.png' width='13' height='13' alt='tout' class='format_png' />") . "</span>";

		if ($GLOBALS['partie_cal'] == "matin") $img_att = " class='navigation-bouton-desactive'";
		else $img_att = "";
		$retour .= "<span$img_att>".http_calendrier_href(($script . "type=$type".
			"&set_partie_cal=matin" .
			"&$args"),
								 "<img\nsrc='" . _DIR_IMG_PACK . "/heures-am.png' width='13' height='13' alt='AM' class='format_png' />") . "</span>";

		if ($GLOBALS['partie_cal'] == "soir") $img_att = " class='navigation-bouton-desactive'";
		else $img_att = "";
		$retour .= "<span$img_att>".http_calendrier_href(($script . "type=$type".
			"&set_partie_cal=soir" .
			"&$args"),
								 "<img\nsrc='" . _DIR_IMG_PACK . "/heures-pm.png' width='13' height='13' alt='PM' class='format_png' />") . "</span>";
 	}




			$gadget = "<div id='nav_agenda' onMouseOver=\"montrer('nav_agenda');\" onMouseOut=\"cacher('nav_agenda');\" style='position: relative; visibility: hidden;z-index: 1000; '><div style='position: absolute; padding: 5px; background-color: $couleur_claire; margin-bottom: 5px; -moz-border-radius-bottomleft: 8px; -moz-border-radius-bottomright: 8px;'>";
			//$gadget .= "<a href='calendrier_semaine.php3' class='lien_sous'>";
			//$gadget .= _T('icone_agenda');
			//$gadget .= "</a>";
			
		//	$mois = $GLOBALS['mois'];
		//	$jour = $GLOBALS['jour'];
		//	$annee =$GLOBALS['annee'];
			
			$annee_avant = $annee - 1;
			$annee_apres = $annee + 1;

			$gadget .= "<table cellpadding='0' cellspacing='5' border='0' width='100%'>";
			$gadget .= "<tr><td colspan='3' style='text-align:$spip_lang_left;'>";
			for ($i=$mois; $i < 13; $i++) {
				$gadget .= http_calendrier_href("calendrier.php3?mois=$i&annee=$annee_avant",
					nom_mois("$annee_avant-$i-1"),'','', 'calendrier-annee') ;
			}
			for ($i=1; $i < $mois - 1; $i++) {
				$gadget .= http_calendrier_href("calendrier.php3?mois=$i&annee=$annee",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
			$gadget .= "</td>";
			$gadget .= "</tr>";
			$gadget .= "<tr>";
			$gadget .= "<td valign='top' width='33%'>";
			$gadget .= http_calendrier_agenda($mois-1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine']) ;
			$gadget .= "</td><td valign='top' width='33%'>";
			$gadget .= http_calendrier_agenda($mois, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine']) ;
			$gadget .= "</td><td valign='top' width='33%'>";
			$gadget .= http_calendrier_agenda($mois+1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine']) ;
			$gadget .= "</td>";
			$gadget .= "</tr>";
			$gadget .= "<tr><td colspan='3' style='text-align:$spip_lang_right;'>";
			for ($i=$mois+2; $i <= 12; $i++) {
				$gadget .= http_calendrier_href("calendrier.php3?mois=$i&annee=$annee",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
			for ($i=1; $i < $mois+1; $i++) {
				$gadget .= http_calendrier_href("calendrier.php3?mois=$i&annee=$annee_apres",
					nom_mois("$annee_apres-$i-1"),'','', 'calendrier-annee');
			}
			$gadget .= "</td></tr>";
			$gadget .= "</table>";
			
			$gadget .= "</div></div>";


 
	return $retour .
	  "&nbsp;&nbsp;&nbsp;" .
	  (_DIR_RESTREINT ? '' :  aide("messcalen")) .
	  "</div>".$gadget;    
}

# affichage du bandeau d'un calendrier d'une journee

function http_calendrier_navigation_jour($jour,$mois,$annee, $echelle, $script, $nav)
{
  $today=getdate(time());
  $jour_today = $today["mday"];
  $mois_today = $today["mon"];
  $annee_today = $today["year"];
//  return "<table width='100%'>" .
   return
     http_calendrier_navigation($jour, $mois, $annee, $echelle,
				(nom_jour("$annee-$mois-$jour") . " " .
				 affdate_jourcourt("$annee-$mois-$jour")),
				$script,
				"jour=".($jour-1)."&mois=$mois&annee=$annee",
				"jour=".($jour+1)."&mois=$mois&annee=$annee",
				'jour',
				$nav);
// "</table>";
}

# affichage du bandeau d'un calendrier d'une semaine

function http_calendrier_navigation_semaine($jour_today,$mois_today,$annee_today, $echelle, $jour_semaine, $script, $nav)
{
   $debut = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today));
  $fin = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+7, $annee_today));

  return     "\n<tr><td colspan='7'>" .
    http_calendrier_navigation($jour_today,
			       $mois_today,
			       $annee_today,
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
    "</td></tr>\n" ;
}

# affichage du bandeau d'un calendrier de plusieurs semaines
# si la periode est inferieure a 31 jours, on considere que c'est un mois
# et on place les boutons de navigations vers les autres mois et connexe;
# sinon on considere que c'est un planning ferme et il n'y a pas de navigation

function http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, $echelle, $evenements, $fclic)
{
  global $couleur_claire, $couleur_foncee;

      $today=getdate(time());
      $j=$today["mday"];
      $m=$today["mon"];
      $a=$today["year"];
      $mois_suiv=$mois+1;
      $annee_suiv=$annee;
      $mois_prec=$mois-1;
      $annee_prec=$annee;
      if ($mois==1){
	$mois_prec=12;
	$annee_prec=$annee-1;
	}
      else if ($mois==12){$mois_suiv=1;	$annee_suiv=$annee+1;}
      $nav = http_calendrier_navigation($j,
					$mois,
					$annee,
					$echelle,
					affdate_mois_annee("$annee-$mois-1"),
					$GLOBALS['PHP_SELF'],
					"mois=$mois_prec&annee=$annee_prec",
					"mois=$mois_suiv&annee=$annee_suiv",
					'mois',
					(($dernier_jour <= 31) ? '' : ('&' .
					 http_calendrier_retire_args($GLOBALS['QUERY_STRING'], 
								     array('echelle','jour','mois','annee', 'type')))));
 
					 
    
      return "<table border='0' cellspacing='0' cellpadding='0' width='100%' >" .
	"\n<tr><td colspan='7'>$nav</td></tr>".
	http_calendrier_les_jours(array(_T('date_jour_2'),
			    _T('date_jour_3'),
			    _T('date_jour_4'),
			    _T('date_jour_5'),
			    _T('date_jour_6'),
			    _T('date_jour_7'),
			    _T('date_jour_1')),
		      $couleur_claire,
		      $couleur_foncee) .
    http_calendrier_suitede7($mois,$annee, $premier_jour, $dernier_jour,$evenements, $fclic) .
    "\n</table>";
}

# affiche le bandeau des jours

function http_calendrier_les_jours($intitul, $claire, $foncee)
{
  $nb = count($intitul);
  if (!$nb) return '';
  $r = '';
  $bo = "style='width: " .
    round(100/$nb) .
    "%; padding: 3px; color: black; text-align: center; background-color: $claire; font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px;'";
  foreach($intitul as $j) $r .= "\n\t<td $bo><b>$j</b></td>";
  return  "<tr>$r\n</tr>";
}

# dispose les lignes d'un calendrier de 7 colonnes (les jours)
# chaque case est garnie avec les evenements du jour figurant dans $evenements
# et avec le resultat de l'application du parametre fonctionnel $fclic
# sur les valeurs jour/mois/annee

function http_calendrier_suitede7($mois_today,$annee_today, $premier_jour, $dernier_jour,$evenements,$fclic)
{
	global $couleur_claire, $spip_lang_left, $spip_lang_right;
	
	$class_dispose = "border-bottom: 1px solid $couleur_claire; border-$spip_lang_right: 1px solid $couleur_claire;"; 
  
	// affichage du debut de semaine hors periode
	$jour_semaine = date("w",mktime(1,1,1,$mois_today,$premier_jour,$annee_today));
	if ($jour_semaine==0) $jour_semaine=7;

	$total = '';
	$ligne = '';
	for ($i=1;$i<$jour_semaine;$i++){$ligne .= "\n\t<td style=\"border-bottom: 1px solid $couleur_claire;\">&nbsp;</td>";}

	$ce_jour=date("Ymd");
	$premier = true;
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

		$jour_mois = 
			("<span style='font-family: arial,helvetica,sans-serif; font-size: 16px; color: black'>" .
			(($dernier_jour <= 31) ? 	$jour : "$jour/$mois_en_cours") .
			"</span>");

		if ($premier) {
			$border_left = " border-$spip_lang_left: 1px solid $couleur_claire;";
			$premier = false;
		}
		else $border_left = "";

		$ligne .= "\n\t<td style='$class_dispose background-color: $couleur_fond;$border_left height: 100px; width: 14%; vertical-align: top'>" .
			$fclic($annee_en_cours, $mois_en_cours, $jour, $jour_mois) .
			(!$evenements[$amj] ? '' : http_calendrier_ics($evenements[$amj], $amj) ).
			"\n\t</td>";
		if ($jour_semaine==0) 
		{ 
			$total .= "\n<tr>$ligne\n</tr>";
			$ligne = '';
			$premier = true;
		}
	}
	return  $total . ($ligne ? "\n<tr>$ligne\n</tr>" : '');
}

# 3 fonctions pour servir de parametre a la precedente

function http_calendrier_sans_clics($annee, $mois, $jour, $clic)
{
    return $clic;
}

function http_calendrier_clics_jour_semaine($annee, $mois, $jour, $clic)
{
  global $REQUEST_URI;
  $req = ereg_replace("&jour=[0-9]*&mois=[0-9]*&annee=[0-9]*",'',$REQUEST_URI);
  $req = ereg_replace("&type=[a-z]*", '', ereg_replace("&var_.*=[^&]*", '', $req));
  $d = mktime(0,0,0,$mois, $jour, $annee);
  $mois = date("m", $d);
  $annee = date("Y", $d);
  $jour = date("d", $d);
  $ancre = 'Calendriers'; // si seulement PHP connaissait les fermetures ....
  $commun = $req . "&jour=$jour&mois=$mois&annee=$annee";
  ereg('^(.*>)[^<>]+(<.*)$',$clic,$m);
  $semaine = $m[1] . "S" . date("W", $d) . $m[2];
  return 
    "<table width='100%'>\n<tr><td align='left'>". 
    http_calendrier_href("$commun&type=jour#$ancre", $clic) .
    "</td><td align='right'>" .
    http_calendrier_href("$commun&type=semaine#$ancre",$semaine) .
    "</td></tr>\n</table>";
}

function http_calendrier_clics($annee, $mois, $jour, $clic)
{
  global $bleu, $jaune, $vert;
  $href = "message_edit.php3?rv=$annee-$mois-$jour&new=oui";

  return "\n" .
    http_calendrier_href("calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee", $clic) .
    "\n" .
    http_calendrier_href("$href&type=pb", 
		 $bleu, 
		 _T("lien_nouvea_pense_bete"),
		 'color: blue; font-family: Arial, Sans, sans-serif; font-size: 10px; ') .
    "\n" .
    http_calendrier_href("$href&type=normal",
		 $vert,
		 _T("lien_nouveau_message"),
		 'color: green; font-family: Arial, Sans, sans-serif; font-size: 10px; ') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
      http_calendrier_href("$href&type=affich",
		   $jaune,
		   _T("lien_nouvelle_annonce"),
		   'color: #ff9900; font-family: Arial, Sans, sans-serif; font-size: 10px; ')));
}

# dispose les evenements d'une semaine

function http_calendrier_suite_heures($jour_today,$mois_today,$annee_today,
	$articles, $breves, $evenements, 
	$script, $nav)
{
  global $couleur_claire, $couleur_foncee, $spip_ecran, $spip_lang_left,$partie_cal;

  $echelle = $GLOBALS['echelle'];
	
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
		http_calendrier_href(($script .
			(strpos($script,'?') ? '&' : '?') .
			"type=jour&jour=" .
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
		$jarticles = $articles[$d];
		$jbreves = $breves[$d];
		$total .= "\n<td style='width: 14%; height: 100px;  vertical-align: top'>
			<div style='background-color: " . 
			(($v['index'] == 0) ? $couleur_claire :
			(($v['jour'] == $jour_t AND 
			$v['mois'] == $mois_t AND
			$v['annee'] == $annee_t) ? "white;" :
			"#eeeeee;")) .
			"'>" .
			"\n<div style='position: relative; color: #999999; width: 100%; " .
			"border-$spip_lang_left: 1px solid $couleur_claire; " .
			"border-bottom: 1px solid $couleur_claire; " .
			"height: ${dimjour}px; " .
			"font-family: Arial, Sans, sans-serif; font-size: ${fontsize}px;'>" .
			http_calendrier_jour_ics($debut,$fin,$largeur, 'calendrier_div_style', $echelle, $evenements[$d], $d) . 
			'</div></div>' .
			(!($jarticles OR $jbreves) ? '' :
				http_calendrier_articles_et_breves($jarticles, $jbreves,'padding: 5px;')) .
			"\n</td>";
	}
	return 
	"<table border='0' cellspacing='0' cellpadding='0' width='100%'>" .
	http_calendrier_navigation_semaine($jour_today,$mois_today,$annee_today,
		$echelle,
		$jour_semaine,
		$script,
		$nav) .
	http_calendrier_les_jours($liens, $couleur_claire, $couleur_foncee) .
	"\n<tr>$total</tr>" .
	"</table>";
}


// Calcule un agenda mensuel et l'affiche

function http_calendrier_agenda ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, $semaine = false) {
  return 
    "<div style='text-align: center; padding: 5px;'>" .
    http_calendrier_href("calendrier.php3?mois=$mois&annee=$annee",
		       "<b class='verdana1'>" .
		       affdate_mois_annee("$annee-$mois-1").
		       "</b>",
		       '',
		       'color: black;') .
    "<table width='100%' cellspacing='0' cellpadding='0'>" .
    http_calendrier_agenda_rv ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, 
				    $semaine, 
				    sql_calendrier_agenda($mois, $annee),
				    'http_calendrier_agenda_href') .
    "</table>" .
    "</div>";
}

function http_calendrier_agenda_href($type, $jour, $mois, $annee, $color)
{
	static $scripts = array('jour' => "calendrier_jour.php3?",
			        'semaine' => "calendrier_semaine.php3?");

	return http_calendrier_href($scripts[$type] . "jour=$jour&mois=$mois&annee=$annee", 
				    "<b>$jour</b>",
				    '',
				    "color: $color");
}

// typographie un mois sous forme d'un tableau de 7 colonnes

function http_calendrier_agenda_rv ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, $semaine, $les_rv, $liens) {
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
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$total = '';
	$ligne = '';
	$jour_semaine = date("w", mktime(1,1,1,$mois,1,$annee));
	if ($jour_semaine==0) $jour_semaine=7;
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n\t<td></td>";
	$style1 = "-moz-border-radius-top$spip_lang_left: 10px; -moz-border-radius-bottom$spip_lang_left: 10px;";
	$style7 = "-moz-border-radius-top$spip_lang_right: 10px; -moz-border-radius-bottom$spip_lang_right: 10px;";
	for ($j=1; $j<32; $j++) {
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		if ($jour_semaine==0) $jour_semaine=7;

		if (checkdate($mois,$j,$annee)){
		  if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
		    $ligne .= "\n\t<td class='arial2' style='margin: 1px; padding: 2px; background-color: white; border: 1px solid $couleur_foncee; text-align: center; -moz-border-radius: 5px;'>" .
		      $liens('jour', $j, $mois, $annee, 'black') .
		      "</td>";
		  } else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		    $ligne .= "\n\t<td class='arial2' style='margin: 0px; padding: 3px; background-color: white; text-align: center; " .
		      (($jour_semaine==1) ? 
		       $style1 :
		       (($jour_semaine==7) ?
			$style7 : '')) .
		      "'>" .
		      $liens(($semaine ? 'semaine' : 'jour'), $j, $mois, $annee, 'black') .
		      "</td>";
		  } else {
		    if ($j == $jour_today AND $mois == $mois_today AND $annee == $annee_today) {
			$couleur_fond = $couleur_foncee;
			$couleur = "white";
		    } else {
			if ($jour_semaine == 7) {
				$couleur_fond = "#aaaaaa";
				$couleur = 'white';
			} else {
				$couleur_fond = "#ffffff";
				$couleur = "#aaaaaa";
			}
			if ($les_rv[$j] > 0) {
				$couleur = "black";
			}
		    }
		    $ligne .= "\n\t<td><div class='arial2' style='margin-left: 1px; margin-top: 1px; padding: 2px; background-color: $couleur_fond; text-align: center; -moz-border-radius: 5px;'>" .
			$liens(($semaine ? 'semaine' : 'jour'), $j, $mois, $annee, $couleur) .
		    "</div></td>";
		  }
		  if ($jour_semaine==7) 
		    {
		      $total .= "\n<tr>$ligne\n</tr>";
		      $ligne = '';
		    }
		}
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne\n</tr>");

}

function http_calendrier_image_et_typo($evenements)
{
  $res = array();
  if ($evenements)
    foreach($evenements as $k => $v)
      {
	if (!(is_int($v['CATEGORIES'])))
	  {
	    if ($v['CATEGORIES'] == 'a')
	      $i = 'puce-verte-breve.gif';
	    else
	      $i = 'puce-blanche-breve.gif';
	    $v['SUMMARY'] = http_img_pack($i, "alt='.' width='8' height='9' border='0'");
	    $v['DESCRIPTION'] = typo($v['DESCRIPTION']);
	  }
	$res[$k] = $v;
      }
  return $res;
}

# liste les articles & les breves

function http_calendrier_articles_et_breves($articles, $breves, $style)
{
  if ($articles)
    {
      $res1 = "<div><b class='verdana1'>"._T('info_articles')."</b></div>" .
	http_calendrier_ics(http_calendrier_image_et_typo($articles));
	}
  if ($breves)
    {
      $res2 = "<div><b class='verdana1'>"._T('info_breves_02')."</b></div>" .
	http_calendrier_ics(http_calendrier_image_et_typo($breves));
    }
  return "<div style='$style'>$res1$res2</div>";
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
			if ($j == 0) $gras = " font-weight: bold;";
			else $gras = "";
			
			$total .= "\n<div style='position: absolute; $spip_lang_left: 0px; top: ".
				http_cal_top ("$i:".sprintf("%02d",floor(($j*60)/$slice)), $debut, $fin, $dimheure, $dimjour, $fontsize) .
				"px; border-top: 1px solid #cccccc;$gras'>
				<div style='margin-$spip_lang_left: 2px'>$i:" . 
				sprintf("%02d",floor(($j*60)/$slice)) . 
				"</div>\n</div>";
		}
	}
	
	$total .= "\n<div style='position: absolute; top: ".
		http_cal_top ("$fin:00", $debut, $fin, $dimheure, $dimjour, $fontsize).
		"px; border-top: 1px solid #cccccc; font-weight: bold;'>
		<div style='margin-$spip_lang_left: 2px'>$fin:00" . 
		"</div>\n</div>";
	
	
	return "\n<div style='position: absolute; $spip_lang_left: 2px; top: 2px;'><b>0:00</b></div>" .
		$total .
		"\n<div style='position: absolute; $spip_lang_left: 2px; top: ".
		($dimjour - $fontsize - 2) .
		"px;'><b>23:59</b></div>";
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

    
// Visualise les $evenements de la journee $date
// commencant a $debut heure et finissant a $fin heure avec
// des couleurs definies par la fonction $detcolor appliquee sur l'evenement
// une $echelle (nombre de secondes representees par 1 pixel)
// une dimension $large

function http_calendrier_jour_ics($debut, $fin, $largeur, $detcolor, $echelle, $evenements, $date) {
	global $spip_lang_left;

	if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;


	list($dimheure, $dimjour, $fontsize, $padding) = calendrier_echelle($debut, $fin, $echelle);		$modif_decalage = round($largeur/8);

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
			
			
			$radius_top = " -moz-border-radius-topleft: 6px; -moz-border-radius-topright: 6px;";
			$radius_bottom = " -moz-border-radius-bottomleft: 6px; -moz-border-radius-bottomright: 6px;";
			
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
			else $decale = (3 * $fontsize);
			if ($bas > $bas_prec) $bas_prec = $bas;
			$url = $evenement['URL']; 
			$desc = propre($evenement['DESCRIPTION']);
			$sum = ereg_replace(' +','&nbsp;', typo($evenement['SUMMARY']));
			if (!$sum) { $sum = $desc; $desc = '';}
			if ($sum)
			  $sum = "<span style='font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px;'><b>$sum</b></span>";
			if ($largeur > 90) {
			  if ($desc)
			    $sum .=  "<br /><span style='color: black'>$desc</span>" . (!$evenement['ATTENDEE'] ? '' : ("<br />" . $evenement['ATTENDEE']));
			}
			else 
			  $desc .= " " . $evenement['ATTENDEE'];
			$colors = $detcolor($evenement);
			if ($colors)
			{
				list($bcolor,$fcolor) = $colors;
			}
			else 
			{ 
				$bcolor = 'white';
				$fcolor = 'black';
			}
			$total .= "\n<div style='cursor: auto; position: absolute; overflow: hidden;$radius_top$radius_bottom$opacity z-index: " .
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
				 http_calendrier_href($url, $sum, $desc,"color: $fcolor")) . 
			  ((!$evenement['LOCATION']) ? '' : 
			   ("<br />" . $evenement['LOCATION'])) .
				"</div>";
			}
		}
    }
	return
		http_calendrier_heures($debut, $fin, $dimheure, $dimjour, $fontsize) .
			$total ;
}


function http_calendrier_journee($jour_today,$mois_today,$annee_today, $date){
	global $largeur_table, $largeur_gauche, $spip_ecran;
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$largeur_gauche = 200;
		$largeur_centre = $largeur_table - 2 * ($largeur_gauche + 20);
	} else {
		$largeur_table = 750;
		$largeur_gauche = 100;
		$largeur_centre = $largeur_table - ($largeur_gauche + 20);
	}
		
	$retour = "<table cellpadding=0 cellspacing=0 border=0 width='$largeur_table'><tr>";
	
	if ($spip_ecran == "large") {
		$retour .= "<td width='$largeur_gauche' class='verdana1' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
			http_calendrier_jour($jour-1,$mois,$annee, "col") .
			"</td>\n<td width='20'>&nbsp;</td>\n";
	}
	$retour .= "\n<td width='$largeur_centre' valign='top'>"  .
		"<div>" .
		http_calendrier_navigation_jour($jour,$mois,$annee, $GLOBALS['echelle'], 'calendrier.php3', '') .
		"</div>".
		http_calendrier_jour($jour,$mois,$annee, "large") .
		'</td>';
		
		# afficher en reduction le tableau du jour suivant
	$retour .= "\n<td width='20'>&nbsp;</td>" .
			"\n<td width='$largeur_gauche' class='verdana1' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
			http_calendrier_jour($jour+1,$mois,$annee, "col") .
			'</td>';
			
	$retour .= '</tr></table>';
		
	return $retour;
}

function http_calendrier_semaine($jour_today,$mois_today,$annee_today)
{
	global $spip_ecran, $spip_lang_left, $couleur_claire;	
	
	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$largeur_gauche = 170;
	} else {
		$largeur_table = 750;
		$largeur_gauche = 100;
	}
//	$largeur_table = $largeur_table - ($largeur_gauche+20);
  
	$nom = mktime(1,1,1,$mois_today,$jour_today,$annee_today);
	$jour_semaine = date("w",$nom);
	$debut = mktime(1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today);
	$debut = date("Y-m-d",$debut);

	$today=getdate(time());
	$jour = $today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
	$now = date("w",mktime(1,1,1,$mois,$jour,$annee));

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_semaine($annee_today,$mois_today,$jour_today);
	return 
		"<div>&nbsp;</div>" .
		"<table cellpadding=0 cellspacing=0 border=0 width='$largeur_table'><tr>" .
		"<td width='$largeur_table' valign='top'>" .
		http_calendrier_suite_heures($jour_today,$mois_today,$annee_today, 			$articles, $breves, $messages,
			'calendrier.php3',
			'') .
		"</td></tr></table>" .
		(!(strlen($breves["0"]) > 0 OR $articles["0"] > 0) ? '' :
			("<table width=400 background=''><tr width=400><td><FONT FACE='arial,helvetica,sans-serif' SIZE=1>" .
			"<b>"._T('info_mois_courant')."</b>" .
			$breves["0"] .
			$articles["0"] .
			"</font></td></tr></table>")) .
			http_calendrier_aide_mess();
}

function http_calendrier_jour($jour,$mois,$annee,$large = "large", $le_message = 0) {
  global $spip_lang_rtl, $spip_lang_right, $spip_lang_left, $bleu, $vert,$jaune;
	global $calendrier_message_fermeture;
	global $partie_cal;
	
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
	$bgcolor = "white";
	
	if ($large == "etroit") {
		$bgcolor = "#eeeeee";
		
		$today=getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		
		if ($jour == $jour_today AND $mois == $mois_today AND $annee == $annee_today) $bgcolor = "white";
	}
	
	$nom = mktime(1,1,1,$mois,$jour,$annee);
	$jour_semaine = date("w",$nom);
	if ($jour_semaine == 0) $bgcolor = "#e0e0e0";

	if ($large == "col" ) {
	  $entete = "<div align='center' style='padding: 5px;'><b class='verdana1'>" .
	    http_calendrier_href("calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee",
				 affdate_jourcourt("$annee-$mois-$jour"),
				 '',
				 'color:black;') .
	    "</b></div>";
	}
	else {
		if ($large == "large") 
			$entete = "<div align='center' style='padding: 5px;'>" .
			http_calendrier_href("message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=pb",
				$bleu ._T("lien_nouvea_pense_bete"),
				'',
				'font-family: Arial, Sans, sans-serif; font-size: 10px; color: blue;') .
			" &nbsp; " .
			http_calendrier_href("message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=normal",
				$vert ._T("lien_nouveau_message"),
				'',
				'font-family: Arial, Sans, sans-serif; font-size: 10px; color: green;') .
			(!($GLOBALS['connect_statut'] == "0minirezo") ? '' :
				(" &nbsp; " .
				http_calendrier_href("message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=affich",
					$jaune ._T("lien_nouvelle_annonce"),
					'',
					'font-family: Arial, Sans, sans-serif; font-size: 10px; color: #ff9900;'))) .
			"</div>\n";
		else
			$entete = '';
	}

	list($articles, $breves, $messages) =
	  sql_calendrier_interval_jour($annee,$mois,$jour);

	$j = sprintf("%04d%02d%02d", $annee,$mois,$jour);
	
	if ($large == "large") {
		$largeur = 300;
	} else if ($large == "col") {
		$largeur = 90;
	} else {
		$largeur = 50;
	}
	$echelle = $GLOBALS['echelle'];
	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut_cal, $fin_cal, $echelle);
	// faute de fermeture en PHP...
	$calendrier_message_fermeture = $le_message;

	return $entete .
		"\n<div style='position: relative; color: #666666; " .
		"height: ${dimjour}px; " .
		"font-family: Arial, Sans, sans-serif; font-size: ${fontsize}px;".
		' border-left: 1px solid #aaaaaa; border-right: 1px solid #aaaaaa; border-bottom: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa;' .
		"'>" .
	  ((!($articles[$j] OR $breves[$j])) ? '' :
	   http_calendrier_articles_et_breves($articles[$j], $breves[$j],
				      "position: absolute; z-index: 2; $spip_lang_left: "
				      . ($largeur - $padding + 35) .
				      "px; top: 0px;")) .
	  http_calendrier_jour_ics($debut_cal,$fin_cal,$largeur, 'http_calendrier_message',
				   $echelle,
				   $messages[$j],
				   $j) .
	   "\n</div>";
}


function http_calendrier_message($evenement)
{
  global $calendrier_message_fermeture;
  if (ereg("=$calendrier_message_fermeture$", $evenement['URL']))
    {return array('white', 'black');}
  else
    {
      return calendrier_div_style($evenement);
    }
}

# Affiche l'encadre "lien iCal"
# avec une simulation de
# icone_horizontale (_T("icone_suivi_activite"), "synchro.php3", "synchro-24.gif")
# qui fait des echo...
function http_calendrier_ical($id) {
  $lien = 'synchro.php3';
  $fond = 'synchro-24.gif';
  $fonction = "rien.gif";
  $texte =_T("icone_suivi_activite") ;
  return 
    debut_cadre_enfonce('',true) .
    "<div class='verdana1'>"._T("calendrier_synchro") .
    "<a href='$lien' class='cellule-h'><table cellpadding='0' valign='middle'><tr>\n" .
    "<td><a href='$lien'><div class='cell-i'>"
    . http_img_pack($fonction, http_style_background($fond, "background-repeat: no-repeat; background-position: center center;' alt=''"))
    . "</div></a></td>\n"
    . "<td class='cellule-h-lien'><a href='$lien' class='cellule-h'>$texte</a></td>\n"
    . "</tr></table></a>\n" ."</div>" .
    fin_cadre_enfonce(true);
}

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
		else if ($row['type']=="annonces") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div class='arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
		}

		$total .= "<tr><td width='24' valign='middle'>" .
		http_calendrier_href($url,
				     ($rv ?
				      http_img_pack("rv.gif",
						    http_style_background($bouton . '.gif', "no-repeat;' border='0' alt=''")) : 
				      http_img_pack("$bouton.gif", "border='0' alt=''")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		((!$rv) ? '' :
		((affdate($date) == affdate($date_fin)) ?
		 ("<div class='arial0'" . 
		  http_style_background('fond-agenda.gif', 
					"$spip_lang_right center no-repeat; float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px;")
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div class='arial0'" . 
		  http_style_background('fond-agenda.gif', 
					"$spip_lang_right center no-repeat; float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px; text-align: center;")
		  . heures($date).":".minutes($date)."<br />...</div>" ))) .
		"<div><b>" .
		http_calendrier_href($url, typo($row['titre']), '', 
'font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px;') .
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

// Fabrique une balise A, avec un href conforme au validateur W3C
// et une classe uniquement destinee a retirer l'URL lors d'une impression
// attention au cas ou la href est du Javascript avec des "'"

function http_calendrier_href($href, $clic, $title='', $style='', $class='') {
	return '<a href="' .
		str_replace('&', '&amp;', $href) .
		'"' . # class="forum-repondre-message"' .
		(!$style ? '' : (" style=\"" . $style . "\"")) .
		(!$title ? '' : (" title=\"" . supprimer_tags($title)."\"")) .
		(!$class ? '' : (" class=\"" . $class . "\"")) .
		'>' .
		$clic .
		'</a>';
}

function http_calendrier_title($echelle, $href, $clic, $title='', $style='', $class='') {
  if ($echelle);
}


function sql_calendrier_interval_jour($annee,$mois,$jour) {
	$avant = "'$annee-$mois-$jour'";
	$apres = "'$annee-$mois-$jour 23:59:59'";

	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

function sql_calendrier_interval_semaine($annee,$mois,$jour) {
	$w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
	if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
	$debut = $jour-$w_day;
	$avant = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut+7,$annee)) .
	" 23:59:59'";

	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

function sql_calendrier_interval_mois($annee,$mois,$jour) {
	$periode = $annee . '-' . sprintf("%02d", $mois) . '-01';
	$avant = "'$periode'";
	// $apres = "DATE_ADD('$periode', INTERVAL 1 MONTH)";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois+1,$debut,$annee)) .
	" 23:59:59'";
	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.

function sql_calendrier_interval_articles($avant, $apres) {
	$evenements= array();
	$result=spip_query("
SELECT	id_article, titre, date
FROM	spip_articles
WHERE	statut='publie'
 AND	date >= $avant
 AND	date < $apres
ORDER BY date
");
	while($row=spip_fetch_array($result)){
		$amj = sql_calendrier_jour_ical($row['date']);
		$evenements[$amj][]=
		array(
			'URL' => "articles.php3?id_article=" . $row['id_article'],
			'CATEGORIES' => 'a',
			'DESCRIPTION' => $row['titre']);
	}
	return $evenements;
}

function sql_calendrier_interval_breves($avant, $apres) {
	$evenements= array();
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
		$evenements[$amj][]=
		array(
			'URL' => "breves_voir.php3?id_breve=" . $row['id_breve'],
			'CATEGORIES' => 'b',
			'DESCRIPTION' => $row['titre']);
	}
	return $evenements;
}

function sql_calendrier_interval_rv($avant, $apres) {
	global $connect_id_auteur;
	$evenements= array();
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


function sql_calendrier_taches_annonces () {
	$r = array();
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

	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.date_heure FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie'");
	$rv = array();
	while($row=spip_fetch_array($result_messages)){
		$rv[journum($row['date_heure'])] = 1;
	}
	return $rv;
}

function sql_calendrier_jour_ical($d)  {
	return  substr($d, 0, 4) . substr($d, 5, 2) .substr($d, 8, 2);
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

function calendrier_div_style($evenement)
{
  global $contrastes;

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


?>
