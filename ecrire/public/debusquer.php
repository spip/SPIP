<?php

use Spip\Core\Contexte;

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('public/decompiler');
include_spip('inc/filtres_mini');

// Le debusqueur repose sur la globale debug_objets,
// affectee par le compilateur et le code produit par celui-ci.
// Cette globale est un tableau avec comme index:
// 'boucle' : tableau des arbres de syntaxe abstraite des boucles
// 'contexte' : tableau des contextes des squelettes assembles
// 'principal' : nom du squelette principal
// 'profile' : tableau des temps de calcul des squelettes
// 'resultat' : tableau des resultats envoyes (tableau de tableaux pour les boucles)
// 'sequence' : tableau de sous-tableaux resultat/source/numero-de-ligne
// 'sourcefile' : tableau des noms des squelettes inclus
// 'squelette' : tableau des sources de squelettes
// 'validation' : resultat final a passer a l'analyseur XML au besoin

/**
 * Definir le nombre maximal d'erreur possible dans les squelettes
 * au dela, l'affichage est arrete et les erreurs sont affichees.
 * Definir a zero permet de ne jamais bloquer,
 * mais il faut etre tres prudent avec cette utilisation
 *
 * Sert pour les tests unitaires
 */
defined('_DEBUG_MAX_SQUELETTE_ERREURS') || define('_DEBUG_MAX_SQUELETTE_ERREURS', 9);


/**
 * Point d'entrée pour les erreurs de compilation
 *
 * Point d'entrée pour les appels involontaires ($message non vide => erreur)
 *  et volontaires (var_mode et var_profile)
 *
 * Si pas d'autorisation, les erreurs ne sont pas affichées
 * (mais seront dans les logs)
 *
 * Si l'erreur vient de SPIP,  en parler sur `spip@rezo.net`
 *
 * @param bool|string|array $message
 *     - Message d'erreur (string|array)
 *     - false pour retourner le texte des messages d'erreurs
 *     - vide pour afficher les messages d'erreurs
 * @param string|Contexte $lieu
 *     Contexte : lieu d'origine de l'erreur
 * @param array $opt
 *     Options pour debug ou tests unitaires
 *     - 'erreurs' = 'get' : Retourne le tableau des erreurs
 *     - 'erreurs' = 'reset' : Efface le tableau des erreurs
 * @return null|string|array|true|void
 *     - string si $message à false.
 *     - array si $opt 'erreurs' = 'get'
 *     - true si $opt 'erreurs' = 'reset'
 **/
function public_debusquer_dist($message = '', $lieu = '', $opt = []) {
	static $tableau_des_erreurs = [];

	// Pour des tests unitaires, pouvoir récupérer les erreurs générées
	if (isset($opt['erreurs'])) {
		if ($opt['erreurs'] == 'get') {
			return $tableau_des_erreurs;
		}
		if ($opt['erreurs'] == 'reset') {
			$tableau_des_erreurs = [];

			return true;
		}
	}

	// Erreur ou appel final ?
	if ($message) {
		$message = debusquer_compose_message($message);
		$tableau_des_erreurs[] = [$message, $lieu];
		set_request('var_mode', 'debug');
		$GLOBALS['bouton_admin_debug'] = true;
		// Permettre a la compil de continuer
		if (is_object($lieu) and (!isset($lieu->code) or !$lieu->code)) {
			$lieu->code = "''";
		}
		// forcer l'appel au debusqueur en cas de boucles infernales
		$urgence = (_DEBUG_MAX_SQUELETTE_ERREURS and (is_countable($tableau_des_erreurs) ? count($tableau_des_erreurs) : 0) > _DEBUG_MAX_SQUELETTE_ERREURS);
		if (!$urgence) {
			return;
		}
	}
	if (empty($GLOBALS['debug_objets']['principal'])) {
		// espace public ?
		if (isset($GLOBALS['fond'])) {
			$GLOBALS['debug_objets']['principal'] = $GLOBALS['fond'];
		}
	}

	include_spip('inc/autoriser');
	if (!autoriser('debug')) {
		return;
	}
	include_spip('inc/headers');
	include_spip('inc/filtres');

	lang_select($GLOBALS['visiteur_session']['lang']);
	$fonc = preg_replace(',\W,', '_', _request('var_mode_objet') ?? '');
	$mode = preg_replace(',\W,', '_', _request('var_mode_affiche') ?? '');

	$self = str_replace("\\'", '&#39;', self());
	$self = parametre_url($self, 'var_mode', 'debug');

	$res = debusquer_bandeau($tableau_des_erreurs)
		. '<br />'
		. debusquer_squelette($fonc, $mode, $self);

	if (!_DIR_RESTREINT or headers_sent()) {
		return $res;
	}
	if ($tableau_des_erreurs) {
		http_response_code(503);
	}

	http_no_cache();
	if (isset($_GET['var_profile'])) {
		$titre = parametre_url($GLOBALS['REQUEST_URI'], 'var_profile', '');
		$titre = parametre_url($titre, 'var_mode', '');
	} else {
		if (!$fonc) {
			$fonc = $GLOBALS['debug_objets']['principal'];
		}
		$titre = !$mode ? $fonc : ($mode . (isset($GLOBALS['debug_objets']['sourcefile'][$fonc]) ? ' ' . $GLOBALS['debug_objets']['sourcefile'][$fonc] : ''));
	}
	if ($message === false) {
		lang_select();

		return debusquer_entete($titre, $res);
	} else {
		echo debusquer_entete($titre, $res);
	}
	exit;
}

