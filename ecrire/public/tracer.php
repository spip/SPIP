<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function trace_query_start() {
	static $trace = '?';
	if ($trace === '?' or defined('_DEBUG_TRACE_QUERIES')) {
		if (defined('_DEBUG_TRACE_QUERIES') and _DEBUG_TRACE_QUERIES) {
			$trace = true;
		}
		else {
			if (empty($GLOBALS['visiteur_session'])) {
				// si un anonyme fait un var_profile on est oblige de remplir le tableau des temps en attendant de savoir
				// car ici on ne sait pas si c'est un hit anonyme
				// ou une requete SQL faite avant chargement de la session
				$trace = (!empty($_GET['var_profile']) ? '?' : false);
			}
			else {
				include_spip('inc/autoriser');
				// gare au bouclage sur calcul de droits au premier appel
				// A fortiori quand on demande une trace
				$trace = false; // on ne trace pas la requete provoquee par autoriser('debug')
				$trace = (!empty($_GET['var_profile']) and autoriser('debug'));
			}
		}
	}

	return $trace ? microtime() : 0;
}

function trace_query_end($query, $start, $result, $erreur, $serveur = '') {
	static $trace = '?';
	if ($trace === '?') {
		if (empty($GLOBALS['visiteur_session'])) {
			// si un anonyme fait un var_profile on est oblige de remplir le tableau des temps en attendant de savoir
			// car ici on ne sait pas si c'est un hit anonyme
			// ou une requete SQL faite avant chargement de la session
			$trace = (!empty($_GET['var_profile']) ? '?' : false);
		}
		else {
			include_spip('inc/autoriser');
			// gare au bouclage sur calcul de droits au premier appel
			// A fortiori quand on demande une trace
			$trace = false; // on ne trace pas la requete provoquee par autoriser('debug')
			$trace = (!empty($_GET['var_profile']) and autoriser('debug'));
		}
	}
	if ($start) {
		$end = microtime();
		[$usec, $sec] = explode(' ', $start);
		[$usec2, $sec2] = explode(' ', $end);
		$dt = $sec2 + $usec2 - $sec - $usec;
		pipeline('trig_trace_query', ['query' => $query, 'start' => $start, 'end' => $end, 'time' => $dt, 'result' => $result, 'erreur' => $erreur, 'serveur' => $serveur]);
		if ($trace) {
			trace_query_chrono($dt, $query, $result, $serveur);
		}
	}
	// tracer les erreurs, sauf pour select, c'est fait dans abstract_sql
	if ($trace and $erreur and !preg_match('/^select\b/i', $query)) {
		erreur_squelette([sql_errno($serveur), $erreur, $query]);
	}

	return $result;
}

function trace_query_chrono($dt, $query, $result, $serveur = '') {
	include_spip('inc/filtres_mini');
	static $tt = 0, $nb = 0;

	$x = _request('var_mode_objet');
	if (isset($GLOBALS['debug']['aucasou'])) {
		[, $boucle, $serveur, $contexte] = $GLOBALS['debug']['aucasou'];
		if ($x and !preg_match("/$boucle\$/", $x)) {
			return;
		}
		if ($serveur) {
			$boucle .= " ($serveur)";
		}
		$boucle = "<b>$boucle</b>";
	} else {
		if ($x) {
			return;
		}
		$boucle = $contexte = '';
	}

	$tt += $dt;
	$nb++;

	$q = preg_replace('/([a-z)`])\s+([A-Z])/', "$1\n<br />$2", spip_htmlentities($query));
	$e = sql_explain($query, $serveur);
	$r = str_replace('Resource id ', '', (is_object($result) ? get_class($result) : $result));
	$GLOBALS['tableau_des_temps'][] = [$dt, $nb, $boucle, $q, $e, $r, $contexte];
}


function chrono_requete($temps) {
	$total = 0;
	$hors = '<i>' . _T('zbug_hors_compilation') . '</i>';
	$t = $q = $n = $d = [];
	// Totaliser les temps et completer le Explain
	foreach ($temps as $key => $v) {
		[$dt, $nb, $boucle, $query, $explain, $res, $contexte] = $v;
		if (is_array($contexte)) {
			$k = ($contexte[0] . " $boucle");
			include_spip('public/compiler');
			$env = reconstruire_contexte_compil($contexte);
		} else {
			$k = $env = $boucle;
		}

		$total += $dt;
		$t[$key] = $dt;
		$q[$key] = $nb;
		if (!isset($d[$k])) {
			$d[$k] = 0;
			$n[$k] = 0;
		}
		$d[$k] += $dt;
		++$n[$k];

		if (!is_array($explain)) {
			$explain = [];
		}
		foreach ($explain as $j => $v) {
			$explain[$j] = "<tr><th>$j</th><td>"
				. str_replace(';', '<br />', $v)
				. '</td></tr>';
		}
		$e = "<table class='explain'>"
			. '<caption>'
			. $query
			. '</caption>'
			. "<tr><th>Time</th><td>$dt</td></tr>"
			. "<tr><th>Order</th><td>$nb</td></tr>"
			. "<tr><th>Res</th><td>$res</td></tr>"
			. join('', $explain)
			. '</table>';

		$temps[$key] = [$e, $env, $k];
	}
	// Trier par temps d'execution decroissant
	array_multisort($t, SORT_DESC, $q, $temps);
	arsort($d);
	$i = 1;
	$t = [];
	// Fabriquer les liens de navigations dans le tableau des temps
	foreach ($temps as $k => $v) {
		$titre = strip_tags($v[2]);
		$href = quote_amp($GLOBALS['REQUEST_URI']) . "#req$i";
		$href = str_replace("\\'", '&#39;', $href);

		if (!isset($t[$v[2]])) {
			$t[$v[2]] = [];
		}
		$t[$v[2]][] = "<span class='spip-debug-arg'> "
			. "<a title='$titre' href='$href'>$i</a>"
			. '</span>'
			. ((count($t[$v[2]]) % 10 == 9) ? '<br />' : '');
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
		$d[$k] = $n[$k] . "</td><td>$k</td><td class='time'>$v</td><td class='liste-reqs'>"
			. join('', $t[$k]);
	}

	$navigation = [
		_T('zbug_statistiques'),
		'<tr><td>'
		. join("</td></tr>\n<tr><td>", $d)
		. "</td></tr>\n"
		. (# _request('var_mode_objet') ? '' :
		('<tr><td>' . (is_countable($temps) ? count($temps) : 0) . '</td><td>' . _T('info_total') . '</td><td class="time">' . $total . '</td><td></td></tr>'))
	];

	return [$temps, $navigation];
}
