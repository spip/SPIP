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
 * Gestion des textes et échappements (fonctions d'usages fréquents)
 *
 * @package SPIP\Core\Texte
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/filtres');
include_spip('inc/lang');


/**
 * Retourne une image d'une puce
 *
 * Le nom de l'image est déterminé par la globale 'puce' ou 'puce_prive'
 * ou les mêmes suffixées de '_rtl' pour ce type de langues.
 *
 * @note
 *     On initialise la puce pour éviter `find_in_path()` à chaque rencontre de `\n-`
 *     Mais attention elle depend de la direction et de X_fonctions.php, ainsi que
 *     de l'espace choisi (public/prive)
 *
 * @return string
 *     Code HTML de la puce
 **/
function definir_puce() {

	// Attention au sens, qui n'est pas defini de la meme facon dans
	// l'espace prive (spip_lang est la langue de l'interface, lang_dir
	// celle du texte) et public (spip_lang est la langue du texte)
	$dir = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);

	$p = 'puce' . (test_espace_prive() ? '_prive' : '');
	if ($dir == 'rtl') {
		$p .= '_rtl';
	}

	if (!isset($GLOBALS[$p])) {
		$GLOBALS[$p] = '<span class="spip-puce ' . $dir . '"><b>–</b></span>';
	}

	return $GLOBALS[$p];
}


// XHTML - Preserver les balises-bloc : on liste ici tous les elements
// dont on souhaite qu'ils provoquent un saut de paragraphe

if (!defined('_BALISES_BLOCS')) {
	define(
		'_BALISES_BLOCS',
		'address|applet|article|aside|blockquote|button|center|d[ltd]|div|fieldset|fig(ure|caption)|footer|form|h[1-6r]|hgroup|head|header|iframe|li|map|marquee|nav|noscript|object|ol|pre|section|t(able|[rdh]|body|foot|extarea)|ul|script|style'
	);
}

if (!defined('_BALISES_BLOCS_REGEXP')) {
	define('_BALISES_BLOCS_REGEXP', ',</?(' . _BALISES_BLOCS . ')[>[:space:]],iS');
}

//
// Echapper les elements perilleux en les passant en base64
//

// Creer un bloc base64 correspondant a $rempl ; au besoin en marquant
// une $source differente ; le script detecte automagiquement si ce qu'on
// echappe est un div ou un span
function code_echappement($rempl, $source = '', $no_transform = false, $mode = null) {
	if (!strlen($rempl)) {
		return '';
	}

	// Tester si on echappe en span ou en div
	if (is_null($mode) or !in_array($mode, ['div', 'span'])) {
		$mode = preg_match(',</?(' . _BALISES_BLOCS . ')[>[:space:]],iS', $rempl) ? 'div' : 'span';
	}

	// Decouper en morceaux, base64 a des probleme selon la taille de la pile
	$taille = 30000;
	$return = '';
	for ($i = 0; $i < strlen($rempl); $i += $taille) {
		// Convertir en base64 et cacher dans un attribut
		// utiliser les " pour eviter le re-encodage de ' et &#8217
		$base64 = base64_encode(substr($rempl, $i, $taille));
		$return .= "<$mode class=\"base64$source\" title=\"$base64\"></$mode>";
	}

	return $return;
}


// Echapper les <html>...</ html>
function traiter_echap_html_dist($regs, $options = []) {
	return $regs[3];
}

// Echapper les <pre>...</ pre>
function traiter_echap_pre_dist($regs, $options = []) {
	// echapper les <code> dans <pre>
	$pre = $regs[3];

	// echapper les < dans <code>
	// on utilise _PROTEGE_BLOCS pour simplifier le code et la maintenance, mais on est interesse que par <code>
	if (
		strpos($pre, '<') !== false
		and preg_match_all(_PROTEGE_BLOCS, $pre, $matches, PREG_SET_ORDER)
	) {
		foreach ($matches as $m) {
			if ($m[1] === 'code') {
				$code = '<code' . $m[2] . '>' . spip_htmlspecialchars($m[3]) . '</code>';
				$pre = str_replace($m[0], $code, $pre);
			}
		}
	}
	return "<pre>$pre</pre>";
}

