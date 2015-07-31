<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexander Zhukov <alex@veresk.ru> Original port from Python |
// | Authors: Harry Fuecks <hfuecks@phppatterns.com> Port to PEAR + more  |
// | Authors: Many @ Sitepointforums Advanced PHP Forums                  |
// +----------------------------------------------------------------------+
//
if (!defined('XML_HTMLSAX3')) {
 define('XML_HTMLSAX3', 'XML/');
}
require_once(XML_HTMLSAX3 . 'HTMLSax3/States.php');
require_once(XML_HTMLSAX3 . 'HTMLSax3/Decorators.php');

class XML_HTMLSax3_StateParser {
 var $htmlsax;
 var $handler_object_element;
 var $handler_method_opening;
 var $handler_method_closing;
 var $handler_object_data;
 var $handler_method_data;
 var $handler_object_pi;
 var $handler_method_pi;
 var $handler_object_jasp;
 var $handler_method_jasp;
 var $handler_object_escape;
 var $handler_method_escape;
 var $handler_default;
 var $parser_options = array();
 var $rawtext;
 var $position;
 var $length;
 var $State = array();

 function XML_HTMLSax3_StateParser (& $htmlsax) {
  $this->htmlsax = & $htmlsax;

  $this->State[XML_HTMLSAX3_STATE_START] = new XML_HTMLSax3_StartingState();

  $this->State[XML_HTMLSAX3_STATE_CLOSING_TAG] = new XML_HTMLSax3_ClosingTagState();
  $this->State[XML_HTMLSAX3_STATE_TAG] = new XML_HTMLSax3_TagState();
  $this->State[XML_HTMLSAX3_STATE_OPENING_TAG] = new XML_HTMLSax3_OpeningTagState();

  $this->State[XML_HTMLSAX3_STATE_PI] = new XML_HTMLSax3_PiState();
  $this->State[XML_HTMLSAX3_STATE_JASP] = new XML_HTMLSax3_JaspState();
  $this->State[XML_HTMLSAX3_STATE_ESCAPE] = new XML_HTMLSax3_EscapeState();
 }

 function unscanCharacter() {
  $this->position -= 1;
 }

 function ignoreCharacter() {
  $this->position += 1;
 }

 function scanCharacter() {
  if ($this->position < $this->length) {
   return $this->rawtext{$this->position++};
  }
 }

 function scanUntilString($string) {
  $start = $this->position;
  $this->position = strpos($this->rawtext, $string, $start);
  if ($this->position === FALSE) {
   $this->position = $this->length;
  }
  return substr($this->rawtext, $start, $this->position - $start);
 }

 function scanUntilCharacters($string) {}

 function ignoreWhitespace() {}

