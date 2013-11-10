// Inside the function "this" will be "document" when called by ready()
// and "the ajaxed element" when called because of onAjaxLoad
var mediaboxInit = function() {
	//console.log(box_settings);
	var options = {
		transition:box_settings.trans,
		speed:box_settings.speed,
		maxWidth:box_settings.maxW,
		maxHeight:box_settings.maxH,
		minWidth:box_settings.minW,
		minHeight:box_settings.minH,
		opacity:box_settings.opa,
		slideshowSpeed:box_settings.ssSpeed,
		slideshowStart:box_settings.str_ssStart,
		slideshowStop:box_settings.str_ssStop,
		current:box_settings.str_cur,
		previous:box_settings.str_prev,
		next:box_settings.str_next,
		close:box_settings.str_close,
		splash_url:box_settings.splash_url
	};

	// passer le portfolio de la dist en mode galerie
	if (box_settings.sel_g){
		jQuery(box_settings.sel_g, this).not('.hasbox,#colorbox')
		.attr("onclick","") // se debarrasser du onclick de SPIP
		.colorbox(jQuery.extend({}, options, {rel:'galerieauto',slideshow:true,slideshowAuto:false}))
		.addClass("hasbox");
	}

	if (box_settings.tt_img) {
		// selectionner tous les liens vers des images
		jQuery("a[type=\'image/jpeg\'],a[type=\'image/png\'],a[type=\'image/gif\']",this).not('.hasbox')
		.attr("onclick","") // se debarrasser du onclick de SPIP
		.colorbox(options) // activer la box
		.addClass("hasbox") // noter qu\'on l\'a vue
		;
	}

	// charger la box sur autre chose
	if (box_settings.sel_c){
		jQuery(box_settings.sel_c).not('.hasbox')
		.colorbox(jQuery.extend({}, options, {slideshow:true,slideshowAuto:false}))
		.addClass("hasbox") // noter qu\'on l\'a vue
		;
	}
};

/* initialiser maintenant si box_settings est deja la
 * nb en cas de defer sur le chargement du scipt javascript principal
 */
if (typeof(box_settings)!='undefined')
	(function($){ if(typeof onAjaxLoad == "function") onAjaxLoad(mediaboxInit); $(mediaboxInit); })(jQuery);

;(function ($) {
	/*
	 * overlayClose:	(Boolean:false) Allow click on overlay to close the dialog?
	 * iframe:      (Boolean:false) Open box in iframe
	 * minHeight:		(Number:200) The minimum height for the container
	 * minWidth:		(Number:200) The minimum width for the container
	 * maxHeight:		(Number:null) The maximum height for the container. If not specified, the window height is used.
	 * maxWidth:		(Number:null) The maximum width for the container. If not specified, the window width is used.
	 * autoResize:	(Boolean:false) Resize container on window resize? Use with caution - this may have undesirable side-effects.
	 * onOpen:			(Function:null) The callback function used in place of SimpleModal's open
	 * onShow:			(Function:null) The callback function used after the modal dialog has opened
	 * onClose:			(Function:null) The callback function used in place of SimpleModal's close
	 */
	$.fn.mediabox = function (options) {
		var cbox_options = {
			overlayClose: true,
			iframe: false,
			maxWidth:box_settings.maxW,
			maxHeight:box_settings.maxH,
			minWidth:box_settings.minW,
			minHeight:box_settings.minH,
			opacity:box_settings.opa,
			slideshowStart:box_settings.str_ssStart,
			slideshowStop:box_settings.str_ssStop,
			current:box_settings.str_cur,
			previous:box_settings.str_prev,
			next:box_settings.str_next,
			close:box_settings.str_close,
			onOpen: (options && options.onOpen) || null,
			onComplete: (options && options.onShow) || null,
			onClosed: (options && options.onClose) || null
		};
		
		return this.colorbox($.extend(cbox_options,options));
	};
	$.mediaboxClose = function () {$.fn.colorbox.close();};

	// API modalbox
	$.modalbox = function (href, options) {$.fn.mediabox($.extend({href:href,inline:href.match(/^#/),overlayClose:true},options));};
	$.modalboxload = function (url, options) { $.modalbox(url,options); };
	$.modalboxclose = $.mediaboxClose;

})(jQuery);