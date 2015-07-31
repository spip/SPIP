<?php

//
// Lit un document 'pdf' et extrait son contenu en texte brut
//

// NOTE : l'extracteur n'est pas oblige de convertir le contenu dans
// le charset du site, mais il *doit* signaler le charset dans lequel
// il envoie le contenu, de facon a ce qu'il soit converti au moment
// voulu ; dans le cas contraire le document sera lu comme s'il etait
// dans le charset iso-8859-1

function extracteur_pdf($fichier, &$charset) {

	/* methode tout PHP
		$pdf = new Format_PDF;
		$texte = $pdf->extraire_texte($fichier);
		echo $texte;
		exit;
	*/

	$charset = 'iso-8859-1';

	# metamail
	@exec('metamail -d -q -b -c application/pdf '.escapeshellarg($fichier), $r, $e);
	if (!$e) return @join(' ', $r);

	# pdftotext
	# http://www.glyphandcog.com/Xpdf.html
	# l'option "-enc utf-8" peut echouer ... dommage !
	@exec('pdftotext '.escapeshellarg($fichier).' -', $r, $e);
	if (!$e) return @join(' ', $r);
}

// Sait-on extraire ce format ?
// TODO: ici tester si les binaires fonctionnent
$GLOBALS['extracteur']['pdf'] = 'extracteur_pdf';






//
// Methode tout PHP (a tester)
//

class Format_PDF {
	var $trans_chars;
	var $flag_mono, $flag_brut;

	function convertir_caracteres($texte) {
		if (!$this->trans_chars) {
			// Caracteres speciaux
			$this->trans_chars = array(
				// ligatures typographiques (!)
				chr(2) => 'fi',
				chr(3) => 'fl',
				chr(174) => 'fi',
				chr(175) => 'fl',
				// "e" accent aigu
				chr(0) => chr(233)
			);
		}
		$texte = strtr($texte, $this->trans_chars);
		// Caracteres non-ascii codes en octal
		while (preg_match(',\\\\([0-7][0-7][0-7]),', $texte, $regs)) {
			$c = chr(octdec($regs[1]));
			$texte = str_replace($regs[0], $c, $texte);
			$this->trans_chars[$regs[0]] = $c;
		}
		return $texte;
	}

