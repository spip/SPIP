function deplie_arbre(){
	tree = jQuery('#articles_tous');
	jQuery('ul:hidden',tree).siblings('img.expandImage').each(function(){jQuery(this).bascule()});
}
function plie_arbre(){
	tree = jQuery('#articles_tous');
	jQuery('#articles_tous ul').hide();
	jQuery('img.expandImage', tree).attr('src',img_deplierhaut);
}
function annuler_deplacement(){
	liste = jQuery("#deplacements").text();
	tableau = liste.split("\n");
	if (tableau.length>0){
		action = tableau[tableau.length-1];
		tab = action.split(":");
		jQuery("#"+tab[2]).insertion(tab[0],jQuery("#"+tab[0]).parent().attr('id'));
		tableau.pop();
		jQuery("#deplacements").html(tableau.join("\n"));
		if (tableau.length==0) jQuery("#cancel").hide();
		if (tableau.length==0) jQuery("#apply").hide();
	}
}

jQuery.fn.set_expandImage = function(){
	jQuery('ul:hidden',jQuery(this)).parent().prepend('<img src="'+img_deplierhaut+'" class="expandImage" />');
	jQuery('ul:visible',jQuery(this)).parent().prepend('<img src="'+img_deplierbas+'" class="expandImage" />');
	jQuery('img.expandImage', jQuery(this)).click(function (){jQuery(this).bascule();});
	return jQuery(this);
}

var recall;
jQuery.fn.deplie = function(){
	jQuery(this).show();
	jQuery(this).siblings('img.expandImage').eq(0).attr('src',img_deplierbas);
	jQuery(this).children('li').children('a.ajax').each(function(){
		jQuery(this).before("<div>"+ajax_image_searching+"</div>");
		var id = jQuery(this).parent().parent().attr('id');
		jQuery(this).parent().parent().load(jQuery(this).attr('href')+"&var_ajaxcharset=utf-8",function(){jQuery("#"+id).set_expandImage().set_droppables();jQuery.recallDroppables();});
	});
	recall = true;
	jQuery.recallDroppables();
	return jQuery(this);
}

jQuery.fn.bascule = function() {
	subbranch = jQuery(this).siblings('ul').eq(0);
	if (subbranch.is(':hidden')) {
		subbranch.show();
		jQuery(this).attr('src',img_deplierbas);
		subbranch.children('li').children('a.ajax').each(function(){
			jQuery(this).before("<div>"+ajax_image_searching+"</div>");
			var id = jQuery(this).parent().parent().attr('id');
			jQuery(this).parent().parent().load(jQuery(this).attr('href')+"&var_ajaxcharset=utf-8",function(){jQuery("#"+id).set_expandImage().set_droppables();});
		});
	} else {
		subbranch.hide();
		jQuery(this).attr('src',img_deplierhaut);
	}
	return jQuery(this);
}
jQuery.fn.insertion = function(dropped_id,origine_id){
	dropped = jQuery('#'+dropped_id);
	subbranch = jQuery(this).children('ul').eq(0);
	if (subbranch.size() == 0) {
		jQuery(this).prepend('<img src="'+img_deplierbas+'" width="16" height="16" class="expandImage" />');
		id = jQuery(this).attr('id');
		id = id.split("-"); id=id[1]
		jQuery(this).append("<ul id='ul"+id+"' ></ul>");
		jQuery(this).children('img.expandImage').click(function (){jQuery(this).bascule();});
		subbranch = jQuery(this).children('ul').eq(0);
	}
	if((dropped.is('li.art')) && (subbranch.children('li.rub').length>0)){
		subbranch.end().children('li.rub').eq(0).before(dropped);
	}
	else
		subbranch.end().append(dropped);

	if (subbranch.is(':hidden')){
		subbranch.deplie();
	}

	oldParent = jQuery('#'+origine_id);
	oldBranches = jQuery('li', oldParent);
	if (oldBranches.size() == 0) {
		oldParent.siblings('img.expandImage').remove();
		oldParent.end().remove();
	}
}

jQuery.fn.set_droppables = function(){
	jQuery('span.holder',jQuery(this)).Droppable(
		{
			accept			: 'treeItem',
			hoverclass		: 'none',
			activeclass		: 'fakeClass',
			tollerance		: 'intersect',
			onhover			: function(dragged)
			{
				jQuery(this).parent().addClass('selected');
				if (!this.expanded) {
					subbranch = jQuery(this).siblings('ul').eq(0);
					if (subbranch.is(':hidden')){
						subbranch.pause(1000).deplie();
						this.expanded = true;
					}
				}
			},
			onout			: function()
			{
				jQuery(this).parent().removeClass('selected');
				if (this.expanded){
					subbranch = jQuery(this).siblings('ul').eq(0);
					subbranch.unpause();
					if (recall){
						recall=false;
					}
				}
				this.expanded = false;
			},
			ondrop			: function(dropped)
			{
				jQuery(this).parent().removeClass('selected');
				subbranch = jQuery(this).siblings('ul').eq(0);
				if (this.expanded)
					subbranch.unpause();
				var target=jQuery(this).parent().attr('id');
				var quoi=jQuery(dropped).attr('id');
				var source=jQuery(dropped).parent().parent().attr('id'); // il faut stocker l'id du li car le ul peut avoir disparu au moment du cancel
				action=quoi+":"+target+":"+source;
				var dep = jQuery("#deplacements");
				dep.html(dep.text()+"\n"+action);
				jQuery("#apply").show();
				jQuery("#cancel").show();
				jQuery(this).parent().insertion(quoi,jQuery(dropped).parent().attr('id'));
			}
		}
	);
	jQuery('li.treeItem',jQuery(this)).Draggable(
		{
			revert		: true,
			ghosting : true,
			autoSize : true
		}
	);
}

jQuery(
	function()
	{
		jQuery('#articles_tous').set_expandImage();
		jQuery('#articles_tous').set_droppables();
	}
);