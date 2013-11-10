<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function mediabox_config($public=null){
	include_spip("inc/filtres");
	$config = @unserialize($GLOBALS['meta']['mediabox']);
	if (!is_array($config))
		$config = array();
	$config = array_merge(array(
		'active' => 'oui',
		'traiter_toutes_images' => 'oui',
		'selecteur_galerie' => '#documents_portfolio a[type=\'image/jpeg\'],#documents_portfolio a[type=\'image/png\'],#documents_portfolio a[type=\'image/gif\']',
		'selecteur_commun' => '.mediabox',
		'splash_url' => '',
		'skin' => 'black-striped',
		'transition' => 'elastic',
		'speed'=>'200',
		'maxWidth'=>'90%',
		'maxHeight'=>'90%',
		'minWidth'=>'400px',
		'minHeight'=>'',
		'slideshow_speed' => '2500',
		'opacite' => '0.9',
	), $config);

	if ((is_null($public) AND test_espace_prive()) OR $public===false) {
		$config = array_merge($config,array(
		'active' => 'oui',
		'selecteur_galerie' => '#portfolios a[type^=\'image/\']',
		'selecteur_commun' => '.mediabox, .iconifier a[href$=jpg],.iconifier a[href$=png],.iconifier a[href$=gif]',
		'splash_url' => '',
		'skin' => 'white-shadow',
		'maxWidth'=>'90%',
		'maxHeight'=>'95%',
		'minWidth'=>'600px',
		'minHeight'=>'300px',
		'opacite' => '0.9',
		));
	}
	
	// charger la config du theme uniquement dans le public
	if (!test_espace_prive() AND include_spip("colorbox/".$config['skin']."/mediabox_config_theme")) {
		$config_theme = mediabox_config_theme();
		$config = array_merge($config, $config_theme);
	}
	
	return $config;	
}

function mediabox_insert_head_css($flux){
	$config = mediabox_config();
	if ($config['active']=='oui' AND $f = find_in_path((test_espace_prive()?"prive/":"")."colorbox/".$config['skin'].'/colorbox.css'))
		$flux .= '<link rel="stylesheet" href="'.direction_css($f).'" type="text/css" media="all" />';
	/**
	 * Initialiser la config de la mediabox
	 */
	$flux ='<script type="text/javascript">/* <![CDATA[ */
var box_settings = {tt_img:'.($config['traiter_toutes_images'] == 'oui'?'true':'false')
.',sel_g:"'.$config['selecteur_galerie']
.'",sel_c:"'.$config['selecteur_commun']
.'",trans:"'.$config['transition']
.'",speed:"'.$config['speed']
.'",ssSpeed:"'.$config['slideshow_speed']
.'",maxW:"'.$config['maxWidth']
.'",maxH:"'.$config['maxHeight']
.'",minW:"'.$config['minWidth']
.'",minH:"'.$config['minHeight']
.'",opa:"'.$config['opacite']
.'",str_ssStart:"'.unicode2charset(html2unicode(_T('mediabox:boxstr_slideshowStart')))
.'",str_ssStop:"'.unicode2charset(html2unicode(_T('mediabox:boxstr_slideshowStop')))
.'",str_cur:"'._T('mediabox:boxstr_current', array('current'=>'{current}', 'total'=>'{total}'))
.'",str_prev:"'._T('mediabox:boxstr_previous')
.'",str_next:"'._T('mediabox:boxstr_next')
.'",str_close:"'._T('mediabox:boxstr_close')
.'",splash_url:"'.$config['splash_url']
.'"};
/* ]]> */</script>'."\n" . $flux;

	return $flux;
}


function mediabox_timestamp($fichier){
	if ($m = filemtime($fichier))
		return "$fichier?$m";
	return $fichier;
}

function mediabox_insert_head($flux){
	$config = mediabox_config();
	if ($config['active']=='oui') {

		$flux .='
	<script src="'.mediabox_timestamp(find_in_path('javascript/jquery.colorbox.js')).'" type="text/javascript"></script>
	<script src="'.mediabox_timestamp(find_in_path('javascript/spip.mediabox.js')).'" type="text/javascript"></script>';
		if ($config['splash_url'])
			$flux .='<script src="'.mediabox_timestamp(find_in_path('javascript/splash.mediabox.js')).'" type="text/javascript"></script>';
	}

	return $flux;
}

function mediabox_jquery_plugins($plugins){
	$config = mediabox_config();
	if ($config['splash_url']) {
		$plugins[] = 'javascript/jquery.cookie.js';
	}
	return $plugins;
}

?>
