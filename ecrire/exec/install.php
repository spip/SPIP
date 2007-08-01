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

include_spip('inc/minipres');

define("_ECRIRE_INSTALL", "1");
define('_FILE_TMP', '_install');

// http://doc.spip.org/@exec_install_dist
function exec_install_dist()
{
	$etape = _request('etape');
	if (_FILE_CONNECT AND ($etape != 'chmod')) {
		echo minipres();
		exit;
	}

	// On va supprimer les eventuelles vieilles valeurs de meta,
	// on perd l'init des langues, mais elle est refaite par menu_langue
	@unlink(_FILE_META);
	$GLOBALS['meta'] = array();
	include_spip('base/create');
	$fonc = charger_fonction("etape_$etape", 'install');
	$fonc();
}

//  Pour ecrire les fichiers memorisant les parametres de connexion

// http://doc.spip.org/@install_fichier_connexion
function install_fichier_connexion($nom, $texte)
{
	$texte = "<"."?php\n"
	. "if (!defined(\"_ECRIRE_INC_VERSION\")) return;\n"
	. $texte 
	. "?".">";

	ecrire_fichier($nom, $texte);
}

//
// Verifier que l'hebergement est compatible SPIP ... ou l'inverse :-)
// (sert a l'etape 1 de l'installation)
// http://doc.spip.org/@tester_compatibilite_hebergement
function tester_compatibilite_hebergement() {
	$err = array();

	$p = phpversion();
	if (preg_match(',^([0-9]+)\.([0-9]+)\.([0-9]+),', $p, $regs)) {
		$php = array($regs[1], $regs[2], $regs[3]);
		$m = '4.0.8';
		$min = explode('.', $m);
		if ($php[0]<$min[0]
		OR ($php[0]==$min[0] AND $php[1]<$min[1])
		OR ($php[0]==$min[0] AND $php[1]==$min[1] AND $php[2]<$min[2]))
			$err[] = _T('install_php_version', array('version' => $p,  'minimum' => $m));
	}

	if (!function_exists('mysql_query'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/mysql'>MYSQL</a>";

	if (!function_exists('preg_match_all'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/pcre'>PCRE</a>";

	if ($a = @ini_get('mbstring.func_overload'))
		$err[] = _T('install_extension_mbstring')
		. "mbstring.func_overload=$a - <a href='http://se.php.net/mb_string'>mb_string</a>.<br /><small>";

	if ($err) {
			echo "<p class='verdana1 spip_large'><b>"._T('avis_attention').'</b></p><p>'._T('install_echec_annonce')."</p><ul>";
		while (list(,$e) = each ($err))
			echo "<li>$e</li>\n";

		# a priori ici on pourrait die(), mais il faut laisser la possibilite
		# de forcer malgre tout (pour tester, ou si bug de detection)
		echo "</ul><hr />\n";
	}
}


// Une fonction pour faciliter la recherche du login (superflu ?)
// http://doc.spip.org/@login_hebergeur
function login_hebergeur() {
	global $HTTP_X_HOST, $REQUEST_URI, $SERVER_NAME, $HTTP_HOST;

	$base_hebergeur = 'localhost'; # par defaut

	// Lycos (ex-Multimachin)
	if ($HTTP_X_HOST == 'membres.lycos.fr') {
		preg_match(',^/([^/]*),', $REQUEST_URI, $regs);
		$login_hebergeur = $regs[1];
	}
	// Altern
	else if (preg_match(',altern\.com$,', $SERVER_NAME)) {
		preg_match(',([^.]*\.[^.]*)$,', $HTTP_HOST, $regs);
		$login_hebergeur = preg_replace('[^\w\d]', '_', $regs[1]);
	}
	// Free
	else if (preg_match(',(.*)\.free\.fr$,', $SERVER_NAME, $regs)) {
		$base_hebergeur = 'sql.free.fr';
		$login_hebergeur = $regs[1];
	} else $login_hebergeur = '';

	return array($base_hebergeur, $login_hebergeur);
}


// http://doc.spip.org/@info_etape
function info_etape($titre, $complement = ''){
	return "<h2>".$titre."</h2>\n" .
	($complement ? "<br />".$complement."\n":'');
}

// http://doc.spip.org/@bouton_suivant
function bouton_suivant($code = '') {
	if($code=='') $code = _T('bouton_suivant');
	static $suivant = 0;
	$id = 'suivant'.(($suivant>0)?strval($suivant):'');
	$suivant +=1;
	return "\n<span class='suivant'><input id='".$id."' type='submit' class='fondl'\nvalue=\"" .
		$code .
		" >>\" /></span>\n";
}

// http://doc.spip.org/@info_progression_etape
function info_progression_etape($en_cours,$phase,$dir){
	//$en_cours = _request('etape')?_request('etape'):"";
	$liste = find_all_in_path($dir,$phase.'(([0-9])+|fin)[.]php$');
	$debut = 1; $etat = "ok";
	$last = count($liste);
	
	$aff_etapes = "<span id='etapes'>";
	foreach($liste as $etape=>$fichier){
		if ($etape=="$phase$en_cours.php"){
			$etat = "encours";
		}
		$aff_etapes .= ($debut<$last)
			? "<span class='$etat'><em>$debut</em><span>,</span> </span>"
			: '';
		if ($etat == "encours")
			$etat = 'todo';
		$debut++;
	}
	$aff_etapes .= "<br class='nettoyeur' />&nbsp;</span>\n";
	return $aff_etapes;
}


// http://doc.spip.org/@fieldset
function fieldset($legend, $champs = array(), $horchamps='') {
	$fieldset = "<fieldset>\n" .
	($legend ? "<legend>".$legend."</legend>\n" : '');
	foreach ($champs as $nom => $contenu) {
		$type = isset($contenu['hidden']) ? 'hidden' : (preg_match(',^pass,', $nom) ? 'password' : 'text');
		$class = isset($contenu['hidden']) ? '' : "class='formo' size='40' ";
		if(isset($contenu['alternatives'])) {
			$fieldset .= $contenu['label'] ."\n";
			foreach($contenu['alternatives'] as $valeur => $label) {
				$fieldset .= "<input type='radio' name='".$nom .
				"' id='$nom-$valeur' value='$valeur'"
				  .(($valeur==$contenu['valeur'])?"\nchecked='checked'":'')."/>\n";
				$fieldset .= "<label for='$nom-$valeur'>".$label."</label>\n";
			}
			$fieldset .= "<br />\n";
		}
		else {
			$fieldset .= "<label for='".$nom."'>".$contenu['label']."</label>\n";
			$fieldset .= "<input ".$class."type='".$type."' id='" . $nom . "' name='".$nom."'\nvalue='".$contenu['valeur']."' />\n";
		}
	}
	$fieldset .= "$horchamps</fieldset>\n";
	return $fieldset;
}

?>
