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

if (!defined('_ECRIRE_INC_VERSION')) return;

// deduction automatique d'une chaine de jointures 

/**
 * decomposer un champ id_article en (id_objet,objet,'article') si necessaire
 *
 * @param string $champ
 * @return array ou string
 */

// http://doc.spip.org/@decompose_champ_id_objet
function decompose_champ_id_objet($champ){
	if (($champ!=='id_objet') AND preg_match(',^id_([a-z_]+)$,',$champ,$regs)){
		return array('id_objet','objet',$regs[1]);
	}
	return $champ;
}

/**
 * mapping d'un champ d'une jointure en deux champs id_objet,objet si necessaire
 *
 * @param string $champ
 * @param array $desc
 * @return array
 */
// http://doc.spip.org/@trouver_champs_decomposes
function trouver_champs_decomposes($champ,$desc){
	if (!is_array($desc) // on ne se risque pas en conjectures si on ne connait pas la table
	  OR array_key_exists($champ,$desc['field']))
		return array($champ);
	if (is_array($decompose=decompose_champ_id_objet($champ))){
		array_pop($decompose);
		if (count(array_intersect($decompose,array_keys($desc['field'])))==count($decompose))
			return $decompose;
	}
	return array($champ);
}


// http://doc.spip.org/@calculer_jointure
function calculer_jointure(&$boucle, $depart, $arrivee, $col='', $cond=false)
{

  $res = calculer_chaine_jointures($boucle, $depart, $arrivee);
  if (!$res) return "";

  list($nom,$desc) = $depart;
  return fabrique_jointures($boucle, $res, $cond, $desc, $nom, $col);
}

// http://doc.spip.org/@fabrique_jointures
function fabrique_jointures(&$boucle, $res, $cond=false, $desc=array(), $nom='', $col='')
{
	static $num=array();
	$id_table = "";
	$cpt = &$num[$boucle->descr['nom']][$boucle->descr['gram']][$boucle->id_boucle];
	foreach($res as $cle=>$r) {
		list($d, $a, $j) = $r;
		if (!$id_table) $id_table = $d;
		$n = ++$cpt;
		if (is_array($j)){ // c'est un lien sur un champ du type id_objet,objet,'article'
			list($j1,$j2,$obj,$type) = $j;
			// trouver de quel cote est (id_objet,objet)
			if ($j1=="id_$obj")
				$obj = "$id_table.$obj";
			else
				$obj = "L$n.$obj";
			// le where complementaire est envoye dans la jointure pour pouvoir etre elimine avec la jointure
			// en cas d'optimisation
			//$boucle->where[] = array("'='","'$obj'","sql_quote('$type')");
			$boucle->join["L$n"]= array("'$id_table'","'$j2'","'$j1'","'$obj='.sql_quote('$type')");
		}
		else
			$boucle->join["L$n"]= array("'$id_table'","'$j'");
		$boucle->from[$id_table = "L$n"] = $a[0];    
	}


  // pas besoin de group by 
  // (cf http://article.gmane.org/gmane.comp.web.spip.devel/30555)
  // si une seule jointure et sur une table avec primary key formee
  // de l'index principal et de l'index de jointure (non conditionnel! [6031])
  // et operateur d'egalite (http://trac.rezo.net/trac/spip/ticket/477)

	if ($pk = (isset($a[1]) && (count($boucle->from) == 2) && !$cond)) {
		$pk = nogroupby_if($desc, $a[1], $col);
	}
	
	// pas de group by 
	// si une seule jointure
	// et si l'index de jointure est une primary key a l'arrivee !
	if (!$pk
	  AND (count($boucle->from) == 2)
	  AND isset($a[1]['key']['PRIMARY KEY'])
	  AND ($j == $a[1]['key']['PRIMARY KEY'])
	  )
	  $pk = true;

  // la clause Group by est en conflit avec ORDER BY, a completer
	$groups = liste_champs_jointures($nom,$desc,true);
	if (!$pk) foreach($groups as $id_prim){
		$id_field = $nom . '.' . $id_prim;
		if (!in_array($id_field, $boucle->group)) {
			$boucle->group[] = $id_field;
		}
	}

	$boucle->modificateur['lien'] = true;
	return "L$n";
  }

