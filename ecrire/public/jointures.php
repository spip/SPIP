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

// deduction automatique d'une chaine de jointures 

// http://doc.spip.org/@calculer_jointure
function calculer_jointure(&$boucle, $depart, $arrivee, $col='', $cond=false)
{

  $res = calculer_chaine_jointures($boucle, $depart, $arrivee);
  if (!$res) return "";

  list($nom,$desc) = $depart;
  return fabrique_jointures($boucle, $res, $cond, $desc, $nom, $col);
}

function fabrique_jointures(&$boucle, $res, $cond=false, $desc=array(), $nom='', $col='')
{
	static $num=array();
	$id_table = "";
	$cpt = &$num[$boucle->descr['nom']][$boucle->id_boucle];
	foreach($res as $r) {
		list($d, $a, $j) = $r;
		if (!$id_table) $id_table = $d;
		$n = ++$cpt;
		$boucle->join[$n]= array("'$id_table'","'$j'");
		$boucle->from[$id_table = "L$n"] = $a[0];    
	}


  // pas besoin de group by 
  // (cf http://article.gmane.org/gmane.comp.web.spip.devel/30555)
  // si une seule jointure et sur une table avec primary key formee
  // de l'index principal et de l'index de jointure (non conditionnel! [6031])
  // et operateur d'egalite (http://trac.rezo.net/trac/spip/ticket/477)

	if ($pk = ((count($boucle->from) == 2) && !$cond)) {
		if ($pk = $a[1]['key']['PRIMARY KEY']) {
			$id_primary = $desc['key']['PRIMARY KEY'];
			$pk = (preg_match("/^$id_primary, *$col$/", $pk) OR
			       preg_match("/^$col, *$id_primary$/", $pk));
		}
	}

  // la clause Group by est en conflit avec ORDER BY, a completer
	if (!$pk) foreach(liste_champs_jointures($nom,$desc) as $id_prim){
		$id_field = $nom . '.' . $id_prim;
		if (!in_array($id_field, $boucle->group)) {
			$boucle->group[] = $id_field;
			// postgres exige que le champ pour GROUP soit dans le SELECT
			if (!in_array($id_field, $boucle->select)) {
			  		    spip_log("ref $id_field");
			  $boucle->select[] = $id_field;
			}
		}
	}

	$boucle->modificateur['lien'] = true;
	return $n;
  }


// http://doc.spip.org/@liste_champs_jointures
function liste_champs_jointures($nom,$desc){

	static $nojoin = array('idx','maj','date','statut');

	// les champs declares explicitement pour les jointures
	if (isset($desc['join'])) return $desc['join'];
	/*elseif (isset($GLOBALS['tables_principales'][$nom]['join'])) return $GLOBALS['tables_principales'][$nom]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$nom]['join'])) return $GLOBALS['tables_auxiliaires'][$nom]['join'];*/
	
	// si pas de cle, c'est fichu
	if (!isset($desc['key'])) return array();

	// si cle primaire, la privilegier
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

function split_key($v, $join = array())
{
	foreach (preg_split('/,\s*/', $v) as $k) $join[$k] = $k;
	return $join;
}

// http://doc.spip.org/@calculer_chaine_jointures
function calculer_chaine_jointures(&$boucle, $depart, $arrivee, $vu=array(), $milieu_prec = false)
{
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	list($dnom,$ddesc) = $depart;
	list($anom,$adesc) = $arrivee;
	if (!count($vu))
		$vu[] = $dnom; // ne pas oublier la table de depart

	$akeys = $adesc['key'];
	if ($v = $akeys['PRIMARY KEY']) {
		unset($akeys['PRIMARY KEY']);
		$akeys = array_merge(preg_split('/,\s*/', $v), $akeys);
	}

	if ($keys = liste_champs_jointures($dnom,$ddesc)){
		$v = array_intersect(array_values($keys), $akeys);
	}
	if ($v)
		return array(array($dnom, $arrivee, array_shift($v)));
	else    {
		$new = $vu;
		foreach($boucle->jointures as $v) {
			if ($v && (!in_array($v,$vu)) && 
			    ($def = $trouver_table($v, $boucle->sql_serveur))) {
				$milieu = array_intersect($ddesc['key'], trouver_cles_table($def['key']));
				$new[] = $v;
				foreach ($milieu as $k)
					if ($k!=$milieu_prec) // ne pas repasser par la meme cle car c'est un chemin inutilement long
					{
					  $r = calculer_chaine_jointures($boucle, array($v, $def), $arrivee, $new, $k);
						if ($r)	{
						  array_unshift($r, array($dnom, array($def['table'], $def), $k));
							return $r;
						}
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
      foreach (split(" *, *", $v) as $k) {
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

	foreach($joints as $k => $join) {
	  if ($join && $table = $trouver_table($join, $boucle->sql_serveur)) {
	    if (isset($table['field']) && array_key_exists($cle, $table['field'])
		&& ($checkarrivee==false || $checkarrivee==$table['table'])) // si on sait ou on veut arriver, il faut que ca colle
	      return  array($table['table'], $table);
	  }
	}
	return "";
}

function trouver_jointure_champ($champ, $boucle)
{
	$cle = trouver_champ_exterieur($champ, $boucle->jointures, $boucle);
	if ($cle) {
		$desc = $boucle->show;
		$cle = calculer_jointure($boucle, array($desc['id_table'], $desc), $cle, false);
	}
	if ($cle) return "L$cle";
	spip_log("trouver_jointure_champ: $champ inconnu");
	return '';
}
?>