function debusquer_compose_message($msg) {
	if (is_array($msg)) {
		// si c'est un texte, c'est une traduction a faire, mais
		// sqlite renvoit aussi des erreurs alpha num (mais avec 3 arguments)
		if (!is_numeric($msg[0]) and count($msg) == 2) {
			// message avec argument: instancier
			$msg = _T($msg[0], $msg[1], 'spip-debug-arg');
		} else {
			// message SQL: interpreter
			$msg = debusquer_requete($msg);
		}
	}
	// FIXME: le fond n'est pas la si on n'est pas dans un squelette
	// cela dit, ca serait bien d'indiquer tout de meme d'ou vient l'erreur
	$fond = $GLOBALS['fond'] ?? '';
	// une erreur critique sort $message en array
	$debug = is_array($msg) ? $msg[1] : $msg;
	spip_log('Debug: ' . $debug . ' (' . $fond . ')');

	return $msg;
}

function debusquer_bandeau($erreurs) {

	if (!empty($erreurs)) {
		$n = [(is_countable($erreurs) ? count($erreurs) : 0) . ' ' . _T('zbug_erreur_squelette')];

		return debusquer_navigation($erreurs, $n);
	} elseif (!empty($GLOBALS['tableau_des_temps'])) {
		include_spip('public/tracer');
		[$temps, $nav] = chrono_requete($GLOBALS['tableau_des_temps']);

		return debusquer_navigation($temps, $nav, 'debug-profile');
	} else {
		return '';
	}
}

/**
 * Affiche proprement dans un tableau le contexte d'environnement
 *
 * @param array|string $env
 * @return string Code HTML
 **/
function debusquer_contexte($env) {
	if (is_string($env) and is_array($env_tab = @unserialize($env))) {
		$env = $env_tab;
	}

	if (!$env) {
		return '';
	}
	$res = '';
	foreach ($env as $nom => $valeur) {
		if (is_array($valeur)) {
			$valeur_simple = [];
			foreach ($valeur as $v) {
				if (is_array($v)) {
					$valeur_simple[] = 'array:' . count($v);
				} elseif (is_object($v)) {
					$valeur_simple[] = get_class($v);
				} elseif (is_string($v)) {
					$valeur_simple[] = "'" . $v . "'";
				} else {
					$valeur_simple[] = $v;
				}
			}
			$n = count($valeur);
			$valeur = (($n > 3) ? 'array:' . $n . ' ' : '');
			$valeur .= '[' . join(', ', $valeur_simple) . ']';
		} elseif (is_object($valeur)) {
			$valeur = get_class($valeur);
		} elseif (is_string($valeur)) {
			$valeur = "'" . $valeur . "'";
		}
		$res .= "\n<tr><td><strong>" . nl2br((string) entites_html($nom))
			. '</strong></td><td>:&nbsp;' . nl2br((string) entites_html($valeur))
			. "</td></tr>\n";
	}

	return "<div class='spip-env'><fieldset><legend onclick=\"this.parentElement.classList.toggle('expanded');\">#ENV</legend>\n<div><table>$res</table></div></fieldset></div>\n";
}

