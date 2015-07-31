<?php
class XML_HTMLSax3_Trim {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_Trim(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function trimData(&$parser, $data) {
  $data = trim($data);
  if ($data != '') {
   $this->orig_obj->{$this->orig_method}($parser, $data);
  }
 }
}
class XML_HTMLSax3_CaseFolding {
 var $orig_obj;
 var $orig_open_method;
 var $orig_close_method;
 function XML_HTMLSax3_CaseFolding(&$orig_obj, $orig_open_method, $orig_close_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_open_method = $orig_open_method;
  $this->orig_close_method = $orig_close_method;
 }
 function foldOpen(&$parser, $tag, $attrs=array(), $empty = FALSE) {
  $this->orig_obj->{$this->orig_open_method}($parser, strtoupper($tag), $attrs, $empty);
 }
 function foldClose(&$parser, $tag, $empty = FALSE) {
  $this->orig_obj->{$this->orig_close_method}($parser, strtoupper($tag), $empty);
 }
}
class XML_HTMLSax3_Linefeed {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_LineFeed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData(&$parser, $data) {
  $data = explode("\n",$data);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($parser, $chunk);
  }
 }
}
class XML_HTMLSax3_Tab {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_Tab(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData(&$parser, $data) {
  $data = explode("\t",$data);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}
class XML_HTMLSax3_Entities_Parsed {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_Entities_Parsed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData(&$parser, $data) {
  $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  foreach ( $data as $chunk ) {
   $chunk = html_entity_decode($chunk,ENT_NOQUOTES);
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}
if (version_compare(phpversion(), '4.3', '<') && !function_exists('html_entity_decode') ) {
 function html_entity_decode($str, $style=ENT_NOQUOTES) {
  return strtr($str,
   array_flip(get_html_translation_table(HTML_ENTITIES,$style)));
 }
}
class XML_HTMLSax3_Entities_Unparsed {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_Entities_Unparsed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData(&$parser, $data) {
  $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}

class XML_HTMLSax3_Escape_Stripper {
 var $orig_obj;
 var $orig_method;
 function XML_HTMLSax3_Escape_Stripper(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function strip(&$parser, $data) {
  if ( substr($data,0,2) == '--' ) {
   $patterns = array(
    '/^\-\-/',    // Opening comment: --
    '/\-\-$/',    // Closing comment: --
   );
   $data = preg_replace($patterns,'',$data);

  } else if ( substr($data,0,1) == '[' ) {
   $patterns = array(
    '/^\[.*CDATA.*\[/s', // Opening CDATA
    '/\].*\]$/s',    // Closing CDATA
    );
   $data = preg_replace($patterns,'',$data);
  }

  $this->orig_obj->{$this->orig_method}($this, $data);
 }
}
?>