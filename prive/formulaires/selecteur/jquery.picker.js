/**
item_picked et picker doivent seulement etre voisins

<ul class='item_picked'>..</ul>
... 
...
<xx class='item_picker'>
<div class='picker_bouton'>..</div>
</xx>
...
**/
;if (window.jQuery)
(function($) {

	jQuery(document).ready(function(){
		var picked = jQuery('ul.item_picked');
		if (picked.length) {
			picked.find('>li').removeClass('last').find('li:last').addClass('last');
		}
	});

	jQuery.fn.picker_toggle = function(){
		var browser = jQuery(this).parents('.item_picker').find('.browser');
		if (browser.is(':visible')){
			if (jQuery.browser.msie)
				browser.hide();
			else
				browser.slideUp();
			jQuery('a.close',this).hide();
			jQuery('a.edit',this).show();
		}
		else {
			browser.show();
			jQuery('a.close',this).show();
			jQuery('a.edit',this).hide();
		}
	}

	// stop animation du bloc cible pour faire patienter
	jQuery.fn.stopAnimeajax = function(end) {
		this.children().css('opacity', 1.0);
		this.find('.image_loading').html('');
		return this; // don't break the chain
	}

	jQuery.fn.item_pick = function(id_item,name,title,type){
		var label_supprimer = (typeof selecteur_label_supprimer != 'undefined') ? selecteur_label_supprimer : 'del';
		var picker = this.parents('.item_picker');
		var picked = picker.siblings('ul.item_picked');
		if (!picked.length) {
			picker.before("<ul class='item_picked'></ul>");
			picked = picker.siblings('ul.item_picked');
		}
		var select = picked.is('.select');
		var obligatoire = picked.is('.obligatoire');
		if (select)
			picked.html('');
		else
			jQuery('li.on',picked).removeClass('on');
		var sel=jQuery('input[value="'+id_item+'"]',picked);
		if (sel.length==0){
			picked.addClass('changing').animeajax();
			// simulons de la latence pour l'oeil de l'utilisateur
			setTimeout(function(){
				jQuery('li:last',picked).removeClass('last');
				picked.append('<li class="last on '+type+'">'
				+'<input type="hidden" name="'+name+'[]" value="'+id_item+'"/>'
				+ title
				+((select&&obligatoire)?"":" <a title='"+label_supprimer+"' href='#' onclick='jQuery(this).item_unpick();return false;'>"
				  +"<img alt='"+label_supprimer+"' src='"+img_unpick+"' /></a>"
				  )
				+'<span class="sep">, </span></li>').removeClass('changing').stopAnimeajax();
				// masquer le selecteur apres un pick
				picker.find('.picker_bouton').picker_toggle();
			},300);
		}
		else
			sel.parent().addClass('on');
		return this; // don't break the chain
	}
	jQuery.fn.item_unpick = function(){
		var picked = this.parents('ul.item_picked');
		var me = this.parent();
		jQuery(me).fadeOut('fast');
		setTimeout(function(){
			me.remove();
			picked.find('>li').removeClass('last').find('li:last').addClass('last');
		},400);
	}
	
})(jQuery);
