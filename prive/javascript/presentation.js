
// Un petit plugin jQuery pour ajouter une classe au survol d'un element
$.fn.hoverClass = function(c) {
	return this.each(function(){
		$(this).hover(
			function() { $(this).addClass(c); },
			function() { $(this).removeClass(c); }
		);
	});
};


var bandeau_elements = false;
var dir_page = $("html").attr("dir");


function changestyle(id_couche, element, style) {

	// La premiere fois, regler l'emplacement des sous-menus
	if (!bandeau_elements) {
		bandeau_elements = $('#haut-page div.bandeau');
	}

	// Masquer les elements du bandeau
	var select = $(bandeau_elements).not('#'+id_couche);
	// sauf eventuellement la boite de recherche si la souris passe en-dessous
	if (id_couche=='garder-recherche') select.not('#bandeaurecherche');
		select.css({'visibility':'hidden', 'display':'none'});
	// Afficher, le cas echeant, celui qui est demande
	if (element)
		$('#'+id_couche).css({element:style});
	else
		$('#'+id_couche).css({'visibility':'visible', 'display':'block'});
}

var accepter_change_statut = false;

function selec_statut(id, type, decal, puce, script) {

	node = $('.imgstatut'+type+id);

	if (!accepter_change_statut)
		accepter_change_statut = confirm(confirm_changer_statut);

	if (!accepter_change_statut || !node.length) return;

	$('.statutdecal'+type+id)
	.css('margin-left', decal+'px')
	.removeClass('on');

	$.get(script, function(c) {
		if (!c)
			node.attr('src',puce);
		else {
			r = window.open();
			r.document.write(c);
			r.document.close();
		}
	});
}

function prepare_selec_statut(node, nom, type, id, action)
{
	$(node)
	.hoverClass('on')
	.addClass('on')
	.load(action + '&type='+type+'&id='+id);
}

function changeclass(objet, myClass) {
	objet.className = myClass;
}


function hauteurFrame(nbCol) {
	hauteur = $(window).height() - 40;
	hauteur = hauteur - $('#haut-page').height();
	
	if (findObj('brouteur_hierarchie'))
		hauteur = hauteur - $('#brouteur_hierarchie').height();

	for (i=0; i<nbCol; i++) {
		$('#iframe' + i)
		.height(hauteur + 'px');
	}
}

function changeVisible(input, id, select, nonselect) {
	if (input) {
		element = findObj_forcer(id);
		if (element.style.display != select)  element.style.display = select;
	} else {
		element = findObj_forcer(id);
		if (element.style.display != nonselect)  element.style.display = nonselect;
	}
}



// livesearchlike...



// effacement titre quand new=oui
var antifocus=false;
// effacement titre des groupes de mots-cles de plus de 50 mots
var antifocus_mots = new Array();

function puce_statut(selection){
	if (selection=="publie"){
		return "puce-verte.gif";
	}
	if (selection=="prepa"){
		return "puce-blanche.gif";
	}
	if (selection=="prop"){
		return "puce-orange.gif";
	}
	if (selection=="refuse"){
		return "puce-rouge.gif";
	}
	if (selection=="poubelle"){
		return "puce-poubelle.gif";
	}
}
