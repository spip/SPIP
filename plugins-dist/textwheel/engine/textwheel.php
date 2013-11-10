<?php

/*
 * TextWheel 0.1
 *
 * let's reinvent the wheel one last time
 *
 * This library of code is meant to be a fast and universal replacement
 * for any and all text-processing systems written in PHP
 *
 * It is dual-licensed for any use under the GNU/GPL2 and MIT licenses,
 * as suits you best
 *
 * (c) 2009 Fil - fil@rezo.net
 * Documentation & http://zzz.rezo.net/-TextWheel-
 *
 * Usage: $wheel = new TextWheel(); echo $wheel->text($text);
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

require_once dirname(__FILE__)."/textwheelruleset.php";

class TextWheel {
	protected $ruleset;
	protected static $subwheel = array();

	protected $compiled = array();

	/**
	 * Constructor
	 * @param TextWheelRuleSet $ruleset
	 */
	public function TextWheel($ruleset = null) {
		$this->setRuleSet($ruleset);
	}

	/**
	 * Set RuleSet
	 * @param TextWheelRuleSet $ruleset
	 */
	public function setRuleSet($ruleset){
		if (!is_object($ruleset))
			$ruleset = new TextWheelRuleSet ($ruleset);
		$this->ruleset = $ruleset;
	}

	/**
	 * Apply all rules of RuleSet to a text
	 *
	 * @param string $t
	 * @return string
	 */
	public function text($t) {
		$rules = & $this->ruleset->getRules();
		## apply each in order
		foreach ($rules as $name => $rule) #php4+php5
		{
			$this->apply($rules[$name], $t);
		}
		#foreach ($this->rules as &$rule) #smarter &reference, but php5 only
		#	$this->apply($rule, $t);
		return $t;
	}

	private function export($x) {
		return addcslashes(var_export($x, true), "\n\r\t");
	}

	public function compile($b = null) {
		$rules = & $this->ruleset->getRules();

		## apply each in order
		$pre = array();
		$comp = array();

		foreach ($rules as $name => $rule)
		{
			$rule->name = $name;
			$this->initRule($rule);
			if (is_string($rule->replace)
			AND isset($this->compiled[$rule->replace])
			AND $fun = $this->compiled[$rule->replace]) {
				$pre[] = "\n###\n## $name\n###\n" . $fun;
				preg_match(',function (\w+),', $fun, $r);
				$rule->compilereplace = $r[1]; # ne pas modifier ->replace sinon on casse l'execution...
			}

			$r = "\t/* $name */\n";

			if ($rule->require)
				$r .= "\t".'require_once '.TextWheel::export($rule->require).';'."\n";
			if ($rule->if_str)
				$r .= "\t".'if (strpos($t, '.TextWheel::export($rule->if_str).') === false)'."\n";
			if ($rule->if_stri)
				$r .= "\t".'if (stripos($t, '.TextWheel::export($rule->if_stri).') === false)'."\n";
			if ($rule->if_match)
				$r .= "\t".'if (preg_match('.TextWheel::export($rule->if_match).', $t))'."\n";

			if ($rule->func_replace !== 'replace_identity') {
				$fun = 'TextWheel::'.$rule->func_replace;
				switch($fun) {
					case 'TextWheel::replace_all_cb':
						$fun = $rule->replace; # trim()...
						break;
					case 'TextWheel::replace_preg':
						$fun = 'preg_replace';
						break;
					case 'TextWheel::replace_str':
						$fun = 'str_replace';
						break;
					case 'TextWheel::replace_preg_cb':
						$fun = 'preg_replace_callback';
						break;
					default:
						break;
				}
				$r .= "\t".'$t = '.$fun.'('.TextWheel::export($rule->match).', '.TextWheel::export($rule->replace).', $t);'."\n";
			}

			$comp[] = $r;
		}
		$code = join ("\n", $comp);
		$code = 'function '.$b.'($t) {' . "\n". $code . "\n\treturn \$t;\n}\n\n";
		$code = join ("\n", $pre) . $code;

		return $code;
	}


	/**
	 * Get an internal global subwheel
	 * read acces for annymous function only
	 *
	 * @param int $n
	 * @return TextWheel
	 */
	public static function &getSubWheel($n){
		return TextWheel::$subwheel[$n];
	}

	/**
	 * Create SubWheel (can be overriden in debug class)
	 * @param TextWheelRuleset $rules
	 * @return TextWheel
	 */
	protected function &createSubWheel(&$rules){
		$tw = new TextWheel($rules);
		return $tw;
	}

	/**
	 * Initializing a rule a first call
	 * including file, creating function or wheel
	 * optimizing tests
	 *
	 * @param TextWheelRule $rule
	 */
	protected function initRule(&$rule){
		# language specific
		if ($rule->require){
			require_once $rule->require;
		}

		# optimization: strpos or stripos?
		if (isset($rule->strpos)) {
			if (strtolower($rule->if_str) !== strtoupper($rule->if_str)) {
				$rule->if_stri = $rule->if_str;
				unset($rule->if_str);
			}
		}

		if ($rule->create_replace){
			$compile = $rule->replace.'($t)';
			$rule->replace = create_function('$m', $rule->replace);
			$this->compiled[$rule->replace] = $compile;
			$rule->create_replace = false;
			$rule->is_callback = true;
		}
		elseif ($rule->is_wheel){
			$n = count(TextWheel::$subwheel);
			TextWheel::$subwheel[] = $this->createSubWheel($rule->replace);
			$var = '$m['.intval($rule->pick_match).']';
			if ($rule->type=='all' OR $rule->type=='str' OR $rule->type=='split' OR !isset($rule->match))
				$var = '$m';
			$code = 'return TextWheel::getSubWheel('.$n.')->text('.$var.');';
			$rule->replace = create_function('$m', $code);
			$cname = 'compiled_'.str_replace('-','_', $rule->name);
			$compile = TextWheel::getSubWheel($n)->compile($cname);
			$this->compiled[$rule->replace] = $compile;
			$rule->is_wheel = false;
			$rule->is_callback = true;
		}

		# optimization
		$rule->func_replace = '';
		if (isset($rule->replace)) {
			switch($rule->type) {
				case 'all':
					$rule->func_replace = 'replace_all';
					break;
				case 'str':
					$rule->func_replace = 'replace_str';
					// test if quicker strtr usable
					if (!$rule->is_callback
						AND is_array($rule->match) AND is_array($rule->replace)
						AND $c = array_map('strlen',$rule->match) 
						AND $c = array_unique($c)
						AND count($c)==1
						AND reset($c)==1
						AND $c = array_map('strlen',$rule->replace)
						AND $c = array_unique($c)
						AND count($c)==1
						AND reset($c)==1
						){
						$rule->match = implode('',$rule->match);
						$rule->replace = implode('',$rule->replace);
						$rule->func_replace = 'replace_strtr';
					}
					break;
				case 'split':
					$rule->func_replace = 'replace_split';
					$rule->match = array($rule->match,  is_null($rule->glue)?$rule->match:$rule->glue);
					break;
				case 'preg':
				default:
					$rule->func_replace = 'replace_preg';
					break;
			}
			if ($rule->is_callback)
				$rule->func_replace .= '_cb';
		}
		if (!method_exists("TextWheel", $rule->func_replace)){
			$rule->disabled = true;
			$rule->func_replace = 'replace_identity';
		}
		# /end
	}

	/**
	 * Apply a rule to a text
	 *
	 * @param TextWheelRule $rule
	 * @param string $t
	 * @param int $count
	 */
	protected function apply(&$rule, &$t, &$count=null) {

		if ($rule->disabled)
			return;

		if (isset($rule->if_chars) AND (strpbrk($t, $rule->if_chars) === false))
			return;

		if (isset($rule->if_str) AND strpos($t, $rule->if_str) === false)
			return;

		if (isset($rule->if_stri) AND stripos($t, $rule->if_str) === false)
			return;

		if (isset($rule->if_match) AND !preg_match($rule->if_match, $t))
			return;

		if (!isset($rule->func_replace))
			$this->initRule($rule);

		$func = $rule->func_replace;
		TextWheel::$func($rule->match,$rule->replace,$t,$count);
	}

	/**
	 * No Replacement function
	 * fall back in case of unknown method for replacing
	 * should be called max once per rule
	 * 
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_identity(&$match,&$replace,&$t,&$count){
	}

	/**
	 * Static replacement of All text
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_all(&$match,&$replace,&$t,&$count){
		# special case: replace $0 with $t
		#   replace: "A$0B" will surround the string with A..B
		#   replace: "$0$0" will repeat the string
		if (strpos($replace, '$0')!==FALSE)
			$t = str_replace('$0', $t, $replace);
		else
			$t = $replace;
	}

	/**
	 * Call back replacement of All text
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_all_cb(&$match,&$replace,&$t,&$count){
		$t = $replace($t);
	}

	/**
	 * Static string replacement
	 *
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_str(&$match,&$replace,&$t,&$count){
		if (!is_string($match) OR strpos($t,$match)!==FALSE)
			$t = str_replace($match, $replace, $t, $count);
	}

	/**
	 * Fast Static string replacement one char to one char
	 *
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_strtr(&$match,&$replace,&$t,&$count){
		$t = strtr( $t, $match, $replace);
	}

	/**
	 * Callback string replacement
	 *
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_str_cb(&$match,&$replace,&$t,&$count){
		if (strpos($t,$match)!==FALSE)
			if (count($b = explode($match, $t)) > 1)
				$t = join($replace($match), $b);
	}

	/**
	 * Static Preg replacement
	 *
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_preg(&$match,&$replace,&$t,&$count){
		$t = preg_replace($match, $replace, $t, -1, $count);
	}

	/**
	 * Callback Preg replacement
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_preg_cb(&$match,&$replace,&$t,&$count){
		$t = preg_replace_callback($match, $replace, $t, -1, $count);
	}


	/**
	 * Static split replacement : invalid
	 * @param mixed $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_split(&$match,&$replace,&$t,&$count){
		throw new InvalidArgumentException('split rule always needs a callback function as replace');
	}

	/**
	 * Callback split replacement
	 * @param array $match
	 * @param mixed $replace
	 * @param string $t
	 * @param int $count
	 */
	protected static function replace_split_cb(&$match,&$replace,&$t,&$count){
		$a = explode($match[0], $t);
		$t = join($match[1], array_map($replace,$a));
	}
}

