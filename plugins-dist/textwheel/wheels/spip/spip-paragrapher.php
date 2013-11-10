<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * callback fermer-para-mano
 * on refait le preg, a la main
 */
function fermer_para_mano(&$t) {
	# match: ",<p (.*)<(/?)(STOP P|div|pre|ul|ol|li|blockquote|h[1-6r]|t(able|[rdh]|head|body|foot|extarea)|form|object|center|marquee|address|applet|iframe|d[ltd]|script|noscript|map|button|fieldset|style)\b,UimsS"
	# replace: "\n<p "+trim($1)+"</p>\n<$2$3"

	foreach (explode('<p ', $t) as $c => $p) {
		if ($c == 0)
			$t = $p;
		else {
			$pi = strtolower($p);
			if (preg_match(
			",</?(?:stop p|div|pre|ul|ol|li|blockquote|h[1-6r]|t(able|[rdh]|head|body|foot|extarea)|form|object|center|marquee|address|applet|iframe|d[ltd]|script|noscript|map|button|fieldset|style)\b,S",
			$pi, $r)) {
				$pos = strpos($pi, $r[0]);
				$t .= "<p ".str_replace("\n", _AUTOBR."\n", rtrim(substr($p,0,$pos)))."</p>\n".substr($p,$pos);
			} else {
				$t .= '<p '.$p;
			}
		}
	}

	if (_AUTOBR) {
		$t = str_replace(_AUTOBR."\n"."<br", "<br", $t); #manque /i
		$reg = ',(<br\b[^>]*>\s*)'.preg_quote(_AUTOBR."\n", ',').",iS";

		$t = preg_replace($reg, '\1', $t);
	}

	return $t;
}
