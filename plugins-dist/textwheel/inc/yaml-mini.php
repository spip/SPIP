<?php

if (!defined('_ECRIRE_INC_VERSION')) return;
# yaml_decode

function yaml_decode($input) {
	require_once dirname(__FILE__).'/../lib/yaml/sfYaml.php';
	require_once dirname(__FILE__).'/../lib/yaml/sfYamlParser.php';

	$yaml = new sfYamlParser();

	try
	{
	  return $yaml->parse($input);
	  
	}
	catch (Exception $e)
	{
		return null;
	}
}

?>