class TextWheelDebug extends TextWheel {
	static protected $t; #tableaux des temps
	static protected $tu; #tableaux des temps (rules utilises)
	static protected $tnu; #tableaux des temps (rules non utilises)
	static protected $u; #compteur des rules utiles
	static protected $w; #compteur des rules appliques
	static $total;

	/**
	 * Timer for profiling
	 * 
	 * @staticvar int $time
	 * @param string $t
	 * @param bool $raw
	 * @return int/strinf
	 */
	protected function timer($t='rien', $raw = false) {
		static $time;
		$a=time(); $b=microtime();
		// microtime peut contenir les microsecondes et le temps
		$b=explode(' ',$b);
		if (count($b)==2) $a = end($b); // plus precis !
		$b = reset($b);
		if (!isset($time[$t])) {
			$time[$t] = $a + $b;
		} else {
			$p = ($a + $b - $time[$t]) * 1000;
			unset($time[$t]);
			if ($raw) return $p;
			if ($p < 1000)
				$s = '';
			else {
				$s = sprintf("%d ", $x = floor($p/1000));
				$p -= ($x*1000);
			}
			return $s . sprintf("%.3f ms", $p);
		}
	}

	/**
	 * Apply all rules of RuleSet to a text
	 *
	 * @param string $t
	 * @return string
	 */
	public function text($t) {
		$rules = & $this->ruleset->getRules();
		## apply each in order
		foreach ($rules as $name => $rule) #php4+php5
		{
			if (is_int($name))
				$name .= ' '.$rule->match;
			$this->timer($name);
			$b = $t;
			$this->apply($rule, $t);
			TextWheelDebug::$w[$name] ++; # nombre de fois appliquee
			$v = $this->timer($name, true); # timer
			TextWheelDebug::$t[$name] += $v;
			if ($t !== $b) {
				TextWheelDebug::$u[$name] ++; # nombre de fois utile
				TextWheelDebug::$tu[$name] += $v;
			} else {
				TextWheelDebug::$tnu[$name] += $v;
			}
			
		}
		#foreach ($this->rules as &$rule) #smarter &reference, but php5 only
		#	$this->apply($rule, $t);
		return $t;
	}