// Echapper les <code>...</ code>
function traiter_echap_code_dist($regs, $options = []) {
	[, , $att, $corps] = $regs;
	$echap = spip_htmlspecialchars($corps); // il ne faut pas passer dans entites_html, ne pas transformer les &#xxx; du code !

	// ne pas mettre le <div...> s'il n'y a qu'une ligne
	if (is_int(strpos($echap, "\n"))) {
		// supprimer les sauts de ligne debut/fin
		// (mais pas les espaces => ascii art).
		$echap = preg_replace("/^[\n\r]+|[\n\r]+$/s", '', $echap);
		$echap = nl2br($echap);
		$echap = "<div style='text-align: left;' "
			. "class='spip_code' dir='ltr'><code$att>"
			. $echap . '</code></div>';
	} else {
		$echap = "<code$att class='spip_code' dir='ltr'>" . $echap . '</code>';
	}

	$echap = str_replace("\t", '&nbsp; &nbsp; &nbsp; &nbsp; ', $echap);
	$echap = str_replace('  ', ' &nbsp;', $echap);

	return $echap;
}

// Echapper les <cadre>...</ cadre> aka <frame>...</ frame>
function traiter_echap_cadre_dist($regs, $options = []) {
	$echap = trim(entites_html($regs[3]));
	// compter les lignes un peu plus finement qu'avec les \n
	$lignes = explode("\n", trim($echap));
	$n = 0;
	foreach ($lignes as $l) {
		$n += floor(strlen($l) / 60) + 1;
	}
	$n = max($n, 2);
	$echap = "\n<textarea readonly='readonly' cols='40' rows='$n' class='spip_cadre' dir='ltr'>$echap</textarea>";

	return $echap;
}

function traiter_echap_frame_dist($regs, $options = []) {
	return traiter_echap_cadre_dist($regs);
}

function traiter_echap_script_dist($regs, $options = []) {
	// rendre joli (et inactif) si c'est un script language=php
	if (preg_match(',<script\b[^>]+php,ims', $regs[0])) {
		return highlight_string($regs[0], true);
	}

	// Cas normal : le script passe tel quel
	return $regs[0];
}

define('_PROTEGE_BLOCS', ',<(html|pre|code|cadre|frame|script|style)(\b[^>]*)?>(.*)</\1>,UimsS');

/**
 * pour $source voir commentaire infra (echappe_retour)
 * pour $no_transform voir le filtre post_autobr dans inc/filtres
 * @see post_autobr()
 *
 * @param string $letexte
 * @param string $source
 * @param bool $no_transform
 * @param string $preg
 * @param string $callback_prefix
 * @param array $callback_options
 * @return string|string[]
 */
function echappe_html(
	$letexte,
	$source = '',
	$no_transform = false,
	$preg = '',
	$callback_prefix = '',
	$callback_options = []
) {
	if (!is_string($letexte) or !strlen($letexte)) {
		return $letexte;
	}

	if (
		($preg or strpos($letexte, '<') !== false)
		and preg_match_all($preg ?: _PROTEGE_BLOCS, $letexte, $matches, PREG_SET_ORDER)
	) {
		foreach ($matches as $regs) {
			// echappements tels quels ?
			if ($no_transform) {
				$echap = $regs[0];
			} // sinon les traiter selon le cas
			else {
				$callback_secure_prefix = ($callback_options['secure_prefix'] ?? '');
				if (
					function_exists($f = $callback_prefix . $callback_secure_prefix . 'traiter_echap_' . strtolower($regs[1]))
					or function_exists($f = $f . '_dist')
					or ($callback_secure_prefix and (
						function_exists($f = $callback_prefix . 'traiter_echap_' . strtolower($regs[1]))
						or function_exists($f = $f . '_dist')
					))
				) {
					$echap = $f($regs, $callback_options);
				}
			}

			$p = strpos($letexte, (string) $regs[0]);
			$letexte = substr_replace($letexte, code_echappement($echap, $source, $no_transform), $p, strlen($regs[0]));
		}
	}

	if ($no_transform) {
		return $letexte;
	}

	// Echapper le php pour faire joli (ici, c'est pas pour la securite)
	// seulement si on a echappe les <script>
	// (derogatoire car on ne peut pas faire passer < ? ... ? >
	// dans une callback autonommee
	if (strpos($preg ?: _PROTEGE_BLOCS, 'script') !== false) {
		if (
			strpos($letexte, '<' . '?') !== false and preg_match_all(
				',<[?].*($|[?]>),UisS',
				$letexte,
				$matches,
				PREG_SET_ORDER
			)
		) {
			foreach ($matches as $regs) {
				$letexte = str_replace(
					$regs[0],
					code_echappement(highlight_string($regs[0], true), $source),
					$letexte
				);
			}
		}
	}

	return $letexte;
}

