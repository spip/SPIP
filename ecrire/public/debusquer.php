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

include_spip('public/decompiler');

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
define('_DEBUG_MAX_SQUELETTE_ERREURS', 9);

//
// Point d'entree general, 
// pour les appels involontaires ($message non vide => erreur)
// et volontaires (var_mode et var_profile) 
// Si pas d'autorisation, les erreurs ne sont pas affichees
// (mais seront dans les logs)
// Si l'erreur vient de SPIP,  en parler sur spip@rezo.net

function public_debusquer_dist($message='', $lieu='') {
	global $visiteur_session;
	global $debug_objets;
	static $tableau_des_erreurs = array();

	// Erreur ou appel final ?
	if ($message) {
		$message = debusquer_compose_message($message);
		$tableau_des_erreurs[] = array($message, $lieu);
		set_request('var_mode', 'debug');
		$GLOBALS['bouton_admin_debug'] = true;
		// Permettre a la compil de continuer
		if (is_object($lieu) AND !$lieu->code)
			$lieu->code = "''";
		// forcer l'appel au debusqueur en cas de boucles infernales
		$urgence = (_DEBUG_MAX_SQUELETTE_ERREURS AND count($tableau_des_erreurs) > _DEBUG_MAX_SQUELETTE_ERREURS);
		if (!$urgence) return;
	}
	if (empty($debug_objets['principal'])) 
		$debug_objets['principal'] = $GLOBALS['fond'];

	include_spip('inc/autoriser');
	if (!autoriser('debug')) return;
	include_spip('inc/headers');
	include_spip('inc/filtres');

	// en cas de squelette inclus,  virer le code de l'incluant:
	// - il contient souvent une Div restreignant la largeur a 3 fois rien
	// - ca fait 2 headers !
	// sauf si l'on se trouve deja dans un flux compresse (plugin compresseur
	// actif par exemple)
	if (ob_get_length()
	    AND
	    !in_array('ob_gzhandler', ob_get_status())) {
		ob_end_clean();
	}

	lang_select($visiteur_session['lang']);
	$fonc = _request('var_mode_objet');
	$mode = _request('var_mode_affiche');
	$self = str_replace("\\'", '&#39;', self());
	$self = parametre_url($self,'var_mode', 'debug');

	$res = debusquer_bandeau($tableau_des_erreurs) 
		. '<br />'
		. debusquer_squelette($fonc, $mode, $self);

	if (!_DIR_RESTREINT OR headers_sent()) return $res;
	if ($tableau_des_erreurs) http_status(503);

	http_no_cache();
	if (isset($_GET['var_profile'])) {
		$titre = parametre_url($GLOBALS['REQUEST_URI'], 'var_profile', '');
		$titre = parametre_url($titre, 'var_mode', '');
	} else {
		if (!$fonc) $fonc = $debug_objets['principal'];
		$titre = !$mode ? $fonc : ($mode . ' ' . $debug_objets['sourcefile'][$fonc]);
	}
	if ($message===false) {
		lang_select();
		return debusquer_entete($titre, $res);
	}
	else
		echo debusquer_entete($titre, $res);
	exit;
}

function debusquer_compose_message($msg)
{
	if (is_array($msg)) {
		if (!is_numeric($msg[0]))
			// message avec argument: instancier
			$msg = _T($msg[0], $msg[1], 'spip-debug-arg');
		else
			// message SQL: interpreter
			$msg = debusquer_requete($msg);
	}
	spip_log("Debug: " . $msg . " (" . $GLOBALS['fond'] .")" );
	return $msg;
}

function debusquer_bandeau($erreurs) {

	if (!empty($erreurs)) {
		$n = count($erreurs) . ' ' . _T('zbug_erreur_squelette');
		return debusquer_navigation($erreurs, $n);
	} elseif (!empty($GLOBALS['tableau_des_temps'])) {
			include_spip('public/tracer');
			list($temps, $nav) = chrono_requete($GLOBALS['tableau_des_temps']);
			return debusquer_navigation($temps, $nav, 'debug-profile');
	} else return '';
 }

