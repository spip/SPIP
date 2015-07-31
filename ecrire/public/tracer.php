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
	// Totaliser les temps et completer le Explain
	foreach ($temps as $key => $row) {
		list($dt, $nb, $boucle, $query, $explain, $res) = $row;
		$total += $dt;
		$t[$key] = $dt;
		$q[$key] = $nb;
		$d[$boucle]+= $dt;
		if  ($boucle) @++$n[$boucle];

		foreach($explain as $k => $v) {
			$explain[$k] = "<tr><td>$k</td><td>"
			  . str_replace(';','<br />',$v)
			  . "</td></tr>";
		}
		$e = "<br /><table border='1'>"
		. "<caption style='text-align: left'>"
		. $query
		. "</caption>"
		. "<tr><td>Time</td><td>$dt</td></tr>" 
		. "<tr><td>Order</td><td>$nb</td></tr>" 
		. "<tr><td>Res</td><td>$res</td></tr>" 
		. join('', $explain)
		. "</table>";

		$temps[$key] = array($e, $boucle);
	}
	// Trier par temps d'execution decroissant
	array_multisort($t, SORT_DESC, $q, $temps);
	arsort($d);
	$i = 1;
	$t = array();
	// Fabriquer les liens de navigations dans le tableau des temps
	foreach($temps as $k => $v) {
		$titre = textebrut(preg_replace(',</tr>,', "\n",$v[0]));
		$href = quote_amp($GLOBALS['REQUEST_URI'])."#req$i";

		$t[$v[1]][]= "<span class='spip-debug-arg'>" 
		. str_repeat('&nbsp;', 5 - strlen(strval($i)))
		. "<a title='$titre' href='$href'>$i</a>"
		. '</span>'
		. ((count($t[$v[1]]) % 10 == 9) ?  "<br />" : '');
		$i++;
	}

	if ($d['']) {
		$d[$hors] = $d[''];
		$n[$hors] = $n[''];
		$t[$hors] = $t[''];
	}
	unset($d['']);
	// Fabriquer le tableau des liens de navigation dans le grand tableau
	foreach ($d as $k => $v) {
		$d[$k] =  $n[$k] . "</td><td>$k</td><td>$v</td><td>"
		  . join('',$t[$k]);
	}

	$navigation = '<br />'
	  . _T('zbug_statistiques')
	  . '<br />'
	  . "<table style='text-align: left; border: 1px solid;'><tr><td>"
	  . join("</td></tr>\n<tr><td>", $d)
	  . "</td></tr>\n"
	  .  (# _request('var_mode_objet') ? '' : 
	     ("<tr><td>" .  count($temps) . " </td><td> " . _T('info_total') . '</td><td>' . $total . "</td></td><td></td></tr>"))
	  . "</table>";

	return array($temps, $navigation);
}

?>
