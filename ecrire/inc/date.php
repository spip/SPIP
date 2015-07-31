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

//
if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@my_sel
function my_sel($num, $tex, $comp) {
  return "<option value='$num'" . (($num != $comp) ? '' : " selected='selected'") .
    ">$tex</option>\n";
}

// http://doc.spip.org/@format_mysql_date
function format_mysql_date($annee=0, $mois=0, $jour=0, $h=0, $m=0, $s=0) {
	$annee = sprintf("%04s",$annee);
	$mois = sprintf("%02s",$mois);

	if ($annee == "0000") $mois = 0;
	if ($mois == "00") $jour = 0;

	return sprintf("%04u",$annee) . '-' . sprintf("%02u",$mois) . '-'
		. sprintf("%02u",$jour) . ' ' . sprintf("%02u",$h) . ':'
		. sprintf("%02u",$m) . ':' . sprintf("%02u",$s);
}


// http://doc.spip.org/@afficher_mois
function afficher_mois($mois, $attributs, $autre=false){
  return
	"<select $attributs>\n" .
	(!$autre ? '' : my_sel("00",_T('mois_non_connu'),$mois)) .
	my_sel("01", _T('date_mois_1'), $mois) .
	my_sel("02", _T('date_mois_2'), $mois) .
	my_sel("03", _T('date_mois_3'), $mois) .
	my_sel("04", _T('date_mois_4'), $mois) .
	my_sel("05", _T('date_mois_5'), $mois) .
	my_sel("06", _T('date_mois_6'), $mois) .
	my_sel("07", _T('date_mois_7'), $mois) .
	my_sel("08", _T('date_mois_8'), $mois) .
	my_sel("09", _T('date_mois_9'), $mois) .
	my_sel("10", _T('date_mois_10'), $mois) .
	my_sel("11", _T('date_mois_11'), $mois) .
	my_sel("12", _T('date_mois_12'), $mois) .
	"</select>\n";
}

// http://doc.spip.org/@afficher_annee
function afficher_annee($annee, $attributs, $debut=null) {

	if (!isset($debut)) $debut = $annee - 8;
	$fin = max($annee, date('Y')) + 3;

	if ($fin - $debut > 15)
		return "<input type='text' value='$annee' size='4' $attributs />";

	$res = ($annee > $debut) ? '' : my_sel($annee,$annee,$annee);
	for ($i=$debut; $i < $fin; $i++) {
		$res .= my_sel($i,$i,$annee);
	}
	// plus de choix... on met une vieille date arbitraire, et au tour
	// suivant on aura un champ input a la place du select (pas genial...)
	$res .= my_sel(date('Y')-10,'&nbsp; ...',$annee);
	return "<select $attributs>\n$res</select>\n";
}

// http://doc.spip.org/@afficher_jour
function afficher_jour($jour, $attributs, $autre=false){

	$res = (!$autre ? "" : my_sel("00",_T('jour_non_connu_nc'),$jour));
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		$res .= my_sel($i,$aff,$jour);
	}
	return "<select $attributs>\n$res</select>\n";
}

// http://doc.spip.org/@afficher_heure
function afficher_heure($heure, $attributs, $autre=false){
	$res = '';
	for($i=0;$i<=23;$i++){
		$aff = sprintf("%02s", $i);
		$res .= my_sel($i,$aff,$heure);
	}
	return "<select $attributs>\n$res</select>\n";
}

// http://doc.spip.org/@afficher_minute
function afficher_minute($minute, $attributs, $autre=false){
	$res = '';
	for($i=0;$i<=59;$i+=5){
		$aff = sprintf("%02s", $i);
		$res .= my_sel($i,$aff,$minute);

		if ($minute>$i AND $minute<$i+5)
			$res .= my_sel($minute,sprintf("%02s", $minute),$minute);
	}
	return "<select $attributs>\n$res</select>\n";
}


// http://doc.spip.org/@afficher_jour_mois_annee_h_m
function afficher_jour_mois_annee_h_m($date, $heures, $minutes, $suffixe='')
{
  return 
    afficher_jour(jour($date), "name='jour$suffixe' id='jour$suffixe' size='1' class='fondl verdana1'") .
    afficher_mois(mois($date), "name='mois$suffixe' id='mois$suffixe' size='1' class='fondl verdana1'") .
    afficher_annee(annee($date), "name='annee$suffixe' id='annee$suffixe' class='fondl verdana1'", date('Y')-1) .
    "&nbsp;  <input type='text' class='fondl verdana1' name='heures$suffixe' id='heures$suffixe' value=\"".$heures."\" size='3'/>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;" .
    "<input type='text' class='fondl verdana1' name='minutes$suffixe' id='minutes$suffixe' value=\"$minutes\" size='3'/>";
}

?>
