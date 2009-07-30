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
define('_DEBUG_MAX_SQUELETTE_ERREURS', 4);

//
// Point d'entree general, 
// pour les appels involontaires ($message non vide => erreur)
// et volontaires.
//

function public_debusquer_dist($message='', $lieu='', $quoi='') {
	global $tableau_des_erreurs;

	if ($message) {
		if (is_array($message)) list($message, $lieu) = $message;
		elseif ($quoi) $message = debusquer_requete($message, $lieu, $quoi);
		elseif (is_object($lieu))
			$lieu = _T('squelette') 
			. ' <b> ' . $lieu->descr['sourcefile'] . '</b> '
			. (!$lieu->id_boucle ? '' :
				(' ' . _T('zbug_boucle') . ' <b>' . $lieu->id_boucle . '</b>'))
			. ' ' . _T('ligne') . ' <b>' . $lieu->ligne . '</b>';
			       
		spip_log("Debug: $message | $lieu (" . $GLOBALS['fond'] .")" );
		$GLOBALS['bouton_admin_debug'] = true;
		$tableau_des_erreurs[] = array($message, $lieu);
		// Eviter les boucles infernales
		if (!_DEBUG_MAX_SQUELETTE_ERREURS OR count($tableau_des_erreurs) <= _DEBUG_MAX_SQUELETTE_ERREURS) return;
		$lieu = $quoi = '';
	}
	include_spip('inc/autoriser');
	if (autoriser('debug')) {
		if ($tableau_des_erreurs) $lieu = $quoi = '';
		debusquer_squelette($lieu, $quoi);
		exit;
	}
}

function debusquer_contexte($env) {

	if (is_array($env_tab = @unserialize($env)))
		$env = $env_tab;

	$env_texte="";
	if (count($env)>0) {
		$env_texte="<div class='spip-env'>"
			. "<fieldset><legend>#ENV</legend>\n"
			. "<div><table>\n";
		foreach ($env as $nom => $valeur) {
			$env_texte .= "\n<tr><td><strong>".nl2br(entites_html($nom))
				. "</strong></td>";
			if (is_array($valeur))
			  $valeur = '(' . count($valeur) .' items) [' . join(',', $valeur) . ']';
			$env_texte .= "<td>:&nbsp;".nl2br(entites_html($valeur))
				. "</td></tr>\n";
		}
		$env_texte .= "\n</table></div>\n";
		$env_texte .= "</fieldset></div>\n";
	}
	return $env_texte;
}

