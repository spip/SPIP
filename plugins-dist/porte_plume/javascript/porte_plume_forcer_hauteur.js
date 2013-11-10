function barre_forcer_hauteur () {
	jQuery(".markItUpEditor").each(function() {
		var hauteur_min = jQuery(this).height();
		var hauteur_max = parseInt(jQuery(window).height()) - 200;
		var hauteur = hauteur_min;
		
		var signes = jQuery(this).val().length;
		if (signes){
			/* en gros: 400 signes donnent 100 pixels de haut */
			var hauteur_signes = Math.round(signes / 4) + 50;
			if (hauteur_signes > hauteur_min && hauteur_signes < hauteur_max) 
				hauteur = hauteur_signes;
			else 
				if (hauteur_signes > hauteur_max) 
					hauteur = hauteur_max;
		
			jQuery(this).height(hauteur);
		}
	});
}

jQuery(window).bind("load", function() {
	barre_forcer_hauteur();
});