function debusquer_contexte($env) {

	if (is_array($env_tab = @unserialize($env))) $env = $env_tab;

	if (!$env) return '';
	$res = "";
	foreach ($env as $nom => $valeur) {
		if (is_array($valeur))
			$valeur = '(' . count($valeur) .' items) [' . join(',', $valeur) . ']';
		$res .= "\n<tr><td><strong>".nl2br(entites_html($nom))
		. "</strong></td><td>:&nbsp;".nl2br(entites_html($valeur))
		. "</td></tr>\n";
	}

	return "<div class='spip-env'><fieldset><legend>#ENV</legend>\n<div><table>$res</table></div></fieldset></div>\n";
}

// Affichage du tableau des erreurs ou des temps de calcul
// Cliquer sur les numeros en premiere colonne permet de voir le code

function debusquer_navigation($tableau, $caption='', $id='debug-nav') {

	if (_request('exec')=='valider_xml') return '';
	$GLOBALS['bouton_admin_debug'] = true;
	$res = '';
	$href = quote_amp(parametre_url($GLOBALS['REQUEST_URI'], 'var_mode', 'debug'));
	foreach ($tableau as $i => $err) {
		$boucle = $ligne = $skel = '';
		list($msg, $lieu) = $err;
		if (is_object($lieu)) {
			$ligne = $lieu->ligne;
			$boucle = $lieu->id_boucle ? $lieu->id_boucle : '';
			if (isset($lieu->descr['nom'])) {
				$nom_code = $lieu->descr['nom'];
				$skel = $lieu->descr['sourcefile'];
				$h2 = parametre_url($href, 'var_mode_objet', $nom_code);
				$h3 = parametre_url($h2, 'var_mode_affiche', 'squelette') . '#L' . $ligne;
				$skel = "<a href='$h3'><b>$skel</b></a>";
				if ($boucle) {
					$h3 = parametre_url($h2.$boucle, 'var_mode_affiche', 'boucle');
					$boucle = "<a href='$h3'><b>$boucle</b></a>";
				}
			}
		}

		$j = ($i+1); 
		$res .= "<tr id='req$j'><td style='text-align: right'>"
		. $j
		. "&nbsp;</td><td style='text-align: left'>"
		. $msg
		. "</td><td style='text-align: left'>"
		. ($skel ? $skel : "&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;")
		. "</td><td class='spip-debug-arg' style='text-align: left'>"
		. ($boucle ? $boucle : "&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;")
		. "</td><td style='text-align: right'>"
		. $ligne
		.  "</td></tr>\n";

	}

	return "\n<table id='$id'>"
	. "<caption>"
	. $caption
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),
	. "</caption>"
	. "<tr><th>" 
	. _T('numero')
	. "</th><th>" 
	  . _T('message')
	. "</th><th>"
	. _T('squelette')
	. "</th><th>"
	. _T('boucle')
	.  "</th><th>"
	. _T('ligne')
	. "</th></tr>"
	. $res
	. "</table>";
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//

function debusquer_requete($message) {
	list($errno, $msg, $query) = $message;
	if (preg_match(',err(no|code):?[[:space:]]*([0-9]+),i', $msg, $regs))
	  {
		$errno = $regs[2];

	  } else if (($errno == 1030 OR $errno <= 1026)
		AND preg_match(',[^[:alnum:]]([0-9]+)[^[:alnum:]],', $msg, $regs))
		  $errno = $regs[1];

	// Erreur systeme
	if ($errno > 0 AND $errno < 200) {
		$retour = "<tt><br /><br /><blink>"
		. _T('info_erreur_systeme', array('errsys'=>$errno))
		. "</blink><br />\n<b>"
		. _T('info_erreur_systeme2',
			array('script' => generer_url_ecrire('admin_repair'))) 
		. '</b><br />';
		spip_log("Erreur systeme $errno");
		return array($retour,'');
	}
	// Requete erronee

	$err =  "<b>"._T('avis_erreur_mysql')." $errno</b><br /><tt>\n"
		. htmlspecialchars($msg)
		. "\n<br /><span style='color: red'><b>"
		. htmlspecialchars($query)
		. "</b></span></tt><br />"
		;//. aide('erreur_mysql');

	return $err;
}