// Si le code php produit des erreurs, on les affiche en surimpression
// sauf pour un visiteur non admin (lui ne voit rien de special)
// et en mode validation (fausse erreur "double occurrence insert_head")
// ajouter &var_mode=debug pour voir les erreurs et en parler sur spip@rezo.net
// http://doc.spip.org/@affiche_erreurs_page
function affiche_erreurs_page($tableau_des_erreurs, $message='', $style='') {

	if (_request('exec')=='valider_xml' OR !$tableau_des_erreurs)
		return '';
	$GLOBALS['bouton_admin_debug'] = true;
	$res = '';
	$anc = '';
	$i = 1;
	foreach ($tableau_des_erreurs as $err) {
		$res .= "<tr id='req$i'><td style='text-align: right'><a href='".quote_amp($GLOBALS['REQUEST_URI'])."#spip-debug'><b>"
		  . $i
		  ."&nbsp;</b></a>\n</td><td>"
		  .join("</td>\n<td>",$err)
		  ."</td></tr>\n";

		$i++;
	}
	$cols = 1+count($err);
	if (_DIR_RESTREINT AND headers_sent())
		$style = "z-index: 1000; filter:alpha(opacity=95); -moz-opacity:0.9; opacity: 0.95;" 
		  . ($style ? $style : " position: absolute; top: 90px; left: 10px; width: 200px;");

	return "\n<table id='spip-debug' cellpadding='2'  border='1'
	style='text-align: left;$style'><tr><th style='text-align: center' colspan='$cols'>"
	. ($message ? $message : _T('zbug_erreur_squelette'))
## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),
	. "<p style='text-align: left'>$anc</p></th></tr>"
	. $res
	. "</table>";
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//

function debusquer_requete($query, $errno, $erreur) {

	if (preg_match(',err(no|code):?[[:space:]]*([0-9]+),i', $erreur, $regs))
	  {
		$errno = $regs[2];

	  } else if (($errno == 1030 OR $errno <= 1026)
		AND preg_match(',[^[:alnum:]]([0-9]+)[^[:alnum:]],', $erreur, $regs))
	$errno = $regs[1];

	// Erreur systeme
	if ($errno > 0 AND $errno < 200) {
		$retour .= "<tt><br /><br /><blink>"
		. _T('info_erreur_systeme', array('errsys'=>$errno))
		. "</blink><br />\n<b>"
		. _T('info_erreur_systeme2',
			array('script' => generer_url_ecrire('admin_repair'))) 
		. '</b><br />';
		spip_log("Erreur systeme $errno");
	}
	// Requete erronee
	else {
		$err =  "<b>"._T('avis_erreur_mysql')."</b><br /><tt>\n"
		. htmlspecialchars($query)
		. "\n<br /><span style='color: red'><b>"
		. htmlspecialchars($erreur)
		. "</b></span></tt><br />";
		
		if (isset($GLOBALS['debug']['aucasou'])) {
		  list($table, $id, $serveur) = $GLOBALS['debug']['aucasou'];
		  $err = _T('zbug_boucle') . " $id $serveur $table"
		    .   "<br />\n"
		    . $err;
		}
		$retour .=  $err . aide('erreur_mysql');
	}
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
// fin de course pour unhappy-few.

function debusquer_squelette ($texte, $fonc) {
	global $debug_objets ;

	// en cas de squelette inclus,  virer le code de l'incluant:
	// - il contient souvent une Div restreignant la largeur a 3 fois rien
	// - ca fait 2 headers !
	if (ob_get_length()) ob_end_clean();
	echo debusquer_entete($fonc ? $fonc : $debug_objets['principal']);
	echo "<body style='margin:0 10px;'>\n<div id='spip-debug' style='position: absolute; top: 22px; z-index: 1000;height:97%;left:10px;right:10px;'>";
	echo affiche_erreurs_page($GLOBALS['tableau_des_erreurs'], '', 'text-align: center;');
	$titre = _request('var_mode_affiche');
	$validation = ($titre == 'validation');
	$self = str_replace("\\'", '&#39;', self());
	$self = parametre_url($self,'var_mode', 'debug');

	if (!$validation) {
		echo "<div id='spip-boucles'>\n"; 
		echo debusquer_navigation($self);
		echo "</div>";
		echo debusquer_source(($fonc ? $fonc : $debug_objets['principal']), $debug_objets);
		if ($texte) {
				$err = "";
				$titre = 'zbug_' . $titre;
				$texte = ancre_texte($texte, array('',''));
		} 
	} else {
		$valider = charger_fonction('valider', 'xml');
		$res = $valider($debug_objets['validation'][$fonc . 'tout']);
		// Si erreur, signaler leur nombre dans le formulaire admin
		$debug_objets['validation'] = $res[1] ? count($res[1]):'';
		list($texte, $err) = emboite_texte($res, $fonc, $self);
		if ($err === false)
			$err = _T('impossible');
		elseif ($err === true)
		  $err = _T('correcte');
		else $err = ": $err";
	}

	if ($texte) {
		echo "<div id=\"debug_boucle\"><fieldset><legend>",
				_T($titre),	       
				' ',
				$err,
				"</legend>"; 
		echo $texte;
		echo "</fieldset></div>";
		echo "\n</div>";
	}
	include_spip('balise/formulaire_admin');
	echo inclure_balise_dynamique(balise_FORMULAIRE_ADMIN_dyn('spip-admin-float', $debug_objets));
	echo '</body></html>';
}

function debusquer_navigation($self)
{
	global $debug_objets, $spip_lang_right;

	$res = '';
	foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		$self2 = parametre_url($self,'var_mode_objet', $nom_skel);
		$res .= "<fieldset><legend>" ._T('squelette') . ' '  . $sourcefile ."&nbsp;: ";
		$res .= "\n<a href='$self2&amp;var_mode_affiche=squelette#$nom_skel'>"._T('squelette')."</a>";
		$res .= "\n<a href='$self2&amp;var_mode_affiche=resultat#$nom_skel'>"._T('zbug_resultat')."</a>";
		$res .= "\n<a href='$self2&amp;var_mode_affiche=code#$nom_skel'>"._T('zbug_code')."</a>";
		$res .= "\n<a href='" . 
		  str_replace('var_mode=','var_profile=', $self) . "'>" .
		  _T('zbug_calcul')."</a></legend>";
		$res .= "\n<span style='display:block;float:$spip_lang_right'>"._T('zbug_profile',array('time'=>isset($debug_objets['profile'][$sourcefile])?$debug_objets['profile'][$sourcefile]:0))."</span>";

		if (is_array($contexte = $debug_objets['contexte'][$sourcefile]))
			$res .= debusquer_contexte($contexte);

		if (isset($debug_objets['boucle']) AND is_array($debug_objets['boucle']))
			$res .= "<table width='100%'>\n" .
				debusquer_boucles($debug_objets['boucle'], $nom_skel, $self) .
				"</table>\n";
		$res .= "</fieldset>\n";
	}
	return $res;
}

function debusquer_boucles($boucles, $nom_skel, $self)
{
	$i = 0;
	$res = '';
	$var_mode_objet = _request('var_mode_objet');
	foreach ($boucles as $objet => $boucle) {
		if (substr($objet, 0, strlen($nom_skel)) == $nom_skel) {
			$i++;
			$nom = $boucle->id_boucle;
			$req = $boucle->type_requete;
			$self2 = $self .  "&amp;var_mode_objet=" .  $objet;

			$res .= "\n<tr style='background-color: " .
			  ($i%2 ? '#e0e0f0' : '#f8f8ff') .
			  "'><td  align='right'>$i</td><td>\n" .
			  "<a  class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=boucle#$nom_skel'>" .
			  _T('zbug_boucle') .
			  "</a></td><td>\n<a class='debug_link_boucle' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=resultat#$nom_skel'>" .
			  _T('zbug_resultat') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  $self2 .
			  "&amp;var_mode_affiche=code#$nom_skel'>" .
			  _T('zbug_code') .
			  "</a></td><td>\n<a class='debug_link_resultat' href='" .
			  str_replace('var_mode=','var_profile=', $self2) .
			  "'>" .
			  _T('zbug_calcul') .
			  "</a></td><td>\n" .
			  (($var_mode_objet == $objet) ? "<b>$nom</b>" : $nom) .
			  "</td><td>\n" .
			  $req .
			  "</td></tr>";
		}
	}
	return $res;
}

function debusquer_source($fonc, $tout)
{
	$objet = _request('var_mode_objet');
	$affiche = _request('var_mode_affiche');

	if (!$objet) {if ($affiche == 'squelette') $objet = $fonc;}
	if (!$objet OR !isset($tout[$affiche][$objet]) OR !$quoi = $tout[$affiche][$objet]) return '';

	$nom = $tout['boucle'][$objet]->id_boucle;

	if ($affiche == 'resultat') {
		$res = "<legend>" .$nom ."</legend>";
		$req = $tout['requete'][$objet];
		if (function_exists('traite_query')) {
		  $c = _request('connect');
		  $c = $GLOBALS['connexions'][$c ? $c : 0]['prefixe'];
		  $req = traite_query($req,'', $c);
		}
		$res .= ancre_texte($req, array(), true);
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
		$res .= interdire_scripts($brut_sql);
		foreach ($quoi as $view) {
			//  ne pas afficher les $contexte_inclus
			$view = preg_replace(",<\?php.+\?[>],Uims", "", $view);
			if ($view) {
				$res .= "\n<br /><fieldset>" .interdire_scripts($view). "</fieldset>";
			}
		}

	} else if ($affiche == 'code') {
		$res =  "<legend>" .$nom ."</legend>";
		$res .= ancre_texte("<"."?php\n".$quoi."\n?".">");
	} else if ($affiche == 'boucle') {
		$res =  "<legend>" . _T('boucle') . ' ' .  $nom ."</legend>"
		. ancre_texte(decompiler_boucle($quoi));
	} else if ($affiche == 'squelette') {
		$res .=  "<legend>" .$tout['sourcefile'][$objet] ."</legend>";
		$res .= ancre_texte($tout['squelette'][$objet]);
	}

	return "<div id='debug_boucle'><fieldset id='$fonc'>$res</fieldset></div>";
}

// http://doc.spip.org/@debusquer_entete
function debusquer_entete($titre, $erreurs='')
{
	global $visiteur_session;
	include_spip('inc/headers');
	include_spip('inc/filtres');
	if (!headers_sent()) {
		http_status(503);
		http_no_cache();
	}
	lang_select($visiteur_session['lang']);
	return _DOCTYPE_ECRIRE .
	  html_lang_attributes() .
	  "<head>\n<title>" .
	  ('SPIP ' . $GLOBALS['spip_version_affichee'] . ' ' .
	   _T('admin_debug') . ' ' . $titre . ' (' .
	   supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))) . 
	  ")</title>\n" .
	  "<meta http-equiv='Content-Type' content='text/html" .
	  (($c = $GLOBALS['meta']['charset']) ? "; charset=$c" : '') .
	  "' />\n" .
	  http_script('', 'jquery.js')
	  . "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	  . "' type='text/css' />" .
	  "</head>\n";
}

?>
