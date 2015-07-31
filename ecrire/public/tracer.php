<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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
function trace_query_end($query, $start, $result, $erreur, $serveur=''){
	if ($start)
		trace_query_chrono($start, microtime(), $query, $result, $serveur);
	// tracer les erreurs, sauf pour select, c'est fait dans abstract_sql
	if ($erreur AND !preg_match('/^select\b/i', $query))
		erreur_squelette(array(sql_errno($serveur), $erreur, $query));
	return $result;
}

// http://doc.spip.org/@trace_query_chrono
function trace_query_chrono($m1, $m2, $query, $result, $serveur='')
{
	static $tt = 0, $nb=0;
	global $tableau_des_temps;

	include_spip('inc/filtres_mini');

	$x = _request('var_mode_objet');
	if (isset($GLOBALS['debug']['aucasou'])) {
		list(, $boucle, $serveur, $contexte) = $GLOBALS['debug']['aucasou'];
		if ($x AND !preg_match("/$boucle\$/", $x))
			return;
		if ($serveur) $boucle .= " ($serveur)";
		$boucle = "<b>$boucle</b>";
	} else {
		if ($x) return;
		$boucle = $contexte = '';
	}

	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	$nb++;

	$q = preg_replace('/([a-z)`])\s+([A-Z])/', "$1\n<br />$2",spip_htmlentities($query));
	$e =  sql_explain($query, $serveur);
	$r = str_replace('Resource id ','',(is_object($result)?get_class($result):$result));
	$tableau_des_temps[] = array($dt, $nb, $boucle, $q, $e, $r, $contexte);
}


function chrono_requete($temps)
{
	$total = 0;
	$hors = "<i>" . _T('zbug_hors_compilation') . "</i>";
	$t = $q = $n = $d = array();
	// Totaliser les temps et completer le Explain
	foreach ($temps as $key => $v) {
		list($dt, $nb, $boucle, $query, $explain, $res, $contexte) = $v;
		if (is_array($contexte)) {
			$k = ($contexte[0] . " $boucle");
			include_spip('public/compiler');
			$env = reconstruire_contexte_compil($contexte);
		} else $k = $env = $boucle;

		$total += $dt;
		$t[$key] = $dt;
		$q[$key] = $nb;
		$d[$k]+= $dt;
		if ($k) @++$n[$k];

		if (!is_array($explain))
			$explain = array();
		foreach($explain as $j => $v) {
			$explain[$j] = "<tr><th>$j</th><td>"
			  . str_replace(';','<br />',$v)
			  . "</td></tr>";
		}
		$e = "<table class='explain'>"
		. "<caption>"
		. $query
		. "</caption>"
		. "<tr><th>Time</th><td>$dt</td></tr>"
		. "<tr><th>Order</th><td>$nb</td></tr>"
		. "<tr><th>Res</th><td>$res</td></tr>"
		. join('', $explain)
		. "</table>";

		$temps[$key] = array($e, $env, $k);
	}
	// Trier par temps d'execution decroissant
	array_multisort($t, SORT_DESC, $q, $temps);
	arsort($d);
	$i = 1;
	$t = array();
	// Fabriquer les liens de navigations dans le tableau des temps
	foreach($temps as $k => $v) {
		$titre = strip_tags($v[2]);
		$href = quote_amp($GLOBALS['REQUEST_URI'])."#req$i";

		$t[$v[2]][]= "<span class='spip-debug-arg'> "
		. "<a title='$titre' href='$href'>$i</a>"
		. '</span>'
		. ((count($t[$v[2]]) % 10 == 9) ?  "<br />" : '');
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
		$d[$k] =  $n[$k] . "</td><td>$k</td><td class='time'>$v</td><td class='liste-reqs'>"
		  . join('',$t[$k]);
	}

	$navigation = array(_T('zbug_statistiques'),
	  "<tr><td>"
		. join("</td></tr>\n<tr><td>", $d)
	  . "</td></tr>\n"
	  .  (# _request('var_mode_objet') ? '' : 
	     ("<tr><td>" .  count($temps) . "</td><td>" . _T('info_total') . '</td><td class="time">' . $total . "</td><td></td></tr>")));
	  
	return array($temps, $navigation);
}

?>
