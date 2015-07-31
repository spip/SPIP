<?php
define('XML_HTMLSAX3_STATE_STOP', 0);
define('XML_HTMLSAX3_STATE_START', 1);
define('XML_HTMLSAX3_STATE_TAG', 2);
define('XML_HTMLSAX3_STATE_OPENING_TAG', 3);
define('XML_HTMLSAX3_STATE_CLOSING_TAG', 4);
define('XML_HTMLSAX3_STATE_ESCAPE', 6);
define('XML_HTMLSAX3_STATE_JASP', 7);
define('XML_HTMLSAX3_STATE_PI', 8);
class XML_HTMLSax3_StartingState  {
 function parse(&$context) {
  $data = $context->scanUntilString('<');
  if ($data != '') {
   $context->handler_object_data->
    {$context->handler_method_data}($context->htmlsax, $data);
  }
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_TAG;
 }
}
class XML_HTMLSax3_TagState {
 function parse(&$context) {
  switch($context->ScanCharacter()) {
  case '/':
   return XML_HTMLSAX3_STATE_CLOSING_TAG;
   break;
  case '?':
   return XML_HTMLSAX3_STATE_PI;
   break;
  case '%':
   return XML_HTMLSAX3_STATE_JASP;
   break;
  case '!':
   return XML_HTMLSAX3_STATE_ESCAPE;
   break;
  default:
   $context->unscanCharacter();
   return XML_HTMLSAX3_STATE_OPENING_TAG;
  }
 }
}
class XML_HTMLSax3_ClosingTagState {
 function parse(&$context) {
  $tag = $context->scanUntilCharacters('/>');
  if ($tag != '') {
   $char = $context->scanCharacter();
   if ($char == '/') {
    $char = $context->scanCharacter();
    if ($char != '>') {
     $context->unscanCharacter();
    }
   }
   $context->handler_object_element->
    {$context->handler_method_closing}($context->htmlsax, $tag, FALSE);
  }
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_OpeningTagState {
 function parseAttributes(&$context) {
  $Attributes = array();
 
  $context->ignoreWhitespace();
  $attributename = $context->scanUntilCharacters("=/> \n\r\t");
  while ($attributename != '') {
   $attributevalue = NULL;
   $context->ignoreWhitespace();
   $char = $context->scanCharacter();
   if ($char == '=') {
    $context->ignoreWhitespace();
    $char = $context->ScanCharacter();
    if ($char == '"') {
     $attributevalue= $context->scanUntilString('"');
     $context->IgnoreCharacter();
    } else if ($char == "'") {
     $attributevalue = $context->scanUntilString("'");
     $context->IgnoreCharacter();
    } else {
     $context->unscanCharacter();
     $attributevalue =
      $context->scanUntilCharacters("> \n\r\t");
    }
   } else if ($char !== NULL) {
    $attributevalue = NULL;
    $context->unscanCharacter();
   }
   $Attributes[$attributename] = $attributevalue;
   
   $context->ignoreWhitespace();
   $attributename = $context->scanUntilCharacters("=/> \n\r\t");
  }
  return $Attributes;
 }

 function parse(&$context) {
  $tag = $context->scanUntilCharacters("/> \n\r\t");
  if ($tag != '') {
   $this->attrs = array();
   $Attributes = $this->parseAttributes($context);
   $char = $context->scanCharacter();
   if ($char == '/') {
    $char = $context->scanCharacter();
    if ($char != '>') {
     $context->unscanCharacter();
    }
    $context->handler_object_element->
     {$context->handler_method_opening}($context->htmlsax, $tag, 
     $Attributes, TRUE);
    $context->handler_object_element->
     {$context->handler_method_closing}($context->htmlsax, $tag, 
     TRUE);
   } else {
    $context->handler_object_element->
     {$context->handler_method_opening}($context->htmlsax, $tag, 
     $Attributes, FALSE);
   }
  }
  return XML_HTMLSAX3_STATE_START;
 }
}

class XML_HTMLSax3_EscapeState {
 function parse(&$context) {
  $char = $context->ScanCharacter();
  if ($char == '-') {
   $char = $context->ScanCharacter();
   if ($char == '-') {
    $context->unscanCharacter();
    $context->unscanCharacter();
    $text = $context->scanUntilString('-->');
    $text .= $context->scanCharacter();
    $text .= $context->scanCharacter();
   } else {
    $context->unscanCharacter();
    $text = $context->scanUntilString('>');
   }
  } else if ( $char == '[') {
   $context->unscanCharacter();
   $text = $context->scanUntilString(']>');
   $text.= $context->scanCharacter();
  } else {
   $context->unscanCharacter();
   $text = $context->scanUntilString('>');
  }

  $context->IgnoreCharacter();
  if ($text != '') {
   $context->handler_object_escape->
   {$context->handler_method_escape}($context->htmlsax, $text);
  }
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_JaspState {
 function parse(&$context) {
  $text = $context->scanUntilString('%>');
  if ($text != '') {
   $context->handler_object_jasp->
    {$context->handler_method_jasp}($context->htmlsax, $text);
  }
  $context->IgnoreCharacter();
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_PiState {
 function parse(&$context) {
  $target = $context->scanUntilCharacters(" \n\r\t");
  $data = $context->scanUntilString('?>');
  if ($data != '') {
   $context->handler_object_pi->
   {$context->handler_method_pi}($context->htmlsax, $target, $data);
  }
  $context->IgnoreCharacter();
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_START;
 }
}
?>