//
// Traitement final des echappements
// Rq: $source sert a faire des echappements "a soi" qui ne sont pas nettoyes
// par propre() : exemple dans multi et dans typo()
function echappe_retour($letexte, $source = '', $filtre = '') {
	if (strpos($letexte, (string) "base64$source")) {
		# spip_log(spip_htmlspecialchars($letexte));  ## pour les curieux
		$max_prof = 5;
		while (
			strpos($letexte, '<') !== false
			and
			preg_match_all(
				',<(span|div)\sclass=[\'"]base64' . $source . '[\'"]\s(.*)>\s*</\1>,UmsS',
				$letexte,
				$regs,
				PREG_SET_ORDER
			)
			and $max_prof--
		) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
				// recherche d'attributs supplementaires
				$at = [];
				foreach (['lang', 'dir'] as $attr) {
					if ($a = extraire_attribut($reg[0], $attr)) {
						$at[$attr] = $a;
					}
				}
				if ($at) {
					$rempl = '<' . $reg[1] . '>' . $rempl . '</' . $reg[1] . '>';
					foreach ($at as $attr => $a) {
						$rempl = inserer_attribut($rempl, $attr, $a);
					}
				}
				if ($filtre) {
					$rempl = $filtre($rempl);
				}
				$letexte = str_replace($reg[0], $rempl, $letexte);
			}
		}
	}

	return $letexte;
}

// Reinserer le javascript de confiance (venant des modeles)

function echappe_retour_modeles($letexte, $interdire_scripts = false) {
	$letexte = echappe_retour($letexte);

	// Dans les appels directs hors squelette, securiser aussi ici
	if ($interdire_scripts) {
		$letexte = interdire_scripts($letexte);
	}

	return trim($letexte);
}


/**
 * Coupe un texte à une certaine longueur.
 *
 * Il essaie de ne pas couper les mots et enlève le formatage du texte.
 * Si le texte original est plus long que l’extrait coupé, alors des points
 * de suite sont ajoutés à l'extrait, tel que ` (...)`.
 *
 * @note
 *     Les points de suite ne sont pas ajoutés sur les extraits
 *     très courts.
 *
 * @filtre
 * @link https://www.spip.net/4275
 *
 * @param string $texte
 *     Texte à couper
 * @param int $taille
 *     Taille de la coupe
 * @param string $suite
 *     Points de suite ajoutés.
 * @return string
 *     Texte coupé
 **/
function couper($texte, $taille = 50, $suite = null) {
	if (!($length = strlen($texte)) or $taille <= 0) {
		return '';
	}
	$offset = 400 + 2 * $taille;
	while (
		$offset < $length
		and strlen(preg_replace(',<(!--|\w|/)[^>]+>,Uims', '', substr($texte, 0, $offset))) < $taille
	) {
		$offset = 2 * $offset;
	}
	if (
		$offset < $length
		&& ($p_tag_ouvrant = strpos($texte, '<', $offset)) !== null
	) {
		$p_tag_fermant = strpos($texte, '>', $offset);
		if ($p_tag_fermant && ($p_tag_fermant < $p_tag_ouvrant)) {
			$offset = $p_tag_fermant + 1;
		} // prolonger la coupe jusqu'au tag fermant suivant eventuel
	}
	$texte = substr($texte, 0, $offset); /* eviter de travailler sur 10ko pour extraire 150 caracteres */

	if (!function_exists('nettoyer_raccourcis_typo')) {
		include_spip('inc/lien');
	}
	$texte = nettoyer_raccourcis_typo($texte);

	// balises de sauts de ligne et paragraphe
	$texte = preg_replace('/<p( [^>]*)?' . '>/', "\r", $texte);
	$texte = preg_replace('/<br( [^>]*)?' . '>/', "\n", $texte);

	// on repasse les doubles \n en \r que nettoyer_raccourcis_typo() a pu modifier
	$texte = str_replace("\n\n", "\r", $texte);

	// supprimer les tags
	$texte = supprimer_tags($texte);
	$texte = trim(str_replace("\n", ' ', $texte));
	$texte .= "\n";  // marquer la fin

	// corriger la longueur de coupe
	// en fonction de la presence de caracteres utf
	if ($GLOBALS['meta']['charset'] == 'utf-8') {
		$long = charset2unicode($texte);
		$long = spip_substr($long, 0, max($taille, 1));
		$nbcharutf = preg_match_all('/(&#[0-9]{3,6};)/S', $long, $matches);
		$taille += $nbcharutf;
	}


	// couper au mot precedent
	$long = spip_substr($texte, 0, max($taille - 4, 1));
	$u = $GLOBALS['meta']['pcre_u'];
	$court = preg_replace("/([^\s][\s]+)[^\s]*\n?$/" . $u, "\\1", $long);
	if (is_null($suite)) {
		$suite = (defined('_COUPER_SUITE') ? _COUPER_SUITE : '&nbsp;(...)');
	}
	$points = $suite;

	// trop court ? ne pas faire de (...)
	if (spip_strlen($court) < max(0.75 * $taille, 2)) {
		$points = '';
		$long = spip_substr($texte, 0, $taille);
		$texte = preg_replace("/([^\s][\s]+)[^\s]*\n?$/" . $u, "\\1", $long);
		// encore trop court ? couper au caractere
		if (spip_strlen($texte) < 0.75 * $taille) {
			$texte = $long;
		}
	} else {
		$texte = $court;
	}

	if (strpos($texte, "\n")) {  // la fin est encore la : c'est qu'on n'a pas de texte de suite
	$points = '';
	}

	// remettre les paragraphes
	$texte = preg_replace("/\r+/", "\n\n", $texte);

	// supprimer l'eventuelle entite finale mal coupee
	$texte = preg_replace('/&#?[a-z0-9]*$/S', '', $texte);

	return quote_amp(trim($texte)) . $points;
}