// http://doc.spip.org/@trouve_boucle_debug
function trouve_boucle_debug($n, $nom, $debut=0, $boucle = "")
{
	global $debug_objets;

	$id = $nom . $boucle;
	if (is_array($debug_objets['sequence'][$id])) {
	 foreach($debug_objets['sequence'][$id] as $v) {

	  if (!preg_match('/^(.*)(<\?.*\?>)(.*)$/s', $v[0],$r))
	    $y = substr_count($v[0], "\n");
	  else {
	    if ($v[1][0] == '#')
	      // balise dynamique
	      $incl = $debug_objets['resultat'][$v[2]];
	    else
	      // inclusion
	      $incl = $debug_objets['squelette'][trouve_squelette_inclus($v[0])];
	    $y = substr_count($incl, "\n")
	      + substr_count($r[1], "\n") 
	      + substr_count($r[3], "\n");
	  }
	  if ($n <= ($y + $debut)) {
	    if ($v[1][0] == '?')
	      return trouve_boucle_debug($n, $nom, $debut, substr($v[1],1));
 	    elseif ($v[1][0] == '!') {
	      if ($incl = trouve_squelette_inclus($v[1]))
		return trouve_boucle_debug($n, $incl, $debut);
	    }
	    return array($nom, $boucle, $v[2] -1 + $n - $debut );
	  }
	  $debut += $y;
	 }
	}
	return array($nom, $boucle, $n-$debut);
}	  

// http://doc.spip.org/@trouve_squelette_inclus
function trouve_squelette_inclus($script)
{
  global $debug_objets;
  preg_match('/include\(.(.*).php3?.\);/', $script, $reg);
  // si le script X.php n'est pas ecrire/public.php
  // on suppose qu'il prend le squelette X.html (pas sur, mais y a pas mieux)
  if ($reg[1] == 'ecrire/public')
    // si c'est bien ecrire/public on cherche le param 'fond'
    if (!preg_match("/'fond' => '([^']*)'/", $script, $reg))
      // a defaut on cherche le param 'page'
      if (!preg_match("/'param' => '([^']*)'/", $script, $reg))
	$reg[1] = "inconnu";
  $incl = $reg[1] . '.' .  _EXTENSION_SQUELETTES . '$';

  foreach($debug_objets['sourcefile'] as $k => $v) {
    if (preg_match(",$incl,",$v)) return $k;
  }
  return "";
}

// http://doc.spip.org/@reference_boucle_debug
function reference_boucle_debug($n, $nom, $self)
{
  list($skel, $boucle, $ligne) = trouve_boucle_debug($n, $nom);

  if (!$boucle)
    return !$ligne ? "" :  
      (" (" .
       (($nom != $skel) ? _T('squelette_inclus_ligne') :
	_T('squelette_ligne')) .
	" <a href='$self&amp;var_mode_objet=$skel&amp;var_mode_affiche=squelette&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)");
  else {
  $self .= "&amp;var_mode_objet=$skel$boucle&amp;var_mode_affiche=boucle";

    return !$ligne ? " (boucle\n<a href='$self#$skel$boucle'>$boucle</a>)" :
      " (boucle $boucle ligne\n<a href='$self&amp;var_mode_ligne=$ligne#L$ligne'>$ligne</a>)";
  }
}

// affiche un texte avec numero de ligne et ancre.

