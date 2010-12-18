<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

//
// Production dynamique d'un squelette lorsqu'il ne figure pas 
// dans les dossiers de squelettes mais que son nom est celui d'une table SQL:
// on produit une table HTML montrant le contenu de la table SQL
// Le squelette produit illustre quelques possibilites de SPIP:
// - pagination automatique
// - tri ascendant et descendant sur chacune des colonnes
// - critere conditionnel donnant l'extrait correspondant a la colonne en URL
// 

if (!defined('_ECRIRE_INC_VERSION')) return;

// nomme chaque colonne par le nom du champ, 
// qui sert de lien vers la meme page, avec la table triee selon ce champ
// distingue champ numerique et non numerique

// http://doc.spip.org/@vertebrer_sort
function vertebrer_sort($fields, $direction)
{
	$res = '';
	foreach($fields as $n => $t) {
		$tri = $direction
		. ((sql_test_int($t) OR sql_test_date($r)) ? 'tri_n' : 'tri');

		$url = vertebrer_sanstri($tri)
		.  "|parametre_url{" . $tri . ",'" . $n . "'}";

		$res .= "\n\t\t<th style='text-align: center'>"
		. "\n\t\t\t<a href='[(#SELF$url)]'>$n</a>"
		. "\n\t\t</th>";
	}
	return $res;
}

// http://doc.spip.org/@vertebrer_sanstri
function vertebrer_sanstri($sauf='')
{
	$url ="";
	foreach (array('tri', 'tri_n', '_tri', '_tri_n') as $c) {
		if ($sauf != $c) $url .= "|$c";
	}
	return '|parametre_url{"' . substr($url,1) .'",""}';
}

// Autant de formulaire que de champs (pour les criteres conditionnels) 

// http://doc.spip.org/@vertebrer_form
function vertebrer_form($fields)
{
	$res = '';
	$url = join('|', array_keys($fields));
	$url = "#SELF|\n\t\t\tparametre_url{'$url',''}";
	foreach($fields as $n => $t) {
		$s = sql_test_int($t) ? 11
		  :  (preg_match('/char\s*\((\d)\)/i', $t, $r) ? $r[1] : '');

		$res .= "\n\t\t<td><form action='./' method='get'>"
		 . "<div style='text-align: center;' >"
		 . "\n\t\t\t<input name='$n'"
		 . ($s ? " size='$s'" : '')
		 . " />\n\t\t\t[($url|\n\t\t\tform_hidden)]"
		 . "\n\t\t</div></form></td>";
	}
	return $res;
}

// Autant de criteres conditionnels que de champs

// http://doc.spip.org/@vertebrer_crit
function vertebrer_crit($v)
{
	 $res = "";
	 foreach($v as $n => $t) {  $res .= "\n\t\t{" . $n .  " ?}"; }
	 return $res;
}


// Class CSS en fonction de la parite du numero de ligne.
// Style text-align en fonction du type SQL (numerique ou non).
// Filtre de belle date sur type SQL signalant une date ou une estampille.
// Si une colonne reference une table, ajoute un href sur sa page dynamique
// (il faudrait aller chercher sa def pour ilustrer les jointures en SPIP)

// http://doc.spip.org/@vertebrer_cell
function vertebrer_cell($fields)
{
	$res = "";
	foreach($fields as $n => $t) {
		$texte = "#" . strtoupper($n);
		if (preg_match('/\s+references\s+([\w_]+)/' , $t, $r)) {
			$url = "[(#SELF|parametre_url{page,'" . $r[1] . "'})]";
			$texte = "<a href='$url'>" . $texte . "</a>";
		}
		if (sql_test_int($t))
			$s = " style='text-align: right;'";
		else {
			$s = '';
			if (sql_test_date($t))
				$texte = "[($texte|affdate_heure)]";
		}
		$res .= "\n\t\t<td$s>$texte</td>";
	}
	return $res;
}

// http://doc.spip.org/@public_vertebrer_dist
function public_vertebrer_dist($desc)
{
	$nom = $desc['table'];
	$surnom = $desc['id_table'];
	$connexion = $desc['connexion'];
	$field = $desc['field'];
	$key = $desc['key'];

	ksort($field);

	$form = vertebrer_form($field);
	$crit = vertebrer_crit($field);
	$cell = vertebrer_cell($field);
	$sort = vertebrer_sort($field,'');
	$tros = vertebrer_sort($field,'_');
	$titre =  "[(#ENV{page}|image_typo{police=dustismo_bold.ttf,taille=36,couleur=4433bb})]";
	$distant = !$connexion ? '' : "&amp;connect=$connexion";
	$skel = "./?"._SPIP_PAGE."=table:$surnom$distant&amp;var_mode=debug&amp;var_mode_affiche=squelette#debug_boucle";
	  
	return
"#CACHE{0}
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='#LANG' lang='#LANG' dir='#LANG_DIR'>
<head>
<title>[(#NOM_SITE_SPIP|textebrut)] - #ENV{page}</title>
<INCLURE{fond=inc-head}>
</head>
<body class='page_rubrique'><div id='page'>
<INCLURE{fond=inc-entete}>
<div id='contenu'>
<h1 style='text-align:center'>$titre</h1><br />
<B1>
[<p class='pagination'>(#PAGINATION)</p>]
<table class='spip' border='1' width='90%'>
	<tr>
		<th><:info_numero_abbreviation:></th>$sort
	</tr>
	<tr>
		<td></td>$form
	</tr>
<BOUCLE1($surnom){pagination} 
		{par #ENV{tri}}{!par #ENV{_tri}}{par num #ENV{tri_n}}{!par num #ENV{_tri_n}}$crit>
	<tr class='[row_(#COMPTEUR_BOUCLE|alterner{'odd','even'})]'>
		<td style='text-align: right;'>#COMPTEUR_BOUCLE</td>$cell
	</tr>
</BOUCLE1>
	<tr>
		<th><:info_numero_abbreviation:></th>$tros
	</tr>
</table>
</B1>\n<h2 style='text-align:center'><:texte_vide:></h2>
<//B1></div>
<INCLURE{fond=inc-pied}{skel='$skel'}>
</div>
</body>
</html>";
}
?>
