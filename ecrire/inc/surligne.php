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
 * Gestion du surlignage des mots d'une recherche
 *
 * @package SPIP\Core\Surligne
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajoute au HTML un script JS surlignant une recherche indiquée et/ou issue des réferers
 *
 * Ajoute à la page HTML, seulement si des mots de recherches sont présents,
 * — soit transmis, soit dans un réferer de moteur de recherche —
 * un script qui s'occupera de les surligner. Le script est placé dans
 * le head HTML si le texte en possède un, sinon à la fin.
 *
 * @param string $page
 *     Page HTML
 * @param string $surcharge_surligne
 *     Mots à surligner transmis
 * @return string
 *     Page HTML
 **/
function surligner_mots($page, $surcharge_surligne = '') {
	$surlignejs_engines = array(
		array(
			',' . str_replace(array('/', '.'), array('\/', '\.'), $GLOBALS['meta']['adresse_site']) . ',i',
			',recherche=([^&]+),i'
		), //SPIP
		array(',^http://(www\.)?google\.,i', ',q=([^&]+),i'), // Google
		array(',^http://(www\.)?search\.yahoo\.,i', ',p=([^&]+),i'), // Yahoo
		array(',^http://(www\.)?search\.msn\.,i', ',q=([^&]+),i'), // MSN
		array(',^http://(www\.)?search\.live\.,i', ',query=([^&]+),i'), // MSN Live
		array(',^http://(www\.)?search\.aol\.,i', ',userQuery=([^&]+),i'), // AOL
		array(',^http://(www\.)?ask\.com,i', ',q=([^&]+),i'), // Ask.com
		array(',^http://(www\.)?altavista\.,i', ',q=([^&]+),i'), // AltaVista
		array(',^http://(www\.)?feedster\.,i', ',q=([^&]+),i'), // Feedster
		array(',^http://(www\.)?search\.lycos\.,i', ',q=([^&]+),i'), // Lycos
		array(',^http://(www\.)?alltheweb\.,i', ',q=([^&]+),i'), // AllTheWeb
		array(',^http://(www\.)?technorati\.com,i', ',([^\?\/]+)(?:\?.*)$,i'), // Technorati
	);


	$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	//avoid a js injection
	if ($surcharge_surligne) {
		$surcharge_surligne = preg_replace(",(?<!\\\\)((?:(?>\\\\){2})*)('),", '$1\\\$2', $surcharge_surligne);
		$surcharge_surligne = str_replace('\\', '\\\\', $surcharge_surligne);
		if ($GLOBALS['meta']['charset'] == 'utf-8') {
			include_spip('inc/charsets');
			if (!is_utf8($surcharge_surligne)) {
				$surcharge_surligne = utf8_encode($surcharge_surligne);
			}
		}
		$surcharge_surligne = preg_replace(',\*$,', '', trim($surcharge_surligne)); # supprimer un * final
	}
	foreach ($surlignejs_engines as $engine) {
		if ($surcharge_surligne || (preg_match($engine[0], $ref) && preg_match($engine[1], $ref))) {
			//good referrer found or var_recherche is not null
			include_spip('inc/filtres');
			$script = "
      <script type='text/javascript' src='" . url_absolue(find_in_path('javascript/SearchHighlight.js')) . "'></script>
      <script type='text/javascript'>/*<![CDATA[*/
      if (window.jQuery)
        (function(\$){\$(function(){
          \$(document).SearchHighlight({
            tag_name:'" . (html5_permis() ? 'mark' : 'span') . "',
            style_name:'spip_surligne',
            exact:'whole',
            style_name_suffix:false,
            engines:[/^" . str_replace(array('/', '.'), array('\/', '\.'), $GLOBALS['meta']['adresse_site']) . "/i,/recherche=([^&]+)/i],
            highlight:'.surlignable',
            nohighlight:'.pas_surlignable'" .
				($surcharge_surligne ? ",
            keys:'$surcharge_surligne'" : '') . ',
            min_length: 3
          })
        });
      })(jQuery);
      /*]]>*/</script>
      ';
			// on l'insere juste avant </head>, sinon tout en bas
			if (is_null($l = strpos($page, '</head>'))) {
				$l = strlen($page);
			}
			$page = substr_replace($page, $script, $l, 0);
			break;
		}
	}

	return $page;
}