// Affichage du tableau des erreurs ou des temps de calcul
// Cliquer sur les numeros en premiere colonne permet de voir le code

function debusquer_navigation($tableau, $caption = [], $id = 'debug-nav') {

	if (_request('exec') == 'valider_xml') {
		return '';
	}
	$GLOBALS['bouton_admin_debug'] = true;
	$res = '';
	$href = quote_amp(parametre_url($GLOBALS['REQUEST_URI'], 'var_mode', 'debug'));
	foreach ($tableau as $i => $err) {
		$boucle = $ligne = $skel = '';
		[$msg, $lieu] = $err;
		if (is_object($lieu)) {
			$ligne = $lieu->ligne;
			$boucle = !empty($lieu->id_boucle) ? $lieu->id_boucle : '';
			if (isset($lieu->descr['nom'])) {
				$nom_code = $lieu->descr['nom'];
				$skel = $lieu->descr['sourcefile'];
				$h2 = parametre_url($href, 'var_mode_objet', $nom_code);
				$h3 = parametre_url($h2, 'var_mode_affiche', 'squelette') . '#L' . $ligne;
				$skel = "<a href='$h3'><b>$skel</b></a>";
				if ($boucle) {
					$h3 = parametre_url($h2 . $boucle, 'var_mode_affiche', 'boucle');
					$boucle = "<a href='$h3'><b>$boucle</b></a>";
				}
			}
		}

		$j = ($i + 1);
		$res .= "<tr id='req$j'><td style='text-align: right'>"
			. $j
			. "&nbsp;</td><td style='text-align: left'>"
			. (is_array($msg) ? implode('', $msg) : $msg)
			. "</td><td style='text-align: left'>"
			. ($skel ?: '&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;')
			. "</td><td class='spip-debug-arg' style='text-align: left'>"
			. ($boucle ?: '&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;')
			. "</td><td style='text-align: right'>"
			. $ligne
			. "</td></tr>\n";
	}

	return "\n<table id='$id'>"
	. "<caption onclick=\"x = document.getElementById('$id'); (x.style.display == '' ? x.style.display = 'none' : x.style.display = '');\">"
	. $caption[0]
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aider('erreur_compilation'),
	. '</caption>'
	//  fausse caption du chrono (mais vraie nav)
	. (!empty($caption[1]) ? $caption[1] : '')
	. '<tr><th>'
	. _T('numero')
	. '</th><th>'
	. _T('public:message')
	. '</th><th>'
	. _T('squelette')
	. '</th><th>'
	. _T('zbug_boucle')
	. '</th><th>'
	. _T('ligne')
	. '</th></tr>'
	. $res
	. '</table>';
}


/**
 * Retourne le texte d'un message d'erreur de requête
 *
 * Si une boucle cree des soucis, on peut afficher la requete fautive
 * avec son code d'erreur
 *
 * @param array $message
 *    Description du message en 3 éléments :
 *    - numéro d'erreur
 *    - texte de l'erreur
 *    - requête en erreur
 * @return string|array
 *    Retourne le texte de l'erreur a afficher
 *    ou un tableau si l'erreur est critique
 **/
