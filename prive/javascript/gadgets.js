function init_gadgets(url_toutsite,url_navrapide,url_agenda,html_messagerie){
	jQuery('#boutonbandeautoutsite')
	.one('mouseover',function(event){
		if ((typeof(window['_OUTILS_DEVELOPPEURS']) == 'undefined') || ((event.altKey || event.metaKey) != true)) {
			changestyle('bandeautoutsite');
			jQuery('#gadget-rubriques')
			.load(url_toutsite);
		} else { window.open(url_toutsite+'&transformer_xml=valider_xml'); }
	})
	.one('focus', function(){jQuery(this).mouseover();});
	
	jQuery('#boutonbandeaunavrapide')
	.one('mouseover',function(event){
		if ((typeof(window['_OUTILS_DEVELOPPEURS']) == 'undefined') || ((event.altKey || event.metaKey) != true)) {
			changestyle('bandeaunavrapide');
			jQuery('#gadget-navigation')
			.load(url_navrapide);
		} else { window.open(url_navrapide+'&transformer_xml=valider_xml'); }
	})
	.one('focus', function(){jQuery(this).mouseover();});

	jQuery('#boutonbandeauagenda')
	.one('mouseover',function(event){
		if ((typeof(window['_OUTILS_DEVELOPPEURS']) == 'undefined') || ((event.altKey || event.metaKey) != true)) {
			changestyle('bandeauagenda');
			jQuery('#gadget-agenda')
			.load(url_agenda);
		} else { window.open(url_agenda+'&transformer_xml=valider_xml'); }
	})
	.one('focus', function(){jQuery(this).mouseover();});

	jQuery('#gadget-messagerie')
	.html(html_messagerie);

	// la case de recherche s'efface la premiere fois qu'on la clique
	jQuery('#form_recherche')
	.one('click',function(){this.value='';});
}