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
// Production dynamique d'un squelette lorsqu'il ne figure pas 
// dans les dossiers de squelettes mais que son nom est celui d'une table SQL:
// on produit une table HTML montrant le contenu de la table SQL
// Le squelette produit illustre quelques possibilites de SPIP:
// - pagination automatique
// - tri ascendant et descendant sur chacune des colonnes
// - critere conditionnel donnant l'extrait correspondant a la colonne en URL
// 

if (!defined("_ECRIRE_INC_VERSION")) return;

// nomme chaque colonne par le nom du champ, 
// qui sert de lien vers la meme page, avec la table triee selon ce champ
// distingue champ numerique et non numerique

function vertebrer_sort($fields, $direction)
{
	$res = '';
	foreach($fields as $n => $t) {
		$tri = $direction
		. ((test_sql_int($t) OR test_sql_date($r)) ? 'tri_n' : 'tri');
		$args ="";
		foreach (array('tri', 'tri_n', '_tri', '_tri_n') as $c) {
		  if ($tri != $c) $args .= '|parametre_url{' . $c .',""}';
		}
      // #SELF contient tous les parametes *tri*. A ameliorer
		$url = "[(#SELF$args|parametre_url{" . $tri . ",'" . $n . "'})]";
		$res .= "\n\t\t<th><a href='$url'>$n</a></th>";
	}
	return $res;
}

// Autant de criteres conditionnels que de champs

function vertebrer_crit($v)
{
	 $res = "{pagination}" 
	  . "\n\t{par #ENV{tri}}{!par #ENV{_tri}}{par num #ENV{tri_n}}{!par num #ENV{_tri_n}}";

	 foreach($v as $n => $t) {  $res .= "\n\t{" . $n .  " ?}"; }
	 return $res;
}

// Class CSS en fonction de la parite du numero de ligne.
// Si une colonne reference une table, ajoute un href sur sa page dynamique.
// Ce serait encore mieux d'aller chercher sa cle primaire.

function vertebrer_cell($fields)
{
  $res = "\n\t<tr class='[row_(#COMPTEUR_BOUCLE|alterner{'odd','even'})]'>\n\t\t<td>#COMPTEUR_BOUCLE</td>";
  foreach($fields as $n => $t) {
 {
      $texte = "#" . strtoupper($n);
      if (preg_match('/\s+references\s+([\w_]+)/' , $t, $r)) {
	$url = "[(#SELF|parametre_url{page,'" . $r[1] . "'})]";
	$texte = "<a href='$url'>" . $texte . "</a>";
      }
      $res .= "\n\t\t<td>$texte</td>";
    }
  }
  return $res;
}

function public_vertebrer_dist($desc)
{
	$nom = $desc['table'];
	$surnom = $desc['id_table'];
	$field = $desc['field'];
	$key = $desc['key'];
	ksort($field);
	return
"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='#LANG' lang='#LANG' dir='#LANG_DIR'>
<head>
<title>SPIPAdmin $surnom [(#NOM_SITE_SPIP|textebrut)]</title>
<INCLURE{fond=inc-head}>
</head>
<body class=page_rubrique><div id='page'>
<h1 style='text-align:center'>SPIPAdmin $surnom</h1><br />\n" .
	  // au minimum: "<BOUCLE1($fond)></BOUCLE1>#TOTAL_BOUCLE<//B1>")
	  // au maximum:
	"<B1>#ANCRE_PAGINATION[<p class='pagination'>(#PAGINATION)</p>]" .
	"<table class='spip' border='1' width='90%'>" .
	"<tr>\n\t<th>Nb</th>" .
	vertebrer_sort($field,'') .
	"\n</tr>\n<BOUCLE1($nom)" .
	vertebrer_crit($field) .
	'>' .
	vertebrer_cell($field) .
	"\n\t</tr>\n</BOUCLE1>" .
	"\n\t<tr>\n\t<th>Nb</th>" .
	vertebrer_sort($field,'_') .
	"\n</tr></table>" .
'</B1></div></body></html>';
}
?>