// condition suffisante pour qu'un Group-By ne soit pas necessaire
// A ameliorer, notamment voir si calculer_select ne pourrait pas la reutiliser
// lorsqu'on sait si le critere conditionnel est finalement present
// http://doc.spip.org/@nogroupby_if
function nogroupby_if($depart, $arrivee, $col)
{
	$pk = $arrivee['key']['PRIMARY KEY'];
	if (!$pk) return false;
	$id_primary = $depart['key']['PRIMARY KEY'];
	if (is_array($col)) $col = implode(', *',$col); // cas id_objet, objet
	return (preg_match("/^$id_primary, *$col$/", $pk) OR
		preg_match("/^$col, *$id_primary$/", $pk));
}

// http://doc.spip.org/@liste_champs_jointures
function liste_champs_jointures($nom,$desc,$primary=false){

	static $nojoin = array('idx','maj','date','statut');
	
	// si cle primaire demandee, la privilegier
	if ($primary && isset($desc['key']['PRIMARY KEY']))
		return split_key($desc['key']['PRIMARY KEY']);

	// les champs declares explicitement pour les jointures
	if (isset($desc['join'])) return $desc['join'];
	/*elseif (isset($GLOBALS['tables_principales'][$nom]['join'])) return $GLOBALS['tables_principales'][$nom]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$nom]['join'])) return $GLOBALS['tables_auxiliaires'][$nom]['join'];*/
	
	// si pas de cle, c'est fichu
	if (!isset($desc['key'])) return array();

	// si cle primaire
	if (isset($desc['key']['PRIMARY KEY']))
		return split_key($desc['key']['PRIMARY KEY']);
	
	// ici on se rabat sur les cles secondaires, 
	// en eliminant celles qui sont pas pertinentes (idx, maj)
	// si jamais le resultat n'est pas pertinent pour une table donnee,
	// il faut declarer explicitement le champ 'join' de sa description

	$join = array();
	foreach($desc['key'] as $v) $join = split_key($v, $join);
	foreach($join as $k) if (in_array($k, $nojoin)) unset($join[$k]);
	return $join;
}

// http://doc.spip.org/@split_key
function split_key($v, $join = array())
{
	foreach (preg_split('/,\s*/', $v) as $k) $join[$k] = $k;
	return $join;
}