function protege_js_modeles($t) {
	if (isset($GLOBALS['visiteur_session'])) {
		if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER)) {
			if (!defined('_PROTEGE_JS_MODELES')) {
				include_spip('inc/acces');
				define('_PROTEGE_JS_MODELES', creer_uniqid());
			}
			foreach ($r as $regs) {
				$t = str_replace($regs[0], code_echappement($regs[0], 'javascript' . _PROTEGE_JS_MODELES), $t);
			}
		}
		if (preg_match_all(',<\?php.*?($|\?' . '>),isS', $t, $r, PREG_SET_ORDER)) {
			if (!defined('_PROTEGE_PHP_MODELES')) {
				include_spip('inc/acces');
				define('_PROTEGE_PHP_MODELES', creer_uniqid());
			}
			foreach ($r as $regs) {
				$t = str_replace($regs[0], code_echappement($regs[0], 'php' . _PROTEGE_PHP_MODELES), $t);
			}
		}
	}

	return $t;
}


function echapper_faux_tags($letexte) {
	if (strpos($letexte, '<') === false) {
		return $letexte;
	}
	$textMatches = preg_split(',(</?[a-z!][^<>]*>),', $letexte, -1, PREG_SPLIT_DELIM_CAPTURE);

	$letexte = '';
	while (is_countable($textMatches) ? count($textMatches) : 0) {
		// un texte a echapper
		$letexte .= str_replace('<', '&lt;', array_shift($textMatches));
		// un tag html qui a servit a faite le split
		$letexte .= array_shift($textMatches);
	}

	return $letexte;
}

/**
 * Si le html contenu dans un texte ne passe pas sans transformation a travers safehtml
 * on l'echappe
 * si safehtml ne renvoie pas la meme chose on echappe les < en &lt; pour montrer le contenu brut
 *
 * @use wrap()
 *
 * @param string $texte
 * @param array $options
 *   bool strict : etre strict ou non sur la detection
 *   string wrap_suspect : si le html est suspect, on wrap l'affichage avec la balise indiquee dans cette option via la fonction wrap()
 *   string texte_source_affiche : si le html est suspect, on utilise ce texte pour l'affichage final et pas le texte utilise pour la detection
 * @param string $connect
 * @param array $env
 * @return string
 */
