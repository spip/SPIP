<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

//
// LCS (Longest Common Subsequence) en deux versions
// (ref: http://www2.toki.or.id/book/AlgDesignManual/BOOK/BOOK5/NODE208.HTM)

// Version ultra-simplifiee : chaque chaine est une permutation de l'autre 
// et on passe en parametre un des deux tableaux de correspondances
function lcs_opt($s) {
	$n = count($s);
	if (!$n) return array();
	$paths = array();
	$paths_ymin = array();
	$max_len = 0;

	// Insertion des points
	asort($s);
	foreach ($s as $y => $c) {
		for ($len = $max_len; $len > 0; $len--) {
			if ($paths_ymin[$len] < $y) {
				$paths_ymin[$len + 1] = $y;
				$paths[$len + 1] = $paths[$len];
				$paths[$len + 1][$y] = $c;
				break;
			}
		}
		if ($len == 0) {
			$paths_ymin[1] = $y;
			$paths[1] = array($y => $c);
		}
		if ($len + 1 > $max_len) $max_len = $len + 1;
	}
	return $paths[$max_len];
}

// Version normale : les deux chaines n'ont pas ete traitees au prealable
// par la fonction d'appariement
function lcs($s, $t) {
	$n = count($s);
	$p = count($t);
	if (!$n || !$p) return array(0 => array(), 1 => array());
	$paths = array();
	$paths_ymin = array();
	$max_len = 0;
	$s_pos = $t_pos = array();

	// Insertion des points
	foreach ($t as $y => $c) $t_pos[trim($c)][] = $y;

	foreach ($s as $x => $c) {
		$c = trim($c);
		if (!$t_pos[$c]) continue;
		krsort($t_pos[$c]);
		foreach ($t_pos[$c] as $y) {
			for ($len = $max_len; $len > 0; $len--) {
				if ($paths_ymin[$len] < $y) {
					$paths_ymin[$len + 1] = $y;
					// On construit le resultat sous forme de chaine d'abord,
					// car les tableaux de PHP sont dispendieux en taille memoire
					$paths[$len + 1] = $paths[$len]." $x,$y";
					break;
				}
			}
			if ($len + 1 > $max_len) $max_len = $len + 1;
			if ($len == 0) {
				$paths_ymin[1] = $y;
				$paths[1] = "$x,$y";
			}
		}
	}
	if ($paths[$max_len]) {
		$path = explode(" ", $paths[$max_len]);
		$u = $v = array();
		foreach ($path as $p) {
			list($x, $y) = explode(",", $p);
			$u[$x] = $y;
			$v[$y] = $x;
		}
		return array($u, $v);
	}
	return array(0 => array(), 1 => array());
}


function test_lcs($a, $b) {
	$s = explode(" ", $a);
	$t = explode(" ", $b);
	
	$t0 = explode(" ", microtime());
	list($r1, $r2) = lcs($s, $t);
	$t1 = explode(" ", microtime());
	$dt = $t1[0] + $t1[1] - $t0[0] - $t0[1];
	echo join(" ", $r1)."<br />";
	echo join(" ", $r2)."<p>";
	echo "<div style='font-weight: bold; color: red;'>$dt s.</div>";
}

function test_lcs_opt($s) {
	$s = preg_split(',\s+,', $s);

	$t0 = explode(" ", microtime());
	$t = lcs_opt($s);
	$t1 = explode(" ", microtime());
	$dt = $t1[0] + $t1[1] - $t0[0] - $t0[1];
	echo join(" ", $s)."<br />";
	echo join(" ", $t)."<p>";
	echo "<div style='font-weight: bold; color: red;'>$dt s.</div>";
}


//
// Generation de diff a plusieurs etages
//

class Diff {
	var $diff;
	var $fuzzy;

	function Diff($diff) {
		$this->diff = $diff;
		$this->fuzzy = true;
	}