	/**
	 * Ouputs data stored for profiling/debuging purposes
	 */
	public static function outputDebug(){
		if (isset(TextWheelDebug::$t)) {
			$time = array_flip(array_map('strval', TextWheelDebug::$t));
			krsort($time);
			echo "
			<div class='textwheeldebug'>
			<style type='text/css'>
				.textwheeldebug table { margin:1em 0; }
				.textwheeldebug th,.textwheeldebug td { padding-left: 15px }
				.textwheeldebug .prof-0 .number { padding-right: 60px }
				.textwheeldebug .prof-1 .number { padding-right: 30px }
				.textwheeldebug .prof-1 .name { padding-left: 30px }
				.textwheeldebug .prof-2 .name { padding-left: 60px }
				.textwheeldebug .zero { color:orange; }
				.textwheeldebug .number { text-align:right; }
				.textwheeldebug .strong { font-weight:bold; }
			</style>
			<table class='sortable'>
			<caption>Temps par rule</caption>
			<thead><tr><th>temps&nbsp;(ms)</th><th>rule</th><th>application</th><th>t/u&nbsp;(ms)</th><th>t/n-u&nbsp;(ms)</th></tr></thead>\n";
			$total = 0;
			foreach($time as $t => $r) {
				$applications = intval(TextWheelDebug::$u[$r]);
				$total += $t;
				if(intval($t*10))
					echo "<tr>
					<td class='number strong'>".number_format(round($t*10)/10,1)."</td><td> ".htmlspecialchars($r)."</td>
					<td"
					. (!$applications ? " class='zero'" : "")
					.">".$applications."/".intval(TextWheelDebug::$w[$r])."</td>
					<td class='number'>".($applications?number_format(round(TextWheelDebug::$tu[$r]/$applications*100)/100,2):"") ."</td>
					<td class='number'>".(($nu = intval(TextWheelDebug::$w[$r])-$applications)?number_format(round(TextWheelDebug::$tnu[$r]/$nu*100)/100,2):"") ."</td>
					</tr>";
			}
			echo "</table>\n";

			echo "
			<table>
			<caption>Temps total par rule</caption>
			<thead><tr><th>temps</th><th>rule</th></tr></thead>\n";
			ksort($GLOBALS['totaux']);
			TextWheelDebug::outputTotal($GLOBALS['totaux']);
			echo "</table>";
			# somme des temps des rules, ne tient pas compte des subwheels
			echo "<p>temps total rules: ".round($total)."&nbsp;ms</p>\n";
			echo "</div>\n";
		}
	}

	public static function outputTotal($liste, $profondeur=0) {
		ksort($liste);
		foreach ($liste as $cause => $duree) {
			if (is_array($duree)) {
				TextWheelDebug::outputTotal($duree, $profondeur+1);
			} else {
				echo "<tr class='prof-$profondeur'>
					<td class='number'><b>".intval($duree)."</b>&nbsp;ms</td>
					<td class='name'>".htmlspecialchars($cause)."</td>
					</tr>\n";
			}
		}
	}
	
	/**
	 * Create SubWheel (can be overriden in debug class)
	 * @param TextWheelRuleset $rules
	 * @return TextWheel
	 */
	protected function &createSubWheel(&$rules){
		return new TextWheelDebug($rules);
	}

}



/**
 * stripos for php4
 */
if (!function_exists('stripos')) {
	function stripos($haystack, $needle) {
		return strpos($haystack, stristr( $haystack, $needle ));
	}
}

/**
 * approximation of strpbrk for php4
 * return false if no char of $char_list is in $haystack
 */
if (!function_exists('strpbrk')) {
	function strpbrk($haystack, $char_list) {
    $result = strcspn($haystack, $char_list);
    if ($result != strlen($haystack)) {
        return $result;
    }
    return false;
	}
}
