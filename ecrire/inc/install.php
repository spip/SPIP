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

// Attention etape_ldap4 suppose qu'il n'y aura qu'un seul appel de fonction
// dans le fichier produit.

// http://doc.spip.org/@install_connexion
function install_connexion($adr, $port, $login, $pass, $base, $type, $pref, $ldap='')
{
	$adr = addcslashes($adr,"'\\");
	$port = addcslashes($port,"'\\");
	$login = addcslashes($login,"'\\");
	$pass = addcslashes($pass,"'\\");
	$base = addcslashes($base,"'\\");
	$type = addcslashes($type,"'\\");
	$pref = addcslashes($pref,"'\\");
	$ldap = addcslashes($ldap,"'\\");
	return "\$GLOBALS['spip_connect_version'] = 0.7;\n"
	. "spip_connect_db("
	. "'$adr','$port','$login','$pass','$base'"
	. ",'$type', '$pref','$ldap');\n";
}

// Analyse si un fichier contient le resultat de la fonction install_connexion
// y compris ce qu'elle produisait dans les versions precedentes

// http://doc.spip.org/@analyse_fichier_connection
function analyse_fichier_connection($file)
{
	$s = @join('', file($file));
	if (preg_match("#mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)#", $s, $regs)) {
		array_shift($regs);
		return $regs;
	} else {
		$arg = '\s*\'([^\']*)\'\s*,';
		if (preg_match("#spip_connect_db\($arg$arg$arg$arg\s*'([^']*)'\s*(?:,\s*'([^']*))?#", $s, $regs)) {
			$regs[2] = $regs[1] . (!$regs[2] ? '' : ":$port_db;");
			array_shift($regs);
			array_shift($regs);
			return $regs;
		}
	}
	spip_log("$file n'est pas un fichier de connexion");
	return '';
}

// http://doc.spip.org/@bases_referencees
function bases_referencees($exclu='')
{
	$tables = array();
	foreach(preg_files(_DIR_CONNECT, '.php$') as $f) {
		if ($f != $exclu AND analyse_fichier_connection($f))
			$tables[]= basename($f, '.php');
	}
	return $tables;
}