// http://doc.spip.org/@ancre_texte
function ancre_texte($texte, $fautifs=array(), $nocpt=false)
{
	$var_mode_ligne = _request('var_mode_ligne');
	if ($var_mode_ligne) $fautifs[]= array($var_mode_ligne);
	$res ='';

	$s = highlight_string(str_replace('</script>','</@@@@@>',$texte),true);

	$s = str_replace('/@@@@@','/script', // bug de highlight_string
		str_replace('</font>','</span>',
			str_replace('<font color="','<span style="color: ', $s)));
	if (substr($s,0,6) == '<code>') { $s=substr($s,6); $res = '<code>';}

	$s = preg_replace(',<(\w[^<>]*)>([^<]*)<br />([^<]*)</\1>,',
			  '<\1>\2</\1><br />' . "\n" . '<\1>\3</\1>',
			  $s);


	$tableau = explode("<br />", $s);

	$format = "<span style='float:left;display:block;width:50px;height:1px'><a id='L%d' style='background-color: white; visibility: " . ($nocpt ? 'hidden' : 'visible') . ";%s' href='#T%s' title=\"%s\">%0" . strval(@strlen(count($tableau))). "d</a></span> %s<br />\n";

	$format10=str_replace('white','lightgrey',$format);
	$formaterr="color: red;";
	$i=1;
	$flignes = array();
	$loc = array(0,0);
	foreach ($fautifs as $lc)
	  if (is_array($lc)) {
	    $l = array_shift($lc);
	    $flignes[$l] = $lc;
	  } else $flignes[$lc] = $loc;

	$ancre = md5($texte);
	foreach ($tableau as $ligne) {
	  if (isset($flignes[$i])) {
	    $ligne = str_replace('&nbsp;',' ', $ligne);
	    $indexmesg = $flignes[$i][1];
	    $err = textebrut($flignes[$i][2]);
	    // tentative de pointer sur la colonne fautive;
	    // marche pas car highlight_string rajoute des entites. A revoir.
	    // $m = $flignes[$i][0];
	    //  $ligne = substr($ligne, 0, $m-1) .
	    //  sprintf($formaterr, substr($ligne,$m));
	    $bg = $formaterr; 
	  } else {$indexmesg = $ancre; $err= $bg='';}
	  $res .= sprintf((($i%10) ? $format :$format10), $i, $bg, $indexmesg, $err, $i, $ligne);
	  $i++;
	}

	return "<div id='T$ancre'>"
	.'<div onclick="javascript:'
	  . "\$(this).parent().find('a').toggle();"
	  . '" title="'
	  . _T('masquer_colonne')
	  . '" >'
	  . ($nocpt ? '' : _T('info_numero_abbreviation'))
	  . "</div>
	".$res."</div>\n";
}

// l'environnement graphique du debuggueur 

function debusquer_squelette ($fonc, $mode, $self) {
	global $debug_objets;

	if ($mode !== 'validation') {
		if ($debug_objets['sourcefile']) {
			$res = "<div id='spip-boucles'>\n" 
			. debusquer_navigation_squelettes($self)
			. "</div>";
		} else $res = '';
		if ($fonc) {
			$id = " id='$fonc'";
			if (!empty($GLOBALS['debug_objets'][$mode][$fonc])) {
				list($legend, $texte, $res2) = debusquer_source($fonc, $mode);
				$texte .= $res2;
			} elseif (!empty($debug_objets[$mode][$fonc . 'tout'])) {
			  $legend = _T('zbug_' . $mode);
			  $texte = $debug_objets[$mode][$fonc . 'tout'];
			  $texte = ancre_texte($texte, array('',''));
			}
		} else return strlen(trim($res))
				? "<div id='spip-debug'>$res</div>"
			 // cas de l'appel sur erreur: montre la page
				: $GLOBALS['debug_objets']['resultat']['tout'];
	} else {
		$valider = charger_fonction('valider', 'xml');
		$val = $valider($debug_objets['validation'][$fonc . 'tout']);
		// Si erreur, signaler leur nombre dans le formulaire admin
		$debug_objets['validation'] = $val[1] ? count($val[1]):'';
		list($texte, $err) = emboite_texte($val, $fonc, $self);
		if ($err === false)
			$err = _T('impossible');
		elseif ($err === true)
		  $err = _T('correcte');
		else $err = ": $err";
		$legend = _T('validation') . ' ' . $err;
		$res = $id = '';
	}
	return !trim($texte) ? '' : (
		"<div id='spip-debug'>$res"
		. "<div id='debug_boucle'><fieldset$id><legend>"
		. $legend
		. "</legend>"
		. $texte
		. "</fieldset></div>"
		. "</div>");
}

