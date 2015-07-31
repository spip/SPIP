<?php
/**
 * SourceMap class,
 *	reads a generic language source code and returns its map.
 * ______________________________________________________________
 * The SourceMap goals is to create a map of a generic script/program language.
 * The getMap method returns an array/list of arrays/dictionary/objects
 * of source map using delimeters variable to map correctly:
 *  - multi line comments
 *  - single line comments
 *  - double quoted strings
 *  - single quoted strings
 *  - pure code
 *  - everything else (for example regexp [/re/] with javascript), just adding a correct delimeter
 * --------------------------------------------------------------
 * What about the delimeter
 * 	It's an array/list of arrays/dictionary/obects with some properties to find what you're looking for.
 * 
 * parameters are:
 *  - name, the name of the delimeter (i.e. "doublequote")
 *  - start, one or mode chars to find as start delimeter (i.e. " for double quoted string)
 *  - end, one or mode chars to find as end delimeter (i.e. " for double quoted string) [end should be an array/list too]
 * 
 * optional parameters are:
 *  - noslash, if true find the end of the delimeter only if last char is not slashed (i.e. "string\"test" find " after test)
 *  - match, if choosed language has regexp, verify if string from start to end matches used regexp (i.e. /^\/[^\n\r]+\/$/ for JavaScript regexp)
 * 
 * If end parameter is an array, match and noslash are not supported (i.e. ["\n", "\r"] for end delimeter of a single line comment)
 * --------------------------------------------------------------
 * What about SourceMap usage
 * 	It should be a good solution to create sintax highlighter, parser,
 * 	verifier or some other source code parsing procedure
 * --------------------------------------------------------------
 * What about SourceMap performance script/languages
 * 	I've created different version of this class to test each script/program language performance too.
 * Python with or without Psyco is actually the faster parser.
 * However with this PHP version this class has mapped "dojo.js.uncompressed.js" file (about 211Kb) in less than 0.5 second.
 * Test has been done with embed class and PHP as module, any accelerator was used for this PHP test.
 * --------------------------------------------------------------
 * @Compatibility	>= PHP 4
 * @Author		Andrea Giammarchi
 * @Site		http://www.devpro.it/
 * @Date		2006/08/01
 * @LastMOd		2006/08/01
 * @Version		0.1
 * @Application		Last version of JavaScriptCompressor class use this one to map source code.
 */
class SourceMap {
	