function debusquer_requete($message) {
	[$errno, $msg, $query] = $message;

	// FIXME: ces écritures mélangent divers syntaxe des moteurs SQL
	// il serait plus prudent certainement d'avoir une fonction d'analyse par moteur
	if (preg_match(',err(no|code):?[[:space:]]*([0-9]+),i', $msg, $regs)) {
		$errno = $regs[2];
	} elseif (
		is_numeric($errno) and ($errno == 1030 or $errno <= 1026)
		and preg_match(',[^[:alnum:]]([0-9]+)[^[:alnum:]],', $msg, $regs)
	) {
		$errno = $regs[1];
	}

	// Erreur systeme
	if (is_numeric($errno) and $errno > 0 and $errno < 200) {
		$retour = '<tt><br /><br /><blink>'
			. _T('info_erreur_systeme', ['errsys' => $errno])
			. "</blink><br />\n<b>"
			. _T(
				'info_erreur_systeme2',
				['script' => generer_url_ecrire('base_repair')]
			)
			. '</b><br />';
		spip_log("Erreur systeme $errno");

		return [$retour, ''];
	}

	// Requete erronee
	$err = '<b>' . _T('avis_erreur_mysql') . " $errno</b><br /><tt>\n"
		. spip_htmlspecialchars($msg)
		. "\n<br /><span style='color: red'><b>"
		. spip_htmlspecialchars($query)
		. '</b></span></tt><br />';

	//. aider('erreur_mysql');

	return $err;
}


function trouve_boucle_debug($n, $nom, $debut = 0, $boucle = '') {

	$id = $nom . $boucle;
	if (is_array($GLOBALS['debug_objets']['sequence'][$id])) {
		foreach ($GLOBALS['debug_objets']['sequence'][$id] as $v) {
			if (!preg_match('/^(.*)(<\?.*\?>)(.*)$/s', $v[0], $r)) {
				$y = substr_count($v[0], "\n");
			} else {
				if ($v[1][0] == '#') { // balise dynamique
				$incl = $GLOBALS['debug_objets']['resultat'][$v[2]];
				} else // inclusion
				{
					$incl = $GLOBALS['debug_objets']['squelette'][trouve_squelette_inclus($v[0])];
				}
				$y = substr_count($incl, "\n")
					+ substr_count($r[1], "\n")
					+ substr_count($r[3], "\n");
			}
			if ($n <= ($y + $debut)) {
				if ($v[1][0] == '?') {
					return trouve_boucle_debug($n, $nom, $debut, substr($v[1], 1));
				} elseif ($v[1][0] == '!') {
					if ($incl = trouve_squelette_inclus($v[1])) {
						return trouve_boucle_debug($n, $incl, $debut);
					}
				}

				return [$nom, $boucle, $v[2] - 1 + $n - $debut];
			}
			$debut += $y;
		}
	}

	return [$nom, $boucle, $n - $debut];
}

function trouve_squelette_inclus($script) {

	preg_match('/include\(.(.*).php3?.\);/', $script, $reg);
	// si le script X.php n'est pas ecrire/public.php
	// on suppose qu'il prend le squelette X.html (pas sur, mais y a pas mieux)
	if ($reg[1] == 'ecrire/public') { // si c'est bien ecrire/public on cherche le param 'fond'
	if (!preg_match("/'fond' => '([^']*)'/", $script, $reg)) { // a defaut on cherche le param 'page'
		if (!preg_match("/'param' => '([^']*)'/", $script, $reg)) {
				$reg[1] = 'inconnu';
		}
	}
	}
	$incl = ',' . $reg[1] . '[.]\w$,';

	foreach ($GLOBALS['debug_objets']['sourcefile'] as $k => $v) {
		if (preg_match($incl, $v)) {
			return $k;
		}
	}

	return '';
}

function reference_boucle_debug($n, $nom, $self) {
	[$skel, $boucle, $ligne] = trouve_boucle_debug($n, $nom);

	if (!$boucle) {
		return !$ligne ? '' :
			(' (' .
				(($nom != $skel) ? _T('squelette_inclus_ligne') :
					_T('squelette_ligne')) .
				" <a href='$self&amp;var_mode_objet=$skel&amp;var_mode_affiche=squelette&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)");
	} else {
		$self .= "&amp;var_mode_objet=$skel$boucle&amp;var_mode_affiche=boucle";

		return !$ligne ? " (boucle\n<a href='$self#$skel$boucle'>$boucle</a>)" :
			" (boucle $boucle ligne\n<a href='$self&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)";
	}
}

// affiche un texte avec numero de ligne et ancre.

