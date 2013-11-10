var actiongroup={
	checkedItems:{},
	countchecked:0,
	countCurrent:0
}
/**
 * Position fixe lors du scroll
 */
actiongroup.followScroll = function(){
	var container = $("#actiongroup").parents('.box.raccourcis');
	var limite = container.offset().top;
	$(window).scroll(function() {
		if($(this).scrollTop() > limite)
			container.css({'position': 'fixed', 'top': 0, 'width': container.width()+'px'});
		if($(this).scrollTop() < limite)
			container.css({'position': 'static', 'width': 'auto'});	
	});
}
/**
 * Vider completement la selection
 */
actiongroup.emptySelection = function(){
	actiongroup.checkedItems={};
	actiongroup.countchecked=0;
	actiongroup.unselectAll();
}
/**
 * Selectionner les items de la page
 */
actiongroup.selectAll = function(sel){
	sel = sel || document;
	$('input.actiongroup',sel)
		.attr('checked',true)
		.each(function(){
			actiongroup.updateChecklist($(this).attr('value'),true,false);
		});
	actiongroup.updateChecklist(0,false);
}
/**
 * Deselectionner les items de la page
 */
actiongroup.unselectAll = function(sel){
	sel = sel || document;
	$('input.actiongroup',sel)
		.attr('checked',false)
		.each(function(){
			actiongroup.updateChecklist($(this).attr('value'),false,false);
		});
	actiongroup.updateChecklist(0,false);
}
/**
 * Mettre a jour la listes des donnees et la boite html #actiongroup
 * consecutivement
 * @param int value
 * @param bool checked
 * @param bool|void update_status
 */
actiongroup.updateChecklist = function(value,checked,update_status){
	if (checked && !(actiongroup.checkedItems[value] || false))
		actiongroup.countchecked++;
	if (!checked && (actiongroup.checkedItems[value] || false))
		actiongroup.countchecked--;
	actiongroup.checkedItems[value]=checked;
	if (update_status || typeof update_status=="undefined") {
		jQuery.spip.log(actiongroup.countchecked);
		if (actiongroup.countchecked==0){
			$('#actiongroup .shortcut.empty,#actiongroup .shortcut.unselectall')
				.addClass('hidden');
			$('#actiongroup .shortcut.selectall')
				.removeClass('hidden');
			$('#actiongroup .status .zero')
				.show()
				.siblings(':visible')
				.hide();
			$('#actiongroup .action')
				.addClass('hidden');
			if ($(".checkable").length)
				$('#actiongroup').closest('.box:hidden').show('fast');
			else
				$('#actiongroup').closest('.box:visible').hide('fast');
		}
		else {
			$('#actiongroup .action')
				.removeClass('hidden');
			if (actiongroup.countchecked==1)
				$('#actiongroup .status .one')
					.show()
					.siblings(':visible')
					.hide();
			else{
				$('#actiongroup .status .many b')
					.html(actiongroup.countchecked);
				$('#actiongroup .status .many')
					.show()
					.siblings(':visible')
					.hide();
			}
			var checked = $('input.actiongroup:checked').length;
			if (checked)
				$('#actiongroup .shortcut.unselectall')
					.removeClass('hidden');
			else
				$('#actiongroup .shortcut.unselectall')
					.addClass('hidden');
			if (actiongroup.countchecked>checked)
				$('#actiongroup .shortcut.empty')
					.removeClass('hidden');
			else
				$('#actiongroup .shortcut.empty')
					.addClass('hidden');
			if (actiongroup.countCurrent>checked)
				$('#actiongroup .shortcut.selectall')
					.removeClass('hidden');
			else
				$('#actiongroup .shortcut.selectall')
					.addClass('hidden');
		}
		$('#actiongroup')
			.siblings('.success:visible')
			.hide('slow');
	}
}
/**
 * Remettre a jour les checkbox du html a partir de la liste en memoire
 * apres un chargement ajax par exemple
 */
actiongroup.updateHtmlFromChecklist = function(){
	actiongroup.countCurrent = $('input.actiongroup')
		.each(function(){
			$(this)
			  .attr('checked',actiongroup.checkedItems[$(this).attr('value')] || false);
		})
		.length;
	// forcer la mise a jour des messages
	actiongroup.updateChecklist(0,false);
}
/**
 * Activer le traitement par lot sur une liste d'items
 * @param node
 * @param type
 */
actiongroup.activate = function(sel){
	$(sel)
		.not('.checkable')
		.each(function(){
			var id = parseInt($(this).attr('data-id'));
			if (id)
				$(this)
					.append("<input type='checkbox' name='actiongroup[]' class='actiongroup checkbox' value='"+id+"'/>");
		})
		.click(function(){
			var check = $('input.actiongroup',this);
			check
				.prop('checked',!check.prop('checked'))
				.trigger('change');
		})
		.addClass('checkable')
		.find('input.actiongroup, form, a')
		.click(function(e){
			e.stopPropagation();
		})
		.filter('input.actiongroup')
		.change(function(){
			actiongroup.updateChecklist($(this).attr('value'),$(this).prop('checked'));
		});

	$('#actiongroup .action')
		.unbind('click')
		.click(function(){
			var href=$(this).attr('data-href');
			var ids = [];
			for(var id in actiongroup.checkedItems)
				if (actiongroup.checkedItems[id])
					ids.push(id);
			jQuery.spip.log(href);
			jQuery.spip.log(ids);
			if (href && ids.length) {
				$('#actiongroup')
					.siblings('.success')
					.hide()
					.parent()
					.animateLoading();
				$.ajax({
					url: href,
					data: {"ids":ids},
					onAjaxLoad:false,
					success: function(){
						// vider le cache car on a fait une operation en base
						jQuery.spip.preloaded_urls = {};
						// recharger le bloc de la selection
						$(sel).eq(0).ajaxReload();
						actiongroup.checkedItems={};
						actiongroup.countchecked=0;
						actiongroup.countCurrent=0;
						actiongroup.updateChecklist(0,false);
						$('#actiongroup')
							.css('opacity','1.0')
							.siblings()
							.css('opacity','1.0')
							.filter('.success')
							.show()
							.parent().find('.image_loading').remove();
					}
				});
			}
		});
	actiongroup.updateHtmlFromChecklist();
	actiongroup.followScroll();
}
