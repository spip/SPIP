<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Gestion du raccourci <math>...</math> en client-serveur
//

// http://doc.spip.org/@image_math
function produire_image_math($tex) {
	global $traiter_math;
	switch ($traiter_math) {
		// Attention: mathml desactiv'e pour l'instant
		case 'mathml':
			$ext = '.xhtml';
			$server = $GLOBALS['mathml_server'];
			break;
		case 'tex':
			$ext = '.png';
			$server = $GLOBALS['tex_server'];
			break;
		default:
			return $tex;
	}

	// Regarder dans le repertoire local des images TeX et blocs MathML
	if (!@is_dir($dir_tex = _DIR_VAR.'cache-TeX/'))
		@mkdir ($dir_tex, _SPIP_CHMOD);
	$fichier = $dir_tex .md5(trim($tex)).$ext;
	

	if (!@file_exists($fichier)) {
		// Aller chercher l'image sur le serveur
		if ($server) {
			spip_log($url = $server.'?'.rawurlencode($tex));
			include_spip('inc/distant');
			recuperer_page($url,$fichier);
		}
	}


	// Composer la reponse selon presence ou non de l'image
	$tex = entites_html($tex);
	if (@file_exists($fichier)) {

		// MathML
		if ($traiter_math == 'mathml') {
			return join(file("$fichier"),"");
		}

		// TeX
		else {
			list(,,,$size) = @getimagesize($fichier);
			$alt = "alt=\"$tex\" title=\"$tex\""; 
			return "<img src=\"$fichier\" style=\"vertical-align:middle;\" $size $alt />";
		}

	}
	else // pas de fichier
		return "<tt><span class='spip_code' dir='ltr'>$tex</span></tt>";

}


// Fonction appelee par propre() s'il repere un mode <math>
// http://doc.spip.org/@traiter_math
function traiter_math($letexte, $source='') {

	$texte_a_voir = $letexte;
	while (($debut = strpos($texte_a_voir, "<math>")) !== false) {
		if (!$fin = strpos($texte_a_voir,"</math>"))
			$fin = strlen($texte_a_voir);

		$texte_debut = substr($texte_a_voir, 0, $debut);
		$texte_milieu = substr($texte_a_voir,
			$debut+strlen("<math>"), $fin-$debut-strlen("<math>"));
		$texte_fin = substr($texte_a_voir,
			$fin+strlen("</math>"), strlen($texte_a_voir));

		// Les doubles $$x^2$$ en mode 'div'
		while((preg_match(",[$][$]([^$]+)[$][$],",$texte_milieu, $regs))) {
			$echap = "\n<p class=\"spip\" style=\"text-align: center;\">".produire_image_math($regs[1])."</p>\n";
			$pos = strpos($texte_milieu, $regs[0]);
			$texte_milieu = substr($texte_milieu,0,$pos)
				. code_echappement($echap, $source)
				. substr($texte_milieu,$pos+strlen($regs[0]));
		}

		// Les simples $x^2$ en mode 'span'
		while((preg_match(",[$]([^$]+)[$],",$texte_milieu, $regs))) {
			$echap = produire_image_math($regs[1]);
			$pos = strpos($texte_milieu, $regs[0]);
			$texte_milieu = substr($texte_milieu,0,$pos)
				. code_echappement($echap, $source)
				. substr($texte_milieu,$pos+strlen($regs[0]));
		}

		$texte_a_voir = $texte_debut.$texte_milieu.$texte_fin;
	}

	return $texte_a_voir;
}

?>