	/**
	 * public method
         * 	getMap(&$source:string, &$delimeters:array):array
	 * Maps the source code using $delimeters rules and returns map as an array
         * NOTE: read comments to know more about map and delimeter
         *
         * @param	string		generic source code
         * @param	array		array with nested array with code rules
	 */
	function getMap(&$source, &$delimeters) {
		
		# "unsigned" integer variables
		$sourcePosition = 0;
		$delimetersPosition = 0;
		$findLength = 0;
		$len = 0;
		$tempIndex = 0;
		$sourceLength = strlen($source);
		$delimetersLength = count($delimeters);
		
		# integer variables
		$tempPosition = -1;
		$endPosition = -1;
		
		# array variables
		$map = array();
		$tempMap = array();
		$tempDelimeter = array();
		
		while($sourcePosition < $sourceLength) {
			$endPosition = -1;
			for($delimetersPosition = 0; $delimetersPosition < $delimetersLength; $delimetersPosition++) {
				$tempPosition = strpos($source, $delimeters[$delimetersPosition]['start'], $sourcePosition);
				if($tempPosition !== false && ($tempPosition < $endPosition || $endPosition === -1)) {
					$endPosition = $tempPosition;
					$tempIndex = $delimetersPosition;
				}
			}
			if($endPosition !== -1) {
				$sourcePosition = $endPosition;
				$tempDelimeter = &$delimeters[$tempIndex];
				$findLength = strlen($tempDelimeter['start']);
				if(is_array($tempDelimeter['end'])) {
					$delimetersPosition = 0;
					$endPosition = -1;
					for($len = count($tempDelimeter['end']); $delimetersPosition < $len; $delimetersPosition++) {
						$tempPosition = strpos($source, $tempDelimeter['end'][$delimetersPosition], $sourcePosition + $findLength);
						if($tempPosition !== false && ($tempPosition < $endPosition || $endPosition === -1)) {
							$endPosition = $tempPosition;
							$tempIndex = $delimetersPosition;
						}	
					}
					if($endPosition !== -1)
						$endPosition = $endPosition + strlen($tempDelimeter['end'][$tempIndex]);
					else
						$endPosition = $sourceLength;
					array_push($map, array('name'=>$tempDelimeter['name'], 'start'=>$sourcePosition, 'end'=>$endPosition));
					$sourcePosition = $endPosition - 1;
				}
				elseif(isset($tempDelimeter['match'])) {
					$tempPosition = strpos($source, $tempDelimeter['end'], $sourcePosition + $findLength);
					$len = strlen($tempDelimeter['end']);
					if($tempPosition !== false && preg_match($tempDelimeter['match'], substr($source, $sourcePosition, $tempPosition - $sourcePosition + $len))) {
						$endPosition = isset($tempDelimeter['noslash']) ? $this->__endCharNoSlash($source, $sourcePosition, $tempDelimeter['end'], $sourceLength) : $tempPosition + $len;
						array_push($map, array('name'=>$tempDelimeter['name'], 'start'=>$sourcePosition, 'end'=>$endPosition));
						$sourcePosition = $endPosition - 1;
					}
				}
				else {
					if(isset($tempDelimeter['noslash']))
						$endPosition = $this->__endCharNoSlash($source, $sourcePosition, $tempDelimeter['end'], $sourceLength);
					else {
						$tempPosition = strpos($source, $tempDelimeter['end'], $sourcePosition + $findLength);
						if($tempPosition !== false)
							$endPosition = $tempPosition + strlen($tempDelimeter['end']);
						else
							$endPosition = $sourceLength;
					}
					array_push($map, array('name'=>$tempDelimeter['name'], 'start'=>$sourcePosition, 'end'=>$endPosition));
					$sourcePosition = $endPosition - 1;
				}
			}
			else
				$sourcePosition = $sourceLength - 1;
			++$sourcePosition;
		}
		$len = count($map);
		if($len === 0)
			array_push($tempMap, array('name'=>'code', 'start'=>0, 'end'=>$sourceLength));
		else {
			for($tempIndex = 0; $tempIndex < $len; $tempIndex++) {
				if($tempIndex === 0 && $map[$tempIndex]['start'] > 0)
					array_push($tempMap, array('name'=>'code', 'start'=>0, 'end'=>$map[$tempIndex]['start']));
				elseif($tempIndex > 0 && $map[$tempIndex]['start'] > $map[$tempIndex-1]['end'])
					array_push($tempMap, array('name'=>'code', 'start'=>$map[$tempIndex-1]['end'], 'end'=>$map[$tempIndex]['start']));
				array_push($tempMap, array('name'=>$map[$tempIndex]['name'], 'start'=>$map[$tempIndex]['start'], 'end'=>$map[$tempIndex]['end']));
				if($tempIndex + 1 === $len && $map[$tempIndex]['end'] < $sourceLength)
					array_push($tempMap, array('name'=>'code', 'start'=>$map[$tempIndex]['end'], 'end'=>$sourceLength));
			}
		}
		return $tempMap;
	}
	
	function __endCharNoSlash(&$source, $position, &$find, &$len) {
		$temp = strlen($find);
		do {
			$position = strpos($source, $find, $position + 1);
		}while($position !== false && !$this->__charNoSlash($source, $position));
		if($position === false) $position = $len - $temp;
		return $position + $temp;
	}
	
	function __charNoSlash(&$source, &$position) {
		$next = 1; $len = $position - $next;
		while($len > 0 && $source{$len} === '\\') $len = $position - (++$next);
		return (($next - 1) % 2 === 0);
	}
}
/**
 * BaseConvert class,
 *	converts an unsigned base 10 integer to a different base and vice versa.
 * ______________________________________________________________
 * BaseConvert
 *    |
 *    |________ constructor(newBase:string)
 *    |         	uses newBase string var for convertion
 *    |                 [i.e. "0123456789abcdef" for an hex convertion]
 *    |
 *    |________ toBase(unsignedInteger:uint):string
 *    |         	return base value of input
 *    |
 *    |________ fromBase(baseString:string):uint
 *              	return base 10 integer value of base input
 * --------------------------------------------------------------
 * REMEMBER: PHP < 6 doesn't work correctly with integer greater than 2147483647 (2^31 - 1)
 * --------------------------------------------------------------
 * @Compatibility	>= PHP 4
 * @Author		Andrea Giammarchi
 * @Site		http://www.devpro.it/
 * @Date		2006/06/05
 * @Version		1.0
 */