function ancre_texte($texte, $fautifs = [], $nocpt = false) {

	$var_mode_ligne = _request('var_mode_ligne');
	if ($var_mode_ligne) {
		$fautifs[] = [$var_mode_ligne];
	}
	$res = '';

	$s = highlight_string($texte, true);
	if (substr($s, 0, 6) == '<code>') {
		$s = substr($s, 6);
		$res = '<code>';
	}

	$s = preg_replace(
		',<(\w[^<>]*)>([^<]*)<br />([^<]*)</\1>,',
		'<\1>\2</\1><br />' . "\n" . '<\1>\3</\1>',
		$s
	);


	$tableau = explode('<br />', $s);

	$format = "<span style='float:left;display:block;width:50px;height:1px'><a id='L%d' style='background-color: white; visibility: " . ($nocpt ? 'hidden' : 'visible') . ";%s' href='#T%s' title=\"%s\">%0" . strval(@strlen(count($tableau))) . "d</a></span> %s<br />\n";

	$format10 = str_replace('white', 'lightgrey', $format);
	$formaterr = 'color: red;';
	$i = 1;
	$flignes = [];
	$loc = [0, 0];
	foreach ($fautifs as $lc) {
		if (is_array($lc)) {
			$l = array_shift($lc);
			$flignes[$l] = $lc;
		} else {
			$flignes[$lc] = $loc;
		}
	}

	$ancre = md5($texte);
	foreach ($tableau as $ligne) {
		if (isset($flignes[$i])) {
			$ligne = str_replace('&nbsp;', ' ', $ligne);
			$indexmesg = $flignes[$i][1];
			$err = textebrut($flignes[$i][2]);
			// tentative de pointer sur la colonne fautive;
			// marche pas car highlight_string rajoute des entites. A revoir.
			// $m = $flignes[$i][0];
			// $ligne = substr($ligne, 0, $m-1) .
			// sprintf($formaterr, substr($ligne,$m));
			$bg = $formaterr;
		} else {
			$indexmesg = $ancre;
			$err = $bg = '';
		}
		$res .= sprintf((($i % 10) ? $format : $format10), $i, $bg, $indexmesg, $err, $i, $ligne);
		$i++;
	}

	return "<div id='T$ancre'>"
	. '<div onclick="'
	. "jQuery(this).parent().find('a').toggle();"
	. '" title="'
	. _T('masquer_colonne')
	. '" style="cursor: pointer;">'
	. ($nocpt ? '' : _T('info_numero_abbreviation'))
	. '</div>
	' . $res . "</div>\n";
}

// l'environnement graphique du debuggueur

function debusquer_squelette($fonc, $mode, $self) {
	$legend = null;
	$texte = '';

	if ($mode !== 'validation') {
		if (isset($GLOBALS['debug_objets']['sourcefile']) and $GLOBALS['debug_objets']['sourcefile']) {
			$res = "<div id='spip-boucles'>\n"
				. debusquer_navigation_squelettes($self)
				. '</div>';
		} else {
			$res = '';
		}
		if ($fonc) {
			$id = " id='$fonc'";
			if (!empty($GLOBALS['debug_objets'][$mode][$fonc])) {
				[$legend, $texte, $res2] = debusquer_source($fonc, $mode);
				$texte .= $res2;
			} elseif (!empty($GLOBALS['debug_objets'][$mode][$fonc . 'tout'])) {
				$legend = _T('zbug_' . $mode);
				$texte = $GLOBALS['debug_objets'][$mode][$fonc . 'tout'];
				$texte = ancre_texte($texte, ['', '']);
			}
		} else {
			if (strlen(trim($res))) {
				return "<img src='" . chemin_image('debug-xx.svg') . "' alt='afficher-masquer le debug' id='spip-debug-toggle' onclick=\"var x = document.getElementById('spip-debug'); (x.style.display == '' ? x.style.display = 'none' : x.style.display = '');\" /><div id='spip-debug'>$res</div>";
			} else {
				// cas de l'appel sur erreur: montre la page
				return $GLOBALS['debug_objets']['resultat']['tout'] ?? '';
			}
		}
	} else {
		$valider = charger_fonction('valider', 'xml');
		$val = $valider($GLOBALS['debug_objets']['validation'][$fonc . 'tout']);
		// Si erreur, signaler leur nombre dans le formulaire admin
		$GLOBALS['debug_objets']['validation'] = $val->err ? count($val->err) : '';
		[$texte, $err] = emboite_texte($val, $fonc, $self);
		if ($err === false) {
			$err = _T('impossible');
		} elseif ($err === true) {
			$err = _T('correcte');
		} else {
			$err = ": $err";
		}
		$legend = _T('validation') . ' ' . $err;
		$res = $id = '';
	}

	return !trim($texte) ? '' : (
		"<img src='" . chemin_image('debug-xx.svg') . "' alt='afficher-masquer le debug' id='spip-debug-toggle' onclick=\"var x = document.getElementById('spip-debug'); (x.style.display == '' ? x.style.display = 'none' : x.style.display = '');\" /><div id='spip-debug'>$res"
		. "<div id='debug_boucle'><fieldset$id><legend>"
		. "<a href='" . $self . '#f_' . substr($fonc, 0, 37) . "'> &#8593; "
		. ($legend ?: $mode)
		. '</a></legend>'
		. $texte
		. '</fieldset></div>'
		. '</div>');
}