function install_mode_appel($server_db, $tout=true)
{
	return ($server_db != 'mysql') ? ''
	: (($tout  ? test_rappel_nom_base_mysql($server_db) : '')
		. test_sql_mode_mysql($server_db)	);
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

	// Il faut une base de donnees tout de meme ...
	if (!function_exists('mysql_query')
	AND !function_exists('pg_connect')
	AND !function_exists('sqlite_open'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://www.php.net/mysql'>MYSQL</a>"
		. "| <a href='http://www.php.net/pgsql'>PostgreSQL</a>"
		. "| <a href='http://www.php.net/sqlite'>SQLite</a>";

	// et il faut preg
	if (!function_exists('preg_match_all'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/pcre'>PCRE</a>";

	// et surtout pas ce mbstring.overload
	if ($a = @ini_get('mbstring.func_overload'))
		$err[] = _T('install_extension_mbstring')
		. "mbstring.func_overload=$a - <a href='http://www.php.net/mb_string'>mb_string</a>.<br /><small>";

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
	return "\n<span class='suivant'><input id='".$id."' type='submit'\nvalue=\"" .
		$code .
		" >>\" /></span>\n";
}

// http://doc.spip.org/@info_progression_etape
function info_progression_etape($en_cours,$phase,$dir, $erreur = false){
	//$en_cours = _request('etape')?_request('etape'):"";
	$liste = find_all_in_path($dir,$phase.'(([0-9])+|fin)[.]php$');
	$debut = 1; $etat = "ok";
	$last = count($liste);
//	$texte_etat = array('ok'=>'OK','encours'=>_T('en_cours'),'todo'=>_T('todo'));

	$intitule_etat["etape_"][1] = typo(_T('info_connexion_base_donnee'));
	$intitule_etat["etape_"][2] = typo(_T('menu_aide_installation_choix_base'));
	$intitule_etat["etape_"][3] = typo(_T('info_informations_personnelles'));
	$intitule_etat["etape_"][4] = typo(_T('info_derniere_etape'));

	$intitule_etat["etape_ldap"][1] = typo(_T('titre_connexion_ldap'));
	$intitule_etat["etape_ldap"][2] = typo(_T('titre_connexion_ldap'));
	$intitule_etat["etape_ldap"][3] = typo(_T('info_chemin_acces_1'));
	$intitule_etat["etape_ldap"][4] = typo(_T('info_reglage_ldap'));
	$intitule_etat["etape_ldap"][5] = typo(_T('info_ldap_ok'));

//	$aff_etapes = "<span id='etapes'>";

	$aff_etapes = "<ul id='infos_etapes' class='infos_$phase$en_cours'>";

	foreach($liste as $etape=>$fichier){
		if ($debut < $last) {
			if ($debut == $en_cours && $erreur) $class = "on erreur";
			else if ($debut == $en_cours) $class = "on";
			else if ($debut > $en_cours) $class = "prochains";
			else $class = "valides";

			$aff_etapes .= "<li class='$class'><div class='fond'>";
			$aff_etapes .= ($debut == $en_cours)?"<strong>":'';
			$aff_etapes .= "<em>"._T('etape')." </em><span class='numero_etape'>$debut</span><em>&nbsp;: </em>";
			$aff_etapes .= $intitule_etat["$phase"][$debut];
			$aff_etapes .= ($debut == $en_cours)?"</strong>":'';
			$aff_etapes .= "</div></li>";
		}
		$debut++;
	}
	$aff_etapes .= "</ul>";
	$aff_etapes .= "<br class='nettoyeur' />\n";
	return $aff_etapes;
}


// http://doc.spip.org/@fieldset
function fieldset($legend,  $champs = array(), $apres='', $avant='') {
	return "<fieldset>\n" .
	  $avant .
	  ($legend ? "<legend>".$legend."</legend>\n" : '') .
	  fieldset_champs($champs) .
	  $apres .
	  "</fieldset>\n";
}

function fieldset_champs($champs = array())
  {
	$fieldset = '';
	foreach ($champs as $nom => $contenu) {
		$type = isset($contenu['hidden']) ? 'hidden' : (preg_match(',^pass,', $nom) ? 'password' : 'text');
		$class = isset($contenu['hidden']) ? '' : "class='formo' size='40' ";
		if(isset($contenu['alternatives'])) {
			$fieldset .= $contenu['label'] ."\n";
			foreach($contenu['alternatives'] as $valeur => $label) {
				$fieldset .= "<input type='radio' name='".$nom .
				"' id='$nom-$valeur' value='$valeur'"
				  .(($valeur==$contenu['valeur'])?"\nchecked='checked'":'')
				  ."/>\n";
				$fieldset .= "<label for='$nom-$valeur'>".$label."</label>\n";
			}
			$fieldset .= "<br />\n";
		}
		else {
			$fieldset .= "<label for='".$nom."'>".$contenu['label']."</label>\n";
			$fieldset .= "<input ".$class."type='".$type."' id='" . $nom . "' name='".$nom."'\nvalue='".$contenu['valeur']."'"
							  .(preg_match(',^(pass|login),', $nom)?" autocomplete='off'":'') .	" />\n";
		}
	}
	return $fieldset;
}

function install_select_serveur()
{
	$options = array();
	$dir = _DIR_RESTREINT . 'req/';
	$d = @opendir($dir);
	if (!$d) return array();
	while ($f = readdir($d)) {
		if ((preg_match('/^(.*)[.]php$/', $f, $s))
		AND is_readable($f = $dir . $f)) {
			require_once($f);
			$s = $s[1];
			$v = 'spip_versions_' . $s;
			if (function_exists($v) AND $v()) {
			  $titre = _T("install_select_type_$s");
			  $options[$s] =  "<option value='$s'>"
			    . ($titre ? $titre : $s)
			    ."</option>";
			} else spip_log("$s: portage indisponible");
		}
	}
	sort($options);
	return $options;
}

// http://doc.spip.org/@install_connexion_form
function install_connexion_form($db, $login, $pass, $predef, $hidden, $etape)
{
	$server_db = (is_string($predef[0])) ? $predef[0] : '';

	return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='$etape' />"
	. $hidden
	. (_request('echec')?
			("<p><b>"._T('avis_connexion_echec_1').
			"</b></p><p>"._T('avis_connexion_echec_2')."</p><p style='font-size: small;'>"._T('avis_connexion_echec_3')."</p>")
			:"")

	. http_script('',  'jquery.js')
	. http_script('
		$(document).ready(function() {
			$("input[type=hidden][name=server_db]").each(function(){
				if ($(this).attr("value").match("sqlite*")){
					$("#install_adresse_base_hebergeur").hide();
					$("#install_login_base_hebergeur").hide();
					$("#install_pass_base_hebergeur").hide();
				}
			});
			$("#sql_serveur_db").change(function(){
				if ($(this).find("option:selected").attr("value").match("sqlite*")){
					$("#install_adresse_base_hebergeur").hide();
					$("#install_login_base_hebergeur").hide();
					$("#install_pass_base_hebergeur").hide();
				} else {
					$("#install_adresse_base_hebergeur").show();
					$("#install_login_base_hebergeur").show();
					$("#install_pass_base_hebergeur").show();
				}
			});
		});')

	. ($server_db
		? '<input type="hidden" name="server_db" value="'.$server_db.'" />'
			. (($predef[0])
			   ?('<h3>'._T('install_serveur_hebergeur').'</h3>')
				:'')
		: ('<fieldset><legend>'
		   ._T('install_select_type_db')
		. "</legend>"
			.'<label for="sql_serveur_db">'
			. _T('install_types_db_connus')
			// Passer l'avertissement SQLIte en  commentaire, on pourra facilement le supprimer par la suite sans changer les traductions.
			. "<br /><small>(". _T('install_types_db_connus_avertissement') .')</small>'
			.'</label>'
		. "\n<div style='text-align: center;'><select name='server_db' id='sql_serveur_db' >\n"
		.   join("\n", install_select_serveur())
		. "\n</select></div></fieldset>")
	)
	. '<div id="install_adresse_base_hebergeur">'
	. ($predef[1]
	? '<h3>'._T('install_adresse_base_hebergeur').'</h3>'
	: fieldset(_T('entree_base_donnee_1'),
		array(
			'adresse_db' => array(
				'label' => $db[1],
				'valeur' => $db[0]
			),
		)
	)
	)
	. '</div>'

	. '<div id="install_login_base_hebergeur">'
	. ($predef[2]
	? '<h3>'._T('install_login_base_hebergeur').'</h3>'
	: fieldset(_T('entree_login_connexion_1'),
		array(
			'login_db' => array(
					'label' => $login[1],
					'valeur' => $login[0]
			),
		)
	)
	)
	. '</div>'

	. '<div id="install_pass_base_hebergeur">'
	. ($predef[3]
	? '<h3>'._T('install_pass_base_hebergeur').'</h3>'
	: fieldset(_T('entree_mot_passe_1'),
		array(
			'pass_db' => array(
				'label' => $pass[1],
				'valeur' => $pass[0]
			),
		)
	)
	)
	. '</div>'

	. bouton_suivant()));

}

// 4 valeurs qu'on reconduit d'un script a l'autre
// sauf s'ils sont predefinis.

// http://doc.spip.org/@predef_ou_cache
function predef_ou_cache($adresse_db, $login_db, $pass_db, $server_db)
{
	return ((defined('_INSTALL_HOST_DB'))
		? ''
		: "\n<input type='hidden' name='adresse_db'  value=\"".htmlspecialchars($adresse_db)."\" />"
	)
	. ((defined('_INSTALL_USER_DB'))
		? ''
		: "\n<input type='hidden' name='login_db' value=\"".htmlspecialchars($login_db)."\" />"
	)
	. ((defined('_INSTALL_PASS_DB'))
		? ''
		: "\n<input type='hidden' name='pass_db' value=\"".htmlspecialchars($pass_db)."\" />"
	)

	. ((defined('_INSTALL_SERVER_DB'))
		? ''
		: "\n<input type='hidden' name='server_db' value=\"".htmlspecialchars($server_db)."\" />"
	   );
}

// presentation des bases existantes

// http://doc.spip.org/@install_etape_liste_bases
function install_etape_liste_bases($server_db, $login_db, $disabled=array())
{
	$result = sql_listdbs($server_db);
	if (!$result) return '';

	$bases = $checked = $noms = array();

	// si sqlite : result est deja un tableau
	if (is_array($result)){
		$noms = $result;
	} else {
		while ($row = sql_fetch($result, $server_db)) {
			$noms[] = array_shift($row);
		}
	}
	foreach ($noms as $nom){
		$id = htmlspecialchars($nom);
		$dis = in_array($nom, $disabled) ? " disabled='disabled'" : '';
		$base = " name=\"choix_db\" value=\""
		  . $nom
		  . '"'
		  . $dis
		  . " type='radio' id='$id'";
		$label = "<label for='$id'>"
		. ($dis ? "<i>$nom</i>" : $nom)
		. "</label>";

		if (!$checked AND !$dis AND
		    (($nom == $login_db) OR
			($GLOBALS['table_prefix'] == $nom))) {
			$checked = "<input$base checked='checked' />\n$label";
		} else {
			$bases[]= "<input$base />\n$label";
		}
	}

	if (!$bases && !$checked) return false;

	if ($checked) {array_unshift($bases, $checked); $checked = true;}

	return array($checked, $bases);
}

function install_propager($hidden)
{
	$res = '';
	foreach($hidden as $k) {
		$v = htmlentities(_request($k));
		$res .= "<input type='hidden' name='$k' value='$v' />";
	}
	return $res;
}
?>
