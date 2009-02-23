// JavaScript Document
jQuery.fn.async_upload = function(add_function) {
  return this.ajaxForm({
    beforeSubmit:async_upload_before_submit,
    success:add_function,
    iframe:true
  });
}

// Safari plante quand on utilise clone() -> on utilise html()
// Mais FF a un bug sur les urls contenant ~ quand on utilise html() -> on utilise clone()
jQuery.fn.clone2 = jQuery.browser.mozilla ? jQuery.fn.clone : jQuery.fn.html;

var iframeHandler = function(data,jForm,success) {
        //remove the previous message
        jQuery("div.upload_message",$(jForm).parent()).remove();
        var res = jQuery(data).filter(".upload_answer");
        //possible classes 
        //upload_document_added
        if(res.is(".upload_document_added")) {
          return res;
        }
        //upload_error
        if(res.is(".upload_error")) {
          var msg = jQuery("<div class='upload_message'>")
          .append(res.html())
          jForm.after(msg[0]);
          return false;
        } 
        //upload_zip_list
        if(res.is(".upload_zip_list")) {
          var zip_form = jQuery("<div class='upload_message'>").append(res.html());
          zip_form
          .find("form")
            .async_upload(function(res,s){
              success(res,s,jForm);
            });
          jForm.after(zip_form[0]);
          return false;  
        }
};
    

function async_upload_before_submit(data,form) {
   form.before(jQuery("<div class='upload_message' style='height:1%'>").append(ajax_image_searching)[0]);
   //if not present add the iframe input
   if(!form.find("input[name=iframe]").length)
    form.append("<input type='hidden' name='iframe' value='iframe'>");
   //reset the redirect input
   form
   .find("input[name='redirect']")
   .val("");
};

function async_upload_article_edit(res,s,jForm){
      res = iframeHandler(res,jForm,async_upload_article_edit);
      if(!res) return true;
      var cont;
      //verify if a new document or a customized vignette
      var bloc = jQuery(res.find(">div:first[id^=document]"));
			if(jQuery("#"+bloc.attr('id')).size()) {
				cont = jQuery("#"+bloc.attr('id')).html(bloc.html());
			} else {
	      //add a class to new documents
	      res.
	      find(">div[class]")
	      .addClass("documents_added")
	      .css("display","none");
	      if (jForm.find("input[name='arg']").val().search("/0/image")!=-1){
	        cont = jQuery("#liste_images");
	        // cas de l'interface document unifiee
	        if (!cont.length)
		        cont = jQuery("#liste_documents");
	      }
	      else
	        cont = jQuery("#liste_documents");
	      cont
	      .prepend(res.clone2());
	      //find added documents, remove label and show them nicely
	      cont = cont.
	      find("div.documents_added")
	        .removeClass("documents_added")
	        .show("slow",function(){
	            var anim = jQuery(this).css("height","");
	            //bug explorer-opera-safari
	            if(!jQuery.browser.mozilla)
	              anim.css('width', jQuery(this).width()-2);
	            a = jQuery(anim).find("img[onclick]")
	            if (a.length) a.get(0).onclick();
	        })
	        .css('overflow','');
	    }
			jQuery("form.form_upload",cont).async_upload(async_upload_article_edit);
      verifForm(cont);
      return true;
}

function async_upload_icon(res,s,jForm) {
  res = iframeHandler(res,jForm);
  if(!res) return true;
  res.find(">div").each(function(){
    var cont = jQuery("#"+this.id);
    verifForm(cont.html(jQuery(this).html()));
    jQuery("form.form_upload_icon",cont).async_upload(async_upload_icon);
		cont.find("img[onclick]").each(function(){this.onclick();});
  });
  return true;                     
}

function async_upload_portfolio_documents(res,s,jForm){
  res = iframeHandler(res,jForm,async_upload_portfolio_documents);

  if(!res) return true;

  // on dirait que ca passe mieux sur Safari avec un setTimeout cf #1408
  setTimeout(function() {
  res.find(">div").each(function(){
    // this.id = documenter--id_article ou documenter-id_article
    var cont = jQuery("#"+this.id);
    var self = jQuery(this);
    if(!cont.size()) {
      cont = jQuery(this.id.search(/--/)!=-1 ? "#portfolio":"#documents")
      .append(self.clone2());
    }
    verifForm(cont.html(self.html()));
    jQuery("form.form_upload",cont).async_upload(async_upload_portfolio_documents);
  });
  }, 50);
  return true;             
}