function emboite_texte($res, $fonc = '', $self = '') {
	$errs = $res->err;
	$texte = $res->entete . ($errs ? '' : $res->page);

	if (!$texte and !$errs) {
		return [ancre_texte('', ['', '']), false];
	}
	if (!$errs) {
		return [ancre_texte($texte, ['', '']), true];
	}

	if (!isset($GLOBALS['debug_objets'])) {
		$colors = ['#e0e0f0', '#f8f8ff'];
		$encore = count_occ($errs);
		$encore2 = [];
		$fautifs = [];

		$err = '<tr><th>'
			. _T('numero')
			. '</th><th>'
			. _T('occurence')
			. '</th><th>'
			. _T('ligne')
			. '</th><th>'
			. _T('colonne')
			. '</th><th>'
			. _T('erreur')
			. '</th></tr>';

		$i = 0;
		$style = "style='text-align: right; padding-right: 5px'";
		foreach ($errs as $r) {
			$i++;
			[$msg, $ligne, $col] = $r;
			#spip_log("$r = list($msg, $ligne, $col");
			if (isset($encore2[$msg])) {
				$ref = ++$encore2[$msg];
			} else {
				$encore2[$msg] = $ref = 1;
			}
			$err .= "<tr  style='background-color: "
				. $colors[$i % 2]
				. "'><td $style><a href='#debut_err'>"
				. $i
				. "</a></td><td $style>"
				. "$ref/$encore[$msg]</td>"
				. "<td $style><a href='#L"
				. $ligne
				. "' id='T$i'>"
				. $ligne
				. "</a></td><td $style>"
				. $col
				. "</td><td>$msg</td></tr>\n";
			$fautifs[] = [$ligne, $col, $i, $msg];
		}
		$err = "<h2 style='text-align: center'>"
			. $i
			. "<a href='#fin_err'>"
			. ' ' . _T('erreur_texte')
			. "</a></h2><table id='debut_err' style='width: 100%'>"
			. $err
			. " </table><a id='fin_err'></a>";

		return [ancre_texte($texte, $fautifs), $err];
	} else {
		[$msg, $fermant, $ouvrant] = $errs[0];
		$rf = reference_boucle_debug($fermant, $fonc, $self);
		$ro = reference_boucle_debug($ouvrant, $fonc, $self);
		$err = $msg .
			"<a href='#L" . $fermant . "'>$fermant</a>$rf<br />" .
			"<a href='#L" . $ouvrant . "'>$ouvrant</a>$ro";

		return [ancre_texte($texte, [[$ouvrant], [$fermant]]), $err];
	}
}

function count_occ($regs) {
	$encore = [];
	foreach ($regs as $r) {
		if (isset($encore[$r[0]])) {
			$encore[$r[0]]++;
		} else {
			$encore[$r[0]] = 1;
		}
	}

	return $encore;
}

