<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
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

// http://doc.spip.org/@vertebrer_sort
function vertebrer_sort($fields, $direction)
{
	$res = '';
	foreach($fields as $n => $t) {
		$res .= "\n\t\t<th scope='col'>[(#TRI{"."$n,$n,ajax})]</th>";
	}
	return $res;
}

// Autant de formulaire que de champs (pour les criteres conditionnels) 
// http://doc.spip.org/@vertebrer_form
function vertebrer_form($fields)
{
	$res = '';
	$url = join('|', array_keys($fields));
	$url = "#SELF|\n\t\t\tparametre_url{'$url',''}";
	foreach($fields as $n => $t) {
		$s = Sql::test_int($t) ? 11
		  :  (preg_match('/char\s*\((\d)\)/i', $t, $r) ? $r[1] : '');

		if (!in_array($n, array('date', 'date_redac', 'lang'))){
			$res .= "\n\t\t<td>
				[(#ENV{".$n."}|non)
				<a href='#' onclick=\"jQuery(this).toggle('fast').siblings('form').toggle('fast');return false;\">[(#CHEMIN_IMAGE{rechercher-20.png}|balise_img)]</a>
				]
				<form class='[(#ENV{".$n."}|non)none-js]' action='./' method='get'>"
			 . "<div>"
			 . "\n\t\t\t<input name='$n'"
			 . ($s ? " size='$s'" : '')
			 . "value=\"[(#ENV{".$n."}|entites_html)]\""
			 . " />\n\t\t\t[($url|\n\t\t\tform_hidden)]"
			 . "\n\t\t</div></form></td>";
		}
		else
			$res .= "<td></td>";
	}
	return $res;
}

// Autant de criteres conditionnels que de champs

// http://doc.spip.org/@vertebrer_crit
function vertebrer_crit($v)
{
	$res = "";
	foreach($v as $n => $t) {
		if (!in_array($n, array('date', 'date_redac', 'lang', 'recherche','logo')))
			$res .= "\n\t\t{" . $n .  " ?}";
	}
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
		$texte = "#CHAMP_SQL{".$n."}";
		if (preg_match('/\s+references\s+([\w_]+)/' , $t, $r)) {
			$url = "[(#SELF|parametre_url{page,'" . $r[1] . "'})]";
			$texte = "<a href='$url'>" . $texte . "</a>";
		}
		if (Sql::test_int($t))
			$s = " style='text-align: right;'";
		else {
			$s = '';
			if (Sql::test_date($t))
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

	$defaut_tri = array_keys($field);
	$defaut_tri = reset($defaut_tri);

	//ksort($field);

	$form = vertebrer_form($field);
	$crit = vertebrer_crit($field);
	$cell = vertebrer_cell($field);
	$sort = vertebrer_sort($field,'');
	$distant = !$connexion ? '' : "&amp;connect=$connexion";

	return
"#CACHE{0}
<B1>
<h2>[(#GRAND_TOTAL|singulier_ou_pluriel{vertebres:1_donnee,vertebres:nb_donnees})]</h2>
[<p class='pagination'>(#PAGINATION)</p>]
<div style='overflow: scroll;overflow-y: auto'>
<table class='spip'>
	<thead>
	<tr class='row_first'>
		<th>
			<p class='tri'>#TRI{'>',#CHEMIN_IMAGE{tri-asc-16.png}|balise_img{up},ajax}  #TRI{'<',#CHEMIN_IMAGE{tri-desc-16.png}|balise_img{desc},ajax}</p>
		</th>
		$sort
	</tr>
	<tr>
		<td></td>$form
	</tr>
	</thead>
	<tbody>
<BOUCLE1($surnom){pagination}
		{tri $defaut_tri, direct}$crit>
	<tr class='[row_(#COMPTEUR_BOUCLE|alterner{'odd','even'})]'>
		<td style='text-align: right;'>#COMPTEUR_BOUCLE</td>$cell
	</tr>
</BOUCLE1>
	</tbody>
	<tfoot>
	<tr>
		<th>
			<p class='tri'>#TRI{'>',#CHEMIN_IMAGE{tri-asc-16.png}|balise_img{up},ajax}  #TRI{'<',#CHEMIN_IMAGE{tri-desc-16.png}|balise_img{desc},ajax}</p>
		</th>
		$sort
	</tr>
	</tfoot>
</table>
</B1>
<div style='overflow: scroll;overflow-y: auto'>
<h2><:texte_vide:></h2>
<table class='spip'>
	<thead>
	<tr class='row_first'>
		<th>
			<p class='tri'>#TRI{'>',#CHEMIN_IMAGE{tri-asc-16.png}|balise_img{up},ajax}  #TRI{'<',#CHEMIN_IMAGE{tri-desc-16.png}|balise_img{desc},ajax}</p>
		</th>
		$sort
	</tr>
	<tr>
		<td></td>$form
	</tr>
	</thead>
</table>
<//B1>
</div>
";
}
?>