class BaseConvert {
	
	var	$base, $baseLength;
	
	function BaseConvert($base) {
		$this->base = &$base;
		$this->baseLength = strlen($base);
	}
	
	function toBase($num) {
		$module = 0; $result = '';
		while($num) {
			$result = $this->base{($module = $num % $this->baseLength)}.$result;
			$num = (int)(($num - $module) / $this->baseLength);
		}
		return $result !== '' ? $result : $this->base{0};
	}
	
	function fromBase($str) {
		$pos = 0; $len = strlen($str) - 1; $result = 0;
		while($pos < $len)
			$result += pow($this->baseLength, ($len - $pos)) * strpos($this->base, $str{($pos++)});
		return $len >= 0 ? $result + strpos($this->base, $str{($pos)}) : null;
	}
}

/**
 * JavaScriptCompressor class,
 *	removes comments or pack JavaScript source[s] code.
 * ______________________________________________________________
 * JavaScriptCompressor (just 2 public methods)
 *    |
 *    |________ getClean(jsSource:mixed):string
 *    |         	returns one or more JavaScript code without comments,
 *    |         	by default removes some spaces too
 *    |
 *    |________ getPacked(jsSource:mixed):string
 *              	returns one or more JavaScript code packed,
 *	        	using getClean and obfuscating output
 * --------------------------------------------------------------
 * Note about $jsSource input varible:
 * 	this var should be a string (i.e. $jsSource = file_get_contents("myFile.js");)
 *      should be an array of strings (i.e. array(file_get_contents("1.js"), file_get_contents("2.js"), ... ))
 *      should be an array with 1 or 2 keys:
 *      	(i.e. array('code'=>file_get_contents("mySource.js")))
 *              (i.e. array('code'=>file_get_contents("mySource.js"), 'name'=>'mySource'))
 *      ... and should be an array of arrays created with theese rules
 *      array(
 *		file_get_contents("secret.js"),
 *              array('code'=>$anotherJS),
 *              array('code'=>$myJSapplication, 'name'=>'JSApplication V 1.0')
 *      )
 *
 *      The name used on dedicated key, will be write on parsed source header
 * --------------------------------------------------------------
 * Note about returned strings:
 * 	Your browser should wrap very long strings, then don't use
 *      cut and paste from your browser, save output into your database or directly
 *      in a file or print them only inside <script> and </script> tags
 * --------------------------------------------------------------
 * Note about parser performance:
 * 	With pure PHP embed code this class should be slow and not really safe
 *      for your server performance then don't parse JavaScript runtime for each
 *      file you need and create some "parsed" caching system
 *      (at least while i've not created a compiled version of theese class functions).
 *      Here there's a caching system example: http://www.phpclasses.org/browse/package/3158.html
 * --------------------------------------------------------------
 * Note about JavaScript packed compatibility:
 * 	To be sure about compatibility include before every script JSL Library:
 *      http://www.devpro.it/JSL/
 * JSL library add some features for old or buggy browsers, one of
 * those functions is String.replace with function as second argument,
 * used by JavaScript generated packed code to rebuild original code.
 *
 * Remember that KDE 3.5, Safari and IE5 will not work correctly with packed version
 * if you'll not include JSL.
 * --------------------------------------------------------------
 * @Compatibility	>= PHP 4
 * @Author		Andrea Giammarchi
 * @Site		http://www.devpro.it/
 * @Date		2006/05/31
 * @LastMOd		2006/08/01 [requires SourceMap.class.php to parse source faster and better (dojo.js.uncompressed.js file (211Kb) successfull cleaned or packed)]
 * @Version		0.8
 * @Dependencies	Server: BaseConvert.class.php
 *			Server: SourceMap.class.php
 *			Client: JSL.js (http://www.devpro.it/JSL/)
 * @Browsers		Convertion is supported by every browser with JSL Library (FF 1+ Opera 8+ and IE5.5+ are supported without JSL too)
 * @Credits		Dean Edwards for his originally idea [dean.edwards.name] and his JavaScript packer
 */
