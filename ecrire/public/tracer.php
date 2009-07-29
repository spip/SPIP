<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@trace_query_start
function trace_query_start()
{
	static $trace = '?';
	if ($trace === '?') {
		include_spip('inc/autoriser');
		// gare au bouclage sur calcul de droits au premier appel
		// A fortiori quand on demande une trace
		$trace = isset($_GET['var_profile']) AND (autoriser('debug'));
	}
	return  $trace ?  microtime() : 0;
}

// http://doc.spip.org/@trace_query_end
function trace_query_end($query, $start, $result, $err, $serveur='')
{
	global $tableau_des_erreurs;
	if ($start)
		trace_query_chrono($start, microtime(), $query, $result, $serveur);
	if (!($err = sql_errno())) return $result;
	$err .= ' '.sql_error();
	$tableau_des_erreurs[] = array(
		_T('info_erreur_requete'). " "  .  htmlentities($query),
		"&laquo; " .  htmlentities($err)," &raquo;");
	return $result;
}

// http://doc.spip.org/@trace_query_chrono
function trace_query_chrono($m1, $m2, $query, $result, $serveur='')
{
	static $tt = 0, $nb=0;
	global $tableau_des_temps;

	$x = _request('var_mode_objet');
	if (isset($GLOBALS['debug']['aucasou'])) {
		list(, $boucle, $serveur) = $GLOBALS['debug']['aucasou'];
		if ($x AND !preg_match("/$boucle\$/", $x))
			return;
		if ($serveur) $boucle .= " ($serveur)";
		$boucle = "<b>$boucle</b>";
	} else {
		if ($x) return;
		$boucle = '';
	}

	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	$nb++;

	$q = preg_replace('/([a-z)`])\s+([A-Z])/', '$1<br />$2',htmlentities($query));
	$e =  sql_explain($query, $serveur);
	$r = str_replace('Resource id ','',(is_object($result)?get_class($result):$result));
	$tableau_des_temps[] = array($dt, $nb, $boucle, $q, $e, $r);
}


function chrono_requete($temps)
{
	$total = 0;
	$hors = "<i>" . _T('zbug_hors_compilation') . "</i>";
	$t = $q = $n = $d = array();
	foreach ($temps as $key => $row) {
		list($dt, $nb, $boucle, $query, $explain, $res) = $row;
		$total += $dt;
		$d[$boucle]+= $dt;
		$t[$key] = $dt;
		$q[$key] = $nb;

		$e = "<tr><th colspan='2' style='text-align:center'>"
		. (!$boucle ? $hors :
		   ($boucle . '&nbsp;(' . @++$n[$boucle] . ")"))
		. "</th></tr>"
		.  "<tr><td>Time</td><td>$dt</td></tr>" 
		.  "<tr><td>Order</td><td>$nb</td></tr>" 
		. "<tr><td>Res</td><td>$res</td></tr>" ;

		foreach($explain as $k => $v) {
			$e .= "<tr><td>$k</td><td>"
			  . str_replace(';','<br />',$v)
			  . "</td></tr>";
		}
		$e = "<br /><table border='1'>$e</table>";
		$temps[$key] = array($boucle, $e, $query);
	}
	array_multisort($t, SORT_DESC, $q, $temps);
	arsort($d);
	$i = 1;
	$t = array();
	foreach($temps as $k => $v) {
		$boucle = array_shift($v);
		$temps[$k] = $v;
		$x = "<a style='font-family: monospace' title='"
		  .  textebrut(preg_replace(',</tr>,', "\n",$v[0]))
		  . "' href='".quote_amp($GLOBALS['REQUEST_URI'])."#req$i'>"
		  . str_replace(' ', '&nbsp;', sprintf("%5d",$i))
		  . "</a>";
		if (count($t[$boucle]) % 30 == 29) $x .= "<br />";
		$t[$boucle][] = $x;
		$i++;
	}

	if ($d['']) {
		$d[$hors] = $d[''];
		$n[$hors] = $n[''];
		$t[$hors] = $t[''];
	}
	unset($d['']);
	foreach ($d as $k => $v) {
		$d[$k] =  $n[$k] . "</td><td>$k</td><td>$v</td><td>"
		  . join('',$t[$k]);
	}

	$titre = '<br />'
	  . _T('zbug_statistiques')
	  . '<br />'
	  . "<table style='text-align: left; border: 1px solid;'><tr><td>"
	  . join("</td></tr>\n<tr><td>", $d)
	  . "</td></tr>\n"
	  . (_request('var_mode_objet') ? '' : 
	     ("<tr><td>" .  count($temps) . " </td><td> " . _T('info_total') . '</td><td>' . $total . "</td></td><td></td></tr>"))
	  . "</table>";

	include_spip('public/debusquer');
	return (_DIR_RESTREINT ? '' : affiche_erreurs_page($GLOBALS['tableau_des_erreurs']))
	. affiche_erreurs_page($temps, $titre);
}

?>