// http://doc.spip.org/@calculer_chaine_jointures
function calculer_chaine_jointures(&$boucle, $depart, $arrivee, $vu=array(), $milieu_exclus = array(), $max_liens = 5)
{
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');
		
	if (is_string($milieu_exclus))
		$milieu_exclus = array($milieu_exclus);

	list($dnom,$ddesc) = $depart;
	list($anom,$adesc) = $arrivee;
	if (!count($vu))
		$vu[] = $dnom; // ne pas oublier la table de depart

	$akeys = $adesc['key'];
	if ($v = $akeys['PRIMARY KEY']) {
		unset($akeys['PRIMARY KEY']);
		$akeys = array_merge(preg_split('/,\s*/', $v), $akeys);
	}
	// enlever les cles d'arrivee exclues par l'appel
	$akeys = array_diff($akeys,$milieu_exclus);

	// cles candidates au depart
	$keys = liste_champs_jointures($dnom,$ddesc);
	// enlever les cles dde depart exclues par l'appel
	$keys = array_diff($keys,$milieu_exclus);
	
	$v = !$keys ? false : array_intersect(array_values($keys), $akeys);

	if ($v)
		return array(array($dnom, array($adesc['table'],$adesc), array_shift($v)));

	// regarder si l'on a (id_objet,objet) au depart et si on peut le mapper sur un id_xx
	if (count(array_intersect(array('id_objet','objet'),$keys))==2){
		// regarder si l'une des cles d'arrivee peut se decomposer en 
		// id_objet,objet
		// si oui on la prend
		foreach($akeys as $key){
			$v = decompose_champ_id_objet($key);
			if (is_array($v)){
				$objet = array_shift($v);// objet,'article'
				array_unshift($v,$key); // id_article,objet,'article'
				array_unshift($v,$objet); // id_objet,id_article,objet,'article'
				return array(array($dnom, array($adesc['table'],$adesc), $v));
			}
		}
	}
	else {
		// regarder si l'une des cles de depart peut se decomposer en 
		// id_objet,objet a l'arrivee
		// si oui on la prend
		foreach($keys as $key){
			if (count($v = trouver_champs_decomposes($key,$adesc))>1){
				if (count($v)==count(array_intersect($v, $akeys)))
					$v = decompose_champ_id_objet($key); // id_objet,objet,'article'
					array_unshift($v,$key); // id_article,id_objet,objet,'article'
					return array(array($dnom, array($adesc['table'],$adesc), $v));
			}
		}
	}
	// si l'on voulait une jointure direct, c'est rate !
	if ($max_liens<=1) return array();
	
	// sinon essayer de passer par une autre table
	$new = $vu;
	foreach($boucle->jointures as $v) {
		if ($v && (!in_array($v,$vu)) && 
		    ($def = $trouver_table($v, $boucle->sql_serveur))) {
			// ne pas tester les cles qui sont exclues a l'appel
			// ie la cle de la jointure precedente
			$test_cles = $milieu_exclus;
			$new[] = $v;
			$max_iter = 50; // securite
			while (count($jointure_directe_possible = calculer_chaine_jointures($boucle,$depart,array($v, $def),$vu,$test_cles,1))
			  AND $max_iter--) {
				$jointure_directe_possible = reset($jointure_directe_possible);
				$milieu = end($jointure_directe_possible);
				if (is_string($milieu))
					$test_cles[] = $milieu;
				else
					$test_cles = array_merge($test_cles,$milieu);
				// essayer de rejoindre l'arrivee a partir de cette etape intermediaire
				// sans repasser par la meme cle milieu
			  $r = calculer_chaine_jointures($boucle, array($v, $def), $arrivee, $new, $milieu,$max_liens-1);
				if ($r)	{
				  array_unshift($r, $jointure_directe_possible);
					return $r;
				}
			}
		}
	}
	return array();
}

// applatit les cles multiples

// http://doc.spip.org/@trouver_cles_table
function trouver_cles_table($keys)
{
  $res =array();
  foreach ($keys as $v) {
    if (!strpos($v,","))
      $res[$v]=1; 
    else {
      foreach (preg_split("/\s*,\s*/", $v) as $k) {
	$res[$k]=1;
      }
    }
  }
  return array_keys($res);
}

// http://doc.spip.org/@trouver_champ_exterieur
function trouver_champ_exterieur($cle, $joints, &$boucle, $checkarrivee = false)
{
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	// support de la recherche multi champ
	if (!is_array($cle))
		$cle = array($cle);
	foreach($joints as $k => $join) {
	  if ($join && $table = $trouver_table($join, $boucle->sql_serveur)) {
	    if (isset($table['field']) 
	    	// verifier que toutes les cles cherchees sont la
			  AND (count(array_intersect($cle, array_keys($table['field'])))==count($cle))
			  // si on sait ou on veut arriver, il faut que ca colle
			  AND ($checkarrivee==false || $checkarrivee==$table['table']))
	      return  array($table['table'], $table);
	  }
	}
	// une cle id_xx peut etre implementee par un couple (id_objet,objet)
	foreach($cle as $k=>$c) {
		if (is_array($decompose = decompose_champ_id_objet($c))){
			unset($cle[$k]);
			$cle[] = array_shift($decompose); // id_objet
			$cle[] = array_shift($decompose); // objet
			return trouver_champ_exterieur($cle,$joints,$boucle,$checkarrivee);
		}
	}
	return "";
}

// http://doc.spip.org/@trouver_jointure_champ
function trouver_jointure_champ($champ, &$boucle)
{
	$cle = trouver_champ_exterieur($champ, $boucle->jointures, $boucle);
	if ($cle) {
		$desc = $boucle->show;
		$cle = calculer_jointure($boucle, array($desc['id_table'], $desc), $cle, false);
	}
	if ($cle) return $cle;
	spip_log("trouver_jointure_champ: $champ inconnu");
	return '';
}
?>
