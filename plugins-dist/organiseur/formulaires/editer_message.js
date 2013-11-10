function formulaire_editer_message_set_dest(input,data,value){
	console.log(data);
	console.log(value);
	var id_auteur;
	var box = jQuery(input).siblings('.selected');
	if (data[1]) {
		id_auteur = data[1];
		var nom = value;
		if (box.find('input[value='+id_auteur+']').length==0){
			box.find('.on').removeClass('on');
			box.append(" <span class='dest on'>"
			+ value
			+"<input type='hidden' name='"
			+ jQuery(input).attr('data-name')
			+ "' value='"+id_auteur+"' /> "
			+ $(box).find('span.dest:first').html()
			+"</span>");
		}
		else
			box.find('input[value='+id_auteur+']').closest('span').addClass('on').siblings('.on').removeClass('on');
	}
	jQuery(input).attr('value','');//.get(0).focus();
}
function formulaire_editer_message_init(){
	jQuery("input.destinataires:not(.autocompleted)").each(function(){
		var me = this;
		jQuery(me)
			.autocomplete(url_trouver_destinataire, {minChars:2, mustMatchOrEmpty:1,autoFill:true,matchSubset:0, matchContains:1, cacheLength:10 })
		  .bind('result',function(e,data,value){return formulaire_editer_message_set_dest(me,data,value);})
		  .parent().bind('click',function(){jQuery(me).get(0).focus();});
	})
	.addClass('autocompleted');
}
if (window.jQuery){
	jQuery(function(){
		formulaire_editer_message_init();
		onAjaxLoad(formulaire_editer_message_init);
	});
}