	function comparer($new, $old) {
		$paras = $this->diff->segmenter($new);
		$paras_old = $this->diff->segmenter($old);
		if ($this->diff->fuzzy()) {
			list($trans_rev, $trans) = apparier_paras($paras_old, $paras);
			$lcs = lcs_opt($trans);
			$lcs_rev = array_flip($lcs);
		}
		else {
			list($trans_rev, $trans) = lcs($paras_old, $paras);
			$lcs = $trans;
			$lcs_rev = $trans_rev;
		}
	
		reset($paras_old);
		reset($paras);
		reset($lcs);
		unset($i_old);
		$fin_old = false;
		foreach ($paras as $i => $p) {
			if (!isset($trans[$i])) {
				// Paragraphe ajoute
				$this->diff->ajouter($p);
				continue;
			}
			$j = $trans[$i];
			if (!isset($lcs[$i])) {
				// Paragraphe deplace
				$this->diff->deplacer($p, $paras_old[$j]);
				continue;
			}
			if (!$fin_old) {
				// Paragraphes supprimes jusqu'au paragraphe courant
				if (!isset($i_old)) {
					list($i_old, $p_old) = each($paras_old);
					if (!$p_old) $fin_old = true;
				}
				while (!$fin_old && $i_old < $j) {
					if (!isset($trans_rev[$i_old])) {
						$this->diff->supprimer($p_old);
					}
					unset($i_old);
					list($i_old, $p_old) = each($paras_old);
					if (!$p_old) $fin_old = true;
				}
			}
			// Paragraphe n'ayant pas change de place
			$this->diff->comparer($p, $paras_old[$j]);
		}
		// Paragraphes supprimes a la fin du texte
		if (!$fin_old) {
			if (!isset($i_old)) {
				list($i_old, $p_old) = each($paras_old);
				if (!strlen($p_old)) $fin_old = true;
			}
			while (!$fin_old) {
				if (!isset($trans_rev[$i_old])) {
					$this->diff->supprimer($p_old);
				}
				list($i_old, $p_old) = each($paras_old);
				if (!$p_old) $fin_old = true;
			}
		}
		if (isset($i_old)) {
			if (!isset($trans_rev[$i_old])) {
				$this->diff->supprimer($p_old);
			}
		}
		return $this->diff->resultat();
	}
}

class DiffTexte {
	var $r;

	function DiffTexte() {
		$this->r = "";
	}

	function _diff($p, $p_old) {
		$diff = new Diff(new DiffPara);
		return $diff->comparer($p, $p_old);
	}

	function fuzzy() {
		return true;
	}
	function segmenter($texte) {
		return separer_paras($texte);
	}

	// NB :  rem=\"diff-\" est un signal pour la fonction "afficher_para_modifies"
	function ajouter($p) {
		$p = trim($p);
		$this->r .= "\n\n\n<div class=\"diff-para-ajoute\" title=\""._T('diff_para_ajoute')."\">".$p."</div rem=\"diff-\">";
	}
	function supprimer($p_old) {
		$p_old = trim($p_old);
		$this->r .= "\n\n\n<div class=\"diff-para-supprime\" title=\""._T('diff_para_supprime')."\">".$p_old."</div rem=\"diff-\">";
	}
	function deplacer($p, $p_old) {
		$this->r .= "\n\n\n<div class=\"diff-para-deplace\" title=\""._T('diff_para_deplace')."\">";
		$this->r .= trim($this->_diff($p, $p_old));
		$this->r .= "</div rem=\"diff-\">";
	}
	function comparer($p, $p_old) {
		$this->r .= "\n\n\n".$this->_diff($p, $p_old);
	}
	
	function resultat() {
		return $this->r;
	}
}

class DiffPara {
	var $r;

	function DiffPara() {
		$this->r = "";
	}

	function _diff($p, $p_old) {
		$diff = new Diff(new DiffPhrase);
		return $diff->comparer($p, $p_old);
	}