	function recoller_texte($stream) {
		static $chars_voyelles, $chars_fusion, $chars_caps, $chars_nums, $bichars_fusion;
		if (!$chars_voyelles) {
			$chars_voyelles = array('a'=>1, 'e'=>1, 'i'=>1, 'o'=>1, 'u'=>1, 'y'=>1);
			$chars_fusion = array('v'=>1, 'w'=>1, 'x'=>1, 'V'=>1, 'W'=>1, 'T'=>1);
			$chars_caps = array('A'=>1, 'B'=>1, 'C'=>1, 'D'=>1, 'E'=>1, 'F'=>1, 'G'=>1,
					'H'=>1, 'I'=>1, 'J'=>1, 'K'=>1, 'L'=>1, 'M'=>1, 'N'=>1,
					'O'=>1, 'P'=>1, 'Q'=>1, 'R'=>1, 'S'=>1, 'T'=>1, 'U'=>1,
					'V'=>1, 'W'=>1, 'X'=>1, 'Y'=>1, 'Z'=>1);
			$chars_nums = array('0'=>1, '1'=>1, '2'=>1, '3'=>1, '4'=>1, '5'=>1, '6'=>1, '7'=>1, '8'=>1, '9'=>1);
			$bichars_fusion = array('ve'=>1, 'vo'=>1, 'ev'=>1, 'ov'=>1,
						'xe'=>1, 'xo'=>1, 'ox'=>1, 'ex'=>1,
						'we'=>1, 'wo'=>1, 'ow'=>1, 'ew'=>1, 'ff'=>1);
		}
		// Longueur max pour limiter les erreurs d'extraction
		$chaine_len = 140;

		$stream = preg_split(",\)[^(]*\(,", $stream);
		$extrait = '';
		$fini = false;
		$this->flag_brut = false;
		// Cette boucle est capable de basculer entre deux trois d'execution :
		// - normal (plusieurs caracteres par chaine avec fusion)
		// - brut (plusieurs caracteres par chaine sans fusion)
		// - mono (un caractere par chaine)
		while (1) {
			if ($this->flag_mono) {
				// Un caractere par chaine : fusion rapide
				while (list(, $s) = each($stream)) {
					if (strlen($s) != 1) {
						if (strlen($s) < $chaine_len) $extrait .= $s;
						$this->flag_mono = false;
						break;
					}
					$extrait .= $s;
				}
				if ($this->flag_mono) break;
			}
			else if ($this->flag_brut) {
				// Concatenation sans fusion
				while (list(, $s) = each($stream)) $extrait .= $s;
				break;
			}
			$prev_s = '';
			$prev_c = '';
			$prev_l = 0;
			$nb_mono = 0;
			$nb_brut = 0;
			// Cas general : appliquer les regles de fusion
			while (list(, $s) = each($stream)) {
				$l = strlen($s);
				if ($l >= $chaine_len) continue;
				$c = $s{0};
				// Annulation de la cesure
				if ($prev_c == '-') {
					$extrait .= substr($prev_s, 0, -1);
				}
				else {
					$extrait .= $prev_s;
					$len_w = strpos($s.' ', ' ');
					$prev_len_w = $prev_l - strrpos($prev_s, ' ');
					$court = ($prev_len_w < 3 OR $len_w < 3);
					// Heuristique pour separation des mots
					if (/*$len_w == 1 OR $prev_len_w == 1
						OR */($court AND ($chars_fusion[$prev_c] OR $chars_fusion[$c]
							OR ($chars_caps[$prev_c] AND ($chars_caps[$c] OR $chars_nums[$c]))))
						OR ($prev_c == 'f' AND $chars_voyelles[$c])
						OR $bichars_fusion[$prev_c.$c]) {
					}
					else $extrait .= ' ';
				}
				$prev_c = $s{$l - 1};
				$prev_s = $s;
				$prev_l = $l;
				// Detection du format mono-caractere
				if ($l == 1) {
					if (++$nb_mono >= 3) {
						$this->flag_mono = true;
						break;
					}
				}
				else {
					$nb_mono = 0;
					if ($c == ' ' OR $prev_c == ' ') {
						$this->flag_brut = true;
						break;
					}
				}
			}
			$extrait .= $prev_s;
			if (!$this->flag_mono && !$this->flag_brut) break;
		}
		return $extrait;
	}