function debusquer_navigation_squelettes($self)
{
	global $debug_objets, $spip_lang_right;

	$res = '';
	$boucles = !empty($debug_objets['boucle']) ? $debug_objets['boucle']:'';
	$contexte = $debug_objets['contexte'];
	$t_skel = _T('squelette');
	foreach ($debug_objets['sourcefile'] as $nom => $sourcefile) {
		$self2 = parametre_url($self,'var_mode_objet', $nom);
		$nav = !$boucles ? '' : debusquer_navigation_boucles($boucles, $nom, $self);
		$temps = !isset($debug_objets['profile'][$sourcefile]) ? '' : _T('zbug_profile', array('time'=>$debug_objets['profile'][$sourcefile]));

		$res .= "<fieldset><legend>"
		. $t_skel
		. ' ' 
		. $sourcefile
		."&nbsp;:\n<a href='$self2&amp;var_mode_affiche=squelette#$nom'>"
		. $t_skel
		. "</a>\n<a href='$self2&amp;var_mode_affiche=resultat#$nom'>"
		. _T('zbug_resultat')
		. "</a>\n<a href='$self2&amp;var_mode_affiche=code#$nom'>"
		. _T('zbug_code')
		."</a>\n<a href='"
		. str_replace('var_mode=debug', 'var_profile=1&amp;var_mode=recalcul', $self)
		. "'>"
		.  _T('zbug_calcul')
		. "</a></legend>"
		. (!$temps ? '' : ("\n<span style='display:block;float:$spip_lang_right'>$temps</span><br />"))
		. debusquer_contexte($contexte[$sourcefile])
		. (!$nav ? '' : ("<table width='100%'>\n$nav</table>\n"))
		. "</fieldset>\n";
	}
	return $res;
}

function debusquer_navigation_boucles($boucles, $nom_skel, $self)
{
	$i = 0;
	$res = '';
	$var_mode_objet = _request('var_mode_objet');
	foreach ($boucles as $objet => $boucle) {
		if (substr($objet, 0, strlen($nom_skel)) == $nom_skel) {
			$i++;
			$nom = $boucle->id_boucle;
			$req = $boucle->type_requete;
			$crit = decompiler_criteres($boucle->param, $boucle->criteres);
			$self2 = $self .  "&amp;var_mode_objet=" .  $objet;

			$res .= "\n<tr style='background-color: " .
			  ($i%2 ? '#e0e0f0' : '#f8f8ff') .
			  "'><td  align='right'>$i</td><td>\n" .
			  "<a  class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=boucle#$nom_skel$nom'>" .
			  _T('zbug_boucle') .
			  "</a></td><td>\n<a class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=resultat#$nom_skel$nom'>" .
			  _T('zbug_resultat') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=code#$nom_skel$nom'>" .
			  _T('zbug_code') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  str_replace('var_mode=','var_profile=', $self2) .
			  "'>" .
			  _T('zbug_calcul') .
			  "</a></td><td>\n" .
			  (($var_mode_objet == $objet) ? "<b>$nom</b>" : $nom) .
			  "</td><td>\n" .
			  $req .
			  "</td><td>\n" .
			  $crit .
			  "</td></tr>";
		}
	}
	return $res;
}