	function fuzzy() {
		return true;
	}
	function segmenter($texte) {
		$paras = array();
		$texte = trim($texte);
		while (preg_match('/[\.!\?]+\s*/u', $texte, $regs)) {
			$p = strpos($texte, $regs[0]) + strlen($regs[0]);
			$paras[] = substr($texte, 0, $p);
			$texte = substr($texte, $p);
		}
		if ($texte) $paras[] = $texte;
		return $paras;
	}

	function ajouter($p) {
		$this->r .= "<span class=\"diff-ajoute\" title=\""._T('diff_texte_ajoute')."\">".$p."</span rem=\"diff-\">";
	}
	function supprimer($p_old) {
		$this->r .= "<span class=\"diff-supprime\" title=\""._T('diff_texte_supprime')."\">".$p_old."</span rem=\"diff-\">";
	}
	function deplacer($p, $p_old) {
		$this->r .= "<span class=\"diff-deplace\" title=\""._T('diff_texte_deplace')."\">".$this->_diff($p, $p_old)."</span rem=\"diff-\">";
	}
	function comparer($p, $p_old) {
		$this->r .= $this->_diff($p, $p_old);
	}
	
	function resultat() {
		return $this->r;
	}
}

class DiffPhrase {
	var $r;

	function DiffPhrase() {
		$this->r = "";
	}

	function fuzzy() {
		return false;
	}
	function segmenter($texte) {
		$paras = array();
		if (test_pcre_unicode()) {
			$punct = '([[:punct:]]|'.plage_punct_unicode().')';
			$mode = 'u';
		}
		else {
			// Plages de poncutation pour preg_match bugge (ha ha)
			$punct = '([^\w\s\x80-\xFF]|'.plage_punct_unicode().')';
			$mode = '';
		}
		$preg = '/('.$punct.'+)(\s+|$)|(\s+)('.$punct.'*)/'.$mode;
		while (preg_match($preg, $texte, $regs)) {
			$p = strpos($texte, $regs[0]);
			$l = strlen($regs[0]);
			$punct = $regs[1] ? $regs[1] : $regs[6];
			$milieu = "";
			if ($punct) {
				// Attacher les raccourcis fermants au mot precedent
				if (preg_match(',^[\]}]+$,', $punct)) {
					$avant = substr($texte, 0, $p) . $regs[5] . $punct;
					$texte = $regs[4] . substr($texte, $p + $l);
				}
				// Attacher les raccourcis ouvrants au mot suivant
				else if ($regs[5] && preg_match(',^[\[{]+$,', $punct)) {
					$avant = substr($texte, 0, $p) . $regs[5];
					$texte = $punct . substr($texte, $p + $l);
				}
				// Les autres signes de ponctuation sont des mots a part entiere
				else {
					$avant = substr($texte, 0, $p);
					$milieu = $regs[0];
					$texte = substr($texte, $p + $l);
				}
			}
			else {
				$avant = substr($texte, 0, $p + $l);
				$texte = substr($texte, $p + $l);
			}
			if ($avant) $paras[] = $avant;
			if ($milieu) $paras[] = $milieu;
		}
		if ($texte) $paras[] = $texte;
		return $paras;
	}

	function ajouter($p) {
		$this->r .= "<span class=\"diff-ajoute\" title=\""._T('diff_texte_ajoute')."\">".$p."</span rem=\"diff-\"> ";
	}
	function supprimer($p_old) {
		$this->r .= "<span class=\"diff-supprime\" title=\""._T('diff_texte_supprime')."\">".$p_old."</span rem=\"diff-\"> ";
	}
	function comparer($p, $p_old) {
		$this->r .= $p;
	}

	function resultat() {
		return $this->r;
	}
}


function preparer_diff($texte) {
	include_ecrire("inc_charsets");

	$charset = $GLOBALS['meta']['charset'];
	if ($charset == 'utf-8')
		return unicode_to_utf_8(html2unicode($texte));
	return unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));
}

function afficher_diff($texte) {
	$charset = $GLOBALS['meta']['charset'];
	if ($charset == 'utf-8') return $texte;
	return charset2unicode($texte, 'utf-8');
}


?>
