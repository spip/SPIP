<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_TIDY")) return;
define("_ECRIRE_INC_TIDY", "1");


function version_tidy() {
	static $version = -1;
	if ($version == -1) {
		$version = 0;
		if (function_exists('tidy_parse_string')) {
			$version = 1;
			if (function_exists('tidy_get_body')) {
				$version = 2;
			}
		}
	}
	return $version;
}


function echappe_xhtml ($letexte) { // oui, c'est dingue... on echappe le mathml
	$regexp_echap_math = "/<math((.*?))<\/math>/si";
	$source = "xhtml";

	while (preg_match($regexp_echap_math, $letexte, $regs)) {
		$num_echap++;
		
		$les_echap[$num_echap] = $regs[0];

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
			.substr($letexte,$pos+strlen($regs[0]));
	}
	
	// et les textarea
	$regexp_echap_cadre = "/<textarea((.*?))<\/textarea>/si";
	$source = "xhtml";

	while (preg_match($regexp_echap_cadre, $letexte, $regs)) {
		$num_echap++;
		
		$les_echap[$num_echap] = $regs[0];

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
			.substr($letexte,$pos+strlen($regs[0]));
	}

	return array($letexte, $les_echap);
}


function xhtml($buffer) {
	$buffer = traite_xhtml($buffer);

/** ici commence la petite usine a gaz des traitements d'erreurs de tidy **/
	## NB: seul tidy en ligne de commande sait gerer ses erreurs,
	if (defined('_erreur_tidy')) {
		$url = "http://".$_SERVER['HTTP_HOST'].nettoyer_uri();
		if (defined('_calcul_tidy')) {
			spip_log("Erreur tidy : $url\n"._erreur_tidy, 'tidy');

			// Conserver une liste des URLs en erreur tidy
			lire_fichier($f = _DIR_SESSIONS.'w3c-go-home.txt', $liste);
			if (strpos($liste, "- $url -\n") === false) {
				$liste = substr($liste, - 8*1024); # anti-explosions
				ecrire_fichier($f, $liste."- $url -\n");
			}
		}

		// Ajouter un pseudo bouton d'admin
		if ($GLOBALS['affiche_boutons_admin']) {
			$buffer = preg_replace(',<div class="spip-admin-bloc" dir=\'(ltr|rtl)\'>\s*<ul>,ms',
			'$0<li><a href="http://validator.w3.org/check?uri='.stripslashes(preg_quote(entites_html($url))).'" class=\'spip-admin-boutons\'>Erreur tidy !</a></li>', $buffer);
		}

		// Na, na na nere, tidy a plante !
		header("X-SPIP-Message: tidy/W3C could not repair this page");

	}
	else # pas d'erreur
	{
		// Nettoyer la liste des URLs pourries, si maintenant on est correct
		if (defined('_calcul_tidy')) {
			lire_fichier($f = _DIR_SESSIONS.'w3c-go-home.txt', $liste);
			$url = "- http://".$_SERVER['HTTP_HOST'].nettoyer_uri()." -\n";
			if (($liste2 = str_replace($url, '', $liste)) != $liste)
				ecrire_fichier($f, $liste2);
		}

		// Faire la pub parce qu'on est content
		header("X-SPIP-Message: tidy/W3C has verified this page");

# tres perilleux, et totalement inutile a ce stade (mais fonctionnel si on veut)
#		if (defined('_TIDY_COMMAND'))
#			entetes_xhtml();

	}

/** fin de l'usine a gaz **/


	return $buffer;
}