	function extraire_texte($fichier) {

		$source_len = 1024*1024;
		$stream_len = 20*1024;
		$texte_len = 40*1024;

		$f = fopen($fichier, "rb");
		if (!$f) die ("Fichier $fichier impossible a ouvrir");

		$in_stream = false;

		// Decouper le fichier en objets
		unset($objs);
		$objs = fread($f, $source_len);
		$objs = preg_split('/[\s>]endobj\s+/', $objs);
#		echo "<h3>".count($objs)." objets présents dans le buffer</h3>";

		// Parcourir le fichier pour trouver les streams
		reset($objs);
		$n = count($objs);
		for ($i = 0; $i < $n; $i++) {
			$obj = $objs[$i];

			if (!$in_stream) {
				// Stream (eviter les commentaires)
				$ok = preg_match("/stream(\r\n?|\n)/", $obj); // version rapide d'abord
				if ($ok) $ok = preg_match("/[\r\n](([^\r\n%]*[ \t>])*stream(\r\n?|\n))/", $obj, $regs);
				if (!$ok) continue;
				$p = strpos($obj, $regs[1]);
				$t = substr($obj, $p + strlen($regs[1]));
				$stream = "";
				$in_stream = true;

				$obj_text = substr($obj, 0, $p + strlen($regs[1]));

				// Parasites avant et apres
				//$obj_text = preg_replace("/^\s+obj\s+/", "", $obj_text);
				//$obj_text = preg_replace("/(\s+endobj)\s+.*$/", "\\1", $obj_text);

				// Commentaires
				$obj_text = preg_replace("/\\\\%/", ' ', $obj_text);
				$obj_text = preg_replace("/%[^\r\n]*[\r\n]+/", '', $obj_text);

				// Dictionnaire
				$obj_dict = "";
				//if (ereg("<<(.*)>>", $obj_text, $regs))
				if (preg_match("/<<(.*)>>/s", $obj_text, $regs)) // bug ?!
					$obj_dict = $regs[1];

#				echo "<hr>";
#				echo "Objet numéro $i<p>";
#				echo "<pre>".htmlspecialchars($obj_text)."</pre>";
			}
			else {
				$t = " endobj ".$obj; // approximation
			}
			unset($obj);

			// Recoller les morceaux du stream (au cas ou un "obj" se trouvait en clair dans un stream)
			if ($in_stream) {
				if (!($p = strpos($t, "endstream")) && !($q = strpos($t, "endobj"))) {
					$stream .= $t;
#					echo "<font color='red'>Stream continué</font><p>";
					continue;
				}
				$in_stream = false;
				if ($p) $stream .= substr($t, 0, $p);
				else $stream .= substr($t, 0, $q);
				unset($t);

				// Decoder le contenu du stream
				$encoding = '';
				if (preg_match(",/Filter\s*/([A-Za-z]+),", $obj_dict, $regs))
					$encoding = $regs[1];
				switch($encoding) {
				case 'FlateDecode':
					$stream = gzuncompress($stream); // pb avec certains PDFs !?
					break;
				case '':
					break;
				default:
					$stream = '';
				}
				/*if (preg_match("/\(d.marrage:\)/", $stream, $regs)) {
					$fs = fopen("demarrage.txt", "w");
					fwrite($fs, $regs[0]);
					fclose($fs);
					exit;
				}*/
			}

			if (!$stream) continue;

#			echo "Stream : ".strlen($stream)." octets<p>";

			// Eviter les fontes embarquees, etc.
			if (preg_match(',^%!,', $stream)) {
				unset($stream);
				continue;
			}
			// Detection texte / binaire
			$stream = substr($stream, 0, $stream_len);
			$stream = str_replace('\\(', ",", $stream);
			$stream = str_replace('\\)', ",", $stream);
			$n1 = substr_count($stream, '(');
			$n2 = substr_count($stream, ')');
			$freq = (substr_count($stream, ' ') + $n1 + $n2) / strlen($stream);
			if ($freq < 0.04 || (!$n1 && !$n2)) {
#				echo "no text (1)<p>";
				//echo htmlspecialchars($stream);
				unset($stream);
				continue;
			}
			$dev = abs($n1 - $n2) / ($n1 + $n2);
			if ($dev > 0.05) {
#				echo "no text (2)<p>";
				unset($stream);
				continue;
			}
			// Extraction des chaines
			if (strpos($stream, '<<') && strpos($stream, '>>'))
				$stream = preg_replace(',<<.*?'.'>>,s', '', $stream); // bug avec preg
			$stream = substr($stream, strpos($stream, '(') + 1);
			$stream = substr($stream, 0, strrpos($stream, ')')); // ici un bug occasionnel...
			$stream = $this->convertir_caracteres($stream);
			$extrait = $this->recoller_texte($stream);
			unset($stream);
			$texte .= $extrait;

			// Se limiter a une certaine taille de texte en sortie
			if (strlen($texte) > $texte_len) {
				$texte = substr($texte, 0, strrpos(substr($texte, 0, $texte_len), ' '));
				break;
			}
		}

		fclose($f);

		return $texte;
	}

} // class


?>
