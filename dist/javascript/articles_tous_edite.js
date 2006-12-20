function deplie_arbre(){
	tree = $('#articles_tous');
	$('ul:hidden',tree).siblings('img.expandImage').each(function(){$(this).bascule()});
}
function plie_arbre(){
	tree = $('#articles_tous');
	$('#articles_tous ul').hide();
	$('img.expandImage', tree).attr('src',img_deplierhaut);
}
function annuler_deplacement(){
	liste = $("#deplacements").text();
	tableau = liste.split("\n");
	if (tableau.length>0){
		action = tableau[tableau.length-1];
		tab = action.split(":");
		$("#"+tab[2]).insertion(tab[0],$("#"+tab[0]).parent().id());
		tableau.pop();
		$("#deplacements").html(tableau.join("\n"));
		if (tableau.length==0) $("#cancel").hide();
		if (tableau.length==0) $("#apply").hide();
	}
}

jQuery.fn.set_expandImage = function(){
	$('ul:hidden',$(this)).parent().prepend('<img src="'+img_deplierhaut+'" class="expandImage" />');
	$('ul:visible',$(this)).parent().prepend('<img src="'+img_deplierbas+'" class="expandImage" />');
	$('img.expandImage', $(this)).click(function (){$(this).bascule();});
	return $(this);
}

var recall;
jQuery.fn.deplie = function(){
	$(this).show();
	$(this).siblings('img.expandImage').eq(0).attr('src',img_deplierbas);
	$(this).children('li').children('a.ajax').each(function(){
		$(this).before("<div>"+ajax_image_searching+"</div>");
		var id = $(this).parent().parent().id();
		$(this).parent().parent().load($(this).href()+"&var_ajaxcharset=utf-8",function(){$("#"+id).set_expandImage().set_droppables();jQuery.recallDroppables();});
	});
	recall = true;
	jQuery.recallDroppables();
	return $(this);
}

jQuery.fn.bascule = function() {
	subbranch = $(this).siblings('ul').eq(0);
	if (subbranch.is(':hidden')) {
		subbranch.show();
		$(this).attr('src',img_deplierbas);
		subbranch.children('li').children('a.ajax').each(function(){
			$(this).before("<div>"+ajax_image_searching+"</div>");
			var id = $(this).parent().parent().id();
			$(this).parent().parent().load($(this).href()+"&var_ajaxcharset=utf-8",function(){$("#"+id).set_expandImage().set_droppables();});
		});
	} else {
		subbranch.hide();
		$(this).attr('src',img_deplierhaut);
	}
	return $(this);
}
jQuery.fn.insertion = function(dropped_id,origine_id){
	dropped = $('#'+dropped_id);
	subbranch = $(this).children('ul').eq(0);
	if (subbranch.size() == 0) {
		$(this).prepend('<img src="'+img_deplierbas+'" width="16" height="16" class="expandImage" />');
		id = $(this).id();
		id = id.split("-"); id=id[1]
		$(this).append("<ul id='ul"+id+"' ></ul>");
		$(this).children('img.expandImage').click(function (){$(this).bascule();});
		subbranch = $(this).children('ul').eq(0);
	}
	if((dropped.is('li.art')) && (subbranch.children('li.rub').length>0)){
		subbranch.end().children('li.rub').eq(0).before(dropped);
	}
	else
		subbranch.end().append(dropped);

	if (subbranch.is(':hidden')){
		subbranch.deplie();
	}

	oldParent = $('#'+origine_id);
	oldBranches = $('li', oldParent);
	if (oldBranches.size() == 0) {
		oldParent.siblings('img.expandImage').remove();
		oldParent.end().remove();
	}
}

jQuery.fn.set_droppables = function(){
	$('span.holder',$(this)).Droppable(
		{
			accept			: 'treeItem',
			hoverclass		: 'none',
			activeclass		: 'fakeClass',
			tollerance		: 'intersect',
			onhover			: function(dragged)
			{
				$(this).parent().addClass('selected');
				if (!this.expanded) {
					subbranch = $(this).siblings('ul').eq(0);
					if (subbranch.is(':hidden')){
						subbranch.pause(1000).deplie();
						this.expanded = true;
					}
				}
			},
			onout			: function()
			{
				$(this).parent().removeClass('selected');
				if (this.expanded){
					subbranch = $(this).siblings('ul').eq(0);
					subbranch.unpause();
					if (recall){
						recall=false;
					}
				}
				this.expanded = false;
			},
			ondrop			: function(dropped)
			{
				$(this).parent().removeClass('selected');
				subbranch = $(this).siblings('ul').eq(0);
				if (this.expanded)
					subbranch.unpause();
				var target=$(this).parent().id();
				var quoi=$(dropped).id();
				var source=$(dropped).parent().parent().id(); // il faut stocker l'id du li car le ul peut avoir disparu au moment du cancel
				action=quoi+":"+target+":"+source;
				var dep = $("#deplacements");
				dep.html(dep.text()+"\n"+action);
				$("#apply").show();
				$("#cancel").show();
				$(this).parent().insertion(quoi,$(dropped).parent().id());
			}
		}
	);
	$('li.treeItem',$(this)).Draggable(
		{
			revert		: true,
			ghosting : true,
			autoSize : true
		}
	);
}

$(document).ready(
	function()
	{
		$('#articles_tous').set_expandImage();
		$('#articles_tous').set_droppables();
	}
);