var login_info;

function informe_auteur(c){
	login_info.informe_auteur_en_cours = false;
	// JSON envoye par informer_auteur.html
	c = jQuery.parseJSON(c);
	if (c) {
		// indiquer le cnx si on n'y a pas touche
		jQuery('input#session_remember:not(.modifie)')
		.prop('checked',(c.cnx=='1')?true:false);
	}
	if (c.logo)
		jQuery('#spip_logo_auteur').html(c.logo);
	else
		jQuery('#spip_logo_auteur').html('');
}

function actualise_auteur(){
	if (login_info.login != jQuery('#var_login').prop('value')) {
		login_info.informe_auteur_en_cours = true;
		login_info.login = jQuery('#var_login').prop('value');
		var currentTime = new Date();// on passe la date en var pour empecher la mise en cache de cette requete (bug avec FF3 & IE7)
		jQuery.get(login_info.page_auteur, {var_login:login_info.login,var_compteur:currentTime.getTime()},informe_auteur);
	}
}