function traite_xhtml ($buffer) {

	// Seuls les charsets iso-latin et utf-8 sont concernes
	$charset = lire_meta('charset');
	if ($charset == "iso-8859-1")
		$enc_char = "latin1";
	else if ($charset == "utf-8")
		$enc_char = "utf8";
	else {
		spip_log("erreur inc_tidy : charset=$charset", 'tidy');
		return $buffer;
	}

	if (defined('_TIDY_COMMAND')) {
		// Si l'on a defini un chemin pour tidyHTML 
		// en ligne de commande 
		// (pour les sites qui n'ont pas tidyPHP)
		include_ecrire("inc_texte.php3");

		$retour_echap = echappe_xhtml ($buffer);
		$buffer = $retour_echap[0];
		$les_echap = $retour_echap[1];

		$cache = _DIR_CACHE.creer_repertoire(_DIR_CACHE,'tidy');
		$nomfich = $cache.'tidy'.md5($buffer);
		if (!file_exists($nomfich)) {
			define ('_calcul_tidy', 1);
			$tmp = "$nomfich.".@getmypid().".tmp";
			ecrire_fichier($tmp, $buffer);

			$c = _TIDY_COMMAND
				." --tidy-mark false"
				." --char-encoding $enc_char"
				." --quote-nbsp false"
				." --show-body-only false"
				." --indent true"
				." --wrap false"
				." --output-xhtml true"
				." --add-xml-decl false"
				." -m $tmp"
				." 2>&1";
			spip_log(nettoyer_uri(), 'tidy');
			spip_timer('tidy');
			exec($c, $verbose, $exit_code);
			spip_log ($c.' ('.spip_timer('tidy').')', 'tidy');
			if ($exit_code == 2) {
				define ('_erreur_tidy', join("\n", $verbose));
				# un fichier .err accompagne le cache, qu'on s'en souvienne
				# au prochain hit (gestion d'erreur)
				spip_touch("$nomfich.err");
			} else {
				@unlink("$nomfich.err");
			}

			rename($tmp,$nomfich);
		}

		if (lire_fichier($nomfich, $tidy)
		AND strlen(trim($tidy)) > 0) {
			// purger le petit cache toutes les 5 minutes (300s)
			spip_touch($nomfich); # rester vivant
			if (spip_touch($cache.'purger_tidy', 300, true)) {
				if ($h = @opendir($cache)) {
					while (($f = readdir($h)) !== false) {
						if (preg_match(',^tidy[^.]*$,', $f)
						AND time() - filemtime("$cache$f") > 300) {
							@unlink("$cache$f");
							@unlink("$cache$f.err");
						}
					}
				}
			}

			$tidy = echappe_retour($tidy, $les_echap, "xhtml");
			$tidy = preg_replace (",<[?]xml.*>,U", "", $tidy);
			if (@file_exists("$nomfich.err")) {
				define ('_erreur_tidy', 1);
			}
			return $tidy;
		} else {
			define ('_erreur_tidy', 1);
			return $buffer;
		}
	}

	### tout ce qui suit est non teste, et probablement non fonctionnel
	else if (version_tidy() == "1") {
		include_ecrire("inc_texte.php3");

		$retour_echap = echappe_xhtml ($buffer);
		$buffer = $retour_echap[0];
		$les_echap = $retour_echap[1];

		tidy_set_encoding ($enc_char);
		tidy_setopt('wrap', 0);
		tidy_setopt('indent-spaces', 4);
		tidy_setopt('output-xhtml', true);
		tidy_setopt('add-xml-decl', false);
		tidy_setopt('indent', 5);
		tidy_setopt('show-body-only', false);
		tidy_setopt('quote-nbsp', false);

		tidy_parse_string($buffer);
		tidy_clean_repair();
		$tidy = tidy_get_output();
		$tidy = echappe_retour($tidy, $les_echap, "xhtml");
		// En Latin1, tidy ajoute une declaration XML
		// (malgre add-xml-decl a false) ; il faut le supprimer
		// pour eviter interpretation PHP provoquant une erreur
		$tidy = ereg_replace ("\<\?xml([^\>]*)\>", "", $tidy);
		# pas de gestion d'erreur ?
		return $tidy;
	}
	else if (version_tidy() == "2") {
		include_ecrire("inc_texte.php3");
	
		$retour_echap = echappe_xhtml ($buffer);
		$buffer = $retour_echap[0];
		$les_echap = $retour_echap[1];

		$config = array(
			'wrap' => 0,
			'indent-spaces' => 4,
			'output-xhtml' => true,
			'add-xml-decl' => false,
			'indent' => 5,
			'show-body-only' => false,
			'quote-nbsp' => false
			);
		$tidy = tidy_parse_string($buffer, $config, $enc_char);
		tidy_clean_repair($tidy);
		$tidy = ereg_replace ("\<\?xml([^\>]*)\>", "", $tidy);
		# pas de gestion d'erreur ?
		return $tidy;
	}
	else {
		define ('_erreur_tidy', 1);
		return $buffer;
	}
}

## desactive pour le moment... complications dans tous les sens et gros risque d'erreur
function entetes_xhtml() {
	// Si Mozilla et tidy actif, passer en "application/xhtml+xml"
	// extremement risque: Mozilla passe en mode debugueur strict
	// mais permet d'afficher du MathML directement dans le texte
	// (et sauf erreur, c'est la bonne facon de declarer du xhtml)
	if (strpos($_SERVER['HTTP_ACCEPT'], "application/xhtml+xml")) {
		@header("Content-Type: application/xhtml+xml; charset=".lire_meta('charset'));
	} else {
		@header("Content-Type: text/html; charset=".lire_meta('charset'));
		echo '<'.'?xml version="1.0" encoding="'. lire_meta('charset').'"?'.">\n";
	}
}

?>