function echapper_html_suspect($texte, $options = [], $connect = null, $env = []) {
	static $echapper_html_suspect;
	if (!$texte or !is_string($texte)) {
		return $texte;
	}

	if (!isset($echapper_html_suspect)) {
		$echapper_html_suspect = charger_fonction('echapper_html_suspect', 'inc', true);
	}
	// si fonction personalisee, on delegue
	if ($echapper_html_suspect) {
		// on collecte le tableau d'arg minimal pour ne pas casser un appel a une fonction inc_echapper_html_suspect() selon l'ancienne signature
		$args = [$texte, $options];
		if ($connect or !empty($env)) {
			$args[] = $connect;
		}
		if (!empty($env)) {
			$args[] = $env;
		}
		return $echapper_html_suspect(...$args);
	}

	if (is_bool($options)) {
		$options = ['strict' => $options];
	}
	$strict = $options['strict'] ?? true;

	// pas de balise html ou pas d'attribut sur les balises ? c'est OK
	if (
		strpos($texte, '<') === false
		or strpos($texte, '=') === false
	) {
		return $texte;
	}

	// dans le prive, on veut afficher tout echappé pour la moderation
	if (!isset($env['espace_prive'])) {
		// conserver le comportement historique en cas d'appel court sans env
		$env['espace_prive'] = test_espace_prive();
	}
	if (!empty($env['espace_prive']) or !empty($env['wysiwyg'])) {

		// quand c'est du texte qui passe par propre on est plus coulant tant qu'il y a pas d'attribut du type onxxx=
		// car sinon on declenche sur les modeles ou ressources
		if (
			!$strict and
			(strpos($texte, 'on') === false or !preg_match(",<\w+.*\bon\w+\s*=,UimsS", $texte))
		) {
			return $texte;
		}

		[$texte, $markid] = modeles_echapper_raccourcis($texte, false);
		$texte = echappe_js($texte);

		// on teste sur strlen car safehtml supprime le contenu dangereux
		// mais il peut aussi changer des ' en " sur les attributs html,
		// donc un test d'egalite est trop strict
		if (strlen(safehtml($texte)) !== strlen($texte)) {
			$texte = $options['texte_source_affiche'] ?? $texte;
			$texte = preg_replace(",<(/?\w+\b[^>]*>),", "<tt>&lt;\\1</tt>", $texte);
			$texte = str_replace('<', '&lt;', $texte);
			$texte = str_replace('&lt;tt>', '<tt>', $texte);
			$texte = str_replace('&lt;/tt>', '</tt>', $texte);
			if (!function_exists('attribut_html')) {
				include_spip('inc/filtres');
			}
			if (!empty($options['wrap_suspect'])) {
				$texte = wrap($texte, $options['wrap_suspect']);
			}
			$texte = "<mark class='danger-js' title='" . attribut_html(_T('erreur_contenu_suspect')) . "'>⚠️</mark> " . $texte;
		}
		$texte = modele_retablir_raccourcis_echappes($texte, $markid);
	}

	// si on est là dans le public c'est le mode parano
	// on veut donc un rendu propre et secure, et virer silencieusement ce qui est dangereux
	else {
		[$texte, $markid] = modeles_echapper_raccourcis($texte, false);
		$texte = safehtml($texte);
		$texte = modele_retablir_raccourcis_echappes($texte, $markid);
	}

	return $texte;
}


/**
 * Sécurise un texte HTML
 *
 * Échappe le code PHP et JS.
 * Applique en plus safehtml si un plugin le définit dans inc/safehtml.php
 *
 * Permet de protéger les textes issus d'une origine douteuse (forums, syndications...)
 *
 * @filtre
 * @link https://www.spip.net/4310
 *
 * @param string $t
 *      Texte à sécuriser
 * @return string
 *      Texte sécurisé
 **/
function safehtml($t) {
	static $safehtml;

	if (!$t or !is_string($t)) {
		return $t;
	}
	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t, '<') === false) {
		return str_replace("\x00", '', $t);
	}

	if (!function_exists('interdire_scripts')) {
		include_spip('inc/texte');
	}
	$t = interdire_scripts($t); // jolifier le php
	$t = echappe_js($t);

	if (!isset($safehtml)) {
		$safehtml = charger_fonction('safehtml', 'inc', true);
	}
	if ($safehtml) {
		$t = $safehtml($t);
	}

	return interdire_scripts($t); // interdire le php (2 precautions)
}


/**
 * Supprime les modèles d'image d'un texte
 *
 * Fonction en cas de texte extrait d'un serveur distant:
 * on ne sait pas (encore) rapatrier les documents joints
 * Sert aussi à nettoyer un texte qu'on veut mettre dans un `<a>` etc.
 *
 * @todo
 *     gérer les autres modèles ?
 *
 * @param string $letexte
 *     Texte à nettoyer
 * @param string|null $message
 *     Message de remplacement pour chaque image enlevée
 * @return string
 *     Texte sans les modèles d'image
 **/
function supprime_img($letexte, $message = null) {
	if ($message === null) {
		$message = '(' . _T('img_indisponible') . ')';
	}

	return preg_replace(
		',<(img|doc|emb)([0-9]+)(\|([^>]*))?' . '\s*/?' . '>,i',
		$message,
		$letexte
	);
}