if(!class_exists('SourceMap'))
	require 'SourceMap.class.php';
class JavaScriptCompressor {

	/**
	 * public variables
         * 	stats:string		after every compression has some informations
         *      version:string		version of this class
	 */
	var	$stats = '',
		$version = '0.8';

	/** 'private' variables, any comment sorry */
	var	$__startTime = 0,
		$__sourceLength = 0,
		$__sourceNewLength = 0,
		$__totalSources = 0,
		$__sources = array(),
		$__delimeter = array(),
		$__cleanFinder = array("/(\n|\r)+/", "/( |\t)+/", "/(\n )|( \n)|( \n )/", "/[[:space:]]+(\)|})/", "/(\(|{)[[:space:]]+/", "/[[:space:]]*(;|,|:|<|>|\&|\||\=|\?|\+|\-|\%)[[:space:]]*/", "/\)[[:space:]]+{/", "/}[[:space:]]+\(/"),
		$__cleanReplacer = array("\n", " ", "\n", "\\1", "\\1", "\\1", "){", "}("),
		$__BC = null,
		$__SourceMap = null;

	/**
	 * public constructor
         * 	creates a new BaseConvert class variable (base 36)
	 */
	function JavaScriptCompressor() {
		$this->__SourceMap = new SourceMap();
		$this->__BC = new BaseConvert('0123456789abcdefghijklmnopqrstuvwxyz');
		$this->__delimeter = array(
			array('name'=>'doublequote', 'start'=>'"', 'end'=>'"', 'noslash'=>true),
			array('name'=>'singlequote', 'start'=>"'", 'end'=>"'", 'noslash'=>true),
			array('name'=>'singlelinecomment', 'start'=>'//', 'end'=>array("\n", "\r")),
			array('name'=>'multilinecomment', 'start'=>'/*', 'end'=>'*/'),
			array('name'=>'regexp', 'start'=>'/', 'end'=>'/', 'match'=>"/^\/[^\n\r]+\/$/", 'noslash'=>true)
		);
	}

	/**
	 * public method
         * 	getClean(mixed [, bool]):string
         *      compress JavaScript removing comments and somespaces (on by default)
         * @param	mixed		view example and notes on class comments
	 */
	function getClean($jsSource) {
		return $this->__commonInitMethods($jsSource, false);
	}
	
	/**
	 * public method
         * 	getPacked(mixed):string
         *      compress JavaScript replaceing words and removing comments and some spaces
         * @param	mixed		view example and notes on class comments
	 */
	function getPacked($jsSource) {
		return $this->__commonInitMethods($jsSource, true);
	}
	