function debusquer_navigation_squelettes($self) {

	$res = '';
	$boucles = !empty($GLOBALS['debug_objets']['boucle']) ? $GLOBALS['debug_objets']['boucle'] : '';
	$contexte = $GLOBALS['debug_objets']['contexte'];
	$t_skel = _T('squelette');
	foreach ($GLOBALS['debug_objets']['sourcefile'] as $nom => $sourcefile) {
		$self2 = parametre_url($self, 'var_mode_objet', $nom);
		$nav = !$boucles ? '' : debusquer_navigation_boucles($boucles, $nom, $self, $sourcefile);
		$temps = !isset($GLOBALS['debug_objets']['profile'][$sourcefile]) ? '' : _T(
			'zbug_profile',
			['time' => $GLOBALS['debug_objets']['profile'][$sourcefile]]
		);

		$res .= "<fieldset id='f_" . $nom . "'><legend>"
			. $t_skel
			. ' '
			. $sourcefile
			. "&nbsp;:\n<a href='$self2&amp;var_mode_affiche=squelette#f_$nom'>"
			. $t_skel
			. "</a>\n<a href='$self2&amp;var_mode_affiche=resultat#f_$nom'>"
			. _T('zbug_resultat')
			. "</a>\n<a href='$self2&amp;var_mode_affiche=code#f_$nom'>"
			. _T('zbug_code')
			. "</a>\n<a href='"
			. str_replace('var_mode=debug', 'var_profile=1&amp;var_mode=recalcul', $self)
			. "'>"
			. _T('zbug_calcul')
			. '</a></legend>'
			. (!$temps ? '' : ("\n<span style='display:block;float:" . $GLOBALS['spip_lang_right'] . "'>$temps</span><br />"))
			. debusquer_contexte($contexte[$sourcefile])
		. (!$nav ? '' : ("<table width='100%'>\n$nav</table>\n"))
		. "</fieldset>\n";
	}

	return $res;
}

function debusquer_navigation_boucles($boucles, $nom_skel, $self, $nom_source) {
	$i = 0;
	$res = '';
	$var_mode_objet = _request('var_mode_objet');
	$gram = preg_match('/[.](\w+)$/', $nom_source, $r) ? $r[1] : '';

	foreach ($boucles as $objet => $boucle) {
		if (substr($objet, 0, strlen($nom_skel)) == $nom_skel) {
			$i++;
			$nom = $boucle->id_boucle;
			$req = $boucle->type_requete;
			$crit = public_decompiler($boucle, $gram, 0, 'criteres');
			$self2 = $self . '&amp;var_mode_objet=' . $objet;

			$res .= "\n<tr style='background-color: " .
				($i % 2 ? '#e0e0f0' : '#f8f8ff') .
				"'><td  align='right'>$i</td><td>\n" .
				"<a  class='debug_link_boucle' href='" .
				$self2 .
				"&amp;var_mode_affiche=boucle#f_$nom_skel'>" .
				_T('zbug_boucle') .
				"</a></td><td>\n<a class='debug_link_boucle' href='" .
				$self2 .
				"&amp;var_mode_affiche=resultat#f_$nom_skel'>" .
				_T('zbug_resultat') .
				"</a></td><td>\n<a class='debug_link_resultat' href='" .
				$self2 .
				"&amp;var_mode_affiche=code#f_$nom_skel'>" .
				_T('zbug_code') .
				"</a></td><td>\n<a class='debug_link_resultat' href='" .
				str_replace('var_mode=', 'var_profile=', $self2) .
				"'>" .
				_T('zbug_calcul') .
				"</a></td><td>\n" .
				(($var_mode_objet == $objet) ? "<b>$nom</b>" : $nom) .
				"</td><td>\n" .
				$req .
				"</td><td>\n" .
				spip_htmlspecialchars($crit) .
				'</td></tr>';
		}
	}

	return $res;
}