 function parse($data) {
  if ($this->parser_options['XML_OPTION_TRIM_DATA_NODES']==1) {
   $decorator = new XML_HTMLSax3_Trim(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'trimData';
  }
  if ($this->parser_options['XML_OPTION_CASE_FOLDING']==1) {
   $open_decor = new XML_HTMLSax3_CaseFolding(
    $this->handler_object_element,
    $this->handler_method_opening,
    $this->handler_method_closing);
   $this->handler_object_element =& $open_decor;
   $this->handler_method_opening ='foldOpen';
   $this->handler_method_closing ='foldClose';
  }
  if ($this->parser_options['XML_OPTION_LINEFEED_BREAK']==1) {
   $decorator = new XML_HTMLSax3_Linefeed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_TAB_BREAK']==1) {
   $decorator = new XML_HTMLSax3_Tab(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_ENTITIES_UNPARSED']==1) {
   $decorator = new XML_HTMLSax3_Entities_Unparsed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_ENTITIES_PARSED']==1) {
   $decorator = new XML_HTMLSax3_Entities_Parsed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  // Note switched on by default
  if ($this->parser_options['XML_OPTION_STRIP_ESCAPES']==1) {
   $decorator = new XML_HTMLSax3_Escape_Stripper(
    $this->handler_object_escape,
    $this->handler_method_escape);
   $this->handler_object_escape =& $decorator;
   $this->handler_method_escape = 'strip';
  }
  $this->rawtext = $data;
  $this->length = strlen($data);
  $this->position = 0;
  $this->_parse();
 }

 function _parse($state = XML_HTMLSAX3_STATE_START) {
  do {
   $state = $this->State[$state]->parse($this);
  } while ($state != XML_HTMLSAX3_STATE_STOP &&
     $this->position < $this->length);
 }
}

class XML_HTMLSax3_StateParser_Lt430 extends XML_HTMLSax3_StateParser {
 function XML_HTMLSax3_StateParser_Lt430(& $htmlsax) {
  parent::XML_HTMLSax3_StateParser($htmlsax);
  $this->parser_options['XML_OPTION_TRIM_DATA_NODES'] = 0;
  $this->parser_options['XML_OPTION_CASE_FOLDING'] = 0;
  $this->parser_options['XML_OPTION_LINEFEED_BREAK'] = 0;
  $this->parser_options['XML_OPTION_TAB_BREAK'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_PARSED'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_UNPARSED'] = 0;
  $this->parser_options['XML_OPTION_STRIP_ESCAPES'] = 0;
 }

 function scanUntilCharacters($string) {
  $startpos = $this->position;
  while ($this->position < $this->length && strpos($string, $this->rawtext{$this->position}) === FALSE) {
   $this->position++;
  }
  return substr($this->rawtext, $startpos, $this->position - $startpos);
 }

 function ignoreWhitespace() {
  while ($this->position < $this->length && 
   strpos(" \n\r\t", $this->rawtext{$this->position}) !== FALSE) {
   $this->position++;
  }
 }

 function parse($data) {
  parent::parse($data);
 }
}

class XML_HTMLSax3_StateParser_Gtet430 extends XML_HTMLSax3_StateParser {
 function XML_HTMLSax3_StateParser_Gtet430(& $htmlsax) {
  parent::XML_HTMLSax3_StateParser($htmlsax);
  $this->parser_options['XML_OPTION_TRIM_DATA_NODES'] = 0;
  $this->parser_options['XML_OPTION_CASE_FOLDING'] = 0;
  $this->parser_options['XML_OPTION_LINEFEED_BREAK'] = 0;
  $this->parser_options['XML_OPTION_TAB_BREAK'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_PARSED'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_UNPARSED'] = 0;
  $this->parser_options['XML_OPTION_STRIP_ESCAPES'] = 0;
 }
 function scanUntilCharacters($string) {
  $startpos = $this->position;
  $length = strcspn($this->rawtext, $string, $startpos);
  $this->position += $length;
  return substr($this->rawtext, $startpos, $length);
 }

 function ignoreWhitespace() {
  $this->position += strspn($this->rawtext, " \n\r\t", $this->position);
 }

 function parse($data) {
  parent::parse($data);
 }
}

class XML_HTMLSax3_NullHandler {
 function DoNothing() {
 }
}

class XML_HTMLSax3 {
 var $state_parser;

 function XML_HTMLSax3() {
  if (version_compare(phpversion(), '4.3', 'ge')) {
   $this->state_parser = new XML_HTMLSax3_StateParser_Gtet430($this);
  } else {
   $this->state_parser = new XML_HTMLSax3_StateParser_Lt430($this);
  }
  $nullhandler = new XML_HTMLSax3_NullHandler();
  $this->set_object($nullhandler);
  $this->set_element_handler('DoNothing', 'DoNothing');
  $this->set_data_handler('DoNothing');
  $this->set_pi_handler('DoNothing');
  $this->set_jasp_handler('DoNothing');
  $this->set_escape_handler('DoNothing');
 }

 function set_object(&$object) {
  if ( is_object($object) ) {
   $this->state_parser->handler_default =& $object;
   return true;
  } else {
   require_once('PEAR.php');
   PEAR::raiseError('XML_HTMLSax3::set_object requires '.
    'an object instance');
  }
 }

 function set_option($name, $value=1) {
  if ( array_key_exists($name,$this->state_parser->parser_options) ) {
   $this->state_parser->parser_options[$name] = $value;
   return true;
  } else {
   require_once('PEAR.php');
   PEAR::raiseError('XML_HTMLSax3::set_option('.$name.') illegal');
  }
 }

 function set_data_handler($data_method) {
  $this->state_parser->handler_object_data =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_data = $data_method;
 }

 function set_element_handler($opening_method, $closing_method) {
  $this->state_parser->handler_object_element =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_opening = $opening_method;
  $this->state_parser->handler_method_closing = $closing_method;
 }

 function set_pi_handler($pi_method) {
  $this->state_parser->handler_object_pi =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_pi = $pi_method;
 }

 function set_escape_handler($escape_method) {
  $this->state_parser->handler_object_escape =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_escape = $escape_method;
 }

 function set_jasp_handler ($jasp_method) {
  $this->state_parser->handler_object_jasp =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_jasp = $jasp_method;
 }

 function get_current_position() {
  return $this->state_parser->position;
 }

 function get_length() {
  return $this->state_parser->length;
 }

 function parse($data) {
  $this->state_parser->parse($data);
 }
}
?>