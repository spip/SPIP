<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'installation de SPIP
 *
 * @package SPIP\Core\Installation
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Écrit un fichier PHP nécessitant SPIP
 *
 * Écrit le texte transmis dans un fichier PHP. Cette fonction
 * ajoute les entêtes PHP et le test de sécurité vérifiant que SPIP
 * est chargé.
 *
 * @example
 *     ```
 *     install_fichier_connexion(_FILE_CONNECT_TMP, $contenu);
 *     ```
 *
 * @todo
 *     Renommer cette fonction qui peut servir à d'autres utilisations ?
 *
 * @param string $nom
 *     Chemin du fichier à créer
 * @param string $texte
 *     Code source du fichier (sans l'ouverture/fermeture PHP)
 * @return void
 **/
function install_fichier_connexion($nom, $texte) {
	$texte = '<' . "?php\n"
		. "if (!defined(\"_ECRIRE_INC_VERSION\")) return;\n"
		. $texte
		. '?' . '>';

	ecrire_fichier($nom, $texte);
}


/**
 * Retourne le code source d'un fichier de connexion à une base de données
 *
 * Le code est un appel à la fonction spip_connect_db()
 *
 * @see spip_connect_db()
 *
 * @internal
 *     Attention etape_ldap4 suppose qu'il n'y aura qu'un seul appel de fonction
 *     dans le fichier produit.
 *
 * @param string $adr Adresse de la base de données {@example 'localhost'}
 * @param string $port Numéro de port
 * @param string $login Login de connexion
 * @param string $pass Mot de passe de connexion
 * @param string $base Nom de la base de données
 * @param string $type Moteur SQL {@example 'sqlite3', 'mysql'}
 * @param string $pref Préfixe des tables {@example 'spip'}
 * @param string $ldap Type d'authentification (cas si 'ldap')
 * @param string $charset Charset de la connexion SQL
 * @return string
 *     Texte du fichier de connexion
 *
 **/
function install_connexion($adr, $port, $login, $pass, $base, $type, $pref, $ldap = '', $charset = '') {
	$adr = addcslashes($adr, "'\\");
	$port = addcslashes($port, "'\\");
	$login = addcslashes($login, "'\\");
	$pass = addcslashes($pass, "'\\");
	$base = addcslashes($base, "'\\");
	$type = addcslashes($type, "'\\");
	$pref = addcslashes($pref, "'\\");
	$ldap = addcslashes($ldap, "'\\");
	$charset = addcslashes($charset, "'\\");

	return "\$GLOBALS['spip_connect_version'] = 0.8;\n"
	. 'spip_connect_db('
	. "'$adr','$port','$login','$pass','$base'"
	. ",'$type', '$pref','$ldap','$charset');\n";
}


/**
 * Analyse un fichier de connexion à une base de données
 *
 * Le fichier contient normalement le résultat de la fonction install_connexion().
 * L'analyse tient également compte des syntaxes des versions précédentes.
 *
 * @param string $file
 *     Chemin du fichier de connexion à analyser
 * @return array
 *     Tableau des informations sur la connexion
 **/
function analyse_fichier_connection($file) {
	$s = @join('', file($file));
	if (preg_match("#mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)#", $s, $regs)) {
		array_shift($regs);

		return $regs;
	} else {
		$ar = '\s*\'([^\']*)\'';
		$r = '\s*,' . $ar;
		$r = "#spip_connect_db[(]$ar$r$r$r$r(?:$r(?:$r(?:$r(?:$r)?)?)?)?#";
		if (preg_match($r, $s, $regs)) {
			$regs[2] = $regs[1] . (!$regs[2] ? '' : ':' . $regs[2] . ';');
			array_shift($regs);
			array_shift($regs);

			return $regs;
		}
	}
	spip_log("$file n'est pas un fichier de connexion");

	return [];
}

/**
 * Liste les connecteurs aux bases SQL disponibles
 *
 * Dans le code SPIP ces connecteurs sont souvent appelés $connect ou $serveur
 *
 * @example
 *     $bases = bases_referencees(_FILE_CONNECT_TMP);
 *
 * @param string $exclu
 *     Exclure un connecteur particulier (nom du fichier)
 * @return array
 *     Liste des noms de connecteurs
 **/
function bases_referencees($exclu = '') {
	$tables = [];
	foreach (preg_files(_DIR_CONNECT, '.php$') as $f) {
		if ($f != $exclu and analyse_fichier_connection($f)) {
			$tables[] = basename($f, '.php');
		}
	}

	return $tables;
}


function install_mode_appel($server_db, $tout = true) {
	return ($server_db != 'mysql') ? ''
		: (($tout ? test_rappel_nom_base_mysql($server_db) : '')
			. test_sql_mode_mysql($server_db));
}

//
// Verifier que l'hebergement est compatible SPIP ... ou l'inverse :-)
// (sert a l'etape 1 de l'installation)
// https://code.spip.net/@tester_compatibilite_hebergement
function tester_compatibilite_hebergement() {
	$err = [];

	$p = phpversion();
	if (version_compare($p, _PHP_MIN, '<')) {
		$err[] = _T('install_php_version', ['version' => $p, 'minimum' => _PHP_MIN]);
	}

	// Si on n'a pas la bonne version de PHP, c'est la fin
	if ($err) {
		die("<div class='error'>"
			. '<h3>' . _T('avis_attention') . '</h3><p>' . _T('install_echec_annonce') . "</p><ul class='spip'>"
			. "<li><strong>{$err[0]}</strong></li>\n</ul></div>");
	}

	// Il faut une base de donnees tout de meme ...
	$serveurs = install_select_serveur();
	if (!$serveurs) {
		$err[] = _T('install_extension_php_obligatoire')
			. " <a href='http://www.php.net/mysql'>MYSQL</a>"
			. "| <a href='http://www.php.net/pgsql'>PostgreSQL</a>"
			. "| <a href='http://www.php.net/sqlite'>SQLite</a>";
	}

	// et il faut preg
	if (!function_exists('preg_match_all')) {
		$err[] = _T('install_extension_php_obligatoire')
			. " <a href='http://se.php.net/pcre'>PCRE</a>";
	}

	// et surtout pas ce mbstring.overload
	if ($a = @ini_get('mbstring.func_overload')) {
		$err[] = _T('install_extension_mbstring')
			. "mbstring.func_overload=$a - <a href='http://www.php.net/mb_string'>mb_string</a>.<br /><small>";
	}

	if ($err) {
		echo "<div class='error'>"
			. '<h3>' . _T('avis_attention') . '</h3><p>' . _T('install_echec_annonce') . "</p><ul class='spip'>";
		foreach ($err as $e) {
			echo "<li><strong>$e</strong></li>\n";
		}

		# a priori ici on pourrait die(), mais il faut laisser la possibilite
		# de forcer malgre tout (pour tester, ou si bug de detection)
		echo "</ul></div>\n";
	}
}


/**
 * Faciliter la recherche du login d'installation en fonction de certains hébergeurs connus
 *
 * @note superflu ??
 */
function login_hebergeur() {
	$base_hebergeur = 'localhost'; # par defaut

	// Free
	if (preg_match(',(.*)\.free\.fr$,', $_SERVER['SERVER_NAME'], $regs)) {
		$base_hebergeur = 'sql.free.fr';
		$login_hebergeur = $regs[1];
	} else {
		$login_hebergeur = '';
	}

	return [$base_hebergeur, $login_hebergeur];
}


// https://code.spip.net/@info_etape
function info_etape($titre, $complement = '') {
	return '<h2>' . $titre . "</h2>\n" .
	($complement ? '' . $complement . "\n" : '');
}

/**
 * Retourne le code HTML d'un bouton `suivant>>` pour les phases d'installation
 *
 * @param string $code Texte du bouton
 * @return string Code HTML du bouton
 **/
function bouton_suivant($code = '') {
	if ($code == '') {
		$code = _T('bouton_suivant');
	}
	static $suivant = 0;
	$id = 'suivant' . (($suivant > 0) ? strval($suivant) : '');
	$suivant += 1;

	return "\n<p class='boutons suivant'><input id='" . $id . "' type='submit'\nvalue=\"" .
	$code .
	" >>\" /></p>\n";
}

// https://code.spip.net/@info_progression_etape
function info_progression_etape($en_cours, $phase, $dir, $erreur = false) {
	//$en_cours = _request('etape')?_request('etape'):"";
	$liste = find_all_in_path($dir, $phase . '(([0-9])+|fin)[.]php$');
	$debut = 1;
	$etat = 'ok';
	$last = count($liste);
//	$texte_etat = array('ok'=>'OK','encours'=>_T('en_cours'),'todo'=>_T('todo'));

	$intitule_etat['etape_'][1] = typo(_T('info_connexion_base_donnee'));
	$intitule_etat['etape_'][2] = typo(_T('menu_aide_installation_choix_base'));
	$intitule_etat['etape_'][3] = typo(_T('info_informations_personnelles'));
	$intitule_etat['etape_'][4] = typo(_T('info_derniere_etape'));

	$intitule_etat['etape_ldap'][1] = typo(_T('titre_connexion_ldap'));
	$intitule_etat['etape_ldap'][2] = typo(_T('titre_connexion_ldap'));
	$intitule_etat['etape_ldap'][3] = typo(_T('info_chemin_acces_1'));
	$intitule_etat['etape_ldap'][4] = typo(_T('info_reglage_ldap'));
	$intitule_etat['etape_ldap'][5] = typo(_T('info_ldap_ok'));

//	$aff_etapes = "<span id='etapes'>";

	$aff_etapes = "<ul id='infos_etapes' class='infos_$phase$en_cours'>";

	foreach ($liste as $etape => $fichier) {
		if ($debut < $last) {
			if ($debut == $en_cours && $erreur) {
				$class = 'on erreur';
			} else {
				if ($debut == $en_cours) {
					$class = 'on';
				} else {
					if ($debut > $en_cours) {
						$class = 'prochains';
					} else {
						$class = 'valides';
					}
				}
			}

			$aff_etapes .= "<li class='$class'><div class='fond'>";
			$aff_etapes .= ($debut == $en_cours) ? '<strong>' : '';
			$aff_etapes .= '<em>' . _T('etape') . " </em><span class='numero_etape'>$debut</span><em>&nbsp;: </em>";
			$aff_etapes .= $intitule_etat["$phase"][$debut];
			$aff_etapes .= ($debut == $en_cours) ? '</strong>' : '';
			$aff_etapes .= '</div></li>';
		}
		$debut++;
	}
	$aff_etapes .= '</ul>';
	$aff_etapes .= "<br class='nettoyeur' />\n";

	return $aff_etapes;
}


// https://code.spip.net/@fieldset
function fieldset($legend, $champs = [], $apres = '', $avant = '') {
	return "<fieldset>\n" .
	$avant .
	($legend ? '<legend>' . $legend . "</legend>\n" : '') .
	fieldset_champs($champs) .
	$apres .
	"</fieldset>\n";
}

function fieldset_champs($champs = []) {
	$fieldset = '';
	foreach ($champs as $nom => $contenu) {
		$type = isset($contenu['hidden']) ? 'hidden' : (preg_match(',^pass,', $nom) ? 'password' : 'text');
		$class = isset($contenu['hidden']) ? '' : "class='formo' size='40' ";
		if (isset($contenu['alternatives'])) {
			$fieldset .= $contenu['label'] . "\n";
			foreach ($contenu['alternatives'] as $valeur => $label) {
				$fieldset .= "<input type='radio' name='" . $nom .
					"' id='$nom-$valeur' value='$valeur'"
					. (($valeur == $contenu['valeur']) ? "\nchecked='checked'" : '')
					. "/>\n";
				$fieldset .= "<label for='$nom-$valeur'>" . $label . "</label>\n";
			}
			$fieldset .= "<br />\n";
		} else {
			$fieldset .= "<label for='" . $nom . "'>" . $contenu['label'] . "</label>\n";
			$fieldset .= '<input ' . $class . "type='" . $type . "' id='" . $nom . "' name='" . $nom . "'\nvalue='" . $contenu['valeur'] . "'"
				. (preg_match(',^(pass|login),', $nom) ? " autocomplete='off'" : '')
				. ((isset($contenu['required']) and $contenu['required']) ? " required='required'" : '')
				. " />\n";
		}
	}

	return $fieldset;
}

function install_select_serveur() {
	$options = [];
	$dir = _DIR_RESTREINT . 'req/';
	$d = opendir($dir);
	if (!$d) {
		return [];
	}
	while (($f = readdir($d)) !== false) {
		if (
			(preg_match('/^(.*)[.]php$/', $f, $s))
			and is_readable($f = $dir . $f)
		) {
			require_once($f);
			$s = $s[1];
			$v = 'spip_versions_' . $s;
			if (function_exists($v) and $v()) {
				$titre = _T("install_select_type_$s");
				// proposer mysql par defaut si dispo
				$checked = ($s == 'mysql' ? " checked='checked'" : '');
				$options[$s] = "<li><input type='radio' id='$s' value='$s' name='server_db'$checked>"
					. "<label for='$s'>" . ($titre ? $titre : $s) . '</label></li>';
			} else {
				spip_log("$s: portage indisponible");
			}
		}
	}
	sort($options);

	return $options;
}

// https://code.spip.net/@install_connexion_form
function install_connexion_form($db, $login, $pass, $predef, $hidden, $etape, $jquery = true) {
	$server_db = (is_string($predef[0])) ? $predef[0] : '';

	return generer_form_ecrire('install', (
		"\n<input type='hidden' name='etape' value='$etape' />"
		. $hidden
		. (_request('echec') ?
			('<p><b>' . _T('avis_connexion_echec_1') .
				'</b></p><p>' . _T('avis_connexion_echec_2') . "</p><p style='font-size: small;'>" . _T('avis_connexion_echec_3') . '</p>')
			: '')

		. ($jquery ? http_script('', 'jquery.js') : '')
		. http_script('
		jQuery(function($) {
			$details_db = $("#install_adresse_base_hebergeur,#install_login_base_hebergeur,#install_pass_base_hebergeur");
			$("input[type=hidden][name=server_db]").each(function(){
				if ($(this).attr("value").match("sqlite*")){
					$details_db.hide();
				}
			});
			$choix = $("input[type=radio][name=server_db]");
			if ($choix.length == 1) {
				$choix.eq(0).prop("checked", true);
			}
			$checked = $("input[name=server_db][checked]");
			if (!$checked.length) {
				$details_db.hide();
			} else if ($checked.attr("value").match("sqlite*")) {
				$details_db.hide();
			} else {
				$details_db.show();
			}

			$("input[name=server_db]").each(function(){
				$(this).on("change",function(){
					if ($(this).prop("checked") && $(this).attr("value").match("sqlite*")) {
						$details_db.hide();
					}
					if ($(this).prop("checked") && !$(this).attr("value").match("sqlite*")) {
						$details_db.show();
					}
				});
			});
		});')

		. ($server_db
			? '<input type="hidden" name="server_db" value="' . $server_db . '" />'
			. (($predef[0])
				? ('<h3>' . _T('install_serveur_hebergeur') . '</h3>')
				: '')
			: ('<fieldset><legend>'
				. _T('install_select_type_db')
				. '</legend>'
				. '<p class="explication">'
				. _T('install_types_db_connus')
				// Passer l'avertissement SQLIte en  commentaire, on pourra facilement le supprimer par la suite sans changer les traductions.
				// . "<br /><small>(". _T('install_types_db_connus_avertissement') .')</small>'
				. '</p>'
				. "\n<div class='p'>\n<ul>\n"
				. join("\n", install_select_serveur())
				. "\n</ul>\n</div></fieldset>")
		)
		. '<div id="install_adresse_base_hebergeur">'
		. '<p>' . _T('texte_connexion_mysql') . '</p>'
		. ($predef[1]
			? '<h3>' . _T('install_adresse_base_hebergeur') . '</h3>'
			: fieldset(
				_T('entree_base_donnee_1'),
				[
					'adresse_db' => [
						'label' => $db[1],
						'valeur' => $db[0]
					],
				]
			)
		)
		. '</div>'

		. '<div id="install_login_base_hebergeur">'
		. ($predef[2]
			? '<h3>' . _T('install_login_base_hebergeur') . '</h3>'
			: fieldset(
				_T('entree_login_connexion_1'),
				[
					'login_db' => [
						'label' => $login[1],
						'valeur' => $login[0]
					],
				]
			)
		)
		. '</div>'

		. '<div id="install_pass_base_hebergeur">'
		. ($predef[3]
			? '<h3>' . _T('install_pass_base_hebergeur') . '</h3>'
			: fieldset(
				_T('entree_mot_passe_1'),
				[
					'pass_db' => [
						'label' => $pass[1],
						'valeur' => $pass[0]
					],
				]
			)
		)
		. '</div>'

		. bouton_suivant()));
}

// 4 valeurs qu'on reconduit d'un script a l'autre
// sauf s'ils sont predefinis.

// https://code.spip.net/@predef_ou_cache
function predef_ou_cache($adresse_db, $login_db, $pass_db, $server_db) {
	return ((defined('_INSTALL_HOST_DB'))
		? ''
		: "\n<input type='hidden' name='adresse_db'  value=\"" . spip_htmlspecialchars($adresse_db) . '" />'
	)
	. ((defined('_INSTALL_USER_DB'))
		? ''
		: "\n<input type='hidden' name='login_db' value=\"" . spip_htmlspecialchars($login_db) . '" />'
	)
	. ((defined('_INSTALL_PASS_DB'))
		? ''
		: "\n<input type='hidden' name='pass_db' value=\"" . spip_htmlspecialchars($pass_db) . '" />'
	)

	. ((defined('_INSTALL_SERVER_DB'))
		? ''
		: "\n<input type='hidden' name='server_db' value=\"" . spip_htmlspecialchars($server_db) . '" />'
	);
}

// presentation des bases existantes

// https://code.spip.net/@install_etape_liste_bases
function install_etape_liste_bases($server_db, $login_db, $disabled = []) {
	$bases = $checked = [];
	$noms = sql_listdbs($server_db);
	if (!$noms) {
		return '';
	}

	foreach ($noms as $nom) {
		$id = spip_htmlspecialchars($nom);
		$dis = in_array($nom, $disabled) ? " disabled='disabled'" : '';
		$base = ' name="choix_db" value="'
			. $nom
			. '"'
			. $dis
			. " type='radio' id='$id'";
		$label = "<label for='$id'>"
			. ($dis ? "<i>$nom</i>" : $nom)
			. '</label>';

		if (
			!$checked and !$dis and
			(($nom == $login_db) or
				($GLOBALS['table_prefix'] == $nom))
		) {
			$checked = "<input$base checked='checked' />\n$label";
		} else {
			$bases[] = "<input$base />\n$label";
		}
	}

	if (!$bases && !$checked) {
		return false;
	}

	if ($checked) {
		array_unshift($bases, $checked);
		$checked = true;
	}

	return [$checked, $bases];
}

function install_propager($hidden) {
	$res = '';
	foreach ($hidden as $k) {
		$v = spip_htmlentities(_request($k));
		$res .= "<input type='hidden' name='$k' value='$v' />";
	}

	return $res;
}