function debusquer_source($objet, $affiche) {
	$quoi = $GLOBALS['debug_objets'][$affiche][$objet];
	if (!empty($GLOBALS['debug_objets']['boucle'][$objet]->id_boucle)) {
		$nom = $GLOBALS['debug_objets']['boucle'][$objet]->id_boucle;
	} else {
		$nom = $GLOBALS['debug_objets']['sourcefile'][$objet];
	}
	$res2 = '';

	if ($affiche == 'resultat') {
		$legend = $nom;
		$req = $GLOBALS['debug_objets']['requete'][$objet];
		if (function_exists('_mysql_traite_query')) {
			$c = strtolower(_request('connect') ?? '');
			$c = $GLOBALS['connexions'][$c ?: 0]['prefixe'];
			$req = _mysql_traite_query($req, '', $c);
		}
		//  permettre le copier/coller facile
		// $res = ancre_texte($req, array(), true);
		$res = "<div id='T" . md5($req) . "'>\n<pre>\n" . $req . "</pre>\n</div>\n";
		//  formatage et affichage des resultats bruts de la requete
		$ress_req = spip_query($req);
		$brut_sql = '';
		$num = 1;
		//  eviter l'affichage de milliers de lignes
		//  personnalisation possible dans mes_options
		$max_aff = defined('_MAX_DEBUG_AFF') ? _MAX_DEBUG_AFF : 50;
		while ($retours_sql = sql_fetch($ress_req)) {
			if ($num <= $max_aff) {
				$brut_sql .= '<h3>' . ($num == 1 ? $num . ' sur ' . sql_count($ress_req) : $num) . '</h3>';
				$brut_sql .= '<p>';
				foreach ($retours_sql as $key => $val) {
					$brut_sql .= '<strong>' . $key . '</strong> => ' . spip_htmlspecialchars(couper($val, 150)) . "<br />\n";
				}
				$brut_sql .= '</p>';
			}
			$num++;
		}
		$res2 = interdire_scripts($brut_sql);
		foreach ($quoi as $view) {
			//  ne pas afficher les $contexte_inclus
			$view = preg_replace(',<\?php.+\?[>],Uims', '', $view);
			if ($view) {
				$res2 .= "\n<br /><fieldset>" . interdire_scripts($view) . '</fieldset>';
			}
		}
	} elseif ($affiche == 'code') {
		$legend = $nom;
		$res = ancre_texte('<' . "?php\n" . $quoi . "\n?" . '>');
	} elseif ($affiche == 'boucle') {
		$legend = _T('zbug_boucle') . ' ' . $nom;
		// Le compilateur prefixe le nom des boucles par l'extension du fichier source.
		$gram = preg_match('/^([^_]+)_/', $objet, $r) ? $r[1] : '';
		$res = ancre_texte(public_decompiler($quoi, $gram, 0, 'boucle'));
	} elseif ($affiche == 'squelette') {
		$legend = $GLOBALS['debug_objets']['sourcefile'][$objet];
		$res = ancre_texte($GLOBALS['debug_objets']['squelette'][$objet]);
	}

	return [$legend, $res, $res2];
}

function debusquer_entete($titre, $corps) {

	include_spip('balise/formulaire_admin');
	include_spip('public/assembler'); // pour inclure_balise_dynamique
	include_spip('inc/texte'); // pour corriger_typo

	return _DOCTYPE_ECRIRE .
	html_lang_attributes() .
	"<head>\n<title>" .
	('SPIP ' . $GLOBALS['spip_version_affichee'] . ' ' .
		_T('admin_debug') . ' ' . spip_htmlspecialchars($titre) . ' (' .
		supprimer_tags(corriger_typo($GLOBALS['meta']['nom_site']))) .
	")</title>\n" .
	"<meta http-equiv='Content-Type' content='text/html" .
	(($c = $GLOBALS['meta']['charset']) ? "; charset=$c" : '') .
	"' />\n" .
	http_script('', 'jquery.js')
	. "<link rel='stylesheet' href='" . url_absolue(find_in_path('spip_admin.css'))
	. "' type='text/css' />" .
	"</head>\n" .
	"<body style='margin:0 10px;'>\n" .
	"<div id='spip-debug-header'>" .
	$corps .
	inclure_balise_dynamique(balise_FORMULAIRE_ADMIN_dyn('spip-admin-float', $GLOBALS['debug_objets']), false) .
	'</div></body></html>';
}