	/** 'private' methods, any comment sorry */
	function __addCleanCode($str) {
		return preg_replace($this->__cleanFinder, $this->__cleanReplacer, trim($str));
	}
	function __addClean(&$arr, &$str, &$start, &$end, $clean) {
		if($clean)
			array_push($arr, $this->__addCleanCode(substr($str, $start, $end - $start)));
		else
			array_push($arr, substr($str, $start, $end - $start));
	}
	function __clean(&$str) {
		$len = strlen($str);
		$type = '';
		$clean = array();
		$map = $this->__SourceMap->getMap($str, $this->__delimeter);
		for($a = 0, $b = 0, $c = count($map); $a < $c; $a++) {
			$type = &$map[$a]['name'];
			switch($type) {
				case 'code':
				case 'regexp':
				case 'doublequote':
				case 'singlequote':
					$this->__addClean($clean, $str, $map[$a]['start'], $map[$a]['end'], ($type === 'code'));
					if($type !== 'regexp')
						array_push($clean, "\n");	
					break;
			}
 		}
		return preg_replace("/(\n)+/", "\n", trim(implode('', $clean)));
	}
	function __commonInitMethods(&$jsSource, $packed) { 
		$header = '';
		$this->__startTime = $this->__getTime();
		$this->__sourceLength = 0;
		$this->__sourceManager($jsSource);
		for($a = 0, $b = $this->__totalSources; $a < $b; $a++)
			$this->__sources[$a]['code'] = $this->__clean($this->__sources[$a]['code']);
		$header = $this->__getHeader();
		for($a = 0, $b = $this->__totalSources; $a < $b; $a++)
			$this->__sources[$a] = &$this->__sources[$a]['code'];
		$this->__sources = implode(';', $this->__sources);
		if($packed)
			$this->__sources = $this->__pack($this->__sources);
		$this->__sourceNewLength = strlen($this->__sources);
		$this->__setStats();
		return $header.$this->__sources;
	}
	function __getHeader() {
		return implode('', array(
			'/* ',$this->__getScriptNames(),'JavaScriptCompressor ',$this->version,' [www.devpro.it], ',
			'thanks to Dean Edwards for idea [dean.edwards.name]',
			" */\r\n"		
		));
	}
	function __getScriptNames() {
		$a = 0;
		$result = array();
		for($b = $this->__totalSources; $a < $b; $a++) {
			if($this->__sources[$a]['name'] !== '')
				array_push($result, $this->__sources[$a]['name']);
		}
		$a = count($result);
		if($a-- > 0)
			$result[$a] .= ' with ';
		return $a < 0 ? '' : implode(', ', $result);
	}
	function __getSize($size, $dec = 2) {
		$toEval = '';
		$type = array('bytes', 'Kb', 'Mb', 'Gb');
		$nsize = $size;
		$times = 0;
		while($nsize > 1024) {
			$nsize = $nsize / 1024;
			$toEval .= '/1024';
			$times++;
		}
		if($times === 0)
			$fSize = $size.' '.$type[$times];
		else {
			eval('$size=($size'.$toEval.');');
			$fSize =  number_format($size, $dec, '.', '').' '.$type[$times];
		}
		return $fSize;
	}
	function __getTime($startTime = null) {
		list($usec, $sec) = explode(' ', microtime());
		$newtime = (float)$usec + (float)$sec;
		if($startTime !== null)
			$newtime = number_format(($newtime - $startTime), 3);
		return $newtime;
	}
	function __pack(&$str) {
		$container = array();
		$str = preg_replace("/(\w+)/e", '$this->__BC->toBase($this->__wordsParser("\\1",$container));', $this->__clean($str));
		$str = str_replace("\n", '\n', addslashes($str));
		return 'eval(function(A,G){return A.replace(/(\\w+)/g,function(a,b){return G[parseInt(b,36)]})}("'.$str.'","'.implode(',', $container).'".split(",")));';
	}
	function __setStats() {
		$this->stats = implode(' ', array(
			$this->__getSize($this->__sourceLength),
			'to',
			$this->__getSize($this->__sourceNewLength),
			'in',
			$this->__getTime($this->__startTime),
			'seconds'
		));
	}
	function __sourceManager(&$jsSource) {
		$b = count($jsSource);
		$this->__sources = array();
		if(is_string($jsSource))
			$this->__sourcePusher($jsSource, '');
		elseif(is_array($jsSource) && $b > 0) {
			if(isset($jsSource['code']))
				$this->__sourcePusher($jsSource['code'], (isset($jsSource['name']) ? $jsSource['name'] : ''));
			else {
				for($a = 0; $a < $b; $a++) {
					if(is_array($jsSource[$a]) && isset($jsSource[$a]['code'], $jsSource[$a]['name']))
						$this->__sourcePusher($jsSource[$a]['code'], trim($jsSource[$a]['name']));
					elseif(is_string($jsSource[$a]))
						$this->__sourcePusher($jsSource[$a], '');
				}
			}
		}
		$this->__totalSources = count($this->__sources);
	}
	function __sourcePusher(&$code, $name) {
		$this->__sourceLength += strlen($code);
		array_push($this->__sources, array('code'=>$code, 'name'=>$name));
	}
	function __wordsParser($str, &$d) {
		if(is_null($key = array_shift($key = array_keys($d,$str))))
			$key = array_push($d, $str) - 1;
		return $key;
	}
}
?>