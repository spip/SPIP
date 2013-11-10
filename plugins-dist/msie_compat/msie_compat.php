<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function msie_compat_insert_head($flux) {

	$iecompat = $GLOBALS['meta']["iecompat"];

	if ($iecompat == "ifixpng") {
	$flux .= "<!--[if lt IE 7]>
	<script src='".find_in_path('javascript/jquery.ifixpng.js')."'></script>
	<script type='text/javascript'>//<![CDATA[
		jQuery.ifixpng('rien.gif');		
		function fixie() {
			jQuery('img').ifixpng();
		}
		$(document).ready(function() { fixie(); });
		onAjaxLoad(fixie);	
	//]]></script>
<![endif]-->
";
	} else if ($iecompat == "IE7") {
	$flux .= "<!--[if lt IE 7]>
	<script type='text/javascript'>//<![CDATA[
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE7.js')."'></script>
<![endif]-->
";
	
	} else if ($iecompat == "IE7squish") {
	$flux .= "<!--[if lt IE 7]>
	<script type='text/javascript'>//<![CDATA[
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE7.js')."'></script>
	<script src='".find_in_path('javascript/ie7-squish.js')."'></script>
<![endif]-->
";
	
	}  else if ($iecompat == "IE8") {
	$flux .= "<!--[if lt IE 8]>
	<script type='text/javascript'>//<![CDATA[
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE8.js')."'></script>
<![endif]-->
";
	
	}  else if ($iecompat == "IE8squish") {
	$flux .= "<!--[if lt IE 8]>
	<script type='text/javascript'>//<![CDATA[
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE8.js')."'></script>
	<script src='".find_in_path('javascript/ie7-squish.js')."'></script>
<![endif]-->
";
	
	} /* else if ($iecompat == "IE9") {
	$flux .= "<!--[if lt IE 9]>
	<script type='text/javascript'>//<![CDATA[ 
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE9.js')."'></script>
<![endif]-->
";
	
	}   else if ($iecompat == "IE9squish") {
	$flux .= "<!--[if lt IE 9]>
	<script type='text/javascript'>//<![CDATA[ 
		var IE7_PNG_SUFFIX = '.png'; 
	//]]></script>
	<script src='".find_in_path('javascript/IE9.js')."'></script>
	<script src='".find_in_path('javascript/ie7-squish.js')."'></script>
<![endif]-->
";
	
	} */

	return $flux;
}

function msie_compat_affiche_milieu ($flux) {
	if ($flux["args"]["exec"] == "configurer_avancees")
		$flux["data"] .= recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_vieux_butineurs'));

	return $flux;
}


function msie_compat_configurer_liste_metas ($metas) {
	$metas['iecompat']='non';
	
	return ($metas);
}

?>