function debusquer_source($objet, $affiche)
{
	$quoi = $GLOBALS['debug_objets'][$affiche][$objet];
	$nom =  $GLOBALS['debug_objets']['boucle'][$objet]->id_boucle;
	$res2 = "";

	if ($affiche == 'resultat') {
		$legend = $nom;
		$req = $GLOBALS['debug_objets']['requete'][$objet];
		if (function_exists('traite_query')) {
		  $c = strtolower(_request('connect'));
		  $c = $GLOBALS['connexions'][$c ? $c : 0]['prefixe'];
		  $req = traite_query($req,'', $c);
		}
		//  permettre le copier/coller facile
		// $res = ancre_texte($req, array(), true);
		$res = "<div id='T".md5($req)."'>\n<pre>\n" . $req . "</pre>\n</div>\n";
		//  formatage et affichage des resultats bruts de la requete
		$ress_req = spip_query($req);
		$brut_sql = '';
		$num = 1;
		//  eviter l'affichage de milliers de lignes
		//  personnalisation possible dans mes_options
		$max_aff = defined('_MAX_DEBUG_AFF') ? _MAX_DEBUG_AFF : 50;
		while ($retours_sql = sql_fetch($ress_req)) {
			if ($num <= $max_aff) {
				$brut_sql .= "<h3>" .($num == 1 ? $num. " sur " .sql_count($ress_req):$num). "</h3>";
				$brut_sql .= "<p>";
				foreach ($retours_sql as $key => $val) {
					$brut_sql .= "<strong>" .$key. "</strong> => " .htmlspecialchars(couper($val, 150)). "<br />\n";
				}
				$brut_sql .= "</p>";
			}
			$num++;
		}
		$res2 = interdire_scripts($brut_sql);
		foreach ($quoi as $view) {
			//  ne pas afficher les $contexte_inclus
			$view = preg_replace(",<\?php.+\?[>],Uims", "", $view);
			if ($view) {
				$res2 .= "\n<br /><fieldset>" .interdire_scripts($view). "</fieldset>";
			}
		}

	} else if ($affiche == 'code') {
		$legend = $nom;
		$res = ancre_texte("<"."?php\n".$quoi."\n?".">");
	} else if ($affiche == 'boucle') {
		$legend = _T('boucle') . ' ' .  $nom;
		$res = ancre_texte(decompiler_boucle($quoi));
	} else if ($affiche == 'squelette') {
		$legend = $GLOBALS['debug_objets']['sourcefile'][$objet];
		$res = ancre_texte($GLOBALS['debug_objets']['squelette'][$objet]);
	}

	return array($legend, $res, $res2);
}

// http://doc.spip.org/@debusquer_entete
function debusquer_entete($titre, $corps)
{
	global $debug_objets;
	include_spip('balise/formulaire_admin');
	include_spip('public/assembler'); // pour inclure_balise_dynamique
	include_spip('inc/texte'); // pour corriger_typo

	return _DOCTYPE_ECRIRE .
	  html_lang_attributes() .
	  "<head>\n<title>" .
	  ('SPIP ' . $GLOBALS['spip_version_affichee'] . ' ' .
	   _T('admin_debug') . ' ' . $titre . ' (' .
	   supprimer_tags(corriger_typo($GLOBALS['meta']['nom_site']))) . 
	  ")</title>\n" .
	  "<meta http-equiv='Content-Type' content='text/html" .
	  (($c = $GLOBALS['meta']['charset']) ? "; charset=$c" : '') .
	  "' />\n" .
	  http_script('', 'jquery.js')
	  . "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	  . "' type='text/css' />" .
	  "</head>\n" .
	  "<body style='margin:0 10px;'>\n" .
	  "<div id='spip-debug' style='position: absolute; top: 22px; z-index: 1000;height:97%;left:10px;right:10px;'>" .
	  $corps .
	  inclure_balise_dynamique(balise_FORMULAIRE_ADMIN_dyn('spip-admin-float', $debug_objets), false) .
	  '</div></body></html>';
}

?>
