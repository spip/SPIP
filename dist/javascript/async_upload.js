// JavaScript Document
jQuery.async_upload_count = 0;
jQuery.fn.async_upload = function(add_function) {
  return this.submit(function(){
    return do_async_upload(this);
  });
  
  function do_async_upload(form) {
    jQuery.async_upload_count++;
    var num = jQuery.async_upload_count;
    var jForm = jQuery(form);
    var par = jQuery(jForm).parent();
    jQuery("div.upload_message",par)
    .remove();
    if(!form.async_init) {
      form.async_init = true
      jForm
      .append("<input type='hidden' name='iframe' value='iframe'>")
      .find("input[@name='redirect']")
        .val("")
      .end();
    }
    
		jForm.attr("target","upload_frame"+num);
    var jFrame = jQuery("<iframe id='upload_frame"+num+"' name='upload_frame"+num+"' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' style='position:absolute;width:1px;height:1px;' onload='this.iframeload("+num+")'></iframe>")
      .appendTo("body");
    
    //IE apparently do not write anything in an iframe onload event handler 
    jFrame[0].iframeload = function(num) {
        //remove the previous message
        jQuery("div.upload_message",par).remove();
        var res = jQuery(".upload_answer",this.contentDocument || document.frames(this.name).document.body);
        //possible classes 
        //upload_document_added
        if(res.is(".upload_document_added")) {
          return add_function(res,jForm);
        }
        //upload_error
        if(res.is(".upload_error")) {
          var msg = jQuery("<div class='upload_message'>")
          .append(res.html())
          jForm.after(msg[0]);
          return true;
        } 
        //upload_zip_list
        if(res.is(".upload_zip_list")) {
          var zip_form = jQuery("<div class='upload_message'>").append(res.html());
          zip_form
          .find("form")
            .attr("target","upload_frame"+num)
            .append("<input type='hidden' name='iframe' value='iframe'>")
          .end();
          jForm.after(zip_form[0]);
          return true;  
        }
    };
    
    jForm.before(jQuery("<div class='upload_message' style='height:1%'>").append(ajax_image_searching)[0]);
    return true;
  }
}

// Safari plante quand on utilise clone() -> on utilise html()
// Mais FF a un bug sur les urls contenant ~ quand on utilise html() -> on utilise clone()
jQuery.fn.clone2 = jQuery.browser.safari ? jQuery.fn.html : jQuery.fn.clone;


function async_upload_article_edit(res,jForm){
      var cont;
      //verify if a new document or a customized vignette
      var anchor = jQuery(res.find(">a:first"));
      res.end(); 
			if(jQuery("#"+anchor.attr('id')).size()) {
				cont = jQuery("#"+anchor.attr('id')).next().next().html(anchor.next().next().html());
			} else {
	      //add a class to new documents
	      res.
	      find(">div[@class]")
	        .addClass("documents_added")
	        .css("display","none")
	      .end();
	      if (jForm.find("input[@name='arg']").val().search("/0/vignette")!=-1)
	        cont = jQuery("#liste_images");
	      else
	        cont = jQuery("#liste_documents");
	      cont
	      .prepend(res.clone2());
	      //find added documents, remove label and show them nicely
	      cont.
	      find("div.documents_added")
	        .removeClass("documents_added")
	        .show("slow",function(){
	            var anim = jQuery(this).css("height","");
	            //bug explorer-opera-safari
	            if(!jQuery.browser.mozilla) anim.css('width', this.orig.width-2);
	            jQuery(anim).find("img[@onclick]").get(0).onclick();
	        })
	        .css('overflow','');
	    }
			jQuery("form.form_upload",cont).async_upload(async_upload_article_edit);
      verifForm(cont);
      return true;
}

function async_upload_icon(res) {
  res.find(">div").each(function(){
    var cont = jQuery("#"+this.id);
    verifForm(cont.html(jQuery(this).html()));
    jQuery("form.form_upload_icon",cont).async_upload(async_upload_icon);
		cont.find("img[@onclick]").each(function(){this.onclick();});
  });
  return true;                     
}

function async_upload_portfolio_documents(res){
  res.find(">div").each(function(){
    var cont = jQuery("#"+this.id);
    var self = jQuery(this);
    if(!cont.size()) {
      cont = jQuery(this.id.search(/--/)!=-1 ? "#portfolio":"#documents")
      .append(self.clone2().get());
    }
    verifForm(cont.html(self.html()));
    jQuery("form.form_upload",cont).async_upload(async_upload_portfolio_documents);
  });
  return true;             
}
