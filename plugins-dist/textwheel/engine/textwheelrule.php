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

class TextWheelRule {

	## rule description
	# optional
	var $priority = 0; # rule priority (rules are applied in ascending order)
		# -100 = application escape, +100 = application unescape
	var $name; # rule's name
	var $author; # rule's author
	var $url; # rule's homepage
	var $package; # rule belongs to package
	var $version; # rule version
	var $test; # rule test function
	var $disabled=false; # true if rule is disabled

	## rule init checks
	## the rule will be applied if the text...
	# optional
	var $if_chars; # ...contains one of these chars
	var $if_str; # ...contains this string (case sensitive)
	var $if_stri; # ...contains this string (case insensitive)
	var $if_match; # ...matches this simple expr


	## rule effectors, matching
	# mandatory
	var $type; # 'preg' (default), 'str', 'all', 'split'...
	var $match; # matching string or expression
	# optional
	# var $limit; # limit number of applications (unused)

	## rule effectors, replacing
	# mandatory
	var $replace; # replace match with this expression

	# optional
	var $is_callback=false; # $replace is a callback function
	var $is_wheel; # flag to create a sub-wheel from rules given as replace
	var $pick_match = 0; # item to pick for sub-wheel replace
	var $glue = null; # glue for implode ending split rule

	# optional
	# language specific
	var $require; # file to require_once
	var $create_replace; # do create_function('$m', %) on $this->replace, $m is the matched array

	# optimizations
	var $func_replace;

	/**
	 * Rule constructor
	 * @param <type> $args
	 * @return <type>
	 */
	public function TextWheelRule($args) {
		if (!is_array($args))
			return;
		foreach($args as $k=>$v)
			if (property_exists($this, $k))
				$this->$k = $args[$k];
		$this->checkValidity(); // check that the rule is valid
	}

	/**
	 * Rule checker
	 */
	protected function checkValidity(){
		if ($this->type=='split'){
			if (is_array($this->match))
				throw new InvalidArgumentException('match argument for split rule can\'t be an array');
			if (isset($this->glue) AND is_array($this->glue))
				throw new InvalidArgumentException('glue argument for split rule can\'t be an array');
		}
	}

}
